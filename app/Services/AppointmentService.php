<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentConflictException;
use App\Exceptions\ClinicScopeViolationException;
use App\Exceptions\InvalidAppointmentStateException;
use App\Jobs\SendAppointmentConfirmation;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ScheduleRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AppointmentService
{
    public function __construct(
        private AppointmentRepository $appointmentRepo,
        private ScheduleRepository $scheduleRepo,
        private PatientRepository $patientRepo,
    ) {}

    private function slotsCacheKey(string $doctorId, string $date): string
    {
        return "slots:{$doctorId}:{$date}";
    }

    public function getAvailableSlots(string $doctorId, string $date): array
    {
        $cacheKey = $this->slotsCacheKey($doctorId, $date);

        return Cache::remember($cacheKey, 300, function () use ($doctorId, $date) {
            $dayOfWeek = Carbon::parse($date)->dayOfWeek;
            $schedule  = $this->scheduleRepo->findForDayAndDoctor($doctorId, $dayOfWeek);

            if (! $schedule) {
                return [];
            }

            $bookedTimes = $this->appointmentRepo->getBookedTimeSlots($doctorId, $date);

            $slots    = [];
            $current  = Carbon::parse($date . ' ' . $schedule->start_time);
            $end      = Carbon::parse($date . ' ' . $schedule->end_time);
            $duration = $schedule->slot_duration;

            while ($current->copy()->addMinutes($duration)->lte($end)) {
                $slotTime = $current->format('H:i');

                if (! in_array($slotTime, $bookedTimes)) {
                    $slots[] = $slotTime;
                }

                $current->addMinutes($duration);
            }

            return $slots;
        });
    }

    /**
     * Book a new appointment.
     *
     * BR-07: The patient must belong to the same clinic as the booking assistant.
     */
    public function book(array $data): Appointment
    {
        // BR-07: patient must belong to the same clinic
        $patient = $this->patientRepo->getPatientById($data['patient_id']);
        if ($patient->clinic_id !== $data['clinic_id']) {
            throw new ClinicScopeViolationException('Patient does not belong to this clinic.');
        }

        $doctorId = $data['doctor_id'];
        $date     = $data['appointment_date'];
        $start    = $data['start_time'];
        $lockKey  = "booking:{$doctorId}:{$date}:{$start}";

        /** @var Appointment $appointment */
        $appointment = Cache::lock($lockKey, 10)->block(5, function () use ($data, $doctorId, $date, $start) {
            $availableSlots = $this->getAvailableSlots($doctorId, $date);

            if (! in_array($start, $availableSlots)) {
                throw new AppointmentConflictException();
            }

            $dayOfWeek = Carbon::parse($date)->dayOfWeek;
            $schedule  = $this->scheduleRepo->findForDayAndDoctor($doctorId, $dayOfWeek);

            $data['end_time'] = Carbon::parse($start)->addMinutes($schedule->slot_duration)->format('H:i:s');

            Cache::forget($this->slotsCacheKey($doctorId, $date));

            return $this->appointmentRepo->create($data);
        });

        SendAppointmentConfirmation::dispatch($appointment);

        return $appointment;
    }

    public function reschedule(array $data): Appointment
    {
        // Single DB hit — reuse the loaded model throughout the method
        $appointment = $this->appointmentRepo->find($data['id']);

        if ($appointment->status === AppointmentStatus::COMPLETED) {
            throw new InvalidAppointmentStateException('A completed appointment cannot be rescheduled.');
        }

        $doctorId = $appointment->doctor_id;
        $oldDate  = Carbon::parse($appointment->appointment_date)->format('Y-m-d');
        $date     = $data['appointment_date'];
        $start    = $data['start_time'];

        $availableSlots = $this->getAvailableSlots($doctorId, $date);

        if (! in_array($start, $availableSlots)) {
            throw new AppointmentConflictException();
        }

        $dayOfWeek        = Carbon::parse($date)->dayOfWeek;
        $schedule         = $this->scheduleRepo->findForDayAndDoctor($doctorId, $dayOfWeek);
        $data['end_time'] = Carbon::parse($start)->addMinutes($schedule->slot_duration)->format('H:i:s');

        Cache::forget($this->slotsCacheKey($doctorId, $oldDate));
        Cache::forget($this->slotsCacheKey($doctorId, $date));

        return $this->appointmentRepo->update($data['id'], $data);
    }

    public function cancel(array $data): Appointment
    {
        // Single DB hit — no need to call find() twice
        $appointment = $this->appointmentRepo->find($data['id']);
        $date        = Carbon::parse($appointment->appointment_date)->format('Y-m-d');
        $doctorId    = $appointment->doctor_id;

        Cache::forget($this->slotsCacheKey($doctorId, $date));

        return $this->appointmentRepo->update($data['id'], [
            'status' => AppointmentStatus::CANCELLED,
        ]);
    }

    public function updateStatus(array $data): Appointment
    {
        return $this->appointmentRepo->update($data['id'], ['status' => $data['status']]);
    }
}
