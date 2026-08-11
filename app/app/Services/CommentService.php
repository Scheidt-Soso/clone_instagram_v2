<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class CommentService
{
    public function list(Post $post)
    {
        return $post->comments()->with('user')->get();
    }

    public function create(User $user, Post $post, string $body): Comment
    {
        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);

        return $comment->load('user');
    }

    public function delete(User $user, Post $post, Comment $comment): void
    {
        if ($user->id !== $comment->user_id && $user->id !== $post->user_id) {
            throw new DomainException('Você não tem permissão para excluir este comentário.');
        }

        $comment->delete();
    }
}