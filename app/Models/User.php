<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email', 
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // العلاقة مع طلبات الصداقة المرسلة
public function sentFriendRequests()
{
    return $this->hasMany(Friendship::class, 'user_id');
}

// العلاقة مع طلبات الصداقة المستلمة
public function receivedFriendRequests()
{
    return $this->hasMany(Friendship::class, 'friend_id');
}

// العلاقة مع الرسائل المرسلة
public function sentMessages()
{
    return $this->hasMany(Message::class, 'sender_id');
}

// العلاقة مع الرسائل المستلمة
public function receivedMessages()
{
    return $this->hasMany(Message::class, 'receiver_id');
}
}