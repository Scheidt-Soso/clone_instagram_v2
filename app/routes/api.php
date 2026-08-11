<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\HighlightController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return $request->user();
    });

    Route::get('/users/suggestions', [UserController::class, 'suggestions']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    // Follows
    Route::post('/users/{user}/follow', [FollowController::class, 'store']);
    Route::delete('/users/{user}/follow', [FollowController::class, 'destroy']);

    // Posts
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::delete('/posts/{post}/images/{imageId}', [PostController::class, 'destroyImage']);
    Route::post('/posts/{post}/archive', [PostController::class, 'archive']);
    Route::post('/posts/{post}/unarchive', [PostController::class, 'unarchive']);

    Route::get('/stories', [StoryController::class, 'index']);
    Route::post('/stories', [StoryController::class, 'store']);
    Route::delete('/stories/{story}', [StoryController::class, 'destroy']);

    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::delete('/posts/{post}/comments/{comment}', [CommentController::class, 'destroy']);

    Route::post('/posts/{post}/like', [LikeController::class, 'store']);
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy']);

    Route::get('/users/{user}/highlights', [HighlightController::class, 'index']);
    Route::get('/highlights/{highlight}', [HighlightController::class, 'show']);
    Route::post('/highlights', [HighlightController::class, 'store']);
    Route::post('/highlights/{highlight}/stories/{storyId}', [HighlightController::class, 'addStory']);
    Route::delete('/highlights/{highlight}/stories/{storyId}', [HighlightController::class, 'removeStory']);
    Route::delete('/highlights/{highlight}', [HighlightController::class, 'destroy']);
});