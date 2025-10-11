<?php
// app/Http/Controllers/CommentController.php
namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string|max:500'
        ]);

        $post = Post::findOrFail($postId);
        $user = Auth::user();

        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $postId,
            'content' => $request->content
        ]);

        // إنشاء إشعار للمستخدم صاحب المنشور (إذا لم يكن هو نفسه)
        if ($post->user_id != $user->id) {
            Notification::create([
                'user_id' => $post->user_id,
                'from_user_id' => $user->id,
                'post_id' => $postId,
                'type' => 'comment',
                'message' => "علق {$user->name} على منشورك: " . substr($request->content, 0, 50) . "..."
            ]);
        }

        return response()->json([
            'message' => 'تم إضافة التعليق',
            'comment' => $comment->load('user')
        ], 201);
    }

    public function getPostComments($postId)
    {
        $comments = Comment::with('user')
            ->where('post_id', $postId)
            ->latest()
            ->get();

        return response()->json([
            'data' => $comments
        ]);
    }

    public function destroy($commentId)
    {
        $comment = Comment::findOrFail($commentId);
        
        if ($comment->user_id != Auth::id()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'تم حذف التعليق']);
    }
}