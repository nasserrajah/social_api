<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FriendController extends Controller
{
    // إرسال طلب صداقة
    public function sendFriendRequest($userId)
    {
        try {
            $user = Auth::user();
            $friend = User::findOrFail($userId);

            if ($user->id == $friend->id) {
                return response()->json(['message' => 'لا يمكن إرسال طلب صداقة لنفسك'], 400);
            }

            // التحقق من وجود طلب صداقة مسبق
            $existingRequest = DB::table('friendships')
                ->where(function($query) use ($user, $friend) {
                    $query->where('user_id', $user->id)
                          ->where('friend_id', $friend->id);
                })
                ->orWhere(function($query) use ($user, $friend) {
                    $query->where('user_id', $friend->id)
                          ->where('friend_id', $user->id);
                })
                ->first();

            if ($existingRequest) {
                return response()->json(['message' => 'طلب الصداقة موجود مسبقاً'], 400);
            }

            DB::table('friendships')->insert([
                'user_id' => $user->id,
                'friend_id' => $friend->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // إنشاء إشعار للمستخدم
            Notification::create([
                'user_id' => $friend->id,
                'from_user_id' => $user->id,
                'type' => 'friend_request',
                'message' => "أرسل {$user->name} طلب صداقة لك"
            ]);

            return response()->json(['message' => 'تم إرسال طلب الصداقة']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في إرسال طلب الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // قبول طلب الصداقة
    public function acceptFriendRequest($userId)
    {
        try {
            $user = Auth::user();
            
            $updated = DB::table('friendships')
                ->where('user_id', $userId)
                ->where('friend_id', $user->id)
                ->where('status', 'pending')
                ->update(['status' => 'accepted']);

            if ($updated) {
                // إنشاء إشعار للمستخدم الذي أرسل الطلب
                Notification::create([
                    'user_id' => $userId,
                    'from_user_id' => $user->id,
                    'type' => 'friend_accept',
                    'message' => "قبل {$user->name} طلب صداقتك"
                ]);

                return response()->json(['message' => 'تم قبول طلب الصداقة']);
            }

            return response()->json(['message' => 'طلب الصداقة غير موجود'], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في قبول طلب الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // رفض طلب الصداقة
    public function rejectFriendRequest($userId)
    {
        try {
            $user = Auth::user();
            
            $deleted = DB::table('friendships')
                ->where('user_id', $userId)
                ->where('friend_id', $user->id)
                ->where('status', 'pending')
                ->delete();

            if ($deleted) {
                return response()->json(['message' => 'تم رفض طلب الصداقة']);
            }

            return response()->json(['message' => 'طلب الصداقة غير موجود'], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في رفض طلب الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // إزالة صديق
    public function removeFriend($userId)
    {
        try {
            $user = Auth::user();
            
            $deleted = DB::table('friendships')
                ->where(function($query) use ($user, $userId) {
                    $query->where('user_id', $user->id)
                          ->where('friend_id', $userId);
                })
                ->orWhere(function($query) use ($user, $userId) {
                    $query->where('user_id', $userId)
                          ->where('friend_id', $user->id);
                })
                ->delete();

            if ($deleted) {
                return response()->json(['message' => 'تم إزالة الصديق']);
            }

            return response()->json(['message' => 'الصديق غير موجود'], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في إزالة الصديق',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // الحصول على قائمة الأصدقاء
    public function getFriends()
    {
        try {
            $user = Auth::user();
            
            $friends = DB::table('friendships')
                ->join('users', function($join) use ($user) {
                    $join->on('friendships.user_id', '=', 'users.id')
                         ->orOn('friendships.friend_id', '=', 'users.id');
                })
                ->where(function($query) use ($user) {
                    $query->where('friendships.user_id', $user->id)
                          ->orWhere('friendships.friend_id', $user->id);
                })
                ->where('friendships.status', 'accepted')
                ->where('users.id', '!=', $user->id)
                ->select('users.id', 'users.name', 'users.email', 'users.profile_image', 'users.bio')
                ->distinct()
                ->get();

            return response()->json(['data' => $friends]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في جلب قائمة الأصدقاء',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // الحصول على طلبات الصداقة المعلقة
    public function getPendingRequests()
    {
        try {
            $user = Auth::user();
            
            $pendingRequests = DB::table('friendships')
                ->join('users', 'friendships.user_id', '=', 'users.id')
                ->where('friendships.friend_id', $user->id)
                ->where('friendships.status', 'pending')
                ->select('users.id', 'users.name', 'users.profile_image', 'friendships.created_at')
                ->get();

            return response()->json(['data' => $pendingRequests]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في جلب طلبات الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // الحصول على حالة الصداقة بين مستخدمين
    public function getFriendshipStatus($userId)
    {
        try {
            $user = Auth::user();
            $otherUser = User::findOrFail($userId);

            $friendship = DB::table('friendships')
                ->where(function($query) use ($user, $otherUser) {
                    $query->where('user_id', $user->id)
                          ->where('friend_id', $otherUser->id);
                })
                ->orWhere(function($query) use ($user, $otherUser) {
                    $query->where('user_id', $otherUser->id)
                          ->where('friend_id', $user->id);
                })
                ->first();

            $status = 'not_friends';
            if ($friendship) {
                if ($friendship->status == 'pending') {
                    $status = $friendship->user_id == $user->id ? 'request_sent' : 'request_received';
                } elseif ($friendship->status == 'accepted') {
                    $status = 'friends';
                }
            }

            return response()->json(['status' => $status]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في جلب حالة الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}