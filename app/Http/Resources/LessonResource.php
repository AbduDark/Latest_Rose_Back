<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'video_url' => $this->when(
                $this->is_free || $this->isUserSubscribed($user),
                $this->video_url
            ),
            'duration_minutes' => $this->duration_minutes,
            'order' => $this->order,
            'is_free' => $this->is_free,
            'can_access' => $this->is_free || $this->isUserSubscribed($user),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function isUserSubscribed(?object $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->course->subscriptions()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }
}
