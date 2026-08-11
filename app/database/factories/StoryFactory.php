<?php

namespace Database\Factories;

use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StoryFactory extends Factory
{
    protected $model = Story::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'media_path' => $this->generatePlaceholderImage(),
            'media_type' => 'image/jpeg',
            'expires_at' => now()->addDay(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHours(rand(1, 48)),
        ]);
    }

    protected function generatePlaceholderImage(): string
    {
        $image = imagecreatetruecolor(400, 700);

        $color = imagecolorallocate(
            $image,
            random_int(0, 255),
            random_int(0, 255),
            random_int(0, 255)
        );
        imagefill($image, 0, 0, $color);

       $filename = 'stories/' . Str::random(40) . '.png';
imagepng($image, storage_path('app/public/' . $filename));
        imagedestroy($image);

        return $filename;
    }
}