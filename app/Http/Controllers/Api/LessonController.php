<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\Subscription; // Import Subscription model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
class LessonController extends Controller
{
    use ApiResponseTrait;
    public function index($courseId, Request $request)
    {
        $course = Course::findOrFail($courseId);
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'يجب تسجيل الدخول لعرض الدروس'], 401);
        }

        $isSubscribed = $user->isSubscribedTo($courseId);

        if (!$isSubscribed) {
            return response()->json(['message' => 'يجب الاشتراك في الدورة أولاً'], 403);
        }

        $userGender = $user->gender;
        $lessons = $course->lessons()
            ->where(function($query) use ($userGender) {
                $query->where('target_gender', 'both')
                      ->orWhere('target_gender', $userGender);
            })
            ->orderBy('order')
            ->get();

        return response()->json($lessons);
    }


    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'video_url' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'is_free' => 'boolean',
            'target_gender' => 'required|in:male,female,both',
        ]);

        $lesson = Lesson::create($request->all());

        return response()->json($lesson, 201);
    }

    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'sometimes|string',
            'video_url' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'is_free' => 'boolean',
            'target_gender' => 'sometimes|in:male,female,both',
        ]);

        $lesson->update($request->all());

        return response()->json($lesson);
    }

    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted successfully']);
    }

    public function show($id)
    {
        try {
              /** @var User $user */
            $user = auth()->user();

            if (!$user) {
                return $this->errorResponse(__('messages.auth.unauthenticated'), 401);
            }

            $lesson = Lesson::with(['course', 'comments.user'])->find($id);

            if (!$lesson) {
                return $this->errorResponse(__('messages.lesson.not_found'), 404);
            }

            // التحقق من توافق الجنس
            if ($lesson->target_gender !== 'both' && $lesson->target_gender !== $user->gender) {
                return $this->errorResponse(__('messages.lesson.gender_not_allowed'), 403);
            }

            // التحقق من حالة الكورس
            if (!$lesson->course->is_active) {
                return $this->errorResponse(__('messages.course.not_active'), 403);
            }

            // التحقق من الاشتراك إذا لم يكن الدرس مجاني
            if (!$lesson->is_free) {
                $subscription = Subscription::where('user_id', $user->id)
                    ->where('course_id', $lesson->course_id)
                    ->where('is_active', true)
                    ->where('is_approved', true)
                    ->first();

                if (!$subscription) {
                    return $this->errorResponse(__('messages.subscription.required'), 403);
                }
            }

            return $this->successResponse($lesson, __('messages.lesson.retrieved_successfully'));

        } catch (\Exception $e) {
            Log::error('Error retrieving lesson: ' . $e->getMessage());
            return $this->errorResponse(__('messages.general.server_error'), 500);
        }
    }
}
