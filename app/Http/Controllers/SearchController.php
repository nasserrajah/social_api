<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    // البحث العام (المنشورات والمستخدمين)
    public function search(Request $request)
    {
        try {
            $query = $request->get('q');
            
            if (!$query) {
                return response()->json([
                    'posts' => [],
                    'users' => []
                ]);
            }

            // البحث في المستخدمين - بدون حقل bio
            $users = User::where('name', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->select('id', 'name', 'email', 'profile_image', 'cover_image') // إزالة bio
                ->where('id', '!=', Auth::id())
                ->limit(10)
                ->get();

            // البحث في المنشورات
            $posts = Post::with(['user' => function($q) {
                    $q->select('id', 'name', 'profile_image');
                }])
                ->where('content', 'LIKE', "%{$query}%")
                ->withCount(['likes', 'comments'])
                ->latest()
                ->limit(20)
                ->get();

            return response()->json([
                'users' => $users,
                'posts' => $posts,
                'query' => $query
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في البحث',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // البحث في المستخدمين فقط
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

    // البحث في المنشورات فقط
    public function searchPosts(Request $request)
    {
        try {
            $query = $request->get('q');
            
            if (!$query) {
                return response()->json(['data' => []]);
            }

            $posts = Post::with(['user' => function($q) {
                    $q->select('id', 'name', 'profile_image');
                }])
                ->where('content', 'LIKE', "%{$query}%")
                ->withCount(['likes', 'comments'])
                ->latest()
                ->limit(20)
                ->get();

            return response()->json(['data' => $posts]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في البحث عن المنشورات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}