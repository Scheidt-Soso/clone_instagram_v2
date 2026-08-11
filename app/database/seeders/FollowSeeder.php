<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FollowSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        $users->each(function ($user) use ($users) {
            $others = $users->where('id', '!=', $user->id)->random(rand(2, 6));

            foreach ($others as $other) {
                $user->following()->syncWithoutDetaching($other->id);
            }
        });
    }
}