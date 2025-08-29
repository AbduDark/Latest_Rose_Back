<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Subscription;
use App\Models\Rating;
use App\Models\Favorite;
use App\Models\Comment;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LargeCourseSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // إنشاء 20 مستخدم
        $users = [];
        for ($i = 1; $i <= 20; $i++) {
            $users[] = User::create([
                'name' => 'طالب ' . $i,
                'email' => 'student' . $i . '@example.com',
                'password' => Hash::make('password'),
                'phone' => '0100000000' . sprintf('%02d', $i),
                'gender' => $i % 2 == 0 ? 'female' : 'male',
                'role' => 'student',
                'email_verified_at' => now(),
            ]);
        }

        // قائمة بمواضيع الكورسات
        $courseTopics = [
            ['title' => 'البرمجة الأساسية', 'description' => 'تعلم أساسيات البرمجة من الصفر', 'price' => 99.99, 'level' => 'beginner'],
            ['title' => 'تطوير المواقع', 'description' => 'تعلم HTML, CSS, JavaScript', 'price' => 149.99, 'level' => 'intermediate'],
            ['title' => 'الذكاء الاصطناعي', 'description' => 'مقدمة في الذكاء الاصطناعي والتعلم الآلي', 'price' => 299.99, 'level' => 'advanced'],
            ['title' => 'تطوير التطبيقات المحمولة', 'description' => 'تطوير تطبيقات Android و iOS', 'price' => 199.99, 'level' => 'intermediate'],
            ['title' => 'قواعد البيانات', 'description' => 'تعلم MySQL و PostgreSQL', 'price' => 129.99, 'level' => 'beginner'],
            ['title' => 'الأمن السيبراني', 'description' => 'حماية الأنظمة والشبكات', 'price' => 249.99, 'level' => 'advanced'],
            ['title' => 'التجارة الإلكترونية', 'description' => 'إنشاء متاجر إلكترونية ناجحة', 'price' => 179.99, 'level' => 'intermediate'],
            ['title' => 'التسويق الرقمي', 'description' => 'استراتيجيات التسويق الحديثة', 'price' => 159.99, 'level' => 'beginner'],
            ['title' => 'تصميم الجرافيك', 'description' => 'تعلم Photoshop و Illustrator', 'price' => 119.99, 'level' => 'beginner'],
            ['title' => 'إدارة المشاريع', 'description' => 'أساليب إدارة المشاريع الحديثة', 'price' => 139.99, 'level' => 'intermediate'],
            ['title' => 'الرياضيات المتقدمة', 'description' => 'التفاضل والتكامل والإحصاء', 'price' => 89.99, 'level' => 'advanced'],
            ['title' => 'اللغة الإنجليزية', 'description' => 'تحسين مهارات اللغة الإنجليزية', 'price' => 79.99, 'level' => 'beginner'],
            ['title' => 'المحاسبة المالية', 'description' => 'أساسيات المحاسبة والمالية', 'price' => 109.99, 'level' => 'beginner'],
            ['title' => 'علوم البيانات', 'description' => 'تحليل البيانات باستخدام Python', 'price' => 259.99, 'level' => 'advanced'],
            ['title' => 'التصوير الفوتوغرافي', 'description' => 'أساسيات التصوير وتقنياته', 'price' => 99.99, 'level' => 'beginner'],
            ['title' => 'كتابة المحتوى', 'description' => 'فن كتابة المحتوى الإبداعي', 'price' => 69.99, 'level' => 'beginner'],
            ['title' => 'إدارة الموارد البشرية', 'description' => 'إدارة الموظفين والتطوير', 'price' => 149.99, 'level' => 'intermediate'],
            ['title' => 'الطبخ العربي', 'description' => 'تعلم الطبخ العربي التقليدي', 'price' => 59.99, 'level' => 'beginner'],
            ['title' => 'اليوجا والتأمل', 'description' => 'تمارين اليوجا والاسترخاء', 'price' => 49.99, 'level' => 'beginner'],
            ['title' => 'ريادة الأعمال', 'description' => 'كيف تبدأ مشروعك الخاص', 'price' => 189.99, 'level' => 'intermediate'],
        ];

        $grades = ['الاول', 'الثاني', 'الثالث'];
        $instructors = [
            'د. أحمد محمد',
            'د. فاطمة علي',
            'د. محمود حسن',
            'أ. سارة أحمد',
            'د. عمر يوسف',
            'أ. نور محمد',
            'د. خالد العلي',
            'أ. رانيا سعد'
        ];

        // إنشاء الكورسات
        $courses = [];
        foreach ($courseTopics as $index => $topic) {
            $courses[] = Course::create([
                'title' => $topic['title'],
                'description' => $topic['description'],
                'price' => $topic['price'],
                'duration_hours' => rand(10, 50),
                'level' => $topic['level'],
                'language' => 'ar',
                'is_active' => true,
                'instructor_name' => $instructors[array_rand($instructors)],
                'grade' => $grades[array_rand($grades)],
                // لا نحتاج لتحديد image لأن CourseController سيولد صورة تلقائياً
            ]);
        }

        // إنشاء الدروس لكل كورس
        foreach ($courses as $courseIndex => $course) {
            $lessonsCount = rand(5, 15);

            for ($i = 1; $i <= $lessonsCount; $i++) {
                $targetGender = 'both';
                if ($i % 3 == 0) {
                    $targetGender = 'male';
                } elseif ($i % 5 == 0) {
                    $targetGender = 'female';
                }

                Lesson::create([
                    'course_id' => $course->id,
                    'title' => "الدرس رقم {$i} - {$course->title}",
                    'description' => "وصف تفصيلي للدرس رقم {$i} في كورس {$course->title}",
                    'content' => "محتوى الدرس رقم {$i} في كورس {$course->title}. يتضمن هذا الدرس شرح مفصل ومفاهيم مهمة.",
                    'video_url' => "https://example.com/video_{$course->id}_{$i}.mp4",
                    'duration_minutes' => rand(15, 90),
                    'order' => $i,
                    'is_free' => $i == 1, // الدرس الأول مجاني
                    'target_gender' => $targetGender,
                ]);
            }
        }

        // إنشاء اشتراكات عشوائية
        foreach ($users as $user) {
            $randomCourses = collect($courses)->random(rand(1, 5));

            foreach ($randomCourses as $course) {
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
]);
            }
        }

        // إنشاء دفعات مالية


        // إنشاء تقييمات عشوائية
        foreach ($users as $user) {
            $randomCourses = collect($courses)->random(rand(1, 8));

            foreach ($randomCourses as $course) {
                $reviews = [
                    'كورس ممتاز جداً، استفدت كثيراً',
                    'المحتوى رائع والشرح واضح',
                    'أنصح بشدة بهذا الكورس',
                    'كورس مفيد ومعلومات قيمة',
                    'الشرح ممتاز والأمثلة واضحة',
                    'محتوى متميز ومفيد جداً',
                    'كورس يستحق الاشتراك',
                    'تجربة تعليمية ممتازة'
                ];

                Rating::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'rating' => rand(3, 5),
                    'review' => $reviews[array_rand($reviews)]
                ]);
            }
        }

        // إنشاء مفضلة عشوائية
        foreach ($users as $user) {
            $randomCourses = collect($courses)->random(rand(2, 6));

            foreach ($randomCourses as $course) {
                Favorite::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id
                ]);
            }
        }

        // إنشاء تعليقات على الدروس (بدون نظام الموافقة)
        $allLessons = Lesson::all();
        foreach ($users as $user) {
            $randomLessons = $allLessons->random(rand(5, 15));

            foreach ($randomLessons as $lesson) {
                $comments = [
                    'شرح ممتاز، شكراً لكم',
                    'استفدت كثيراً من هذا الدرس',
                    'هل يمكن إضافة المزيد من الأمثلة؟',
                    'درس رائع ومفيد جداً',
                    'شكراً على المعلومات القيمة',
                    'أتمنى المزيد من هذه الدروس',
                    'موضوع مهم ومفيد',
                    'تم الفهم بوضوح، شكراً'
                ];

                Comment::create([
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                    'course_id' => $lesson->course_id,
                    'content' => $comments[array_rand($comments)]
                ]);
            }
        }

        $this->command->info('تم إنشاء بيانات تجريبية كبيرة بنجاح!');
        $this->command->info('تم إنشاء:');
        $this->command->info('- 20 مستخدم');
        $this->command->info('- 20 كورس (مع توليد صور تلقائية)');
        $this->command->info('- ' . Lesson::count() . ' درس');
        $this->command->info('- ' . Subscription::count() . ' اشتراك');
        $this->command->info('- ' . Rating::count() . ' تقييم');
        $this->command->info('- ' . Favorite::count() . ' مفضلة');
        $this->command->info('- ' . Comment::count() . ' تعليق');
    }
}
