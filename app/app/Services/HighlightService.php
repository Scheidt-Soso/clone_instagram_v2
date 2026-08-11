<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Highlight;
use App\Models\User;

class HighlightService
{
    public function list(User $user)
    {
        return $user->highlights()->with('stories')->get()->map(function ($highlight) {
            return [
                'id' => $highlight->id,
                'title' => $highlight->title,
                'cover' => optional($highlight->stories->first())->media_path,
                'stories_count' => $highlight->stories->count(),
            ];
        });
    }

    public function show(Highlight $highlight): Highlight
    {
        return $highlight->load('stories');
    }

    public function create(User $user, string $title, array $storyIds): Highlight
    {
        $ownStoryIds = $user->stories()->pluck('id');
        $invalidIds = collect($storyIds)->diff($ownStoryIds);

        if ($invalidIds->isNotEmpty()) {
            throw new DomainException('Você só pode usar suas próprias stories no destaque.');
        }

        $highlight = $user->highlights()->create(['title' => $title]);

        foreach ($storyIds as $index => $storyId) {
            $highlight->stories()->attach($storyId, ['order' => $index]);
        }

        return $highlight->load('stories');
    }

    public function addStory(User $user, Highlight $highlight, int $storyId): Highlight
    {
        $story = $user->stories()->findOrFail($storyId);

        $nextOrder = $highlight->stories()->count();
        $highlight->stories()->syncWithoutDetaching([$story->id => ['order' => $nextOrder]]);

        return $highlight->load('stories');
    }

    public function removeStory(Highlight $highlight, int $storyId): void
    {
        $highlight->stories()->detach($storyId);
    }

    public function delete(Highlight $highlight): void
    {
        $highlight->delete();
    }
}