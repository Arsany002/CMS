<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Repositories\AppointmentRepositry;
use App\Repositories\ScheduleRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(
        private AppointmentRepositry $appointmentRepo,
        private ScheduleRepository $scheduleRepo,
    ) {}

    /**
     * Returns array of available HH:MM slots for a doctor on a given date.
     */
    public function getAvailableSlots(int $doctorId, string $date): array
    {
        $cacheKey = "slots:{$doctorId}:{$date}";

        return Cache::remember($cacheKey, 300, function () use ($doctorId, $date) {
            $dayOfWeek = Carbon::parse($date)->dayOfWeek;
            $schedule = $this->scheduleRepo->findForDayAndDoctor($doctorId, $dayOfWeek);

            if (! $schedule) {
                return [];
            }

            // REFACTORED: Using the Repository instead of direct ORM
            $bookedTimes = $this->appointmentRepo->getBookedTimeSlots($doctorId, $date);

            $slots = [];
            $current = Carbon::parse($date . ' ' . $schedule->start_time);
            $end = Carbon::parse($date . ' ' . $schedule->end_time);
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

    public function book(array $data): Appointment
    {
        $doctorId = $data['doctor_id'];
        $date = $data['appointment_date'];
        $start = $data['start_time'];

        $availableSlots = $this->getAvailableSlots($doctorId, $date);

        if (! in_array($start, $availableSlots)) {
            throw ValidationException::withMessages([
                'start_time' => ['This time slot is not available.'],
            ]);
        }

        // Compute end_time from schedule
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $schedule = $this->scheduleRepo->findForDayAndDoctor($doctorId, $dayOfWeek);

        $data['end_time'] = Carbon::parse($start)->addMinutes($schedule->slot_duration)->format('H:i:s');

        Cache::forget("slots:{$doctorId}:{$date}");

        return $this->appointmentRepo->create($data);
    }

    public function reschedule(Appointment $appointment, array $data): Appointment
    {
        // Use value property if AppointmentStatus is a backed enum
        if ($appointment->status === AppointmentStatus::COMPLETED->value) {
            throw ValidationException::withMessages([
                'status' => ['A completed appointment cannot be rescheduled.'],
            ]);
        }

        $doctorId = $appointment->doctor_id;
        $date = $data['appointment_date'];
        $start = $data['start_time'];

        $availableSlots = $this->getAvailableSlots($doctorId, $date);

        if (! in_array($start, $availableSlots)) {
            throw ValidationException::withMessages([
                'start_time' => ['This time slot is not available.'],
            ]);
        }

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $schedule = $this->scheduleRepo->findForDayAndDoctor($doctorId, $dayOfWeek);

        $data['end_time'] = Carbon::parse($start)->addMinutes($schedule->slot_duration)->format('H:i:s');

        // Clear cache for both the old date and the new date
        $oldDate = Carbon::parse($appointment->appointment_date)->format('Y-m-d');
        Cache::forget("slots:{$doctorId}:{$oldDate}");
        Cache::forget("slots:{$doctorId}:{$date}");

        return $this->appointmentRepo->update($appointment->id, $data);
    }

    public function cancel(Appointment $appointment): Appointment
    {
        $date = Carbon::parse($appointment->appointment_date)->format('Y-m-d');
        Cache::forget("slots:{$appointment->doctor_id}:{$date}");

        return $this->appointmentRepo->update($appointment->id, [
            'status' => 'cancelled' // Or AppointmentStatus::Cancelled->value if using Enums
        ]);
    }

    public function updateStatus(Appointment $appointment, string $status): Appointment
    {
        return $this->appointmentRepo->update($appointment->id, ['status' => $status]);
    }
}
