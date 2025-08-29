<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
/**
 * Class NotifyExpiredSubscriptions
 * @package App\Console\Commands
 *
 * This command checks for expired subscriptions and sends notifications to users.
 */


class NotifyExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:notify-expired';
    protected $description = 'Send notifications to users whose subscriptions have expired';

    public function handle()
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->whereDate('end_date', '<', Carbon::now())
            ->get();

        foreach ($expiredSubscriptions as $subscription) {

            // تحديث حالة الاشتراك
            $subscription->update(['status' => 'expired']);

            // التأكد إن الإشعار مش مبعوت قبل كده
            $alreadyNotified = Notification::where('user_id', $subscription->user_id)
                ->where('course_id', $subscription->course_id)
                ->where('type', 'subscription_expired')
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            // إنشاء الإشعار
            Notification::create([
                'title' => 'انتهاء الاشتراك',
                'message' => 'انتهى اشتراكك في الكورس: ' . $subscription->course->title,
                'type' => 'subscription_expired',
                'user_id' => $subscription->user_id,
                'course_id' => $subscription->course_id,
                'sender_id' => null,
                'is_read' => false,
                'data' => [
                    'course_title' => $subscription->course->title,
                    'expired_at' => $subscription->end_date,
                ]
            ]);

            $this->info("تم إرسال إشعار للمستخدم: {$subscription->user->name}");
        }

        return Command::SUCCESS;
    }
}
