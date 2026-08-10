<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'images'])
            ->whereNull('archived_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($posts);
    }

    public function show(Post $post)
    {
        $post->load(['user', 'images']);

        return response()->json($post);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post = $request->user()->posts()->create([]);

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('posts', 'public');

            $post->images()->create([
                'image_path' => $path,
                'order' => $index,
            ]);
        }

        $post->load(['user', 'images']);

        return response()->json($post, 201);
    }

    public function destroy(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode excluir os próprios posts.'], 403);
        }

        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $post->delete();

        return response()->json(['message' => 'Post excluído com sucesso.']);
    }

    public function destroyImage(Request $request, Post $post, $imageId)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios posts.'], 403);
        }

        if ($post->images()->count() <= 1) {
            return response()->json(['message' => 'Não é possível remover a única imagem do post. Exclua o post inteiro, se for o caso.'], 422);
        }

        $image = $post->images()->findOrFail($imageId);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json(['message' => 'Imagem removida com sucesso.']);
    }

    public function archive(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode arquivar os próprios posts.'], 403);
        }

        $post->update(['archived_at' => now()]);

        return response()->json(['message' => 'Post arquivado com sucesso.']);
    }

    public function unarchive(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode desarquivar os próprios posts.'], 403);
        }

        $post->update(['archived_at' => null]);

        return response()->json(['message' => 'Post desarquivado com sucesso.']);
    }
}