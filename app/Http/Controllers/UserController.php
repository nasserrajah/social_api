<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // الحصول على الملف الشخصي
    public function getProfile($userId = null)
    {
        try {
            if ($userId === null) {
                $user = Auth::user();
                $isOwnProfile = true;
            } else {
                $user = User::findOrFail($userId);
                $isOwnProfile = Auth::id() == $userId;
            }

            // جلب منشورات المستخدم
            $posts = Post::with(['user', 'likes', 'comments.user'])
                ->withCount(['likes', 'comments'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            return response()->json([
                'user' => $user,
                'posts' => $posts,
                'is_own_profile' => $isOwnProfile
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في جلب الملف الشخصي',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // تحديث الملف الشخصي
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'bio' => 'sometimes|string|max:500',
                'profile_image' => 'sometimes|image|max:2048',
                'cover_image' => 'sometimes|image|max:2048'
            ]);

            // تحديث البيانات الأساسية
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('bio')) {
                $user->bio = $request->bio;
            }

            // معالجة صورة الملف الشخصي
            if ($request->hasFile('profile_image')) {
                // حذف الصورة القديمة إذا كانت موجودة
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                $user->profile_image = $request->file('profile_image')->store('profiles', 'public');
            }

            // معالجة صورة الغلاف
            if ($request->hasFile('cover_image')) {
                // حذف الصورة القديمة إذا كانت موجودة
                if ($user->cover_image) {
                    Storage::disk('public')->delete($user->cover_image);
                }
                $user->cover_image = $request->file('cover_image')->store('covers', 'public');
            }

            $user->save();

            return response()->json([
                'message' => 'تم تحديث الملف الشخصي بنجاح',
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في تحديث الملف الشخصي',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // رفع صورة للملف الشخصي
    public function uploadProfileImage(Request $request)
    {
        try {
            $user = Auth::user();
            
            $request->validate([
                'image' => 'required|image|max:2048',
                'type' => 'required|in:profile,cover'
            ]);

            $path = $request->file('image')->store('profiles', 'public');

            if ($request->type === 'profile') {
                // حذف الصورة القديمة إذا كانت موجودة
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                $user->profile_image = $path;
            } else {
                // حذف الصورة القديمة إذا كانت موجودة
                if ($user->cover_image) {
                    Storage::disk('public')->delete($user->cover_image);
                }
                $user->cover_image = $path;
            }

            $user->save();

            return response()->json([
                'message' => 'تم رفع الصورة بنجاح',
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في رفع الصورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // حذف الصور
    public function deleteImage(Request $request, $type)
    {
        try {
            $user = Auth::user();
            
            if ($type === 'profile') {
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                    $user->profile_image = null;
                }
            } elseif ($type === 'cover') {
                if ($user->cover_image) {
                    Storage::disk('public')->delete($user->cover_image);
                    $user->cover_image = null;
                }
            } else {
                return response()->json(['message' => 'نوع الصورة غير صالح'], 400);
            }

            $user->save();

            return response()->json([
                'message' => 'تم حذف الصورة بنجاح',
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في حذف الصورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
  public function searchUsers(Request $request)
{
    try {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json(['data' => []]);
        }

        $users = User::where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'email', 'profile_image', 'cover_image') // إزالة bio
            ->where('id', '!=', Auth::id())
            ->limit(15)
            ->get();

        return response()->json(['data' => $users]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'خطأ في البحث عن المستخدمين',
            'error' => $e->getMessage()
        ], 500);
    }
}
}