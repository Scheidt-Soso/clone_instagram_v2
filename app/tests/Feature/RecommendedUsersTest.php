<?php

use App\Models\Like;
use App\Models\Post;
use App\Models\User;

test('recommends users who liked the same posts as me', function () {
    $me = User::factory()->create();
    $coLiker = User::factory()->create();
    $postAuthor = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $postAuthor->id]);

    Like::create(['user_id' => $me->id, 'post_id' => $post->id]);
    Like::create(['user_id' => $coLiker->id, 'post_id' => $post->id]);

    $this->actingAs($me);

    $response = $this->getJson('/api/users/recommended');

    $response->assertStatus(200);
    expect(collect($response->json())->pluck('username'))->toContain($coLiker->username);
});

test('recommends users who liked my posts', function () {
    $me = User::factory()->create();
    $liker = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $me->id]);

    Like::create(['user_id' => $liker->id, 'post_id' => $post->id]);

    $this->actingAs($me);

    $response = $this->getJson('/api/users/recommended');

    $response->assertStatus(200);
    expect(collect($response->json())->pluck('username'))->toContain($liker->username);
});

test('does not recommend users already followed', function () {
    $me = User::factory()->create();
    $followed = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $me->id]);

    Like::create(['user_id' => $followed->id, 'post_id' => $post->id]);
    $me->following()->attach($followed->id);

    $this->actingAs($me);

    $response = $this->getJson('/api/users/recommended');

    $response->assertStatus(200);
    expect(collect($response->json())->pluck('username'))->not->toContain($followed->username);
});

test('does not recommend myself', function () {
    $me = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $me->id]);

    Like::create(['user_id' => $me->id, 'post_id' => $post->id]);

    $this->actingAs($me);

    $response = $this->getJson('/api/users/recommended');

    $response->assertStatus(200);
    expect(collect($response->json())->pluck('username'))->not->toContain($me->username);
});

test('unauthenticated requests are rejected', function () {
    $this->getJson('/api/users/recommended')->assertStatus(401);
});
