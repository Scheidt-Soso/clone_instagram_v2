<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PostService
{
    public function feed()
    {
        $currentUserId = auth()->id();

        return Post::with(['user', 'images'])
            ->withCount(['likes', 'comments'])
            ->with(['likes' => function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId);
            }])
            ->whereNull('archived_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function show(Post $post): Post
    {
        $currentUserId = auth()->id();

        return $post->loadCount(['likes', 'comments'])
            ->load(['user', 'images', 'likes' => function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId);
            }]);
    }

    public function archived(User $user)
    {
        return $user->posts()
            ->with('images')
            ->whereNotNull('archived_at')
            ->orderBy('archived_at', 'desc')
            ->get();
    }

    public function updateCaption(Post $post, ?string $caption): Post
    {
        $post->update(['caption' => $caption]);

        return $post->load(['user', 'images']);
    }

    public function create(User $user, ?string $caption, array $images): Post
    {
        $post = $user->posts()->create(['caption' => $caption]);

        foreach ($images as $index => $image) {
            $path = $image->store('posts', 'public');

            $post->images()->create([
                'image_path' => $path,
                'order' => $index,
            ]);
        }

        return $post->load(['user', 'images']);
    }

    public function delete(Post $post): void
    {
        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $post->delete();
    }

    public function removeImage(Post $post, int $imageId): void
    {
        if ($post->images()->count() <= 1) {
            throw new DomainException('Não é possível remover a única imagem do post. Exclua o post inteiro, se for o caso.');
        }

        $image = $post->images()->findOrFail($imageId);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();
    }

    public function archive(Post $post): void
    {
        $post->update(['archived_at' => now()]);
    }

    public function unarchive(Post $post): void
    {
        $post->update(['archived_at' => null]);
    }
}