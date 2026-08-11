<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class UserFactory extends Factory
{
    
    protected static ?string $password;

  public function definition(): array
{
    return [
        'name' => fake()->name(),
        'username' => fake()->unique()->userName(),
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => static::$password ??= \Illuminate\Support\Facades\Hash::make('password'),
        'bio' => fake()->boolean(60) ? fake()->sentence() : null,
        'avatar_path' => null,
        'remember_token' => \Illuminate\Support\Str::random(10),
    ];
}
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
