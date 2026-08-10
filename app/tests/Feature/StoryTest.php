<?php

use App\Models\Story;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('user can create a story with an image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/api/stories', [
        'media' => UploadedFile::fake()->image('story.png'),
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'media_path', 'expires_at', 'user']);

    $this->assertDatabaseHas('stories', [
        'user_id' => $user->id,
    ]);

    $story = Story::first();
    Storage::disk('public')->assertExists($story->media_path);
    $this->assertTrue($story->expires_at->gt(now()));
});

test('media is required to create a story', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson('/api/stories', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('media');
});

test('only stories from followed users appear in the feed', function () {
    $user = User::factory()->create();
    $followed = User::factory()->create();
    $stranger = User::factory()->create();

    $user->following()->attach($followed->id);

    $followedStory = Story::factory()->create(['user_id' => $followed->id]);
    Story::factory()->create(['user_id' => $stranger->id]);

    $this->actingAs($user);

    $response = $this->getJson('/api/stories');

    $response->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJson([(string) $followed->id => [
            ['id' => $followedStory->id],
        ]]);
});

test('expired stories are not included in the feed', function () {
    $user = User::factory()->create();
    $followed = User::factory()->create();
    $user->following()->attach($followed->id);

    Story::factory()->expired()->create(['user_id' => $followed->id]);
    Story::factory()->create(['user_id' => $followed->id]);

    $this->actingAs($user);

    $this->getJson('/api/stories')
        ->assertStatus(200)
        ->assertJsonCount(1);
});

test('a user can delete their own story', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::factory()->create(['user_id' => $user->id]);

    Storage::disk('public')->put($story->media_path, 'content');

    $this->actingAs($user);

    $this->deleteJson("/api/stories/{$story->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('stories', ['id' => $story->id]);
    Storage::disk('public')->assertMissing($story->media_path);
});

test('a user cannot delete someone elses story', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $story = Story::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other);

    $this->deleteJson("/api/stories/{$story->id}")
        ->assertStatus(403);

    $this->assertDatabaseHas('stories', ['id' => $story->id]);
});

test('unauthenticated requests are rejected', function () {
    $this->getJson('/api/stories')->assertStatus(401);
    $this->postJson('/api/stories', [])->assertStatus(401);
});
