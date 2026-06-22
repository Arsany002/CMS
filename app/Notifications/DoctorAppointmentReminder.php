<?php

namespace App\Notifications;

use App\Repositories\AppointmentRepository;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DoctorAppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $appointmentId,
        private readonly array $channels,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = app(AppointmentRepository::class)->find($this->appointmentId);

        $appointmentTime = Carbon::parse(
            $appointment->appointment_date->toDateString() . ' ' . $appointment->start_time
        )->format('h:i A');

        $appointmentDate = $appointment->appointment_date->format('l, F j, Y');
        $patientName     = $appointment->patient?->name ?? 'your patient';
        $doctorName      = $notifiable->name ?? 'Doctor';
        $frontendUrl     = rtrim(env('FRONTEND_URL', 'http://localhost:5174'), '/');

        return (new MailMessage)
            ->subject("Appointment Reminder — Today at {$appointmentTime}")
            ->greeting("Hello {$doctorName},")
            ->line("This is a reminder that you have an upcoming appointment in **30 minutes**.")
            ->line("**Patient:** {$patientName}")
            ->line("**Date:** {$appointmentDate}")
            ->line("**Time:** {$appointmentTime}")
            ->action('View Dashboard', $frontendUrl . '/doctor/appointments')
            ->line('Please make sure you are prepared for the appointment.')
            ->salutation('CMS — Clinic Management System');
    }

    public function toDatabase(object $notifiable): array
    {
        $appointment = app(AppointmentRepository::class)->find($this->appointmentId);

        $appointmentTime = Carbon::parse(
            $appointment->appointment_date->toDateString() . ' ' . $appointment->start_time
        )->format('h:i A');

        $patientName = $appointment->patient?->name ?? 'your patient';

        return [
            'type'             => 'appointment_reminder',
            'reminder_minutes' => 15,
            'appointment_id'   => $appointment->id,
            'appointment_date' => $appointment->appointment_date->toDateString(),
            'start_time'       => $appointment->start_time,
            'patient_name'     => $patientName,
            'message'          => "You have an appointment with {$patientName} at {$appointmentTime}",
        ];
    }
}
