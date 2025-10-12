<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Laravel Social API is running!',
        'status' => 'active',
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is working',
        'timestamp' => now()->toDateTimeString()
    ]);


    // Fallback route - يجب أن يكون آخر route
Route::fallback(function () {
    return response()->json([
        'error' => 'Endpoint not found',
        'message' => 'The requested URL does not exist',
        'available_endpoints' => [
            'GET /' => 'API status',
            'GET /health' => 'Health check', 
            'POST /api/register' => 'User registration',
            'POST /api/login' => 'User login'
        ]
    ], 404);
});
});