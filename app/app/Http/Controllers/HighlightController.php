<?php

namespace App\Http\Controllers;

use App\Models\Highlight;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HighlightController extends Controller
{
    public function index(User $user)
    {
        $highlights = $user->highlights()->with('stories')->get()->map(function ($highlight) {
            return [
                'id' => $highlight->id,
                'title' => $highlight->title,
                'cover' => optional($highlight->stories->first())->media_path,
                'stories_count' => $highlight->stories->count(),
            ];
        });

        return response()->json($highlights);
    }

    public function show(Highlight $highlight)
    {
        $highlight->load('stories');

        return response()->json($highlight);
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

        $ownStoryIds = $request->user()->stories()->pluck('id');
        $invalidIds = collect($request->story_ids)->diff($ownStoryIds);

        if ($invalidIds->isNotEmpty()) {
            return response()->json(['message' => 'Você só pode usar suas próprias stories no destaque.'], 403);
        }

        $highlight = $request->user()->highlights()->create([
            'title' => $request->title,
        ]);

        foreach ($request->story_ids as $index => $storyId) {
            $highlight->stories()->attach($storyId, ['order' => $index]);
        }

        $highlight->load('stories');

        return response()->json($highlight, 201);
    }

    public function addStory(Request $request, Highlight $highlight, $storyId)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios destaques.'], 403);
        }

        $story = $request->user()->stories()->findOrFail($storyId);

        $nextOrder = $highlight->stories()->count();
        $highlight->stories()->syncWithoutDetaching([$story->id => ['order' => $nextOrder]]);

        return response()->json($highlight->load('stories'));
    }

    public function removeStory(Request $request, Highlight $highlight, $storyId)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios destaques.'], 403);
        }

        $highlight->stories()->detach($storyId);

        return response()->json(['message' => 'Story removida do destaque.']);
    }

    public function destroy(Request $request, Highlight $highlight)
    {
        if ($request->user()->id !== $highlight->user_id) {
            return response()->json(['message' => 'Você só pode excluir os próprios destaques.'], 403);
        }

        $highlight->delete();

        return response()->json(['message' => 'Destaque excluído com sucesso.']);
    }
}