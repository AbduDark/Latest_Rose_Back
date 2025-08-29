<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $students = User::where('role', 'student')->take(5)->get();
        $courses = Course::take(3)->get();

        $notifications = [
            [
                'title' => 'مرحباً بك في أكاديمية الورد',
                'message' => 'نرحب بك في أكاديمية الورد للتعليم الإلكتروني. نتمنى لك تجربة تعليمية ممتعة ومفيدة.',
                'type' => 'general'
            ],
            [
                'title' => 'تحديث في النظام',
                'message' => 'تم تحديث النظام بميزات جديدة لتحسين تجربة التعلم.',
                'type' => 'system'
            ],
            [
                'title' => 'عرض خاص على الكورسات',
                'message' => 'احصل على خصم 20% على جميع الكورسات لفترة محدودة.',
                'type' => 'general'
            ]
        ];

        foreach ($students as $student) {
            foreach ($notifications as $notificationData) {
                Notification::create([
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'type' => $notificationData['type'],
                    'user_id' => $student->id,
                    'sender_id' => $admin->id,
                    'is_read' => fake()->boolean(30), // 30% احتمال أن يكون مقروء
                    'read_at' => fake()->boolean(30) ? fake()->dateTimeBetween('-1 week', 'now') : null,
                ]);
            }

            // إشعارات خاصة بالكورسات
            if ($courses->isNotEmpty()) {
                $randomCourse = $courses->random();
                Notification::create([
                    'title' => 'درس جديد متاح',
                    'message' => "تم إضافة درس جديد إلى كورس: {$randomCourse->title}",
                    'type' => 'course',
                    'user_id' => $student->id,
                    'course_id' => $randomCourse->id,
                    'sender_id' => $admin->id,
                    'is_read' => fake()->boolean(20),
                    'data' => [
                        'action' => 'new_lesson',
                        'course_id' => $randomCourse->id
                    ]
                ]);
            }
        }
    }
}
