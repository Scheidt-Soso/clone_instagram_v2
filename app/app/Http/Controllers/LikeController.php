<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(protected LikeService $likeService) {}

    public function store(Request $request, Post $post)
    {
        $count = $this->likeService->like($request->user(), $post);

        return response()->json(['message' => 'Post curtido.', 'likes_count' => $count], 201);
    }

    public function destroy(Request $request, Post $post)
    {
        $count = $this->likeService->unlike($request->user(), $post);

        return response()->json(['message' => 'Curtida removida.', 'likes_count' => $count]);
    }
}