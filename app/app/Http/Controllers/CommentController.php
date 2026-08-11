<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        return response()->json(
            $post->comments()->with('user')->get()
        );
    }

    public function store(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:2200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->input('body'),
        ]);

        $comment->load('user');

        return response()->json($comment, 201);
    }

    public function destroy(Request $request, Post $post, Comment $comment)
    {
        $userId = $request->user()->id;

        if ($userId !== $comment->user_id && $userId !== $post->user_id) {
            return response()->json(['message' => 'Você não tem permissão para excluir este comentário.'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comentário excluído com sucesso.']);
    }
}