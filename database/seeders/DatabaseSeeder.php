<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Subscription;
use App\Models\Rating;
use App\Models\Favorite;
use App\Models\Comment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '01234567890',
            'gender' => 'male',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Regular User
        $user = User::create([
            'name' => 'طالب تجريبي',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'phone' => '01987654321',
            'gender' => 'male',
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        // Create Sample Courses
        $course1 = Course::create([
            'title' => 'دورة البرمجة الأساسية',
            'description' => 'تعلم أساسيات البرمجة من الصفر',
            'price' => 99.99,
            'duration_hours' => 20,
            'level' => 'beginner',
            'language' => 'ar',
            'is_active' => true,
            'instructor_name' => 'أحمد محمد',
        ]);

        $course2 = Course::create([
            'title' => 'دورة تطوير المواقع',
            'description' => 'تعلم تطوير المواقع باستخدام HTML, CSS, JavaScript',
            'price' => 149.99,
            'duration_hours' => 35,
            'level' => 'intermediate',
            'language' => 'ar',
            'is_active' => true,
            'instructor_name' => 'فاطمة أحمد',
        ]);

        // Create Sample Lessons
        Lesson::create([
            'course_id' => $course1->id,
            'title' => 'مقدمة في البرمجة',
            'description' => 'فهم أساسيات البرمجة ولغات البرمجة',
            'content' => 'محتوى الدرس الأول - مقدمة شاملة في البرمجة وأساسياتها',
            'video_url' => 'https://example.com/video1.mp4',
            'duration_minutes' => 30,
            'order' => 1,
            'is_free' => true,
            'target_gender' => 'both',
        ]);

        Lesson::create([
            'course_id' => $course1->id,
            'title' => 'المتغيرات والثوابت - للأولاد',
            'description' => 'تعلم كيفية استخدام المتغيرات في البرمجة - درس مخصص للأولاد',
            'video_url' => 'https://example.com/video2-male.mp4',
            'duration_minutes' => 45,
            'order' => 2,
            'content' => 'محتوى الدرس للأولاد...',
            'is_free' => false,
            'target_gender' => 'male',
        ]);

        Lesson::create([
            'course_id' => $course1->id,
            'title' => 'المتغيرات والثوابت - للبنات',
            'description' => 'تعلم كيفية استخدام المتغيرات في البرمجة - درس مخصص للبنات',
            'video_url' => 'https://example.com/video2-female.mp4',
            'duration_minutes' => 45,
            'order' => 2,
            'content' => 'محتوى الدرس للبنات...',
            'is_free' => false,
            'target_gender' => 'female',
        ]);

        Lesson::create([
            'course_id' => $course2->id,
            'title' => 'مقدمة في HTML',
            'description' => 'تعلم أساسيات لغة HTML',
            'content' => 'محتوى درس HTML - تعلم العناصر الأساسية وكيفية إنشاء صفحات الويب',
            'video_url' => 'https://example.com/video3.mp4',
            'duration_minutes' => 40,
            'order' => 1,
            'is_free' => true,
            'target_gender' => 'both',
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // داتا فيك للاشتراكات
        foreach (User::all() as $user) {
            foreach (Course::all() as $course) {
              Subscription::create([
    'user_id' => $user->id,
    'course_id' => $course->id,
    'subscribed_at' => now()->subDays(rand(1, 30)),
    'is_active' => true,
    'is_approved' => true,
    'approved_at' => now()->subDays(rand(1, 30)),
    'admin_notes' => 'اشتراك تجريبي',
    'vodafone_number' => '010' . rand(10000000, 99999999),
    'parent_phone' => '01' . rand(100000000, 999999999), // أضف هذا الحقل
    // أي حقول أخرى مطلوبة
]);      }
        }
        // داتا فيك للتقييمات
        foreach (User::all() as $user) {
            foreach (Course::all() as $course) {
                Rating::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'rating' => rand(1, 5),
                    'review' => 'مراجعة تجريبية'
                ]);
            }
        }
        // داتا فيك للمفضلة
        foreach (User::all() as $user) {
            foreach (Course::all()->random(1) as $course) {
                Favorite::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id
                ]);
            }
        }
        // داتا فيك للتعليقات (بدون نظام الموافقة)
        foreach (User::all() as $user) {
            foreach (Lesson::all()->random(1) as $lesson) {
                Comment::create([
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                    'course_id' => $lesson->course_id,
                    'content' => 'تعليق تجريبي'
                ]);
            }
        }

        $this->call([
            LargeCourseSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
