<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Highlight;
use App\Models\User;
use App\Services\HighlightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class HighlightController extends Controller
{
    public function __construct(protected HighlightService $highlightService) {}

    #[OA\Get(
        path: '/users/{user}/highlights',
        summary: 'Listar destaques de um usuário',
        tags: ['Destaques'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de destaques com capa e contagem'),
            new OA\Response(response: 404, description: 'Usuário não encontrado'),
        ]
    )]
    public function index(User $user)
    {
        return response()->json($this->highlightService->list($user));
    }

    #[OA\Get(
        path: '/highlights/{highlight}',
        summary: 'Ver um destaque com suas stories',
        tags: ['Destaques'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'highlight', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalhes do destaque'),
            new OA\Response(response: 404, description: 'Destaque não encontrado'),
        ]
    )]
    public function show(Highlight $highlight)
    {
        return response()->json($this->highlightService->show($highlight));
    }

    #[OA\Post(
        path: '/highlights',
        summary: 'Criar um destaque com stories próprias',
        tags: ['Destaques'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'story_ids'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Viagem'),
                    new OA\Property(property: 'story_ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Destaque criado'),
            new OA\Response(response: 403, description: 'Só é permitido usar as próprias stories'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:50',
            'story_ids' => 'required|array|min:1',
            'story_ids.*' => 'exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $highlight = $this->highlightService->create(
                $request->user(),
                $request->title,
                $request->story_ids
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($highlight, 201);
    }

    #[OA\Post(
        path: '/highlights/{highlight}/stories/{storyId}',
        summary: 'Adicionar uma story ao destaque',
        tags: ['Destaques'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'highlight', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'storyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Story adicionada ao destaque'),
            new OA\Response(response: 403, description: 'Não é o dono do destaque'),
        ]
    )]
    public function addStory(Request $request, Highlight $highlight, $storyId)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios destaques.'], 403);
        }

        $highlight = $this->highlightService->addStory($request->user(), $highlight, $storyId);

        return response()->json($highlight);
    }

    #[OA\Delete(
        path: '/highlights/{highlight}/stories/{storyId}',
        summary: 'Remover uma story do destaque',
        tags: ['Destaques'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'highlight', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'storyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Story removida do destaque'),
            new OA\Response(response: 403, description: 'Não é o dono do destaque'),
        ]
    )]
    public function removeStory(Request $request, Highlight $highlight, $storyId)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios destaques.'], 403);
        }

        $this->highlightService->removeStory($highlight, $storyId);

        return response()->json(['message' => 'Story removida do destaque.']);
    }

    #[OA\Delete(
        path: '/highlights/{highlight}',
        summary: 'Excluir um destaque',
        tags: ['Destaques'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'highlight', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Destaque excluído'),
            new OA\Response(response: 403, description: 'Não é o dono do destaque'),
        ]
    )]
    public function destroy(Request $request, Highlight $highlight)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode excluir os próprios destaques.'], 403);
        }

        $this->highlightService->delete($highlight);

        return response()->json(['message' => 'Destaque excluído com sucesso.']);
    }
}
