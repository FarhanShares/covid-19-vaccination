<?php

namespace App\Jobs;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreUserJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $nid) {}

    /**
     * Execute the job.
     *
     * We may even handle the case here where it fails to register
     * e.g. notify admin or, the user. Skipping it.
     */
    public function handle(UserRepository $userRepository): void
    {
        $userRepository->store($this->nid);
    }
}
