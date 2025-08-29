<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Middleware\ApiErrorHandler;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
   protected function redirectTo($request)
{
    if (!$request->expectsJson()) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated. Please log in.',
            'data' => null
        ], 401);
    }
}

}
