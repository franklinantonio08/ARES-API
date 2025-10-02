<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\File;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        $schedule->call(function () {
            PersonalAccessToken::query()->delete();
        })->dailyAt('12:00')->timezone('America/Panama');

        $schedule->call(function () {
            $path = storage_path('framework/sessions');
            if (File::exists($path)) {
                foreach (File::files($path) as $file) {
                    @File::delete($file->getPathname());
                }
            }
        })->dailyAt('12:01')->timezone('America/Panama');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
