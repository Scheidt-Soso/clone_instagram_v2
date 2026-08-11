<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Highlight;
use App\Models\User;
use App\Services\HighlightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HighlightController extends Controller
{
    public function __construct(protected HighlightService $highlightService) {}

    public function index(User $user)
    {
        return response()->json($this->highlightService->list($user));
    }

    public function show(Highlight $highlight)
    {
        return response()->json($this->highlightService->show($highlight));
    }

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

    public function addStory(Request $request, Highlight $highlight, $storyId)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios destaques.'], 403);
        }

        $highlight = $this->highlightService->addStory($request->user(), $highlight, $storyId);

        return response()->json($highlight);
    }

    public function removeStory(Request $request, Highlight $highlight, $storyId)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios destaques.'], 403);
        }

        $this->highlightService->removeStory($highlight, $storyId);

        return response()->json(['message' => 'Story removida do destaque.']);
    }

    public function destroy(Request $request, Highlight $highlight)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode excluir os próprios destaques.'], 403);
        }

        $this->highlightService->delete($highlight);

        return response()->json(['message' => 'Destaque excluído com sucesso.']);
    }
}