<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function index($courseId)
    {
        $ratings = Rating::with('user:id,name,image')
            ->where('course_id', $courseId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($ratings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Check if user is subscribed to the course
        if (!$user->isSubscribedTo($request->course_id)) {
            return response()->json(['message' => 'You must be subscribed to rate this course'], 403);
        }

        $rating = Rating::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $request->course_id,
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
            ]
        );

        return response()->json([
            'message' => 'Rating submitted successfully',
            'rating' => $rating
        ], 201);
    }
}
