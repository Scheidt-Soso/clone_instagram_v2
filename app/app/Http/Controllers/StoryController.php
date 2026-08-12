<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Services\StoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class StoryController extends Controller
{
    public function __construct(protected StoryService $storyService) {}

    #[OA\Get(
        path: '/stories',
        summary: 'Feed de stories (usuários seguidos)',
        tags: ['Stories'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Stories ativas agrupadas por usuário'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request)
    {
        return response()->json($this->storyService->feed($request->user()));
    }

    #[OA\Post(
        path: '/stories',
        summary: 'Criar uma story',
        tags: ['Stories'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['media'],
                    properties: [
                        new OA\Property(property: 'media', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Story criada'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'media' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $story = $this->storyService->create($request->user(), $request->file('media'));

        return response()->json($story, 201);
    }

    #[OA\Get(
        path: '/stories/mine',
        summary: 'Listar as próprias stories ativas',
        tags: ['Stories'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de stories do usuário'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function mine(Request $request)
    {
        return response()->json($this->storyService->mine($request->user()));
    }

    #[OA\Delete(
        path: '/stories/{story}',
        summary: 'Excluir uma story',
        tags: ['Stories'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'story', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Story excluída'),
            new OA\Response(response: 403, description: 'Não é o dono da story'),
        ]
    )]
    public function destroy(Request $request, Story $story)
    {
        if ($request->user()->id !== $story->user_id) {
            return response()->json(['message' => 'Você só pode excluir as próprias stories.'], 403);
        }

        $this->storyService->delete($story);

        return response()->json(['message' => 'Story excluída com sucesso.']);
    }
}
