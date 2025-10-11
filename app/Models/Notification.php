<?php
// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    
    // 🧩 الحقول التي يمكن تعبئتها
    protected $fillable = [
        'user_id',
        'from_user_id',
        'post_id',
        'type',
        'message',
        'read',
    ];

    // ✅ تأكد أن قيمة read تُعامل كـ boolean دائماً
    protected $casts = [
        'read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
