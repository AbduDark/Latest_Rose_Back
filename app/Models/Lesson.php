<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'content',
        'order',
        'duration_minutes',
        'is_free',
        'target_gender',
        'video_path',
        'video_status', // جديد: processing, ready, failed
        'video_duration', // جديد: مدة الفيديو بالثواني
        'video_size', // جديد: حجم الفيديو بالبايت
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'video_duration' => 'integer',
            'video_size' => 'integer',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * التحقق من توفر الفيديو
     */
    public function hasVideo(): bool
    {
        return !empty($this->video_path) && $this->video_status === 'ready';
    }

    /**
     * الحصول على رابط playlist الفيديو
     */
    public function getVideoPlaylistUrl(): ?string
    {
        if (!$this->hasVideo()) {
            return null;
        }

        return route('lesson.playlist', ['lesson' => $this->id]);
    }

    /**
     * التحقق من حالة معالجة الفيديو
     */
    public function isVideoProcessing(): bool
    {
        return $this->video_status === 'processing';
    }

    /**
     * التحقق من فشل معالجة الفيديو
     */
    public function isVideoFailed(): bool
    {
        return $this->video_status === 'failed';
    }

    /**
     * الحصول على رسالة حالة الفيديو
     */
    public function getVideoStatusMessage(): string
    {
        return match($this->video_status) {
            'processing' => 'جاري معالجة الفيديو...',
            'ready' => 'الفيديو جاهز للمشاهدة',
            'failed' => 'فشل في معالجة الفيديو',
            default => 'لم يتم رفع الفيديو'
        };
    }

    /**
     * تنسيق مدة الفيديو
     */
    public function getFormattedDuration(): ?string
    {
        if (!$this->video_duration) {
            return null;
        }

        $minutes = floor($this->video_duration / 60);
        $seconds = $this->video_duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * تنسيق حجم الفيديو
     */
    public function getFormattedSize(): ?string
    {
        if (!$this->video_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->video_size;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
