<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/storage/{folder}/{filename}', function ($folder, $filename) {
    $path = storage_path("app/public/{$folder}/{$filename}");
    
    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->where('filename', '.*');

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