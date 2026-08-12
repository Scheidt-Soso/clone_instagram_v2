<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class CommentController extends Controller
{
    public function __construct(protected CommentService $commentService) {}

    #[OA\Get(
        path: '/posts/{post}/comments',
        summary: 'Listar comentários de um post',
        tags: ['Comentários'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de comentários com o autor'),
            new OA\Response(response: 404, description: 'Post não encontrado'),
        ]
    )]
    public function index(Post $post)
    {
        return response()->json($this->commentService->list($post));
    }

    #[OA\Post(
        path: '/posts/{post}/comments',
        summary: 'Comentar em um post',
        tags: ['Comentários'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['body'],
                properties: [
                    new OA\Property(property: 'body', type: 'string', example: 'Que foto incrível!'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Comentário criado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
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

    #[OA\Delete(
        path: '/posts/{post}/comments/{comment}',
        summary: 'Excluir um comentário',
        tags: ['Comentários'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'comment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comentário excluído'),
            new OA\Response(response: 403, description: 'Sem permissão para excluir'),
        ]
    )]
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
