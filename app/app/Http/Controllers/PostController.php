<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PostController extends Controller
{
    public function __construct(protected PostService $postService) {}

    #[OA\Get(
        path: '/posts',
        summary: 'Feed de posts',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de posts com usuário, imagens e contagens'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index()
    {
        return response()->json($this->postService->feed());
    }

    #[OA\Get(
        path: '/posts/archived',
        summary: 'Listar posts arquivados do próprio usuário',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de posts arquivados'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function archived(Request $request)
    {
        return response()->json($this->postService->archived($request->user()));
    }

    #[OA\Get(
        path: '/posts/{post}',
        summary: 'Ver detalhes de um post',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalhes do post'),
            new OA\Response(response: 404, description: 'Post não encontrado'),
        ]
    )]
    public function show(Post $post)
    {
        return response()->json($this->postService->show($post));
    }

    #[OA\Post(
        path: '/posts',
        summary: 'Criar um post com até 10 imagens',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['images'],
                    properties: [
                        new OA\Property(property: 'caption', type: 'string', example: 'Meu novo post'),
                        new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string', format: 'binary'), maxItems: 10),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Post criado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:2200',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post = $this->postService->create(
            $request->user(),
            $request->input('caption'),
            $request->file('images')
        );

        return response()->json($post, 201);
    }

    #[OA\Delete(
        path: '/posts/{post}',
        summary: 'Excluir um post',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Post excluído'),
            new OA\Response(response: 403, description: 'Não é o dono do post'),
            new OA\Response(response: 404, description: 'Post não encontrado'),
        ]
    )]
    public function destroy(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode excluir os próprios posts.'], 403);
        }

        $this->postService->delete($post);

        return response()->json(['message' => 'Post excluído com sucesso.']);
    }

    #[OA\Delete(
        path: '/posts/{post}/images/{imageId}',
        summary: 'Remover uma imagem de um post',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'imageId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Imagem removida'),
            new OA\Response(response: 403, description: 'Não é o dono do post'),
            new OA\Response(response: 422, description: 'Não é possível remover a única imagem'),
        ]
    )]
    public function destroyImage(Request $request, Post $post, $imageId)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios posts.'], 403);
        }

        try {
            $this->postService->removeImage($post, $imageId);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Imagem removida com sucesso.']);
    }

    #[OA\Post(
        path: '/posts/{post}/archive',
        summary: 'Arquivar um post',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Post arquivado'),
            new OA\Response(response: 403, description: 'Não é o dono do post'),
        ]
    )]
    public function archive(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode arquivar os próprios posts.'], 403);
        }

        $this->postService->archive($post);

        return response()->json(['message' => 'Post arquivado com sucesso.']);
    }

    #[OA\Put(
        path: '/posts/{post}',
        summary: 'Editar legenda de um post',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'caption', type: 'string', example: 'Legenda atualizada'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Post atualizado'),
            new OA\Response(response: 403, description: 'Não é o dono do post'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function update(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios posts.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:2200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post = $this->postService->updateCaption($post, $request->input('caption'));

        return response()->json($post);
    }

    #[OA\Post(
        path: '/posts/{post}/unarchive',
        summary: 'Desarquivar um post',
        tags: ['Posts'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Post desarquivado'),
            new OA\Response(response: 403, description: 'Não é o dono do post'),
        ]
    )]
    public function unarchive(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode desarquivar os próprios posts.'], 403);
        }

        $this->postService->unarchive($post);

        return response()->json(['message' => 'Post desarquivado com sucesso.']);
    }
}
