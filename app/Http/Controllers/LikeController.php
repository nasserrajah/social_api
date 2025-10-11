<?php
// app/Http/Controllers/LikeController.php
namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function toggleLike(Request $request, $postId)
    {
        $request->validate([
            'reaction' => 'sometimes|string'
        ]);

        $post = Post::findOrFail($postId);
        $user = Auth::user();

        $existingLike = Like::where('user_id', $user->id)
            ->where('post_id', $postId)
            ->first();

        if ($existingLike) {
            // إذا كان المستخدم معجب بالفعل، قم بحذف الإعجاب
            $existingLike->delete();

            // حذف الإشعار المرتبط
            Notification::where('user_id', $post->user_id)
                ->where('from_user_id', $user->id)
                ->where('post_id', $postId)
                ->where('type', 'like')
                ->delete();

            return response()->json([
                'message' => 'تم إزالة الإعجاب',
                'liked' => false,
                'likes_count' => $post->likes()->count()
            ]);
        } else {
            // إنشاء إعجاب جديد
            $like = Like::create([
                'user_id' => $user->id,
                'post_id' => $postId,
                'reaction' => $request->reaction ?? 'like'
            ]);

            // إنشاء إشعار للمستخدم صاحب المنشور (إذا لم يكن هو نفسه)
            if ($post->user_id != $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'from_user_id' => $user->id,
                    'post_id' => $postId,
                    'type' => 'like',
                    'message' => "أعجب {$user->name} بمنشورك"
                ]);
            }

            return response()->json([
                'message' => 'تم الإعجاب بالمنشور',
                'liked' => true,
                'likes_count' => $post->likes()->count(),
                'like' => $like
            ]);
        }
    }

    public function getUserLikes()
    {
        $user = Auth::user();
        $likes = Like::with(['post.user'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $likes
        ]);
    }
}