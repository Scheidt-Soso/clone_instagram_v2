<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;

class LikeService
{
    public function like(User $user, Post $post): int
    {
        $post->likes()->firstOrCreate(['user_id' => $user->id]);

        return $post->likes()->count();
    }

    public function unlike(User $user, Post $post): int
    {
        $post->likes()->where('user_id', $user->id)->delete();

        return $post->likes()->count();
    }
}