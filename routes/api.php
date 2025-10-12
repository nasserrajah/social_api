<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SearchController;

// routes اختبار قبل الـ group
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is working',
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'success',
            'message' => 'Database connected successfully',
            'database' => DB::connection()->getDatabaseName()
        ]);
    }  catch (\Exception $e) {
        Log::error('Database connection failed: ' . $e->getMessage());
        return response()->json([
            'status' => 'error', 
            'message' => 'Database connection failed: ' . $e->getMessage()
        ], 500);
    }
});

Route::get('/test-env', function () {
    return response()->json([
        'app_env' => env('APP_ENV'),
        'app_debug' => env('APP_DEBUG'),
        'db_connection' => env('DB_CONNECTION'),
        'db_host' => env('DB_HOST'),
    ]);
});

Route::get('/test-env-details', function () {
    return response()->json([
        'db_connection' => env('DB_CONNECTION'),
        'db_host' => env('DB_HOST'),
        'db_port' => env('DB_PORT'),
        'db_database' => env('DB_DATABASE'),
        'db_username' => env('DB_USERNAME'),
        'app_env' => env('APP_ENV'),
    ]);
});

Route::get('/test-connection', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working',
        'database' => DB::connection()->getDatabaseName(),
        'timestamp' => now()
    ]);
});


// في routes/api.php قبل الـ group
Route::get('/routes-list', function () {
    $routes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
        ];
    });
    
    return response()->json($routes->values());
});


Route::get('/test-migrations', function () {
    try {
        // طريقة بديلة للتحقق من الـ migrations
        $migrationsPath = database_path('migrations');
        $migrationFiles = glob($migrationsPath . '/*.php');
        
        $migrations = [];
        foreach ($migrationFiles as $file) {
            $migrations[] = basename($file);
        }
        
        return response()->json([
            'status' => 'success',
            'migrations_count' => count($migrations),
            'migrations_files' => $migrations,
            'message' => 'Migrations directory accessible'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Migrations check failed: ' . $e->getMessage()
        ], 500);
    } 
});

Route::get('/test-tables', function () {
    try {
        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = ?', [env('DB_DATABASE')]);
        
        $tableNames = array_map(function($table) {
            return $table->table_name;
        }, $tables);
        
        return response()->json([
            'status' => 'success',
            'tables_count' => count($tableNames),
            'tables' => $tableNames,
            'message' => 'Database tables retrieved successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tables check failed: ' . $e->getMessage()
        ], 500);
    }
});

Route::post('/test-register', function (Request $request) {
    try {
        Log::info('Test register attempt', $request->all());
        
        // تحقق بسيط من البيانات
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Validation passed - Ready for actual registration',
            'data_received' => $request->only(['name', 'email'])
        ]);
        
    } catch (\Exception $e) {
        Log::error('Test register error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Test registration failed: ' . $e->getMessage()
        ], 500);
    }
});


// ثم باقي الـ routes كما هي...

// الروابط العامة (بدون مصادقة)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [ForgotPasswordController::class, 'reset']);

// الروابط التي تحتاج مصادقة
Route::middleware('auth:sanctum')->group(function () {
    // المصادقة
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // الملف الشخصي
    Route::get('/profile', [UserController::class, 'getProfile']); // الملف الشخصي الحالي
    Route::get('/profile/{userId}', [UserController::class, 'getProfile']); // ملف مستخدم آخر
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/profile/upload-image', [UserController::class, 'uploadProfileImage']);
    Route::delete('/profile/images/{type}', [UserController::class, 'deleteImage']);
    
    // المنشورات
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::get('/users/{userId}/posts', [PostController::class, 'getUserPosts']);
    
    // الإعجابات
    Route::post('/posts/{postId}/like', [LikeController::class, 'toggleLike']);
    Route::get('/likes', [LikeController::class, 'getUserLikes']);
    
    // التعليقات
    Route::post('/posts/{postId}/comments', [CommentController::class, 'store']);
    Route::get('/posts/{postId}/comments', [CommentController::class, 'getPostComments']);
    Route::delete('/comments/{commentId}', [CommentController::class, 'destroy']);
    
    // الإشعارات
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    
    // البحث
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/users', [SearchController::class, 'searchUsers']);
    
    // الأصدقاء
    Route::get('/friends', [FriendController::class, 'getFriends']);
    Route::get('/friends/pending', [FriendController::class, 'getPendingRequests']);
    Route::post('/friends/request/{userId}', [FriendController::class, 'sendFriendRequest']);
    Route::post('/friends/accept/{userId}', [FriendController::class, 'acceptFriendRequest']);
    Route::post('/friends/reject/{userId}', [FriendController::class, 'rejectFriendRequest']);
    Route::delete('/friends/{userId}', [FriendController::class, 'removeFriend']);
    Route::get('/friends/status/{userId}', [FriendController::class, 'getFriendshipStatus']);
    
    // الرسائل
    Route::get('/conversations', [MessageController::class, 'getConversations']);
    Route::get('/messages/{userId}', [MessageController::class, 'getMessages']);
    Route::post('/messages/{userId}', [MessageController::class, 'sendMessage']);


   
// البحث
Route::get('/search', [SearchController::class, 'search']);
Route::get('/search/users', [SearchController::class, 'searchUsers']);
Route::get('/search/posts', [SearchController::class, 'searchPosts']);

// الصداقات
    Route::get('/friends', [FriendController::class, 'getFriends']);
    Route::get('/friends/pending', [FriendController::class, 'getPendingRequests']);
    Route::post('/friends/request/{userId}', [FriendController::class, 'sendFriendRequest']);
    Route::post('/friends/accept/{userId}', [FriendController::class, 'acceptFriendRequest']);
    Route::post('/friends/reject/{userId}', [FriendController::class, 'rejectFriendRequest']);
    Route::delete('/friends/{userId}', [FriendController::class, 'removeFriend']);
    Route::get('/friends/status/{userId}', [FriendController::class, 'getFriendshipStatus']);
});