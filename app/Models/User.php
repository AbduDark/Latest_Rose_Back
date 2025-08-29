<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Course;
use App\Models\Subscription;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'gender',
        'role',
        'image',
        'active_session_id',
        'device_fingerprint',
        'last_login_at',
        'current_session',
        'profile_image'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isSubscribedTo($courseId)
    {
        // Admins have access to all courses
        if ($this->isAdmin()) {
            return true;
        }
        
        return $this->subscriptions()
                    ->where('course_id', $courseId)
                    ->where('is_active', true)
                    ->where('status', 'approved')
                    ->where(function($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    })
                    ->exists();
    }

    public function hasFavorited($courseId)
    {
        return $this->favorites()
                    ->where('course_id', $courseId)
                    ->exists();
    }


}
