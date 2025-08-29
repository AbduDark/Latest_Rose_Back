<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subscription;
use App\Services\NotificationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class SubscriptionController extends Controller
{
    use ApiResponseTrait;

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
public function adminIndex(Request $request)
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

        // جلب جميع الاشتراكات مع العلاقات
        $subscriptions = Subscription::with([
                'course',
                'user',
                'approvedBy',
                'rejectedBy'
            ])
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

   public function subscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'vodafone_number' => 'required|string|regex:/^01[0-2]\d{8}$/',
                'parent_phone' => 'required|string|regex:/^01[0-2]\d{8}$/',
                'student_info' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = Auth::user();
            $course = Course::find($request->course_id);

            // Check if already subscribed
            $existingSubscription = Subscription::where('user_id', $user->id)
                ->where('course_id', $request->course_id)
                ->first();

            if ($existingSubscription) {
                if ($existingSubscription->status === 'pending') {
                    return $this->errorResponse([
                        'ar' => 'لديك طلب اشتراك قيد المراجعة بالفعل',
                        'en' => 'You already have a pending subscription request'
                    ], 400);
                }

                if ($existingSubscription->status === 'approved' && $existingSubscription->is_active) {
                    return $this->errorResponse([
                        'ar' => 'أنت مشترك بالفعل في هذا الكورس',
                        'en' => 'You are already subscribed to this course'
                    ], 400);
                }
            }

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'course_id' => $request->course_id,
                'vodafone_number' => $request->vodafone_number,
                'parent_phone' => $request->parent_phone,
                'student_info' => $request->student_info,
                'status' => 'pending',
                'is_active' => false,
                'is_approved' => false,
                'subscribed_at' => now()
            ]);

            return $this->successResponse([
                'subscription' => $subscription->load(['course', 'user'])
            ], [
                'ar' => 'تم إرسال طلب الاشتراك بنجاح، سيتم مراجعته من قبل الإدارة',
                'en' => 'Subscription request sent successfully, it will be reviewed by administration'
            ]);

    } catch (ValidationException $e) {
     return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }

    public function mySubscriptions()
    {
        try {
            $user = Auth::user();
            $subscriptions = Subscription::where('user_id', $user->id)
                ->with(['course', 'approvedBy', 'rejectedBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse([
                'subscriptions' => $subscriptions
            ], [
                'ar' => 'تم جلب اشتراكاتك بنجاح',
                'en' => 'Your subscriptions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $subscription = Subscription::findOrFail($id);

            if ($subscription->status !== 'pending') {
                return $this->errorResponse([
                    'ar' => 'هذا الطلب تم التعامل معه مسبقاً',
                    'en' => 'This request has already been processed'
                ], 400);
            }

            // تحديث حالة الاشتراك
            $subscription->update([
                'status' => 'approved',
                'is_active' => true,
                'expires_at' => now()->addDays(30), // 30 يوم من تاريخ الموافقة
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'admin_notes' => $request->get('admin_notes')
            ]);

            // إرسال إشعار للطالب
            NotificationService::subscriptionApproved($subscription->user_id, $subscription->course_id);

            return $this->successResponse([
                'subscription' => $subscription->load(['course', 'user', 'approvedBy'])
            ], [
                'ar' => 'تم قبول طلب الاشتراك بنجاح',
                'en' => 'Subscription request approved successfully'
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_notes' => 'required|string|max:500'
            ]);

             if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $subscription = Subscription::findOrFail($id);

            if ($subscription->status !== 'pending') {
                return $this->errorResponse([
                    'ar' => 'هذا الطلب تم التعامل معه مسبقاً',
                    'en' => 'This request has already been processed'
                ], 400);
            }

            // تحديث حالة الاشتراك
            $subscription->update([
                'status' => 'rejected',
                'is_active' => false,
                'rejected_at' => now(),
                'rejected_by' => Auth::id(),
                'admin_notes' => $request->admin_notes
            ]);

            // إرسال إشعار للطالب
            $reason = $request->input('reason', 'لم يتم تحديد السبب');
            NotificationService::subscriptionRejected($subscription->user_id, $subscription->course_id, $reason);

            return $this->successResponse([
                'subscription' => $subscription->load(['course', 'user', 'rejectedBy'])
            ], [
                'ar' => 'تم رفض طلب الاشتراك',
                'en' => 'Subscription request rejected'
            ]);

          } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }

    public function cancelSubscription($id)
    {
        try {
            $user = Auth::user();
            $subscription = Subscription::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            $subscription->update([
                'is_active' => false
            ]);

            return $this->successResponse(null, [
                'ar' => 'تم إلغاء الاشتراك بنجاح',
                'en' => 'Subscription cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }

    public function renewSubscription(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subscription_id' => 'required|exists:subscriptions,id',
                'vodafone_number' => 'required|string|regex:/^01[0-2]\d{8}$/',
                'parent_phone' => 'required|string|regex:/^01[0-2]\d{8}$/',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = Auth::user();
            $subscription = Subscription::where('id', $request->subscription_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // إنشاء طلب تجديد جديد
            $renewalSubscription = Subscription::create([
                'user_id' => $user->id,
                'course_id' => $subscription->course_id,
                'vodafone_number' => $request->vodafone_number,
                'parent_phone' => $request->parent_phone,
                'student_info' => 'طلب تجديد اشتراك',
                'status' => 'pending',
                'is_active' => false,
                'is_approved' => false,
                'subscribed_at' => now()
            ]);

            // إلغاء تفعيل الاشتراك القديم
            $subscription->update(['is_active' => false]);

            return $this->successResponse([
                'renewal_request' => $renewalSubscription->load(['course', 'user'])
            ], [
                'ar' => 'تم إرسال طلب تجديد الاشتراك بنجاح، سيتم مراجعته من قبل الإدارة',
                'en' => 'Subscription renewal request sent successfully, it will be reviewed by administration'
            ]);

       } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }

    public function getExpiredSubscriptions()
    {
        try {
            $user = Auth::user();
            $expiredSubscriptions = Subscription::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('expires_at', '<', now())
                ->with(['course'])
                ->get();

            return $this->successResponse([
                'expired_subscriptions' => $expiredSubscriptions
            ], [
                'ar' => 'تم جلب الاشتراكات المنتهية بنجاح',
                'en' => 'Expired subscriptions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }

    public function getSubscriptionStatus($courseId)
    {
        try {
            $user = Auth::user();
            $subscription = Subscription::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'approved')
                ->first();

            if (!$subscription) {
                return $this->successResponse([
                    'subscription_status' => 'not_subscribed',
                    'message' => [
                        'ar' => 'لم تشترك في هذا الكورس بعد',
                        'en' => 'You are not subscribed to this course yet'
                    ]
                ]);
            }

            $daysRemaining = $subscription->getDaysRemaining();
            $isExpired = $subscription->isExpired();
            $isActive = $subscription->is_active;

            $status = 'active';
            $message = [
                'ar' => "اشتراكك نشط، متبقي {$daysRemaining} يوم",
                'en' => "Your subscription is active, {$daysRemaining} days remaining"
            ];

            if ($isExpired) {
                $status = 'expired';
                $daysExpired = now()->diffInDays($subscription->expires_at);
                $message = [
                    'ar' => "انتهت صلاحية اشتراكك منذ {$daysExpired} يوم. يرجى التجديد.",
                    'en' => "Your subscription expired {$daysExpired} days ago. Please renew."
                ];
            } elseif (!$isActive) {
                $status = 'inactive';
                $message = [
                    'ar' => 'اشتراكك غير نشط حالياً',
                    'en' => 'Your subscription is currently inactive'
                ];
            } elseif ($daysRemaining <= 3) {
                $status = 'expiring_soon';
                $message = [
                    'ar' => "اشتراكك سينتهي قريباً! متبقي {$daysRemaining} يوم فقط",
                    'en' => "Your subscription is expiring soon! Only {$daysRemaining} days remaining"
                ];
            }

            return $this->successResponse([
                'subscription_status' => $status,
                'subscription' => $subscription,
                'days_remaining' => $daysRemaining,
                'is_expired' => $isExpired,
                'is_active' => $isActive,
                'expires_at' => $subscription->expires_at,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }
}
