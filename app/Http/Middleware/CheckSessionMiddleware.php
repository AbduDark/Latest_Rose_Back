<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User; // تمت إضافة الاستيراد

class CheckSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من أن المستخدم مسجل دخول
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'يجب تسجيل الدخول أولاً',
                    'en' => 'You must be logged in first'
                ]
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $currentSession = session()->getId();

        // إذا كان هناك جلسة مختلفة مخزنة للمستخدم
        if ($user->current_session && $user->current_session !== $currentSession) {
            // إنهاء الجلسة الحالية
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'تم تسجيل دخولك من جهاز آخر. يرجى تسجيل الدخول مرة أخرى.',
                    'en' => 'You are logged in from another device. Please log in again.'
                ],
                'error_code' => 'MULTIPLE_SESSIONS'
            ], 403);
        }

        // تحديث معرف الجلسة للمستخدم
        if ($user->current_session !== $currentSession) {
            $user->forceFill([
                'current_session' => $currentSession
            ])->save();
        }

        return $next($request);
    }
}
