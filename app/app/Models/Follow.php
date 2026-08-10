<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function store(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'Você não pode seguir a si mesmo.'
            ], 422);
        }

        $currentUser->following()->syncWithoutDetaching($user->id);

        return response()->json([
            'message' => 'Usuário seguido com sucesso.'
        ], 201);
    }

    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();

        $currentUser->following()->detach($user->id);

        return response()->json([
            'message' => 'Usuário deixou de ser seguido.'
        ]);
    }
}