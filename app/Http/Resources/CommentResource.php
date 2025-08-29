<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_date' => $this->created_at->diffForHumans(),

            // User information
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'profile_image' => $this->user->profile_image
                    ? url('storage/' . $this->user->profile_image)
                    : null,
            ],

            // Lesson information
            'lesson' => [
                'id' => $this->lesson->id,
                'title' => $this->lesson->title,
            ],

            // Course information
            'course' => [
                'id' => $this->course->id,
                'title' => $this->course->title,
            ],

            // Status information
            'status' => [
                'text' => $this->is_approved
                    ? ['ar' => 'معتمد', 'en' => 'Approved']
                    : ['ar' => 'في الانتظار', 'en' => 'Pending'],
                'class' => $this->is_approved ? 'approved' : 'pending',
            ]
        ];
    }
}
