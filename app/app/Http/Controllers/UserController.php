<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
   public function show(Request $request, User $user)
{
    $currentUser = $request->user();

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'bio' => $user->bio,
        'avatar_path' => $user->avatar_path,
        'posts_count' => $user->posts()->count(),
        'followers_count' => $user->followers()->count(),
        'following_count' => $user->following()->count(),
        'is_following' => $currentUser->id !== $user->id
            ? $currentUser->following()->where('users.id', $user->id)->exists()
            : null,
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


    public function suggestions(Request $request)
{
    $currentUser = $request->user();
    $followingIds = $currentUser->following()->pluck('users.id');

    $suggestions = User::where('id', '!=', $currentUser->id)
        ->whereNotIn('id', $followingIds)
        ->inRandomOrder()
        ->limit(5)
        ->get(['id', 'name', 'username', 'avatar_path']);

    return response()->json($suggestions);
}


}