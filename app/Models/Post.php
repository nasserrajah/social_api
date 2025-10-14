<?php
// app/Models/Post.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'content', 'image'];

    // إضافة image_url إلى JSON
    protected $appends = ['image_url'];

    // Accessor لتحويل المسار إلى رابط كامل
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        
        // إذا كان الرابط يحتوي بالفعل على http، ارجعه كما هو
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }
        
        // وإلا قم ببناء الرابط الكامل
        return asset('storage/' . $this->image);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}