<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, $key = null, $maxAttempts = 60, $decayMinutes = 1): Response
    {
        if (!$key) {
            $key = $request->ip();
        }

        $rateLimitKey = 'rate_limit:' . $key;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => 'Too many requests. Try again in ' . $seconds . ' seconds.'
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);

        return $next($request);
    }
}
