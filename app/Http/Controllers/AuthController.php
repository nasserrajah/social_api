<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
   public function register(Request $request)
{
    // ✅ هذه هي التغييرات المهمة - اقرأ البيانات من JSON
    $data = $request->json()->all();
    
    // ✅ إذا لم توجد بيانات JSON، حاول من Form Data (للتوافق مع الإصدارات القديمة)
    if (empty($data)) {
        $data = $request->all();
    }
    
    Log::info('بدء عملية التسجيل', $data);
    
    // ✅ استخدم $data بدلاً من $request->all()
    $validator = Validator::make($data, [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6|confirmed',
    ]);

    if ($validator->fails()) {
        Log::error('فشل التحقق من البيانات', $validator->errors()->toArray());
        return response()->json([
            'message' => 'فشل التحقق من البيانات',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        // ✅ استخدم $data بدلاً من $request
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'token' => $token
        ], 201);

    } catch (\Exception $e) {
        Log::error('Registration error: ' . $e->getMessage());
        
        return response()->json([
            'message' => 'Server Error: ' . $e->getMessage()
        ], 500);
    }
}

public function login(Request $request)
{
    // ✅ اقرأ البيانات من JSON
    $data = $request->json()->all();
    if (empty($data)) {
        $data = $request->all();
    }
    
    $validator = Validator::make($data, [
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'فشل التحقق من البيانات',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور خاطئة'
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'خطأ في تسجيل الدخول',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'message' => 'تم تسجيل الخروج بنجاح'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في تسجيل الخروج',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            return response()->json([
                'user' => $request->user()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في جلب بيانات المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}