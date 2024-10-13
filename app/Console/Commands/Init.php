<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VaccineCenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing cache...');
        Artisan::call('optimize:clear');

        $this->info('Starting with a fresh migration...');
        Artisan::call('migrate:fresh --force');
        $this->info(Artisan::output());

        $this->info('Seeding 20 vaccine centers with random data...');
        VaccineCenter::factory()->count(20)->create();

        $this->info('Seeding 5000 users with random data...');
        User::factory()->count(5000)->create();

        $this->info('Optimizing...');
        Artisan::call('optimize');

        $this->alert('Initialization completed! - Farhan Israq');
    }
}
