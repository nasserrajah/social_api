<?php
// app/Http/Controllers/PostController.php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'likes', 'comments.user'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(10);
            
        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'image' => 'nullable|image|max:4096'
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('posts', 'public');
        }

        $post = $request->user()->posts()->create([
            'content' => $request->content,
            'image' => $path,
        ]);

        return response()->json($post->load('user'), 201);
    }

  // داخل PostController
public function show($id)
{
    $post = Post::with('user')->withCount(['likes','comments'])->findOrFail($id);
    return response()->json(['post' => $post], 200);
}

public function destroy(Request $request, $id)
{
    $post = Post::findOrFail($id);
    if ($request->user()->id !== $post->user_id) {
        return response()->json(['message' => 'غير مصرح'], 403);
    }
    // حذف الصورة من التخزين لو موجودة
    if ($post->image) {
        \Storage::disk('public')->delete($post->image);
    }
    $post->delete();
    return response()->json(['message' => 'تم الحذف'], 200);
}

public function userPosts($userId)
{
    $posts = Post::with('user')->where('user_id', $userId)->latest()->get()->map(function($p){
        $p->likes_count = $p->likes()->count();
        $p->comments_count = $p->comments()->count();
        return $p;
    });
    return response()->json(['data' => $posts], 200);
}

}