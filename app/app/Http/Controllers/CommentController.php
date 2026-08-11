<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function __construct(protected CommentService $commentService) {}

    public function index(Post $post)
    {
        return response()->json($this->commentService->list($post));
    }

    public function store(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:2200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = $this->commentService->create($request->user(), $post, $request->input('body'));

        return response()->json($comment, 201);
    }

    public function destroy(Request $request, Post $post, Comment $comment)
    {
        try {
            $this->commentService->delete($request->user(), $post, $comment);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['message' => 'Comentário excluído com sucesso.']);
    }
}