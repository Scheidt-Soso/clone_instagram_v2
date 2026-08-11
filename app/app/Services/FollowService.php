<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\User;

class FollowService
{
    public function follow(User $currentUser, User $user): void
    {
        if ($currentUser->id === $user->id) {
            throw new DomainException('Você não pode seguir a si mesmo.');
        }

        $currentUser->following()->syncWithoutDetaching($user->id);
    }

    public function unfollow(User $currentUser, User $user): void
    {
        $currentUser->following()->detach($user->id);
    }
}