<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(protected FollowService $followService) {}

    public function store(Request $request, User $user)
    {
        try {
            $this->followService->follow($request->user(), $user);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Usuário seguido com sucesso.'], 201);
    }

    public function destroy(Request $request, User $user)
    {
        $this->followService->unfollow($request->user(), $user);

        return response()->json(['message' => 'Usuário deixou de ser seguido.']);
    }
}