<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendPinMail;
use App\Mail\EmailVerificationMail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Traits\ApiResponseTrait;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use App\Mail\PasswordResetMail;


class AuthController extends Controller
{

    public function getAvatar($filename)
{
    $path = public_path('avatars/' . $filename);

    if (!file_exists($path)) {
        return response()->json(['error' => 'Image not found'], 404);
    }

    return response()->file($path);
}

    public function register(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z\d]+$/',
            'phone'    => 'required|string|max:20|unique:users',
            'gender'   => 'required|in:male,female',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ], [
            'name.required'     => 'الاسم مطلوب|Name is required',
            'name.string'       => 'الاسم يجب أن يكون نص|Name must be a string',
            'name.max'          => 'الاسم يجب ألا يزيد عن 255 حرف|Name must not exceed 255 characters',
            'email.required'    => 'البريد الإلكتروني مطلوب|Email is required',
            'email.email'       => 'البريد الإلكتروني غير صحيح|Invalid email format',
            'email.unique'      => 'البريد الإلكتروني مستخدم بالفعل|Email already exists',
            'password.required' => 'كلمة المرور مطلوبة|Password is required',
            'password.min'      => 'كلمة المرور يجب ألا تقل عن 8 أحرف|Password must be at least 8 characters',
            'password.confirmed'=> 'تأكيد كلمة المرور غير مطابق|Password confirmation does not match',
            'phone.required'    => 'رقم الهاتف مطلوب|Phone number is required',
            'phone.max'         => 'رقم الهاتف يجب ألا يزيد عن 20 رقم|Phone number must not exceed 20 digits',
            'phone.unique'      => 'رقم الهاتف مستخدم بالفعل|Phone number already exists',
            'gender.required'   => 'الجنس مطلوب|Gender is required',
            'gender.in'         => 'الجنس يجب أن يكون ذكر أو أنثى|Gender must be male or female'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse(new ValidationException($validator));
        }

        $pin = rand(100000, 999999);
        $token = Str::random(60);

    //          if (!file_exists(public_path('avatars'))) {
    //     mkdir(public_path('avatars'), 0777, true);
    // }

    //         $imagePath = 'uploads/avatars/default.svg';

    // // لو رفع صورة
    // if ($request->hasFile('image')) {
    //     $image      = $request->file('image');
    //     $imageName  = uniqid('avatar_') . '.' . $image->getClientOriginalExtension();

    //     // التأكد أن المسار موجود
    //     $uploadPath = public_path('uploads/avatars');
    //     if (!file_exists($uploadPath)) {
    //         mkdir($uploadPath, 0777, true);
    //     }

    //     // حفظ الصورة
    //     $image->move($uploadPath, $imageName);
    //     $imagePath = 'uploads/avatars/' . $imageName;
    // }


        $user = User::create([
            'name'             => $request->name,
            'email'            => $request->email,
            'password'         => Hash::make($request->password),
            'phone'            => $request->phone,
            'gender'           => $request->gender,
            'pin'              => $pin,
            'pin_expires_at'   => Carbon::now()->addMinutes(10),
            'email_verified_at'=> null,
            'role'             => 'student',
            // 'image'            =>  $imagePath, // نحفظ المسار فقط
        ]);

        // إنشاء سجل التحقق من البريد
        EmailVerification::create([
            'email'      => $user->email,
            'token'      => $token,
            'expires_at' => Carbon::now()->addHours(24)
        ]);

        // إرسال البريد الإلكتروني للتحقق
        try {
            $verificationUrl = url("/api/auth/verify-email?token={$token}");
            Mail::to($user->email)->send(new EmailVerificationMail($verificationUrl));
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            return $this->errorResponse([
                'ar' => 'حدث خطأ في إرسال بريد التحقق. يرجى المحاولة لاحقاً.',
                'en' => 'Failed to send verification email. Please try again later.'
            ], 500);
        }

        // تجهيز بيانات الاستجابة مع رابط الصورة
        $userData = $user->only(['id', 'name', 'email', 'phone', 'gender', 'role']);
        $userData['image_url'] =url($user->image);

        return $this->successResponse([
            'user' => $userData,
            'email_verification_required' => true
        ], [
            'ar' => 'تم تسجيل المستخدم بنجاح. يرجى التحقق من بريدك الإلكتروني للتحقق.',
            'en' => 'User registered successfully. Please check your email for verification.'
        ], 201);

    } catch (\Exception $e) {
        Log::error('Registration failed: ' . $e->getMessage());
        return $this->serverErrorResponse();
    }
}


    public function login(Request $request)
    {
        try {
            $key = 'login:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableIn($key);
                return $this->errorResponse([
                    'ar' => 'محاولات دخول كثيرة جداً، حاول مرة أخرى خلال ' . $seconds . ' ثانية',
                    'en' => 'Too many login attempts. Try again in ' . $seconds . ' seconds.'
                ], 429);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'البريد الإلكتروني مطلوب|Email is required',
                'email.email' => 'البريد الإلكتروني غير صحيح|Invalid email format',
                'password.required' => 'كلمة المرور مطلوبة|Password is required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                RateLimiter::hit($key, 900);

                Log::channel('security')->warning('Failed login attempt', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return $this->errorResponse([
                    'ar' => 'بيانات الدخول غير صحيحة',
                    'en' => 'Invalid credentials'
                ], 401);
            }

            // التحقق من تأكيد البريد الإلكتروني
            if (!$user->email_verified_at) {
                return $this->errorResponse([
                    'ar' => 'يجب تأكيد البريد الإلكتروني أولاً',
                    'en' => 'Email verification required'
                ], 403, [
                    'email_verification_required' => true,
                    'email' => $user->email
                ]);
            }

            // Check if user is already logged in on another device
            $deviceFingerprint = $this->generateDeviceFingerprint($request);

            // Only check for multiple devices if user has an active session AND a stored device fingerprint
            if ($user->active_session_id &&
                $user->device_fingerprint &&
                $user->device_fingerprint !== $deviceFingerprint &&
                $user->last_login_at) {

                // Revoke all existing tokens
                $user->tokens()->delete();

                Log::channel('security')->warning('Multiple device login attempt', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                return $this->errorResponse([
                    'ar' => 'أنت مسجل دخول على جهاز آخر، يرجى تسجيل الخروج من الجهاز الآخر أولاً',
                    'en' => 'You are already logged in on another device. Please logout from that device first.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $sessionId = Str::random(40);

            $user->update([
                'active_session_id' => $sessionId,
                'device_fingerprint' => $deviceFingerprint,
                'last_login_at' => now(),
            ]);

            Log::channel('security')->info('User logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            RateLimiter::clear($key);

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'role' => $user->role ?? 'student',
            ];

            if ($user->image) {
                $userData['image'] = $user->image;
                $userData['image_url'] = url('storage/' . $user->image);
            }

            return $this->successResponse([
                'token' => $token,
                'session_id' => $sessionId,
                'user' => $userData
            ], [
                'ar' => 'تم تسجيل الدخول بنجاح',
                'en' => 'Login successful'
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return $this->serverErrorResponse();
        }
    }

    public function profile(Request $request)
    {
          $user = $request->user();
          if (!$user) {
            return $this->errorResponse([
                'ar' => 'يجب تسجيل الدخول أولاً',
                'en' => 'You must be logged in to perform this action'
            ], 401);
        }else{
            return response()->json($request->user());
        }


    }

   public function logout(Request $request)
{
    try {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse([
                'ar' => 'يجب تسجيل الدخول أولاً',
                'en' => 'You must be logged in to perform this action'
            ], 401);
        }

        // تسجيل معلومات تسجيل الخروج
        Log::info('User logout', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // حل المشكلة المحتملة مع Sanctum
        if (method_exists($user, 'currentAccessToken')) {
            // إلغاء التوكن الحالي فقط (لـ Sanctum)
            $user->currentAccessToken()->delete();
        } else {
            // حل بديل إذا كان هناك مشكلة مع currentAccessToken()
            $request->user()->token()->revoke();
        }

        // مسح بيانات الجلسة إذا كنت تستخدمها
        if ($user->active_session_id) {
            $user->update(['active_session_id' => null]);
        }

        return $this->successResponse([], [
            'ar' => 'تم تسجيل الخروج بنجاح',
            'en' => 'Logged out successfully'
        ]);

    } catch (\Exception $e) {
        Log::error('Logout error for user ' . ($request->user()?->id ?? 'unknown') . ': ' . $e->getMessage(), [
            'exception' => $e,
            'ip' => $request->ip()
        ]);
        return $this->serverErrorResponse();
    }
}

  public function forceLogout(Request $request)
{
    try {
        $user = $request->user();

        // التحقق من وجود التوكن والمستخدم
        if (!$user || !$request->bearerToken()) {
            return $this->errorResponse([
                'ar' => 'يجب تسجيل الدخول أولاً',
                'en' => 'You must be logged in to perform this action'
            ], 401);
        }

        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // التحقق من أن المستخدم يحاول تسجيل خروج نفسه فقط
        if ($user->email !== $request->email) {
            return $this->errorResponse([
                'ar' => 'غير مصرح لك بتسجيل خروج مستخدم آخر',
                'en' => 'You are not authorized to logout another user'
            ], 403);
        }

        // Force logout from all devices
        $user->tokens()->delete();
        $user->update([
            'active_session_id' => null,
            'device_fingerprint' => null,
        ]);

        Log::channel('security')->info('Force logout performed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return $this->successResponse([], [
            'ar' => 'تم تسجيل الخروج من جميع الأجهزة بنجاح',
            'en' => 'Successfully logged out from all devices'
        ]);

    } catch (\Exception $e) {
        Log::channel('security')->error('Force logout error for user ' . ($user->id ?? 'unknown') . ': ' . $e->getMessage(), [
            'exception' => $e,
            'ip' => $request->ip()
        ]);
        return $this->serverErrorResponse();
    }
}

    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z\d]+$/',
                    'confirmed'
                ]
            ]);

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse([
                    'ar' => 'كلمة المرور الحالية غير صحيحة',
                    'en' => 'Current password is incorrect'
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return $this->successResponse([], [
                'ar' => 'تم تغيير كلمة المرور بنجاح',
                'en' => 'Password changed successfully'
            ]);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->serverErrorResponse();
        }
    }

       public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // تحديث البيانات
        $user->name = $request->name;
        $user->phone = $request->phone;

        // رفع الصورة إذا وجدت
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إذا موجودة
            if ($user->image && file_exists(public_path($user->image))) {
                @unlink(public_path($user->image));
            }

            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/avatars'), $filename);

            // تخزين المسار في قاعدة البيانات
            $user->image = 'uploads/avatars/' . $filename;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البروفايل بنجاح',
            'data'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'phone'  => $user->phone,
                'image'  => $user->image ? asset($user->image) : null,
            ]
        ]);
    }




   public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ], [
                'email.required' => 'البريد الإلكتروني مطلوب|Email is required',
                'email.email' => 'البريد الإلكتروني غير صحيح|Invalid email format',
                'email.exists' => 'البريد الإلكتروني غير مسجل لدينا|Email not found in our records'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            // التحقق من Rate Limiting
            $key = 'password_reset:' . $request->email;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);
                return $this->errorResponse([
                    'ar' => 'محاولات كثيرة جداً. حاول مرة أخرى خلال ' . ceil($seconds/60) . ' دقيقة',
                    'en' => 'Too many attempts. Try again in ' . ceil($seconds/60) . ' minutes.'
                ], 429);
            }

            $user = User::where('email', $request->email)->first();
            $token = Str::random(64);

            // حفظ رمز إعادة التعيين
            EmailVerification::updateOrCreate(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'expires_at' => now()->addMinutes(60)
                ]
            );

            try {
                // إرسال بريد إعادة التعيين
                $resetUrl = url("/reset-password?token={$token}&email=" . urlencode($request->email));

                Mail::to($request->email)->send(new PasswordResetMail($resetUrl, $user));

                // تسجيل نجاح العملية
                Log::channel('security')->info('Password reset requested', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                RateLimiter::hit($key, 3600); // منع المحاولات لمدة ساعة

                return $this->successResponse([], [
                    'ar' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني',
                    'en' => 'Password reset link has been sent to your email'
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send password reset email', [
                    'email' => $request->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->errorResponse([
                    'ar' => 'فشل في إرسال البريد الإلكتروني. يرجى المحاولة لاحقاً',
                    'en' => 'Failed to send email. Please try again later'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Forgot password error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse();
        }
    }

    private function generateDeviceFingerprint(Request $request)
    {
        $data = [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
        ];

        return hash('sha256', json_encode($data));
    }

    public function verifyEmail(Request $request)
    {
        DB::beginTransaction();
        try {
            $token = $request->query('token');

            if (!$token) {
                throw new \Exception('رابط التحقق غير صالح');
            }

            $verification = EmailVerification::where('token', $token)
                ->where('expires_at', '>', now())
                ->first();

            if (!$verification) {
                throw new \Exception('رابط التحقق غير صالح أو منتهي الصلاحية');
            }

            $user = User::where('email', $verification->email)->first();

            if (!$user) {
                throw new \Exception('المستخدم غير موجود');
            }

            // تحديث حالة التحقق من البريد
            $user->email_verified_at = now();
            $user->save();

            // حذف سجل التحقق بعد نجاح التحقق
            $verification->delete();

            DB::commit();

            // تسجيل العملية
            Log::channel('security')->info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // إعادة توجيه إلى صفحة النجاح
            return redirect('/email-verified?success=true');

        } catch (\Exception $e) {
            DB::rollback();

            Log::channel('security')->error('Error verifying email', [
                'error' => $e->getMessage(),
                'email' => $verification->email ?? 'unknown',
                'token' => $token
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return redirect('/email-verified?error=' . urlencode($e->getMessage()));
        }
    }

  public function resetPassword(Request $request)
{
    try {
        // التحقق من البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                'min:8',               // على الأقل 8 أحرف
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z\d]+$/',         // يجب أن تحتوي على حرف كبير ورقم
                'confirmed'            // تأكيد كلمة المرور
            ]
        ], [
            'email.exists' => 'البريد الإلكتروني غير موجود',
            'password.regex' => 'كلمة المرور يجب أن تحتوي على حرف علي الاقل و8 ارقام '
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // البحث عن التوكن في جدول email_verifications
        $record = EmailVerification::where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return $request->expectsJson()
                ? response()->json(['message' => 'رابط غير صالح أو منتهي'], 400)
                : back()->withErrors(['token' => 'رابط غير صالح أو منتهي']);
        }

        // التحقق من صلاحية الرابط
        if ($record->expires_at->isPast()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'انتهت صلاحية الرابط'], 400)
                : back()->withErrors(['token' => 'انتهت صلاحية الرابط']);
        }

        // تحديث كلمة المرور
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // حذف التوكن بعد الاستخدام
        $record->delete();

        // إذا كان الطلب من API
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم تغيير كلمة المرور بنجاح'
            ], 200);
        }

        // إذا كان الطلب من صفحة HTML
        return back()->with('success', 'تم تغيير كلمة المرور بنجاح');


    } catch (\Exception $e) {
        Log::error('Reset password error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        // إذا كان الطلب من API
        if ($request->expectsJson()) {
            return response()->json(['message' => 'حدث خطأ أثناء تغيير كلمة المرور'], 500);
        }

        // إذا كان الطلب من صفحة HTML
        return back()->withErrors(['password' => 'حدث خطأ أثناء تغيير كلمة المرور']);

    }
}



 public function resendVerification(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ], [
                'email.required' => 'البريد الإلكتروني مطلوب|Email is required',
                'email.email' => 'البريد الإلكتروني غير صحيح|Invalid email format',
                'email.exists' => 'البريد الإلكتروني غير مسجل|Email not found'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            $user = User::where('email', $request->email)->first();

            if ($user->email_verified_at) {
                return $this->errorResponse([
                    'ar' => 'البريد الإلكتروني مُفعل بالفعل',
                    'en' => 'Email already verified'
                ], 422);
            }

            // التحقق من Rate Limiting
            $key = 'resend_verification:' . $request->email;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);
                return $this->errorResponse([
                    'ar' => 'محاولات كثيرة جداً. حاول مرة أخرى خلال ' . ceil($seconds/60) . ' دقيقة',
                    'en' => 'Too many attempts. Try again in ' . ceil($seconds/60) . ' minutes.'
                ], 429);
            }

            $token = Str::random(60);
            EmailVerification::updateOrCreate(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'expires_at' => now()->addHours(24)
                ]
            );

            try {
                $verificationUrl = url("/api/auth/verify-email?token={$token}");
                Mail::to($request->email)->send(new EmailVerificationMail($verificationUrl));

                RateLimiter::hit($key, 3600);

                Log::channel('security')->info('Email verification link resent', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ]);

                return $this->successResponse([], [
                    'ar' => 'تم إعادة إرسال رابط التحقق. يرجى فحص بريدك الإلكتروني',
                    'en' => 'Verification link has been resent. Please check your email.'
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to resend verification email', [
                    'email' => $request->email,
                    'error' => $e->getMessage()
                ]);

                return $this->errorResponse([
                    'ar' => 'فشل في إرسال البريد الإلكتروني',
                    'en' => 'Failed to send email'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Resend verification error', [
                'error' => $e->getMessage()
            ]);
            return $this->serverErrorResponse();
        }
    }

    protected function validationErrorResponse(ValidationException $exception)
    {
        $errors = $exception->errors();

        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = [
                'ar' => $messages[0] ?? 'خطأ في التحقق',
                'en' => $messages[0] ?? 'Validation error',
            ];
            if (count($messages) > 1) {
                $formattedErrors[$field]['en'] .= ' (and ' . (count($messages) - 1) . ' more)';
                $formattedErrors[$field]['ar'] .= ' (و ' . (count($messages) - 1) . ' المزيد)';
            }
        }

        return response()->json([
            'success' => false,
            'message' => [
                'ar' => 'فشل التحقق من البيانات',
                'en' => 'The given data failed to validate',
            ],
            'errors' => $formattedErrors,
        ], 422);
    }

    protected function successResponse(array $data = [], array $messages = ['en' => 'Success'], int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $messages,
            'data' => $data,
        ], $status);
    }

    protected function serverErrorResponse(string $message = 'An unexpected error occurred on the server.')
    {
        return response()->json([
            'success' => false,
            'message' => [
                'ar' => 'حدث خطأ غير متوقع في الخادم.',
                'en' => $message,
            ],
        ], 500);
    }

    protected function errorResponse(array $messages, int $status = 400, array $data = [])
    {
        return response()->json([
            'success' => false,
            'message' => $messages,
            'data' => $data
        ], $status);
    }
}
