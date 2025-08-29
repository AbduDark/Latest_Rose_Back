<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Check if API is working
    try {
        $response = [
            'app' => 'Rose Academy',
            'status' => 'running',
            'api_url' => url('/api'),
            'docs_url' => url('/docs'),
            'timestamp' => now()
        ];
        return response()->json($response);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/docs', function () {
    return view('documentation');
})->name('api.docs');

Route::get('/api-docs', function () {
    return view('documentation');
});

Route::get('/roadmap', function () {
    return response()->file(public_path('roadmap.html'));
});
use Illuminate\Http\Request;

Route::get('/reset-password', function (Request $request) {
    return view('auth.reset-password', [
        'token' => $request->query('token'),
        'email' => $request->query('email')
    ]);
});

Route::get('/verify-email', function () {
    return response()->file(public_path('verify-email.html'));
});

Route::get('/email-verified', function () {
    return response()->file(public_path('email-verified.html'));
});

