<?php

namespace App\Services;

use App\Models\Story;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoryService
{
    public function feed(User $currentUser)
    {
        $followingIds = $currentUser->following()->pluck('users.id');

        return Story::with('user')
            ->active()
            ->whereIn('user_id', $followingIds)
            ->orderBy('expires_at')
            ->get()
            ->groupBy('user_id');
    }

    public function create(User $user, UploadedFile $media): Story
    {
        $path = $media->store('stories', 'public');

        $story = $user->stories()->create([
            'media_path' => $path,
            'media_type' => $media->getMimeType(),
            'expires_at' => now()->addDay(),
        ]);

        return $story->load('user');
    }

    public function delete(Story $story): void
    {
        Storage::disk('public')->delete($story->media_path);
        $story->delete();
    }
}