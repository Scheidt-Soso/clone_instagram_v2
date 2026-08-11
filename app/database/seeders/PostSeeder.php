<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostImage;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function ($user) {
            Post::factory(rand(1, 3))
                ->for($user)
                ->create()
                ->each(function ($post) {
                    PostImage::factory(rand(1, 4))
                        ->for($post)
                        ->sequence(fn ($sequence) => ['order' => $sequence->index])
                        ->create();
                });
        });
    }
}