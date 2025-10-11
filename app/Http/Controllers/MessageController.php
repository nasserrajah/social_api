<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // جلب المحادثات
    public function getConversations(Request $request)
    {
        $user = Auth::user();
        
        // جلب آخر محادثة مع كل صديق
        $conversations = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['sender', 'receiver'])
            ->latest()
            ->get()
            ->groupBy(function($message) use ($user) {
                return $message->sender_id == $user->id ? $message->receiver_id : $message->sender_id;
            })
            ->map(function($messages) use ($user) {
                $lastMessage = $messages->first();
                $otherUser = $lastMessage->sender_id == $user->id ? $lastMessage->receiver : $lastMessage->sender;
                
                return [
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'profile_image' => $otherUser->profile_image,
                        'email' => $otherUser->email,
                    ],
                    'last_message' => $lastMessage->message,
                    'last_message_time' => $lastMessage->created_at,
                    'unread_count' => $messages->where('receiver_id', $user->id)->where('read', false)->count()
                ];
            })
            ->values();

        return response()->json([
            'data' => $conversations,
            'message' => 'تم جلب المحادثات بنجاح'
        ]);
    }

    // جلب الرسائل مع مستخدم معين
    public function getMessages($userId, Request $request)
    {
        $user = Auth::user();
        
        $messages = Message::where(function($query) use ($user, $userId) {
                $query->where('sender_id', $user->id)
                      ->where('receiver_id', $userId);
            })
            ->orWhere(function($query) use ($user, $userId) {
                $query->where('sender_id', $userId)
                      ->where('receiver_id', $user->id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        // تحديث الرسائل كمقروءة
        Message::where('sender_id', $userId)
            ->where('receiver_id', $user->id)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json([
            'data' => $messages,
            'message' => 'تم جلب الرسائل بنجاح'
        ]);
    }

    // إرسال رسالة
    public function sendMessage($userId, Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $user = Auth::user();

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $userId,
            'message' => $request->message,
            'read' => false
        ]);

        $message->load(['sender', 'receiver']);

        return response()->json([
            'data' => $message,
            'message' => 'تم إرسال الرسالة بنجاح'
        ], 201);
    }
}