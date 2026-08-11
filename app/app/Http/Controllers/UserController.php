<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    #[OA\Get(
        path: "/users",
        summary: "Listar/buscar usuários",
        tags: ["Usuários"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, description: "Busca por nome ou username", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista paginada de usuários"),
        ]
    )]
    public function index(Request $request)
    {
        return response()->json(
            $this->userService->search($request->query('search'))
        );
    }

    #[OA\Get(
        path: "/users/suggestions",
        summary: "Sugestões de usuários para seguir",
        tags: ["Usuários"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(response: 200, description: "Até 5 usuários aleatórios que você ainda não segue"),
        ]
    )]
    public function suggestions(Request $request)
    {
        return response()->json(
            $this->userService->suggestions($request->user())
        );
    }

    #[OA\Get(
        path: "/users/{user}",
        summary: "Ver perfil de um usuário",
        tags: ["Usuários"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Dados do perfil, contagens e posts"),
            new OA\Response(response: 404, description: "Usuário não encontrado"),
        ]
    )]
    public function show(Request $request, User $user)
    {
        return response()->json(
            $this->userService->getProfile($request->user(), $user)
        );
    }

    #[OA\Put(
        path: "/users/{user}",
        summary: "Editar o próprio perfil",
        tags: ["Usuários"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Maria Silva"),
                    new OA\Property(property: "username", type: "string", example: "mariasilva"),
                    new OA\Property(property: "bio", type: "string", example: "Nova bio"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Perfil atualizado"),
            new OA\Response(response: 403, description: "Não é o dono do perfil"),
            new OA\Response(response: 422, description: "Erro de validação"),
        ]
    )]
    public function update(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json(['message' => 'Você só pode editar o próprio perfil.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'bio' => 'sometimes|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->userService->updateProfile($user, $validator->validated());

        return response()->json($user);
    }

    #[OA\Post(
        path: "/users/{user}/avatar",
        summary: "Trocar foto de perfil",
        tags: ["Usuários"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["avatar"],
                    properties: [
                        new OA\Property(property: "avatar", type: "string", format: "binary"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Avatar atualizado"),
            new OA\Response(response: 403, description: "Não é o dono do perfil"),
            new OA\Response(response: 422, description: "Erro de validação"),
        ]
    )]
    public function updateAvatar(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json(['message' => 'Você só pode editar o próprio avatar.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->userService->updateAvatar($user, $request->file('avatar'));

        return response()->json($user);
    }

    #[OA\Delete(
        path: "/users/{user}",
        summary: "Excluir a própria conta",
        tags: ["Usuários"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Conta excluída (soft delete)"),
            new OA\Response(response: 403, description: "Não é o dono da conta"),
        ]
    )]
    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json(['message' => 'Você só pode excluir a própria conta.'], 403);
        }

        $this->userService->deleteAccount($user);

        return response()->json(['message' => 'Conta excluída com sucesso.']);
    }
}