<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        Commands\DailyStockReport::class,
        Commands\CheckStock::class,
        Commands\DailyStockAwal::class,
        Commands\NonActivePromo::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
        $schedule->command('daily:stock_report')->everyMinute();
    }

    /**
    * Register the Closure based commands for the application.
    *
    * @return void
    */


}
