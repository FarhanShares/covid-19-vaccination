<?php

namespace App\Jobs;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreUserJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user) {}

    /**
     * Execute the job.
     */
    public function handle(UserRepository $repo): void
    {
        $repo->store($this->user);
    }
}
