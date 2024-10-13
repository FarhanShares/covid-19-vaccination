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
            $appointmentDate = $this->bookingService->findDate($user->vaccineCenter);

            $appointment = VaccineAppointment::create([
                'date'              => $appointmentDate,
                'user_id'           => $user->id,
                'vaccine_center_id' => $user->vaccine_center_id,
                'status'            => AppointmentStatus::SCHEDULED,
            ]);

            $this->userRepository->updateStatus(
                user: $user->nid,
                status: VaccinationStatus::SCHEDULED,
                appointmentId: $appointment->id,
            );

            // Increment the vaccine center usage counter in Redis for efficiency
            $this->bookingService->useDate(
                date: $appointment->date,
                vaccineCenterId: $user->vaccine_center_id,
            );
        }

        // After processing all users, batch update the VaccineCenterDailyUsage table
        $this->bookingService->flush();
    }
}
