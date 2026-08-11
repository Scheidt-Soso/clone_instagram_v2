<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function show(Request $request, User $user)
    {
        return response()->json(
            $this->userService->getProfile($request->user(), $user)
        );
    }

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

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json(['message' => 'Você só pode excluir a própria conta.'], 403);
        }

        $this->userService->deleteAccount($user);

        return response()->json(['message' => 'Conta excluída com sucesso.']);
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->userService->search($request->query('search'))
        );
    }

    public function suggestions(Request $request)
    {
        return response()->json(
            $this->userService->suggestions($request->user())
        );
    }
}