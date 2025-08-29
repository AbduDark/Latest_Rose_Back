<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function add(Request $request, $courseId)
    {
        $user = $request->user();

        $favorite = Favorite::firstOrCreate([
            'user_id' => $user->id,
            'course_id' => $courseId,
        ]);

        return response()->json([
            'message' => 'Course added to favorites',
            'favorite' => $favorite
        ], 201);
    }

    public function remove(Request $request, $courseId)
    {
        $user = $request->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$favorite) {
            return response()->json(['message' => 'Favorite not found'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Course removed from favorites']);
    }
}
