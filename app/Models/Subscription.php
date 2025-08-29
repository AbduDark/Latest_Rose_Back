<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'vodafone_number',
        'parent_phone',
        'student_info',
        'subscribed_at',
        'expires_at',
        'is_active',
        'is_approved',
        'status',
        'admin_notes',
        'approved_at',
        'rejected_at',
        'approved_by',
        'rejected_by'
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }



    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActiveAndNotExpired()
    {
        return $this->is_active && $this->status === 'approved' && !$this->isExpired();
    }

    public function getDaysRemaining()
    {
        if (!$this->expires_at) {
            return null;
        }

        $diff = $this->expires_at->diffInDays(now(), false);
        return $this->expires_at->isFuture() ? $diff : 0;
    }

    public function getHoursRemaining()
    {
        if (!$this->expires_at) {
            return null;
        }

        $diff = $this->expires_at->diffInHours(now(), false);
        return $this->expires_at->isFuture() ? $diff : 0;
    }

    public function isExpiringSoon($days = 3)
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->getDaysRemaining() <= $days && !$this->isExpired();
    }
}
