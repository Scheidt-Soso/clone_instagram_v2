<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $followingIds = $request->user()->following()->pluck('users.id');

        $stories = Story::with('user')
            ->active()
            ->whereIn('user_id', $followingIds)
            ->orderBy('expires_at')
            ->get()
            ->groupBy('user_id');

        return response()->json($stories);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'media' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('media')->store('stories', 'public');

        $story = $request->user()->stories()->create([
            'media_path' => $path,
            'media_type' => $request->file('media')->getMimeType(),
            'expires_at' => now()->addDay(),
        ]);

        return response()->json($story->load('user'), 201);
    }

    public function destroy(Request $request, Story $story): JsonResponse
    {
        if ($request->user()->id !== $story->user_id) {
            return response()->json(['message' => 'Você só pode excluir as próprias stories.'], 403);
        }

        Storage::disk('public')->delete($story->media_path);
        $story->delete();

        return response()->json(['message' => 'Story excluída com sucesso.']);
    }
}
