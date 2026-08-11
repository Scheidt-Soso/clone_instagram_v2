<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function __construct(protected PostService $postService) {}

    public function index()
    {
        return response()->json($this->postService->feed());
    }

    public function show(Post $post)
    {
        return response()->json($this->postService->show($post));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:2200',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post = $this->postService->create(
            $request->user(),
            $request->input('caption'),
            $request->file('images')
        );

        return response()->json($post, 201);
    }

    public function destroy(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode excluir os próprios posts.'], 403);
        }

        $this->postService->delete($post);

        return response()->json(['message' => 'Post excluído com sucesso.']);
    }

    public function destroyImage(Request $request, Post $post, $imageId)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode editar os próprios posts.'], 403);
        }

        try {
            $this->postService->removeImage($post, $imageId);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Imagem removida com sucesso.']);
    }

    public function archive(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode arquivar os próprios posts.'], 403);
        }

        $this->postService->archive($post);

        return response()->json(['message' => 'Post arquivado com sucesso.']);
    }

    public function unarchive(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Você só pode desarquivar os próprios posts.'], 403);
        }

        $this->postService->unarchive($post);

        return response()->json(['message' => 'Post desarquivado com sucesso.']);
    }
}