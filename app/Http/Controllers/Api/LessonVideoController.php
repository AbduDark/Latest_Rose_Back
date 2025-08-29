<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLessonVideo;
use App\Models\Lesson;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LessonVideoController extends Controller
{
    use ApiResponseTrait;

    /**
     * رفع الفيديو وبدء معالجته
     */
    public function upload(Request $request, $lessonId)
    {
        try {
            // Validate video file
            $request->validate([
                'video' => 'required|file|mimes:mp4,mov,avi,wmv,webm|max:204800' // 200MB max
            ]);

            // التحقق من أن الملف ليس فارغ
            if ($request->file('video')->getSize() == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف الفيديو فارغ'
                ], 400);
            }

            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'الدرس غير موجود'
                ], 404);
            }

            // التحقق من الصلاحيات (admin only)
            if ($request->user()->role !== 'admin') {
                return $this->errorResponse('غير مصرح لك برفع الفيديوهات', 403);
            }

            // حذف الفيديو السابق إذا وجد
            if ($lesson->video_path) {
                $this->deleteOldVideo($lesson);
            }

            // رفع الفيديو الجديد
            $fileName = Str::uuid() . '.' . $request->video->getClientOriginalExtension();
            $tempPath = $request->video->storeAs('temp_videos', $fileName);

            // تحديث المسار المؤقت في قاعدة البيانات
            $lesson->update([
                'video_path' => $tempPath,
                'video_status' => 'processing'
            ]);

            // التحقق من عدم وجود job معالجة مسبقًا
            $existingJobs = DB::table('jobs')
                ->where('payload', 'like', '%ProcessLessonVideo%')
                ->where('payload', 'like', '%"lesson_id":' . $lesson->id . '%')
                ->count();

            if ($existingJobs == 0) {
                // إضافة Job لمعالجة الفيديو
                ProcessLessonVideo::dispatch($lesson)->onQueue('video-processing');
                Cache::put("video_processing_started_{$lesson->id}", time(), 3600);
                Log::info("تم إضافة job معالجة الفيديو للدرس: {$lesson->id}");
            } else {
                Log::info("يوجد job معالجة فيديو قيد التنفيذ للدرس: {$lesson->id}");
            }

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الفيديو بنجاح، وجاري معالجته...',
                'status' => 'processing',
                'upload_progress' => 100,
                'processing_progress' => 0,
                'status_url' => route('lesson.status', $lesson->id)
            ]);

        } catch (\Exception $e) {
            Log::error('Video upload error: ' . $e->getMessage(), [
                'lesson_id' => $lessonId,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في رفع الفيديو: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرجاع playlist الـ HLS مع التحقق من الصلاحيات
     */
    public function getPlaylist(Request $request, Lesson $lesson)
    {
        try {
            /** @var User $user */
            $user = auth()->user();

            if (!$this->canAccessLesson($user, $lesson)) {
                abort(403, 'ليس لديك صلاحية لمشاهدة هذا الدرس');
            }

            $playlistPath = storage_path("app/private_videos/hls/lesson_{$lesson->id}/index.m3u8");

            if (!file_exists($playlistPath)) {
                abort(404, 'الفيديو غير متوفر حالياً');
            }

            // قراءة محتوى الـ playlist وتعديل مسارات الـ segments
            $content = file_get_contents($playlistPath);
            $content = $this->modifyPlaylistUrls($content, $lesson->id);

            return response($content)
                ->header('Content-Type', 'application/vnd.apple.mpegurl')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('Playlist access error: ' . $e->getMessage());
            abort(500, 'خطأ في الخادم');
        }
    }

    /**
     * إرجاع segment معين مع token قصير العمر
     */
    public function getSegment(Request $request, $lessonId, $segment)
    {
        try {
            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'الدرس غير موجود'
                ], 404);
            }
            $user = auth()->user();

            if (!$this->canAccessLesson($user, $lesson)) {
                abort(403, 'غير مصرح');
            }

            // التحقق من صحة الـ token
            $token = $request->get('token');
            if (!$this->validateSegmentToken($token, $lessonId, $segment)) {
                abort(403, 'رابط منتهي الصلاحية');
            }

            $segmentPath = storage_path("app/private_videos/hls/lesson_{$lessonId}/{$segment}");

            if (!file_exists($segmentPath)) {
                abort(404, 'الملف غير موجود');
            }

            return response()->file($segmentPath, [
                'Content-Type' => 'video/mp2t',
                'Cache-Control' => 'private, max-age=3600',
                'Content-Disposition' => 'inline'
            ]);

        } catch (\Exception $e) {
            Log::error('Segment access error: ' . $e->getMessage());
            abort(500);
        }
    }

    /**
     * إرجاع مفتاح التشفير مع حماية HMAC
     */
    public function getKey(Request $request, Lesson $lesson)
    {
        try {
            /** @var User $user */
            $user = auth()->user();

            if (!$this->canAccessLesson($user, $lesson)) {
                abort(403, 'غير مصرح');
            }

            // التحقق من HMAC token
            $token = $request->get('token');
            if (!$this->validateKeyToken($token, $lesson->id)) {
                Log::warning('محاولة وصول غير مصرح لمفتاح التشفير', [
                    'lesson_id' => $lesson->id,
                    'user_id' => $user->id,
                    'token' => substr($token, 0, 20) . '...'
                ]);
                abort(403, 'رابط منتهي الصلاحية أو غير صحيح');
            }

            $keyPath = storage_path("app/private_videos/hls/lesson_{$lesson->id}/enc.key");

            if (!file_exists($keyPath)) {
                Log::error('مفتاح التشفير غير موجود', ['lesson_id' => $lesson->id, 'path' => $keyPath]);
                abort(404, 'مفتاح التشفير غير متوفر');
            }

            Log::info('تم تسليم مفتاح التشفير بنجاح', [
                'lesson_id' => $lesson->id,
                'user_id' => $user->id,
                'key_size' => filesize($keyPath)
            ]);

            // إضافة headers أمان إضافية
            return response()->file($keyPath, [
                'Content-Type' => 'application/octet-stream',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'X-Robots-Tag' => 'noindex, nofollow, nosnippet, noarchive',
                'Content-Security-Policy' => "default-src 'none'"
            ]);

        } catch (\Exception $e) {
            Log::error('Key access error: ' . $e->getMessage(), [
                'lesson_id' => $lesson->id ?? null,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500);
        }
    }

    /**
     * حالة معالجة الفيديو مع تتبع التقدم المحسن
     */
    public function getProcessingStatus(Lesson $lesson)
    {
        $status = $lesson->video_status ?? 'not_uploaded';

        // تتبع التقدم بناءً على الملفات المنشأة
        $progress = $this->calculateProcessingProgress($lesson, $status);

        $response = [
            'lesson_id' => $lesson->id,
            'status' => $status,
            'processing_progress' => $progress,
            'video_available' => $status === 'ready',
            'message' => $this->getStatusMessage($status),
            'estimated_time_remaining' => $this->getEstimatedTimeRemaining($lesson, $status),
            'video_info' => $this->getVideoInfo($lesson)
        ];

        if ($status === 'ready') {
            $response['playlist_url'] = $lesson->getVideoPlaylistUrl();
            $response['encryption_key_url'] = route('lesson.key', ['lesson' => $lesson->id]);
        }

        return response()->json($response);
    }

    /**
     * حساب تقدم المعالجة بناءً على الملفات المنشأة
     */
    private function calculateProcessingProgress(Lesson $lesson, string $status): int
    {
        if ($status === 'ready') return 100;
        if ($status === 'failed') return 0;
        if ($status !== 'processing') return 0;

        $outputDir = storage_path("app/private_videos/hls/lesson_{$lesson->id}");

        if (!is_dir($outputDir)) return 10; // بدء المعالجة

        $playlistPath = "{$outputDir}/index.m3u8";
        if (!file_exists($playlistPath)) return 25; // إنشاء المجلد

        // عد ملفات الـ segments المنشأة
        $segmentFiles = glob("{$outputDir}/segment_*.ts");
        $segmentCount = count($segmentFiles);

        if ($segmentCount === 0) return 40; // بدء إنشاء المقاطع

        // تقدير عدد المقاطع الإجمالي بناءً على مدة الفيديو
        $expectedSegments = $this->estimateSegmentCount($lesson);

        if ($expectedSegments > 0) {
            $segmentProgress = min(90, 50 + (($segmentCount / $expectedSegments) * 40));
            return (int) round($segmentProgress);
        }

        return 70; // تقدم افتراضي
    }

    /**
     * تقدير عدد المقاطع بناءً على مدة الفيديو
     */
    private function estimateSegmentCount(Lesson $lesson): int
    {
        if ($lesson->video_duration) {
            return (int) ceil($lesson->video_duration / 6); // 6 ثوان لكل مقطع
        }
        return 0;
    }

    /**
     * تقدير الوقت المتبقي للمعالجة
     */
    private function getEstimatedTimeRemaining(Lesson $lesson, string $status): ?string
    {
        if ($status !== 'processing') return null;

        $processingStarted = Cache::get("video_processing_started_{$lesson->id}");
        if (!$processingStarted) return null;

        $elapsedMinutes = (time() - $processingStarted) / 60;
        $progress = $this->calculateProcessingProgress($lesson, $status);

        if ($progress > 10) {
            $totalEstimatedMinutes = ($elapsedMinutes / $progress) * 100;
            $remainingMinutes = max(0, $totalEstimatedMinutes - $elapsedMinutes);

            if ($remainingMinutes < 2) return 'أقل من دقيقتين';
            if ($remainingMinutes < 60) return sprintf('حوالي %d دقيقة', (int) round($remainingMinutes));

            $hours = (int) ($remainingMinutes / 60);
            $minutes = (int) ($remainingMinutes % 60);
            return sprintf('حوالي %d ساعة و %d دقيقة', $hours, $minutes);
        }

        return 'جاري التقدير...';
    }

    /**
     * معلومات الفيديو
     */
    private function getVideoInfo(Lesson $lesson): array
    {
        return [
            'duration' => $lesson->getFormattedDuration(),
            'size' => $lesson->getFormattedSize(),
            'uploaded_at' => $lesson->updated_at?->diffForHumans()
        ];
    }

    /**
     * حذف الفيديو (admin only)
     */
    public function deleteVideo(Request $request, Lesson $lesson)
    {
        if ($request->user()->role !== 'admin')
 {
            return $this->errorResponse('غير مصرح', 403);
        }

        $this->deleteOldVideo($lesson);

        $lesson->update([
            'video_path' => null,
            'video_status' => null
        ]);

        return $this->successResponse(['message' => 'تم حذف الفيديو بنجاح']);
    }

    /**
     * التحقق من صلاحية الوصول للدرس
     */
    private function canAccessLesson(?User $user, Lesson $lesson): bool
    {
        if (!$user) {
            return false;
        }

        // التحقق من الجنس المستهدف
        if ($lesson->target_gender !== 'both' && $lesson->target_gender !== $user->gender) {
            return false;
        }

        // إذا كان الدرس مجاني
        if ($lesson->is_free) {
            return true;
        }

        // التحقق من الاشتراك
        return $user->subscriptions()
            ->where('course_id', $lesson->course_id)
            ->where('is_active', true)
            ->where('is_approved', true)
            ->exists();
    }

    /**
     * تعديل روابط الـ playlist لإضافة tokens
     */
    private function modifyPlaylistUrls(string $content, int $lessonId): string
    {
        $lines = explode("\n", $content);
        $modifiedLines = [];

        foreach ($lines as $line) {
            if (preg_match('/\.ts$/', trim($line))) {
                // إنشاء token للـ segment
                $token = $this->generateSegmentToken($lessonId, trim($line));
                $url = route('lesson.segment', ['lessonId' => $lessonId, 'segment' => trim($line)]) . '?token=' . $token;
                $modifiedLines[] = $url;
            } elseif (preg_match('/URI="([^"]+)"/', $line, $matches)) {
                // تعديل URI للمفتاح مع HMAC token
                $keyToken = $this->generateKeyToken($lessonId);
                $keyUrl = route('lesson.key', ['lesson' => $lessonId]) . '?token=' . $keyToken;
                $line = str_replace($matches[1], $keyUrl, $line);
                $modifiedLines[] = $line;
            } else {
                $modifiedLines[] = $line;
            }
        }

        return implode("\n", $modifiedLines);
    }

    /**
     * إنشاء token للـ segment
     */
    private function generateSegmentToken(int $lessonId, string $segment): string
    {
        $data = [
            'lesson_id' => $lessonId,
            'segment' => $segment,
            'user_id' => auth()->id(),
            'expires_at' => now()->addMinutes(10)->timestamp
        ];

        $token = base64_encode(json_encode($data));
        Cache::put("segment_token_{$token}", true, 600); // 10 دقائق

        return $token;
    }

    /**
     * التحقق من صحة token الـ segment
     */
    private function validateSegmentToken(?string $token, int $lessonId, string $segment): bool
    {
        if (!$token) {
            return false;
        }

        if (!Cache::has("segment_token_{$token}")) {
            return false;
        }

        try {
            $data = json_decode(base64_decode($token), true);

            return $data['lesson_id'] == $lessonId
                && $data['segment'] == $segment
                && $data['user_id'] == auth()->id()
                && $data['expires_at'] > now()->timestamp;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * حذف الفيديو السابق
     */
    private function deleteOldVideo(Lesson $lesson): void
    {
        if ($lesson->video_path) {
            // حذف المجلد الكامل للدرس
            $hlsDir = "private_videos/hls/lesson_{$lesson->id}";
            Storage::deleteDirectory($hlsDir);

            // حذف الفيديو المؤقت
            if (Storage::exists($lesson->video_path)) {
                Storage::delete($lesson->video_path);
            }
        }
    }

    /**
     * رسالة حالة المعالجة
     */
    private function getStatusMessage(string $status): string
    {
        return match($status) {
            'processing' => 'جاري معالجة الفيديو وتشفيره...',
            'ready' => 'الفيديو جاهز للمشاهدة',
            'failed' => 'فشل في معالجة الفيديو',
            default => 'لم يتم رفع الفيديو بعد'
        };
    }

    /**
     * إنشاء HMAC token لمفتاح التشفير
     */
    private function generateKeyToken(int $lessonId): string
    {
        $expiry = now()->addMinutes(15)->timestamp; // 15 دقيقة
        $data = $lessonId . '|' . $expiry;
        $signature = hash_hmac('sha256', $data, config('app.key'));
        
        return base64_encode($lessonId . '|' . $expiry . '|' . $signature);
    }

    /**
     * التحقق من HMAC token لمفتاح التشفير
     */
    private function validateKeyToken(?string $token, int $lessonId): bool
    {
        if (!$token) {
            return false;
        }

        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            [$tokenLessonId, $expiry, $signature] = $parts;
            
            // التحقق من رقم الدرس
            if ((int) $tokenLessonId !== $lessonId) {
                return false;
            }
            
            // التحقق من انتهاء الصلاحية
            if ((int) $expiry < now()->timestamp) {
                return false;
            }
            
            // التحقق من التوقيع
            $expectedSignature = hash_hmac('sha256', $tokenLessonId . '|' . $expiry, config('app.key'));
            
            return hash_equals($expectedSignature, $signature);
            
        } catch (\Exception $e) {
            Log::warning('خطأ في التحقق من token مفتاح التشفير: ' . $e->getMessage());
            return false;
        }
    }
}
