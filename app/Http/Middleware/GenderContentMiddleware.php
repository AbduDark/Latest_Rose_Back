<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GenderContentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user(); // استخدام Auth facade بدلاً من auth() helper

        if ($user && $request->has('gender_filter')) {
            $genderFilter = $request->gender_filter;

            if ($genderFilter !== 'all' && $user->gender !== $genderFilter) {
                return response()->json([
                    'message' => [
                        'ar' => 'هذا المحتوى غير متاح لجنسك',
                        'en' => 'This content is not available for your gender'
                    ],
                    'error_code' => 'GENDER_CONTENT_RESTRICTION'
                ], 403);
            }
        }

        return $next($request);
    }
}
