<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class LikeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        Post::all()->each(function ($post) use ($users) {
            $count = min(rand(0, 8), $users->count());
            $likers = $users->random($count);

            foreach ($likers as $user) {
                $post->likes()->firstOrCreate(['user_id' => $user->id]);
            }
        });
    }
}