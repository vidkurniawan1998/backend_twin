<?php


namespace App\Console\Commands;


use App\Models\Promo;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NonActivePromo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:non_active_promo';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update data promo untuk menonaktifkan status promo';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $today = Carbon::now()->subDays(1)->toDateString();
        Promo::where('tanggal_akhir', '<=', $today)
            ->where('status', 'active')
            ->update(['status'=> 'non_active']);
    }
}
