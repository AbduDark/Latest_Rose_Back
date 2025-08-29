<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;

use Illuminate\Routing\Controller as BaseController; // Add this line
use App\Traits\ApiResponseTrait;
use App\Models\User;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\Lesson;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\MessageBag;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class AdminController extends BaseController
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            if (!$request->user() || $request->user()->role !== 'admin') {
                return $this->errorResponse([
                    'ar' => 'ليس لديك صلاحية للوصول لهذا المحتوى',
                    'en' => 'You do not have permission to access this content'
                ], 403);
            }
            return $next($request);
        });
    }

    // إدارة المستخدمين
    public function getUsers(Request $request)
    {
        try {
            $query = User::query();

            // البحث
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // فلترة حسب الدور
            if ($request->has('role')) {
                $query->where('role', $request->get('role'));
            }

            // فلترة حسب الجنس
            if ($request->has('gender')) {
                $query->where('gender', $request->get('gender'));
            }

            // فلترة حسب حالة التحقق من البريد
            if ($request->has('verified')) {
                if ($request->get('verified') === 'true') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }

            $users = $query->withCount(['subscriptions', 'favorites'])
                          ->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 15));

            return $this->successResponse($users, [
                'ar' => 'تم جلب قائمة المستخدمين بنجاح',
                'en' => 'Users retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get users error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    public function getUserDetails($id)
    {
        try {
            $user = User::with(['subscriptions.course', 'favorites.course'])
                       ->withCount(['subscriptions', 'favorites'])
                       ->findOrFail($id);

            return $this->successResponse($user, [
                'ar' => 'تم جلب تفاصيل المستخدم بنجاح',
                'en' => 'User details retrieved successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse([
                'ar' => 'المستخدم غير موجود',
                'en' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin get user details error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'phone' => 'sometimes|string|max:20|unique:users,phone,' . $id,
                'role' => 'sometimes|in:admin,student',
                'password' => 'sometimes|string|min:8',
                'is_active' => 'sometimes|boolean',
            ], [
                'name.string' => 'الاسم يجب أن يكون نص|Name must be a string',
                'email.email' => 'البريد الإلكتروني غير صحيح|Invalid email format',
                'email.unique' => 'البريد الإلكتروني مستخدم بالفعل|Email already exists',
                'phone.unique' => 'رقم الهاتف مستخدم بالفعل|Phone number already exists',
                'role.in' => 'الدور يجب أن يكون admin أو student|Role must be admin or student',
                'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف|Password must be at least 8 characters',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            $data = $request->only(['name', 'email', 'phone', 'role', 'is_active']);

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            return $this->successResponse($user, [
                'ar' => 'تم تحديث بيانات المستخدم بنجاح',
                'en' => 'User updated successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse([
                'ar' => 'المستخدم غير موجود',
                'en' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin update user error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);

            // منع حذف المستخدم الحالي
            if ($user->id === auth()->id()) {
                return $this->errorResponse([
                    'ar' => 'لا يمكنك حذف حسابك الخاص',
                    'en' => 'You cannot delete your own account'
                ], 422);
            }

            $user->delete();

            return $this->successResponse([], [
                'ar' => 'تم حذف المستخدم بنجاح',
                'en' => 'User deleted successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse([
                'ar' => 'المستخدم غير موجود',
                'en' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin delete user error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    // إحصائيات الدشبورد
    public function getDashboardStats()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'total_students' => User::where('role', 'student')->count(),
                'total_courses' => Course::count(),
                'active_courses' => Course::where('is_active', true)->count(),
                'total_subscriptions' => Subscription::count(),
                'active_subscriptions' => Subscription::where('is_active', true)->count(),
                'approved_subscriptions' => Subscription::where('is_approved', true)->count(),
                'pending_subscriptions' => Subscription::where('is_approved', false)->count(),
            ];

            // إحصائيات شهرية
            $monthlyStats = [
                'new_users_this_month' => User::whereMonth('created_at', Carbon::now()->month)
                                              ->whereYear('created_at', Carbon::now()->year)
                                              ->count(),
                'new_subscriptions_this_month' => Subscription::whereMonth('created_at', Carbon::now()->month)
                                                             ->whereYear('created_at', Carbon::now()->year)
                                                             ->count(),
            ];

            return $this->successResponse([
                'general_stats' => $stats,
                'monthly_stats' => $monthlyStats
            ], [
                'ar' => 'تم جلب إحصائيات الدشبورد بنجاح',
                'en' => 'Dashboard statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin dashboard stats error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    // إدارة الاشتراكات
   public function getSubscriptions(Request $request)
{
    try {
        $user = Auth::user();

        // التحقق من أن المستخدم له دور admin
        if ($user->role !== 'admin') {
            return $this->errorResponse([
                'ar' => 'غير مسموح لك بالوصول إلى هذه البيانات',
                'en' => 'You are not authorized to access this data'
            ], 403);
        }

        $subscriptions = Subscription::with(['course', 'user', 'approvedBy', 'rejectedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse([
            'subscriptions' => $subscriptions
        ], [
            'ar' => 'تم جلب جميع الاشتراكات بنجاح',
            'en' => 'All subscriptions retrieved successfully'
        ]);

    } catch (\Exception $e) {
        return $this->serverErrorResponse();
    }
}

    public function getPendingSubscriptions(Request $request)
    {
        try {
            $subscriptions = Subscription::pending()
                ->with(['user', 'course'])
                ->orderBy('created_at', 'asc')
                ->paginate($request->get('per_page', 15));

            return $this->successResponse($subscriptions, [
                'ar' => 'تم جلب طلبات الاشتراك المعلقة بنجاح',
                'en' => 'Pending subscription requests retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get pending subscriptions error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    public function approveSubscription(Request $request, $id)
    {
        try {
            $subscription = Subscription::findOrFail($id);

            if ($subscription->status !== 'pending') {
                return $this->errorResponse([
                    'ar' => 'هذا الطلب تم التعامل معه مسبقاً',
                    'en' => 'This request has already been processed'
                ], 400);
            }

            $subscription->update([
                'status' => 'approved',
                'is_approved' => true,
                'is_active' => true,
                'expires_at' => now()->addDays(30), // اشتراك شهري (30 يوم)
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'admin_notes' => $request->get('admin_notes')
            ]);

            return $this->successResponse([
                'subscription' => $subscription->load(['course', 'user', 'approvedBy'])
            ], [
                'ar' => 'تم قبول طلب الاشتراك بنجاح',
                'en' => 'Subscription request approved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin approve subscription error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    public function rejectSubscription(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_notes' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            $subscription = Subscription::findOrFail($id);

            if ($subscription->status !== 'pending') {
                return $this->errorResponse([
                    'ar' => 'هذا الطلب تم التعامل معه مسبقاً',
                    'en' => 'This request has already been processed'
                ], 400);
            }

            $subscription->update([
                'status' => 'rejected',
                'is_approved' => false,
                'is_active' => false,
                'rejected_at' => now(),
                'rejected_by' => Auth::id(),
                'admin_notes' => $request->admin_notes
            ]);

            return $this->successResponse([
                'subscription' => $subscription->load(['course', 'user', 'rejectedBy'])
            ], [
                'ar' => 'تم رفض طلب الاشتراك',
                'en' => 'Subscription request rejected'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin reject subscription error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    // إدارة التعليقات
    public function getPendingComments(Request $request)
    {
        try {
            $comments = Comment::where('is_approved', false)
                ->with(['user', 'lesson.course'])
                ->orderBy('created_at', 'asc')
                ->paginate($request->get('per_page', 15));

            return $this->successResponse($comments, [
                'ar' => 'تم جلب التعليقات المعلقة بنجاح',
                'en' => 'Pending comments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get pending comments error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }


    // إدارة الدروس
    public function createLesson(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'video_url' => 'nullable|url',
                'order' => 'required|integer|min:1',
                'duration_minutes' => 'nullable|integer|min:1',
                'is_free' => 'boolean',
                'target_gender' => 'required|in:male,female,both',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $lesson = Lesson::create($request->all());

            return $this->successResponse($lesson, [
    'ar' => 'تم إنشاء الدرس بنجاح',
    'en' => 'Lesson created successfully'
    ]                   , 201);


        } catch (\Exception $e) {
            Log::error('Error creating lesson: ' . $e->getMessage());
            return $this->errorResponse(__('messages.general.server_error'), 500);
        }
    }

    public function updateLesson(Request $request, $id)
    {
        try {
            $lesson = Lesson::find($id);

            if (!$lesson) {
                return $this->errorResponse(__('messages.lesson.not_found'), 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'video_url' => 'nullable|url',
                'order' => 'sometimes|required|integer|min:1',
                'duration_minutes' => 'nullable|integer|min:1',
                'is_free' => 'boolean',
                'target_gender' => 'sometimes|required|in:male,female,both',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $lesson->update($request->all());

            return $this->successResponse($lesson, [
                'ar' => 'تم تحديث الدرس بنجاح',
                'en' => 'Lesson Updated successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error updating lesson: ' . $e->getMessage());
            return $this->errorResponse(__('messages.general.server_error'), 500);
        }
    }
}
