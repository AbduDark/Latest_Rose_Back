<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;

class CheckSubscription
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Allow admins to access everything
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        // Get course_id from route parameters
        $courseId = $request->route('course') ?? $request->route('id');

        if (!$courseId || !$user) {
            return $this->errorResponse([
                'ar' => 'غير مصرح لك بالوصول',
                'en' => 'Unauthorized access'
            ], 401);
        }

        // Check if user has any subscription for this course
        $subscription = $user->subscriptions()
            ->where('course_id', $courseId)
            ->where('status', 'approved')
            ->first();

        if (!$subscription) {
            return $this->errorResponse([
                'ar' => 'يجب أن تشترك في هذا الكورس أولاً للوصول إلى محتواه. قم بطلب الاشتراك من خلال التطبيق.',
                'en' => 'You must subscribe to this course first to access its content. Please request subscription through the app.'
            ], 403);
        }

        // Check if subscription is expired
        if ($subscription->isExpired()) {
            $daysExpired = now()->diffInDays($subscription->expires_at);

            return $this->errorResponse([
                'ar' => "انتهت صلاحية اشتراكك في هذا الكورس منذ {$daysExpired} يوم. يرجى تجديد اشتراكك للمتابعة.",
                'en' => "Your subscription to this course expired {$daysExpired} days ago. Please renew your subscription to continue."
            ], 403, [
                'subscription_expired' => true,
                'expired_since_days' => $daysExpired,
                'subscription_id' => $subscription->id,
                'course_id' => $courseId
            ]);
        }

        // Check if subscription is not active
        if (!$subscription->is_active) {
            return $this->errorResponse([
                'ar' => 'اشتراكك في هذا الكورس غير نشط حالياً. يرجى التواصل مع الإدارة أو تجديد اشتراكك.',
                'en' => 'Your subscription to this course is currently inactive. Please contact administration or renew your subscription.'
            ], 403, [
                'subscription_inactive' => true,
                'subscription_id' => $subscription->id,
                'course_id' => $courseId
            ]);
        }

        return $next($request);
    }
}
