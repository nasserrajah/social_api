<?php
// app/Http/Controllers/PostController.php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096'
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            try {
                // تأكد من وجود المجلد
                if (!Storage::disk('public')->exists('posts')) {
                    Storage::disk('public')->makeDirectory('posts');
                }

                // استخدم storeAs للتحكم أفضل في الأسماء
                $image = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('posts', $filename, 'public');
                
                // سجل للمراقبة
                Log::info('Image stored successfully', [
                    'filename' => $filename,
                    'path' => $path,
                    'full_path' => storage_path('app/public/' . $path),
                    'file_exists' => Storage::disk('public')->exists($path)
                ]);

            } catch (\Exception $e) {
                Log::error('Image upload failed', [
                    'error' => $e->getMessage(),
                    'file' => $request->file('image')->getClientOriginalName()
                ]);
                
                return response()->json([
                    'message' => 'فشل في رفع الصورة',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        $post = Post::create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'image' => $path,
        ]);

        // تحميل العلاقات وإرجاع البيانات
        $post->load('user');

        return response()->json([
            'message' => 'تم إنشاء المنشور بنجاح',
            'post' => $post
        ], 201);
    }

    public function show($id)
    {
        $post = Post::with(['user', 'likes', 'comments.user'])
                    ->withCount(['likes','comments'])
                    ->findOrFail($id);
        
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
            try {
                // احذف فقط إذا كان الملف موجوداً
                if (Storage::disk('public')->exists($post->image)) {
                    Storage::disk('public')->delete($post->image);
                    Log::info('Image deleted', ['path' => $post->image]);
                }
            } catch (\Exception $e) {
                Log::error('Image deletion failed', [
                    'error' => $e->getMessage(),
                    'path' => $post->image
                ]);
            }
        }

        $post->delete();

        return response()->json(['message' => 'تم حذف المنشور بنجاح'], 200);
    }

    public function userPosts($userId)
    {
        $posts = Post::with(['user', 'likes', 'comments.user'])
                    ->withCount(['likes', 'comments'])
                    ->where('user_id', $userId)
                    ->latest()
                    ->get();

        return response()->json(['data' => $posts], 200);
    }

    // دالة مساعدة لفحص التخزين
    public function checkStorage()
    {
        $testFiles = [
            'posts/test1.jpg' => Storage::disk('public')->exists('posts/test1.jpg'),
            'posts/test2.jpg' => Storage::disk('public')->exists('posts/test2.jpg'),
        ];

        $storagePath = storage_path('app/public');
        $publicPath = public_path('storage');

        return response()->json([
            'storage_path' => $storagePath,
            'public_path' => $publicPath,
            'storage_link_exists' => is_link($publicPath),
            'test_files' => $testFiles,
            'posts_directory_exists' => Storage::disk('public')->exists('posts'),
            'posts_directory_files' => Storage::disk('public')->files('posts')
        ]);
    }
}