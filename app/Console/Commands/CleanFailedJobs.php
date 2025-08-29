<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:clean-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean failed jobs with high attempt counts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('تنظيف الوظائف الفاشلة...');
        
        // حذف الوظائف التي فشلت أكثر من 5 مرات
        $deletedJobs = DB::table('jobs')->where('attempts', '>', 5)->delete();
        
        $this->info("تم حذف {$deletedJobs} وظيفة فاشلة");
        
        // حذف الوظائف الفاشلة من جدول failed_jobs
        $deletedFailedJobs = DB::table('failed_jobs')->delete();
        
        $this->info("تم حذف {$deletedFailedJobs} وظيفة من جدول الوظائف الفاشلة");
        
        $this->info('تم تنظيف قاعدة البيانات بنجاح!');
        
        return 0;
    }
}
