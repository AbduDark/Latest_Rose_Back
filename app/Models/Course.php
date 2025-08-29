<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'price',
        'level',
        'is_active',
        'duration_hours',
        'requirements',
        'instructor_name',
        'language',
        'grade',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Accessor للحصول على رابط الصورة الكامل
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return url($this->image);
        }
        return null;
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    public function averageRating()
    {
        return $this->ratings()->avg('rating');
    }

    public function totalRatings()
    {
        return $this->ratings()->count();
    }
}
