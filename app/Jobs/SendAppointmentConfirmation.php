<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAppointmentConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    public function handle(): void
    {
        // TODO: send confirmation email/SMS to patient and doctor
        // Mail::to($this->appointment->patient->email)
        //     ->send(new AppointmentConfirmedMail($this->appointment));
    }
}
