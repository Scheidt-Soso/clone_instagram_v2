<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FollowController extends Controller
{
    public function __construct(protected FollowService $followService) {}

    #[OA\Post(
        path: '/users/{user}/follow',
        summary: 'Seguir um usuário',
        tags: ['Seguidores'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Usuário seguido com sucesso'),
            new OA\Response(response: 422, description: 'Não é possível seguir a si mesmo'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(Request $request, User $user)
    {
        try {
            $this->followService->follow($request->user(), $user);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Usuário seguido com sucesso.'], 201);
    }

    #[OA\Delete(
        path: '/users/{user}/follow',
        summary: 'Deixar de seguir um usuário',
        tags: ['Seguidores'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuário deixou de ser seguido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function destroy(Request $request, User $user)
    {
        $this->followService->unfollow($request->user(), $user);

        return response()->json(['message' => 'Usuário deixou de ser seguido.']);
    }
}
