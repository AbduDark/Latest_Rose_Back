<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Lesson;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    use ApiResponseTrait;

    /**
     * إضافة تعليق جديد
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'lesson_id' => 'required|exists:lessons,id',
                'content' => 'required|string|min:1|max:1000'
            ], [
                'lesson_id.required' => 'معرف الدرس مطلوب|Lesson ID is required',
                'lesson_id.exists' => 'الدرس غير موجود|Lesson does not exist',
                'content.required' => 'محتوى التعليق مطلوب|Comment content is required',
                'content.min' => 'التعليق يجب أن يحتوي على حرف واحد على الأقل|Comment must be at least 1 character',
                'content.max' => 'التعليق لا يجب أن يتجاوز 1000 حرف|Comment must not exceed 1000 characters'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            /** @var User $user */
            $user = Auth::user();
            $lesson = Lesson::with('course')->findOrFail($request->lesson_id);

            // التحقق من أن الكورس نشط
            if (!$lesson->course->is_active) {
                return $this->errorResponse([
                    'ar' => 'الكورس غير متاح حالياً',
                    'en' => 'Course is not available'
                ], 403);
            }

            // التحقق من توافق الجنس مع الدرس
            if ($lesson->target_gender !== 'both' && $lesson->target_gender !== $user->gender) {
                return $this->errorResponse([
                    'ar' => 'هذا الدرس غير متاح للجنس الخاص بك',
                    'en' => 'This lesson is not available for your gender'
                ], 403);
            }

            // التحقق من اشتراك المستخدم في الكورس (إلا إذا كان أدمن أو الدرس مجاني)
            if ($user->Role !== 'admin' && !$lesson->is_free) {
                $isSubscribed = $user->subscriptions()
                    ->where('course_id', $lesson->course_id)
                    ->where('is_active', true)
                    ->where('is_approved', true)
                    ->exists();

                if (!$isSubscribed) {
                    return $this->errorResponse([
                        'ar' => 'يجب أن تكون مشتركاً في الكورس لإضافة تعليق',
                        'en' => 'You must be subscribed to the course to add a comment'
                    ], 403);
                }
            }

            $comment = Comment::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'course_id' => $lesson->course_id,
                'content' => trim($request->content),
            ]);

            $comment->load(['user:id,name,email', 'lesson:id,title', 'course:id,title']);

            return $this->successResponse([
                'comment' => $comment
            ], [
                'ar' => 'تم إضافة التعليق بنجاح',
                'en' => 'Comment added successfully'
            ], 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse([
                'ar' => 'الدرس المطلوب غير موجود',
                'en' => 'The requested lesson does not exist'
            ], 404);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Comment store error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'lesson_id' => $request->lesson_id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse();
        }
    }

    /**
     * جلب تعليقات درس معين
     */
    public function getLessonComments($lessonId)
    {
        try {
            $lesson = Lesson::with('course')->findOrFail($lessonId);
            /** @var User $user */
            $user = Auth::user();

            // التحقق من أن الكورس نشط
            if (!$lesson->course->is_active) {
                return $this->errorResponse([
                    'ar' => 'الكورس غير متاح حالياً',
                    'en' => 'Course is not available'
                ], 403);
            }

            // التحقق من توافق الجنس مع الدرس
            if ($lesson->target_gender !== 'both' && $lesson->target_gender !== $user->gender) {
                return $this->errorResponse([
                    'ar' => 'هذا الدرس غير متاح للجنس الخاص بك',
                    'en' => 'This lesson is not available for your gender'
                ], 403);
            }

            // التحقق من إمكانية الوصول للدرس
            $canAccess = false;

            if ($user->Role === 'admin') {
                $canAccess = true;
            } elseif ($lesson->is_free) {
                $canAccess = true;
            } else {
                $canAccess = $user->subscriptions()
                    ->where('course_id', $lesson->course_id)
                    ->where('is_active', true)
                    ->where('is_approved', true)
                    ->exists();
            }

            if (!$canAccess) {
                return $this->errorResponse([
                    'ar' => 'يجب أن تكون مشتركاً في الكورس لعرض التعليقات',
                    'en' => 'You must be subscribed to the course to view comments'
                ], 403);
            }

            // جلب جميع التعليقات
            $comments = Comment::where('lesson_id', $lessonId)
                ->with(['user:id,name,email', 'lesson:id,title', 'course:id,title'])
                ->latest()
                ->get();

            return $this->successResponse([
                'comments' => $comments,
                'total' => $comments->count()
            ], [
                'ar' => 'تم جلب التعليقات بنجاح',
                'en' => 'Comments retrieved successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse([
                'ar' => 'الدرس المطلوب غير موجود',
                'en' => 'The requested lesson does not exist'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Get lesson comments error: ' . $e->getMessage(), [
                'lesson_id' => $lessonId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse();
        }
    }

    /**
     * حذف تعليق
     */
    public function destroy($id)
    {
        try {
            $comment = Comment::with(['lesson', 'course', 'user'])->findOrFail($id);
            /** @var User $user */
            $user = Auth::user();

            // صلاحيات الحذف:
            // 1. صاحب التعليق يمكنه حذف تعليقه
            // 2. الأدمن يمكنه حذف أي تعليق
            $canDelete = false;

            if ($user->Role === 'admin') {
                $canDelete = true;
            } elseif ($comment->user_id === $user->id) {
                $canDelete = true;
            }

            if (!$canDelete) {
                return $this->errorResponse([
                    'ar' => 'غير مصرح لك بحذف هذا التعليق',
                    'en' => 'You are not authorized to delete this comment'
                ], 403);
            }

            $comment->delete();

            return $this->successResponse(null, [
                'ar' => 'تم حذف التعليق بنجاح',
                'en' => 'Comment deleted successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse([
                'ar' => 'التعليق المطلوب غير موجود',
                'en' => 'The requested comment does not exist'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Delete comment error: ' . $e->getMessage(), [
                'comment_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse();
        }
    }

    /**
     * جلب جميع تعليقات المستخدم
     */
    public function getUserComments()
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            $comments = Comment::where('user_id', $user->id)
                ->with(['lesson:id,title', 'course:id,title'])
                ->latest()
                ->get();

            return $this->successResponse([
                'comments' => $comments,
                'total' => $comments->count()
            ], [
                'ar' => 'تم جلب تعليقاتك بنجاح',
                'en' => 'Your comments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Get user comments error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse();
        }
    }
}