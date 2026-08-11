<?php

namespace Database\Seeders;

use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function ($user) {
            // stories ativas (0 a 3)
            Story::factory(rand(0, 3))->for($user)->create();

            // algumas vencidas, pra testar o filtro (30% de chance por usuário)
            if (fake()->boolean(30)) {
                Story::factory(rand(1, 2))->for($user)->expired()->create();
            }
        });
    }
}