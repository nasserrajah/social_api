<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'recipient_id',
        'status',
    ];

    // علاقة الطلب مع المستخدم اللي أرسل الطلب
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // علاقة الطلب مع المستخدم اللي استقبل الطلب
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
    // العلاقة مع المستخدم الذي أرسل طلب الصداقة
public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

// العلاقة مع المستخدم الذي استلم طلب الصداقة
public function friend()
{
    return $this->belongsTo(User::class, 'friend_id');
}
}
