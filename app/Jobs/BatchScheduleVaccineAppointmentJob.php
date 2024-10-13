<?php

namespace App\Jobs;

use App\Services\BookingService;
use App\Models\VaccineAppointment;
use App\Repositories\UserRepository;
use App\Support\Enums\{AppointmentStatus, VaccinationStatus};

use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BatchScheduleVaccineAppointmentJob implements ShouldQueue
{
    use Queueable;

    protected UserRepository $userRepository;

    protected BookingService $bookingService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $batchSize = 250,
    ) {
        $this->userRepository = new UserRepository();
        $this->bookingService = new BookingService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = $this->userRepository->unappointed($this->batchSize);

        foreach ($users as $user) {
            // Find the closest available date for the chosen vaccine center
            // Booking service along with user repository takes care of the first come, first served principle
            $appointmentDate = $this->bookingService->findDate($user->vaccineCenter);

            // Schedule the appointment
            $appointment = VaccineAppointment::create([
                'date'              => $appointmentDate,
                'user_id'           => $user->id,
                'vaccine_center_id' => $user->vaccine_center_id,
                'status'            => AppointmentStatus::SCHEDULED,
            ]);

            // Update the user user status
            $this->userRepository->updateStatus(
                user: $user,
                status: VaccinationStatus::SCHEDULED,
            );

            // Notify the booking service that we have used the date
            // which it just curated for us.
            $this->bookingService->useDate(
                date: $appointment->date,
                vaccineCenterId: $user->vaccine_center_id,
            );
        }

        // After processing all users in the batch, flush to persist all necessary data
        // from temporary storage to permanent storage and clear the temporary data too.
        $this->bookingService->flush();
    }
}
