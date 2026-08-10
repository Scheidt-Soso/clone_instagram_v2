<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function show(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'bio' => $user->bio,
            'avatar_path' => $user->avatar_path,
        ]);
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

        $user->update($validator->validated());

        return response()->json($user);
    }

    public function index(Request $request)
    {
        $search = $request->query('search');

        $users = User::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
        })->paginate(20);

        return response()->json($users);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json(['message' => 'Você só pode excluir a própria conta.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Conta excluída com sucesso.']);
    }

public function posts()
{
    return $this->hasMany(Post::class);
}
}