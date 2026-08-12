<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LikeController extends Controller
{
    public function __construct(protected LikeService $likeService) {}

    #[OA\Post(
        path: '/posts/{post}/like',
        summary: 'Curtir um post',
        tags: ['Curtidas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Post curtido'),
            new OA\Response(response: 404, description: 'Post não encontrado'),
        ]
    )]
    public function store(Request $request, Post $post)
    {
        $count = $this->likeService->like($request->user(), $post);

        return response()->json(['message' => 'Post curtido.', 'likes_count' => $count], 201);
    }

    #[OA\Delete(
        path: '/posts/{post}/like',
        summary: 'Remover curtida de um post',
        tags: ['Curtidas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Curtida removida'),
            new OA\Response(response: 404, description: 'Post não encontrado'),
        ]
    )]
    public function destroy(Request $request, Post $post)
    {
        $count = $this->likeService->unlike($request->user(), $post);

        return response()->json(['message' => 'Curtida removida.', 'likes_count' => $count]);
    }
}
