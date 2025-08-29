<?php

namespace App\Jobs;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ProcessLessonVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lesson;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The maximum number of seconds the job should run.
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Lesson $lesson)
    {
        $this->lesson = $lesson;
        $this->onQueue('video-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lesson = null;

        try {
            Log::info("بدء معالجة الفيديو للدرس: {$this->lesson->id}");

            // التحقق من وجود الدرس في قاعدة البيانات
            $lesson = Lesson::where('id', $this->lesson->id)->firstOrFail();

            // التحقق من وجود مسار الفيديو
            if (empty($lesson->video_path)) {
                throw new \Exception("مسار الفيديو فارغ");
            }

            $videoPath = storage_path('app/' . $lesson->video_path);
            $outputDir = storage_path("app/private_videos/hls/lesson_{$lesson->id}");

            // التحقق من صحة الملف
            $this->validateVideoFile($videoPath);

            // التحقق من توفر FFmpeg
            $this->checkFFmpegAvailability();

            // إنشاء المجلدات المطلوبة
            $this->createDirectories($outputDir);

            // تحديث حالة المعالجة وحفظ وقت البداية
            $lesson->update(['video_status' => 'processing']);
            Cache::put("video_processing_started_{$lesson->id}", time(), 3600); // حفظ لساعة

            // توليد مفاتيح التشفير
            $keyData = $this->generateEncryptionKeys($outputDir);

            // معالجة الفيديو باستخدام FFmpeg مع التشفير
            $this->processVideoWithFFmpeg($videoPath, $outputDir, $keyData);

            // التحقق من نجاح المعالجة
            $this->verifyProcessing($outputDir);

            // الحصول على معلومات الفيديو
            $videoInfo = $this->getVideoInfo($videoPath);

            // تحديث بيانات الدرس
            $lesson->update([
                'video_status' => 'ready',
                'video_duration' => $videoInfo['duration'] ?? null,
                'video_size' => $videoInfo['size'] ?? null,
            ]);

            // حذف الملف المؤقت
            if (Storage::exists($lesson->video_path)) {
                Storage::delete($lesson->video_path);
                $lesson->update(['video_path' => "private_videos/hls/lesson_{$lesson->id}/index.m3u8"]);
            }

            Log::info("تمت معالجة الفيديو بنجاح للدرس: {$lesson->id}");

        } catch (\Exception $e) {
            Log::error("خطأ في معالجة الفيديو للدرس: {$this->lesson->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // تحديث حالة الدرس في قاعدة البيانات
            if ($lesson) {
                $lesson->update(['video_status' => 'failed']);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("فشل نهائي في معالجة الفيديو للدرس: {$this->lesson->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // تحديث حالة الدرس إلى فاشل
        try {
            $lesson = Lesson::where('id', $this->lesson->id)->first();
            if ($lesson) {
                $lesson->update(['video_status' => 'failed']);
            }
        } catch (\Exception $e) {
            Log::error("لا يمكن تحديث حالة الدرس {$this->lesson->id}: " . $e->getMessage());
        }

        // تنظيف الملفات المؤقتة
        $this->cleanup();
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function retryAfter(): int
    {
        return 30; // انتظار 30 ثانية بين المحاولات
    }

    /**
     * التحقق من صحة ملف الفيديو
     */
    private function validateVideoFile(string $videoPath): void
    {
        if (!file_exists($videoPath)) {
            throw new \Exception("ملف الفيديو غير موجود: {$videoPath}");
        }

        if (filesize($videoPath) == 0) {
            throw new \Exception("ملف الفيديو فارغ: {$videoPath}");
        }

        // التحقق من نوع الملف
        $mimeType = mime_content_type($videoPath);
        $allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/webm'];

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception("نوع الملف غير مدعوم: {$mimeType}");
        }

        Log::info("تم التحقق من صحة ملف الفيديو: {$videoPath} - الحجم: " . $this->formatBytes(filesize($videoPath)));
    }

    /**
     * التحقق من توفر FFmpeg
     */
    private function checkFFmpegAvailability(): void
    {
        $process = new Process(['ffmpeg', '-version']);
        $process->setTimeout(10);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("FFmpeg غير متوفر في النظام. يرجى تثبيته أولاً.");
        }

        Log::info("FFmpeg متوفر: " . trim(explode("\n", $process->getOutput())[0]));
    }

    /**
     * الحصول على معلومات الفيديو
     */
    private function getVideoInfo(string $videoPath): array
    {
        $command = [
            'ffprobe',
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $videoPath
        ];

        $process = new Process($command);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::warning("لا يمكن الحصول على معلومات الفيديو: " . $process->getErrorOutput());
            return ['duration' => null, 'size' => filesize($videoPath)];
        }

        $data = json_decode($process->getOutput(), true);
        $duration = null;

        // البحث عن مدة الفيديو
        if (isset($data['format']['duration'])) {
            $duration = (int) round(floatval($data['format']['duration']));
        } elseif (isset($data['streams'])) {
            foreach ($data['streams'] as $stream) {
                if ($stream['codec_type'] === 'video' && isset($stream['duration'])) {
                    $duration = (int) round(floatval($stream['duration']));
                    break;
                }
            }
        }

        return [
            'duration' => $duration,
            'size' => filesize($videoPath)
        ];
    }

    /**
     * تنسيق الحجم بالبايت
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * إنشاء المجلدات المطلوبة
     */
    private function createDirectories(string $outputDir): void
    {
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new \Exception("فشل في إنشاء مجلد الإخراج: {$outputDir}");
            }
        }
    }

    /**
     * توليد مفاتيح التشفير AES-128
     */
    private function generateEncryptionKeys(string $outputDir): array
    {
        // توليد مفتاح عشوائي 16 بايت
        $key = random_bytes(16);
        $keyFile = "{$outputDir}/enc.key";

        if (file_put_contents($keyFile, $key) === false) {
            throw new \Exception("فشل في كتابة ملف المفتاح");
        }

        // توليد IV عشوائي بطول صحيح (16 بايت = 32 hex chars)
        $ivBytes = random_bytes(16);
        $iv = bin2hex($ivBytes); // هذا ينتج 32 character hex string
        
        Log::info("تم توليد IV بطول: " . strlen($iv) . " chars (expected 32)");

        // إنشاء ملف معلومات المفتاح
        $keyInfoFile = "{$outputDir}/enc.keyinfo";
        $keyUri = route('lesson.key', ['lesson' => $this->lesson->id]);

        $keyInfoContent = "{$keyUri}\n{$keyFile}\n{$iv}";

        if (file_put_contents($keyInfoFile, $keyInfoContent) === false) {
            throw new \Exception("فشل في كتابة ملف معلومات المفتاح");
        }

        Log::info("تم إنشاء مفاتيح التشفير للدرس {$this->lesson->id}");

        return [
            'key_file' => $keyFile,
            'key_info_file' => $keyInfoFile,
            'iv' => $iv
        ];
    }

    /**
     * معالجة الفيديو باستخدام FFmpeg مع HLS والتشفير - دعم جودات متعددة
     */
    private function processVideoWithFFmpeg(string $inputPath, string $outputDir, array $keyData): void
    {
        $masterPlaylist = "{$outputDir}/master.m3u8";
        $qualities = $this->getVideoQualities();
        
        Log::info("بدء معالجة FFmpeg للدرس {$this->lesson->id} بجودات متعددة");
        
        $startTime = microtime(true);
        $successfulQualities = [];
        
        foreach ($qualities as $quality) {
            try {
                $this->processQuality($inputPath, $outputDir, $keyData, $quality);
                $successfulQualities[] = $quality;
                Log::info("تم معالجة الجودة {$quality['name']} بنجاح للدرس {$this->lesson->id}");
            } catch (\Exception $e) {
                Log::warning("فشل في معالجة الجودة {$quality['name']} للدرس {$this->lesson->id}: " . $e->getMessage());
                // إذا فشلت الجودة الأساسية، أعد المحاولة مع جودة واحدة فقط
                if ($quality['name'] === '720p') {
                    $this->processSingleQuality($inputPath, $outputDir, $keyData);
                    return;
                }
            }
        }
        
        // إنشاء master playlist
        if (count($successfulQualities) > 1) {
            $this->createMasterPlaylist($outputDir, $successfulQualities);
        } else {
            // استخدام الجودة الوحيدة كـ index رئيسي
            $singleQuality = $successfulQualities[0] ?? $qualities[0];
            copy("{$outputDir}/{$singleQuality['name']}.m3u8", "{$outputDir}/index.m3u8");
        }
        
        // تنظيف الملفات المؤقتة
        $this->cleanupTempFiles($outputDir, $keyData);
        
        $processingTime = round(microtime(true) - $startTime, 2);
        Log::info("انتهت معالجة FFmpeg بنجاح للدرس {$this->lesson->id} في {$processingTime} ثانية");
    }

    /**
     * التحقق من نجاح المعالجة
     */
    private function verifyProcessing(string $outputDir): void
    {
        $playlistFile = "{$outputDir}/index.m3u8";

        if (!file_exists($playlistFile)) {
            throw new \Exception("لم يتم إنشاء ملف الـ playlist");
        }

        // التحقق من وجود ملفات segments
        $content = file_get_contents($playlistFile);
        if (empty($content)) {
            throw new \Exception("ملف الـ playlist فارغ");
        }

        // عد ملفات الـ segments
        $segmentCount = substr_count($content, '.ts');
        if ($segmentCount === 0) {
            throw new \Exception("لم يتم إنشاء أي مقاطع فيديو");
        }

        // التحقق من ملف المفتاح
        $keyFile = "{$outputDir}/enc.key";
        if (!file_exists($keyFile) || filesize($keyFile) !== 16) {
            throw new \Exception("ملف مفتاح التشفير غير صحيح");
        }

        Log::info("تم التحقق من صحة المعالجة للدرس {$this->lesson->id} - عدد المقاطع: {$segmentCount}");
    }

    /**
     * تنظيف الملفات في حالة الفشل
     */
    private function cleanup(): void
    {
        try {
            $outputDir = "private_videos/hls/lesson_{$this->lesson->id}";
            Storage::deleteDirectory($outputDir);
            Log::info("تم تنظيف الملفات للدرس {$this->lesson->id}");
        } catch (\Exception $e) {
            Log::error("خطأ في تنظيف الملفات للدرس {$this->lesson->id}: " . $e->getMessage());
        }
    }

    /**
     * إعادة المحاولة
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(4);
    }

    /**
     * الحصول على إعدادات الجودات المتعددة
     */
    private function getVideoQualities(): array
    {
        return [
            [
                'name' => '360p',
                'height' => 360,
                'bitrate' => '800k',
                'maxrate' => '1M',
                'bufsize' => '2M'
            ],
            [
                'name' => '720p',
                'height' => 720,
                'bitrate' => '2500k',
                'maxrate' => '3M',
                'bufsize' => '6M'
            ],
            [
                'name' => '1080p',
                'height' => 1080,
                'bitrate' => '5000k',
                'maxrate' => '6M',
                'bufsize' => '12M'
            ]
        ];
    }

    /**
     * معالجة جودة واحدة محددة
     */
    private function processQuality(string $inputPath, string $outputDir, array $keyData, array $quality): void
    {
        $outputFile = "{$outputDir}/{$quality['name']}.m3u8";
        
        $command = [
            'ffmpeg',
            '-i', $inputPath,

            // إعدادات الفيديو حسب الجودة
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', '23',
            '-b:v', $quality['bitrate'],
            '-maxrate', $quality['maxrate'],
            '-bufsize', $quality['bufsize'],
            '-vf', "scale=-2:{$quality['height']}",
            '-profile:v', 'high',
            '-level:v', '4.0',

            // إعدادات الصوت
            '-c:a', 'aac',
            '-b:a', '128k',
            '-ar', '44100',
            '-ac', '2',

            // إعدادات HLS
            '-f', 'hls',
            '-hls_time', '6',
            '-hls_list_size', '0',
            '-hls_segment_filename', "{$outputDir}/{$quality['name']}_segment_%03d.ts",

            // إعدادات التشفير
            '-hls_key_info_file', $keyData['key_info_file'],
            '-hls_flags', 'independent_segments',

            // ملف الإخراج
            $outputFile,

            // إعدادات إضافية
            '-threads', '0',
            '-movflags', '+faststart',
            '-loglevel', 'warning',
            '-y'
        ];

        $process = new Process($command);
        $process->setTimeout(3600);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * معالجة بجودة واحدة فقط (fallback)
     */
    private function processSingleQuality(string $inputPath, string $outputDir, array $keyData): void
    {
        $outputFile = "{$outputDir}/index.m3u8";

        $command = [
            'ffmpeg',
            '-i', $inputPath,

            // إعدادات الفيديو - محسنة للجودة والحجم
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', '23',
            '-maxrate', '2M',
            '-bufsize', '4M',
            '-vf', 'scale=-2:720',

            // إعدادات الصوت
            '-c:a', 'aac',
            '-b:a', '128k',
            '-ar', '44100',

            // إعدادات HLS
            '-f', 'hls',
            '-hls_time', '6',
            '-hls_list_size', '0',
            '-hls_segment_filename', "{$outputDir}/segment_%03d.ts",

            // إعدادات التشفير
            '-hls_key_info_file', $keyData['key_info_file'],
            '-hls_flags', 'independent_segments',

            // ملف الإخراج
            $outputFile,

            // إعدادات إضافية للأداء
            '-threads', '0',
            '-movflags', '+faststart',
            '-loglevel', 'warning',
            '-y'
        ];

        $process = new Process($command);
        $process->setTimeout(3600);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * إنشاء master playlist للجودات المتعددة
     */
    private function createMasterPlaylist(string $outputDir, array $qualities): void
    {
        $masterContent = "#EXTM3U\n#EXT-X-VERSION:6\n\n";
        
        foreach ($qualities as $quality) {
            $bandwidth = (int) str_replace('k', '000', $quality['bitrate']);
            $masterContent .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$quality['height']}x" . 
                              ($quality['height'] == 360 ? '640' : ($quality['height'] == 720 ? '1280' : '1920')) . 
                              "\n{$quality['name']}.m3u8\n\n";
        }
        
        file_put_contents("{$outputDir}/master.m3u8", $masterContent);
        copy("{$outputDir}/master.m3u8", "{$outputDir}/index.m3u8");
        
        Log::info("تم إنشاء master playlist للدرس {$this->lesson->id} بعدد الجودات: " . count($qualities));
    }

    /**
     * تنظيف الملفات المؤقتة بعد نجاح التشفير
     */
    private function cleanupTempFiles(string $outputDir, array $keyData): void
    {
        try {
            // حذف ملف .keyinfo لأنه داخلي ومش مطلوب يتخزن
            if (file_exists($keyData['key_info_file'])) {
                unlink($keyData['key_info_file']);
                Log::info("تم حذف ملف keyinfo المؤقت للدرس {$this->lesson->id}");
            }
            
            // الاحتفاظ بـ m3u8 + ts + المفتاح enc.key فقط
            Log::info("تم الاحتفاظ بالملفات الضرورية فقط للدرس {$this->lesson->id}");
        } catch (\Exception $e) {
            Log::warning("تحذير: لا يمكن حذف بعض الملفات المؤقتة للدرس {$this->lesson->id}: " . $e->getMessage());
        }
    }
}
