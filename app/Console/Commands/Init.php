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
        $this->info('Starting initialization process...');
        $this->line('');

        $this->info('1. Clearing cache and starting fresh...');
        Artisan::call('key:generate');
        Artisan::call('optimize:clear');
        $this->info(Artisan::output());
        $this->line('');

        $this->info('2. Running a fresh migration...');
        Artisan::call('migrate:fresh --force');
        $this->info(Artisan::output());

        $this->info('3. Seeding 20 vaccine centers with random data...');
        VaccineCenter::factory()->count(20)->create();
        $this->line('');

        $this->info('4. Seeding 5000 users with random data...');
        // User::factory()->count(5000)->create();

        $totalUsers = 5000;
        $batchSize  = 500;
        $batches    = ceil($totalUsers / $batchSize);

        for ($i = 0; $i < $batches; $i++) {
            User::factory()->count(min($batchSize, $totalUsers - ($i * $batchSize)))->create();

            // Output progress
            $this->info('   > Created ' . (($i + 1) * $batchSize) . ' users out of ' . $totalUsers);
        }

        $this->line('');

        $this->info('5. Optimizing the app...');
        Artisan::call('optimize');
        $this->info(Artisan::output());
        $this->line('');

        $this->alert('Initialization completed! - Farhan Israq');
    }
}
