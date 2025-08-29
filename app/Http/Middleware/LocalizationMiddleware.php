<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get language from header, default to Arabic
        $locale = $request->header('Accept-Language', 'ar');
        
        // Validate locale (only ar and en allowed)
        if (!in_array($locale, ['ar', 'en'])) {
            $locale = 'ar';
        }
        
        App::setLocale($locale);
        
        // Store locale in request for easy access
        $request->attributes->set('locale', $locale);
        
        return $next($request);
    }
}
