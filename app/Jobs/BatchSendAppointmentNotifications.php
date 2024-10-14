<?php

namespace App\Jobs;

use App\Models\VaccineAppointment;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AppointmentNotification;
use App\Support\Enums\AppointmentStatus;

class BatchSendAppointmentNotifications implements ShouldQueue
{
    use Queueable;

    protected UserRepository $userRepository;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $batchSize = 250,
    ) {
        $this->userRepository = new UserRepository();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $appointments = $this->userRepository->notifiableToday($this->batchSize);

        foreach ($appointments as $appointment) {
            $user = $appointment->user;

            // Send the notification
            Notification::send(notifiables: $user, notification: new AppointmentNotification(
                vaccineAppointment: $appointment
            ));
        }

        // Update the appointment status
        VaccineAppointment::query()
            ->whereIn("id", $appointments->pluck('id')->toArray())
            ->update(['status' => AppointmentStatus::NOTIFIED->value]);
    }
}
