<?php

namespace App\Services;

use App\Models\Like;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function getProfile(User $viewer, User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'bio' => $user->bio,
            'avatar_path' => $user->avatar_path,
            'posts_count' => $user->posts()->whereNull('archived_at')->count(),
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'is_following' => $viewer->id !== $user->id
                ? $viewer->following()->where('users.id', $user->id)->exists()
                : null,
            'posts' => $user->posts()
                ->whereNull('archived_at')
                ->with('images')
                ->orderBy('created_at', 'desc')
                ->get(),
        ];
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $avatar->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        return $user;
    }

    public function deleteAccount(User $user): void
    {
        $user->delete();
    }

    public function search(?string $search)
    {
        return User::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
        })->paginate(20);
    }

    public function suggestions(User $currentUser, int $limit = 5)
    {
        $followingIds = $currentUser->following()->pluck('users.id');

        return User::where('id', '!=', $currentUser->id)
            ->whereNotIn('id', $followingIds)
            ->inRandomOrder()
            ->limit($limit)
            ->get(['id', 'name', 'username', 'avatar_path']);
    }

    public function recommended(User $currentUser, int $limit = 5)
    {
        $followingIds = $currentUser->following()->pluck('users.id');
        $relevantPostIds = $currentUser->likes()->pluck('post_id')
            ->merge($currentUser->posts()->pluck('id'))
            ->unique();

        $candidateUserIds = Like::query()
            ->whereIn('post_id', $relevantPostIds)
            ->where('user_id', '!=', $currentUser->id)
            ->whereNotIn('user_id', $followingIds)
            ->pluck('user_id')
            ->unique();

        $recommended = collect();
        if ($candidateUserIds->isNotEmpty()) {
            $recommended = User::query()
                ->whereIn('id', $candidateUserIds)
                ->withCount(['likes' => function ($query) use ($relevantPostIds) {
                    $query->whereIn('post_id', $relevantPostIds);
                }])
                ->orderByDesc('likes_count')
                ->limit($limit)
                ->get(['id', 'name', 'username', 'avatar_path']);
        }

        if ($recommended->count() < $limit) {
            $recommended = $recommended->concat(
                User::where('id', '!=', $currentUser->id)
                    ->whereNotIn('id', $followingIds)
                    ->whereNotIn('id', $recommended->pluck('id'))
                    ->inRandomOrder()
                    ->limit($limit - $recommended->count())
                    ->get(['id', 'name', 'username', 'avatar_path'])
            );
        }

        return $recommended;
    }
}
