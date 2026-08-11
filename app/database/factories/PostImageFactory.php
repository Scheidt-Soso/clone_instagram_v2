<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostImageFactory extends Factory
{
    protected $model = PostImage::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'image_path' => $this->generatePlaceholderImage(),
            'order' => 0,
        ];
    }

   protected function generatePlaceholderImage(): string
{
    $width = 640;
    $height = 640;
    $image = imagecreatetruecolor($width, $height);

    $color = imagecolorallocate(
        $image,
        random_int(0, 255),
        random_int(0, 255),
        random_int(0, 255)
    );
    imagefill($image, 0, 0, $color);

    $filename = 'posts/' . Str::random(40) . '.png';
    imagepng($image, storage_path('app/public/' . $filename));
    imagedestroy($image);

    return $filename;
}
}