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
     *
     * Instead of creating or updating the records one by one, which is slow and not performant,
     * we'll leverage batch updates. Therefore, we'll prepare the data first and then persist it.
     */
    public function handle(): void
    {
        $users = $this->userRepository->unappointed($this->batchSize);

        if ($users->count() < 1) {
            return;
        }

        $appointments = collect([]);

        foreach ($users as $user) {
            /**
             * Find the closest available date for the chosen vaccine center and store in in-memory array.
             * Booking service along with user repository takes care of the "first come, first served" principle.
             */
            $appointmentDate = $this->bookingService->findDate(
                vaccineCenter: $user->vaccineCenter
            );
            $appointments->push([
                'date'              => $appointmentDate,
                'user_id'           => $user->id,
                'vaccine_center_id' => $user->vaccine_center_id,
                'status'            => AppointmentStatus::SCHEDULED,
                'updated_at'        => now(),
                'created_at'        => now(),
            ]);

            /**
             * Notify the booking service that we have used the date which it just curated for us.
             * This is also super performant because it uses temporary in-memory redis storage.
             */
            $this->bookingService->useDate(
                date: $appointmentDate,
                vaccineCenterId: $user->vaccine_center_id,
            );
        }

        // Handle an edge-case.
        if ($appointments->count() < 1) {
            $this->bookingService->flush(persist: false);
            return;
        }

        // Create appointment records in batch
        VaccineAppointment::insert(
            values: $appointments->toArray()
        );

        // Update users vaccination status in batch
        $this->userRepository->updateManyStatus(
            ids: $appointments->pluck('user_id')->toArray(),
            status: VaccinationStatus::SCHEDULED,
        );

        /**
         * After processing all users in the batch, flush to persist all necessary data
         * from temporary storage to permanent storage and clear the temporary data too.
         */
        $this->bookingService->flush();
    }
}
