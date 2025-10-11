<?php
// app/Http/Controllers/NotificationController.php
namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with(['fromUser', 'post.user'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

              $notifications->getCollection()->transform(function ($notification) {
        $notification->read = (bool) $notification->read;
        return $notification;
    });

        return response()->json([
            'data' => $notifications
        ]);
    }

   public function markAsRead($id)
    {
       try {
        \Log::info('🔄 محاولة تحديث الإشعار: ' . $id);
        
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        \Log::info('📊 قبل التحديث - read: ' . ($notification->read ? 'true' : 'false'));
        
        // ✅ تحديث مباشر في قاعدة البيانات
        $updated = Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read' => true]);
            
        \Log::info('✅ عدد الصفوف المحدثة: ' . $updated);
        
        $notification->refresh();
        \Log::info('📊 بعد التحديث - read: ' . ($notification->read ? 'true' : 'false'));

        return response()->json([
            'message' => 'تم تحديد الإشعار كمقروء',
            'notification' => $notification,
            'updated_rows' => $updated,
            'success' => true
        ]);

    } catch (\Exception $e) {
        \Log::error('❌ فشل تحديث الإشعار: ' . $e->getMessage());
        return response()->json([
            'message' => 'فشل تحديث الإشعار',
            'error' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}
       public function markAllAsRead()
    {
        try {
            $updated = Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json([
                'message' => "تم تحديد {$updated} إشعار كمقروء",
                'updated_count' => $updated,
                'success' => true
            ]);

        } catch (\Exception $e) {
        \Log::error('فشل تحديث جميع الإشعارات: ' . $e->getMessage());
        return response()->json([
            'message' => 'فشل تحديث الإشعارات',
            'error' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}
}