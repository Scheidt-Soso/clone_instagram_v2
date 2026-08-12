<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    #[OA\Post(
        path: "/register",
        summary: "Registrar novo usuário",
        tags: ["Autenticação"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "username", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Maria Silva"),
                    new OA\Property(property: "username", type: "string", example: "mariasilva"),
                    new OA\Property(property: "email", type: "string", example: "maria@teste.com"),
                    new OA\Property(property: "password", type: "string", example: "senha123"),
                    new OA\Property(property: "password_confirmation", type: "string", example: "senha123"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Usuário criado com sucesso"),
            new OA\Response(response: 422, description: "Erro de validação"),
        ]
    )]
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->authService->register($validator->validated());

        return response()->json($result, 201);
    }

    #[OA\Post(
        path: "/login",
        summary: "Login",
        tags: ["Autenticação"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "maria@teste.com"),
                    new OA\Property(property: "password", type: "string", example: "senha123"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Login realizado com sucesso"),
            new OA\Response(response: 401, description: "Credenciais inválidas"),
        ]
    )]
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->authService->login($validator->validated());

        return response()->json($result);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}