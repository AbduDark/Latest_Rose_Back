<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityLogMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log request details
        Log::channel('security')->info('API Request', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => auth('sanctum')->id(),
            'timestamp' => now()->toISOString(),
        ]);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Log response details
        Log::channel('security')->info('API Response', [
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'user_id' => auth('sanctum')->id(),
        ]);
        
        return $response;
    }
}
