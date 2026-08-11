<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Services\StoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{
    public function __construct(protected StoryService $storyService) {}

    public function index(Request $request)
    {
        return response()->json($this->storyService->feed($request->user()));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'media' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $story = $this->storyService->create($request->user(), $request->file('media'));

        return response()->json($story, 201);
    }

    public function destroy(Request $request, Story $story)
    {
        if ($request->user()->id !== $story->user_id) {
            return response()->json(['message' => 'Você só pode excluir as próprias stories.'], 403);
        }

        $this->storyService->delete($story);

        return response()->json(['message' => 'Story excluída com sucesso.']);
    }
}