<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $post->likes()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Post curtido.',
            'likes_count' => $post->likes()->count(),
        ], 201);
    }

    public function destroy(Request $request, Post $post)
    {
        $post->likes()->where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Curtida removida.',
            'likes_count' => $post->likes()->count(),
        ]);
    }
}