<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        Post::all()->each(function ($post) use ($users) {
            for ($i = 0; $i < rand(0, 4); $i++) {
                $post->comments()->create([
                    'user_id' => $users->random()->id,
                    'body' => fake()->sentence(rand(3, 12)),
                ]);
            }
        });
    }
}