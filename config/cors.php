<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | هنا بتحدد إعدادات Cross-Origin Resource Sharing (CORS)
    | علشان تتحكم في من يقدر يبعت Requests للـ API بتاعك
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // الطرق المسموحة (GET, POST, PUT, DELETE ...)
    'allowed_methods' => ['*'],

    // الـ domains المسموح لها تبعت requests
    'allowed_origins' => [
        'http://161.97.130.147.nip.io',
        'http://api.161.97.130.147.nip.io',
    ],

    // لو عايز تعمل regex بدل ما تكتب الـ origins صريحة
    'allowed_origins_patterns' => [],

    // الهيدرز المسموح بيها
    'allowed_headers' => ['*'],

    // الهيدرز اللي ممكن تظهر للـ Frontend
    'exposed_headers' => [],

    // المدة اللي المتصفح ممكن يكاش فيها preflight request (OPTIONS)
    'max_age' => 0,

    // لو الـ frontend بيبعت cookies أو Authorization header
    'supports_credentials' => true,

];
