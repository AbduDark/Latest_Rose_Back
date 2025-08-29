<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    /**
     * عرض إشعارات المستخدم
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);

            $notifications = Notification::forUser($user->id)
                ->with(['course:id,title', 'sender:id,name'])
                ->when($request->type, fn($query, $type) => $query->byType($type))
                ->when($request->boolean('unread_only'), fn($query) => $query->unread())
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return $this->successResponse($notifications, 'تم جلب الإشعارات بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('حدث خطأ أثناء جلب الإشعارات', 500);
        }
    }

    /**
     * عرض إشعار محدد
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::forUser($user->id)
                ->with(['course:id,title', 'sender:id,name'])
                ->findOrFail($id);

            if (!$notification->is_read) {
                $notification->markAsRead();
            }

            return $this->successResponse($notification, 'تم جلب الإشعار بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('الإشعار غير موجود', 404);
        }
    }

    /**
     * تمييز إشعار كمقروء
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::forUser($user->id)->findOrFail($id);
            $notification->markAsRead();

            return $this->successResponse($notification, 'تم تمييز الإشعار كمقروء');
        } catch (\Exception $e) {
            return $this->errorResponse('الإشعار غير موجود', 404);
        }
    }

    /**
     * تمييز جميع الإشعارات كمقروءة
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();

            Notification::forUser($user->id)
                ->unread()
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return $this->successResponse(null, 'تم تمييز جميع الإشعارات كمقروءة');
        } catch (\Exception $e) {
            return $this->errorResponse('حدث خطأ أثناء تحديث الإشعارات', 500);
        }
    }

    /**
     * عدد الإشعارات غير المقروءة
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();
            $count = Notification::forUser($user->id)->unread()->count();

            return $this->successResponse(['count' => $count], 'تم جلب عدد الإشعارات غير المقروءة');
        } catch (\Exception $e) {
            return $this->errorResponse('حدث خطأ أثناء جلب العدد', 500);
        }
    }

    /**
     * حذف إشعار
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::forUser($user->id)->findOrFail($id);
            $notification->delete();

            return $this->successResponse(null, 'تم حذف الإشعار بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('الإشعار غير موجود', 404);
        }
    }

    /**
     * إرسال إشعار من الإدارة (للإدارة فقط)
     */
    public function sendNotification(Request $request)
    {
        try {
            if (Auth::user()->role !== 'admin') {
                return $this->errorResponse('ليس لديك صلاحية لهذا الإجراء', 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'type' => 'required|in:general,course,subscription,system',
                'user_ids' => 'array|exists:users,id',
                'course_id' => 'nullable|exists:courses,id',
                'send_to_all' => 'boolean',
                'gender' => 'nullable|in:male,female,both',
                'data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $admin = Auth::user();
            $users = collect();

            if ($request->send_to_all) {
                $users = User::where('role', 'student')
                    ->when($request->gender, fn($q, $g) => $q->where('gender', $g))
                    ->get();
            } elseif ($request->course_id) {
                $users = User::whereHas('subscriptions', function ($query) use ($request) {
                    $query->where('course_id', $request->course_id)
                        ->where('is_active', true)
                        ->where('status', 'approved');
                })->when($request->gender, fn($q, $g) => $q->where('gender', $g))
                  ->get();
            } else {
                $users = User::whereIn('id', $request->user_ids ?? [])
                    ->when($request->gender, fn($q, $g) => $q->where('gender', $g))
                    ->get();
            }

            foreach ($users as $user) {
                Notification::create([
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                    'user_id' => $user->id,
                    'course_id' => $request->course_id,
                    'sender_id' => $admin->id,
                    'data' => $request->data
                ]);
            }

            return $this->successResponse([
                'notifications_sent' => $users->count(),
                'recipients' => $users->count()
            ], 'تم إرسال الإشعارات بنجاح');

        } catch (\Exception $e) {
            return $this->errorResponse('حدث خطأ أثناء إرسال الإشعارات', 500);
        }
    }

    /**
     * إحصائيات الإشعارات (للإدارة فقط)
     */
    public function statistics()
    {
        try {
            if (Auth::user()->role !== 'admin') {
                return $this->errorResponse('ليس لديك صلاحية لهذا الإجراء', 403);
            }

            $stats = [
                'total_notifications' => Notification::count(),
                'unread_notifications' => Notification::unread()->count(),
                'notifications_by_type' => Notification::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'recent_notifications' => Notification::with(['user:id,name', 'course:id,title'])
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get()
            ];

            return $this->successResponse($stats, 'تم جلب إحصائيات الإشعارات');
        } catch (\Exception $e) {
            return $this->errorResponse('حدث خطأ أثناء جلب الإحصائيات', 500);
        }
    }
}
