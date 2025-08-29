<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'duration_hours' => $this->duration_hours,
            'level' => $this->level,
            'language' => $this->language,
            'instructor_name' => $this->instructor_name,
            'image_url' => $this->image ? url($this->image) : null, // التعديل هنا
            'is_active' => $this->is_active,
            'lessons_count' => $this->lessons_count ?? $this->lessons()->count(),
            'avg_rating' => $this->avg_rating ?? $this->ratings()->avg('rating'),
            'is_subscribed' => $this->when(
                $user !== null,
                fn() => $this->subscriptions()->where('user_id', $user->id)->exists()
            ),
            'is_favorite' => $this->when(
                $user !== null,
                fn() => $this->favorites()->where('user_id', $user->id)->exists()
            ),
            'created_at' => $this->created_at,
        ];
    }
}