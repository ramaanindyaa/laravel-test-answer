<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_published_posts(): void
    {
        $user = User::factory()->create();

        // Published post
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        // Draft post - should not be returned
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
        ]);

        // Scheduled post - should not be returned
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_create_returns_string(): void
    {
        $response = $this->get('/posts/create');
        $response->assertSee('posts.create');
    }

    public function test_store_creates_post_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/posts', [
                'title' => 'Test Post',
                'content' => 'This is a test post',
                'is_draft' => false,
                'published_at' => now(),
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('posts', ['title' => 'Test Post']);
    }

    public function test_store_fails_when_not_authenticated(): void
    {
        $response = $this->postJson('/posts', [
            'title' => 'Test Post',
            'content' => 'This is a test post',
        ]);

        $response->assertUnauthorized();
    }

    public function test_show_returns_published_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertOk();
        $response->assertJson([
            'id' => $post->id,
            'title' => $post->title,
        ]);
    }

    public function test_show_returns_404_for_draft_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_scheduled_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound();
    }

    public function test_edit_returns_string(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get("/posts/{$post->id}/edit");
        $response->assertSee('posts.edit');
    }

    public function test_update_succeeds_for_post_owner(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
                'is_draft' => false,
                'published_at' => now(),
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_update_fails_for_other_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->putJson("/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertForbidden();
    }

    public function test_destroy_succeeds_for_post_owner(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/posts/{$post->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_destroy_fails_for_other_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->deleteJson("/posts/{$post->id}");

        $response->assertForbidden();
    }
}
