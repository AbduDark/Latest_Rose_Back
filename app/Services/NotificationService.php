<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Course;

class NotificationService
{
    /**
     * إرسال إشعار لمستخدم واحد
     */
    public static function sendToUser($userId, $title, $message, $type = 'general', $courseId = null, $senderId = null, $data = null)
    {
        return Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'user_id' => $userId,
            'course_id' => $courseId,
            'sender_id' => $senderId,
            'data' => $data
        ]);
    }

    /**
     * إرسال إشعار لعدة مستخدمين
     */
    public static function sendToUsers($userIds, $title, $message, $type = 'general', $courseId = null, $senderId = null, $data = null)
    {
        $notifications = [];

        foreach ($userIds as $userId) {
            $notifications[] = self::sendToUser($userId, $title, $message, $type, $courseId, $senderId, $data);
        }

        return $notifications;
    }

    /**
     * إرسال إشعار لجميع الطلبة
     */
    public static function sendToAllStudents($title, $message, $type = 'general', $courseId = null, $senderId = null, $data = null)
    {
        $studentIds = User::where('role', 'student')->pluck('id')->toArray();
        return self::sendToUsers($studentIds, $title, $message, $type, $courseId, $senderId, $data);
    }

    /**
     * إرسال إشعار لطلبة حسب الجنس
     */
    public static function sendToStudentsByGender($gender, $title, $message, $type = 'general', $courseId = null, $senderId = null, $data = null)
    {
        $studentIds = User::where('role', 'student')
                         ->where('gender', $gender)
                         ->pluck('id')->toArray();
        return self::sendToUsers($studentIds, $title, $message, $type, $courseId, $senderId, $data);
    }

    /**
     * إرسال إشعار لطلبة كورس معين حسب الجنس
     */
    public static function sendToCourseStudentsByGender($courseId, $gender, $title, $message, $type = 'course', $senderId = null, $data = null)
    {
        $studentIds = User::whereHas('subscriptions', function ($query) use ($courseId) {
            $query->where('course_id', $courseId)
                  ->where('is_active', true)
                  ->where('status', 'approved');
        })->where('gender', $gender)->pluck('id')->toArray();

        return self::sendToUsers($studentIds, $title, $message, $type, $courseId, $senderId, $data);
    }

    /**
     * إرسال إشعار لطلبة كورس معين
     */
    public static function sendToCourseStudents($courseId, $title, $message, $type = 'course', $senderId = null, $data = null)
    {
        $studentIds = User::whereHas('subscriptions', function ($query) use ($courseId) {
            $query->where('course_id', $courseId)
                  ->where('is_active', true)
                  ->where('status', 'approved');
        })->pluck('id')->toArray();

        return self::sendToUsers($studentIds, $title, $message, $type, $courseId, $senderId, $data);
    }

    /**
     * إشعار عند الموافقة على الاشتراك
     */
    public static function subscriptionApproved($userId, $courseId)
    {
        $course = Course::find($courseId);

        return self::sendToUser(
            $userId,
            'تم قبول اشتراكك',
            "تم قبول اشتراكك في كورس: {$course->title}. يمكنك الآن الوصول إلى جميع دروس الكورس.",
            'subscription',
            $courseId,
            null,
            ['action' => 'subscription_approved', 'course_id' => $courseId]
        );
    }

    /**
     * إشعار عند رفض الاشتراك
     */
    public static function subscriptionRejected($userId, $courseId, $reason = null)
    {
        $course = Course::find($courseId);
        $message = "تم رفض اشتراكك في كورس: {$course->title}.";

        if ($reason) {
            $message .= " السبب: {$reason}";
        }

        return self::sendToUser(
            $userId,
            'تم رفض اشتراكك',
            $message,
            'subscription',
            $courseId,
            null,
            ['action' => 'subscription_rejected', 'course_id' => $courseId, 'reason' => $reason]
        );
    }

    /**
     * إشعار عند انتهاء صلاحية الاشتراك
     */
    public static function subscriptionExpired($userId, $courseId)
    {
        $course = Course::find($courseId);

        return self::sendToUser(
            $userId,
            'انتهت صلاحية اشتراكك',
            "انتهت صلاحية اشتراكك في كورس: {$course->title}. يرجى تجديد الاشتراك للاستمرار في الوصول للكورس.",
            'subscription',
            $courseId,
            null,
            ['action' => 'subscription_expired', 'course_id' => $courseId]
        );
    }

    /**
     * إشعار عند إضافة درس جديد
     */
    public static function newLessonAdded($courseId, $lessonTitle)
    {
        $course = Course::find($courseId);

        return self::sendToCourseStudents(
            $courseId,
            'درس جديد متاح',
            "تم إضافة درس جديد '{$lessonTitle}' إلى كورس: {$course->title}",
            'course',
            null,
            ['action' => 'new_lesson', 'course_id' => $courseId, 'lesson_title' => $lessonTitle]
        );
    }

    /**
     * إشعار عند اقتراب انتهاء الاشتراك (تذكير)
     */
    public static function subscriptionExpiringReminder($userId, $courseId, $daysRemaining)
    {
        $course = Course::find($courseId);

        return self::sendToUser(
            $userId,
            'تذكير: اشتراكك على وشك الانتهاء',
            "سينتهي اشتراكك في كورس: {$course->title} خلال {$daysRemaining} أيام. يرجى تجديد الاشتراك.",
            'subscription',
            $courseId,
            null,
            ['action' => 'subscription_reminder', 'course_id' => $courseId, 'days_remaining' => $daysRemaining]
        );
    }
}
