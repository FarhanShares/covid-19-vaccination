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

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $batchSize = 100,
    ) {
        $this->userRepository = new UserRepository();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = $this->userRepository->unappointed($this->batchSize);
        $bookingService = new BookingService();

        foreach ($users as $user) {
            $appointmentDate = $bookingService->findDate($user->vaccineCenter);

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
            $bookingService->useDate(
                date: $appointment->date,
                vaccineCenterId: $user->vaccine_center_id,
            );
        }

        // After processing all users, batch update the VaccineCenterDailyUsage table
        $bookingService->flush();
    }
}


// Bulk insert appointment data
// // Calculate usage counts for each vaccine center
// $groupedUsage = $usageCounter->groupBy(['vaccine_center_id', 'date'])->map(function ($group) {
//     return [
//         'date'  => $group->date,
//         'count' => $group->count(),
//     ];
// });

// // Update vaccine center usage for the scheduled dates
// foreach ($groupedUsage as $centerId => $item) {
//     VaccineCenterDailyUsage::incrementUsage(
//         date: Carbon::parse($item['date']),
//         amount: $item['count'],
//         vaccineCenter: $centerId,
//     );
// }

// $usageCounter = collect([]);
// $usageCounter->push([
//     'date'              => $appointmentDate,
//     'vaccine_center_id' => $user->vaccine_center_id,
// ]);
