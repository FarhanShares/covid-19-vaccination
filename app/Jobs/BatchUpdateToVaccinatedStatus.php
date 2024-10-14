<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VaccineAppointment;
use App\Repositories\UserRepository;
use App\Support\Enums\AppointmentStatus;
use App\Support\Enums\VaccinationStatus;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BatchUpdateToVaccinatedStatus implements ShouldQueue
{
    use Queueable;

    protected UserRepository $userRepository;

    /**
     * Create a new job instance.
     *
     * @param int $batchSize
     */
    public function __construct(public int $batchSize = 250)
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Execute the job to update appointments with past vaccination dates.
     *
     * @return void
     */
    public function handle(): void
    {
        // Fetch the appointments that are overdue (past the vaccination date)
        // Each pas appointment date is considered as vaccinated.
        $appointments = $this->userRepository->pastAppointments($this->batchSize);
        $userIds = $appointments->pluck("user_id")->toArray();

        VaccineAppointment::query()
            ->whereIn("user_id", $userIds)
            ->update(['status' => AppointmentStatus::VACCINATED->value]);

        $this->userRepository->updateManyStatus($userIds, VaccinationStatus::VACCINATED);
    }
}
