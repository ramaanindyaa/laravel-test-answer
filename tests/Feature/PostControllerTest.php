<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    // 4-2. posts.index route tests
    public function test_index_returns_paginated_published_posts_with_user_data(): void
    {
        $user = User::factory()->create();

        // Published post - should be included
        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        // Draft post - should not be included
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
        ]);

        // Scheduled post - should not be included
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'content',
                        'is_draft',
                        'published_at',
                        'created_at',
                        'updated_at',
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'created_at',
                        ],
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publishedPost->id)
            ->assertJsonPath('data.0.user.id', $user->id);
    }

    public function test_index_paginates_20_posts_per_page(): void
    {
        $user = User::factory()->create();

        // Create 25 published posts
        Post::factory()->count(25)->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonStructure([
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25);
    }

    // 4-3. posts.create route tests
    public function test_create_returns_string(): void
    {
        $response = $this->getJson('/posts/create');
        $response->assertOk()
            ->assertJson(['message' => 'posts.create']);
    }

    // 4-4. posts.store route tests
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

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'content',
                    'is_draft',
                    'published_at',
                    'user',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_fails_when_not_authenticated(): void
    {
        $response = $this->postJson('/posts', [
            'title' => 'Test Post',
            'content' => 'This is a test post',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_validates_submitted_data(): void
    {
        $user = User::factory()->create();

        // Test missing required fields
        $response = $this->actingAs($user)->postJson('/posts', []);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);

        // Test invalid data types
        $response = $this->actingAs($user)->postJson('/posts', [
            'title' => '',
            'content' => '',
            'is_draft' => 'invalid',
            'published_at' => 'invalid-date',
        ]);
        $response->assertUnprocessable();
    }

    public function test_store_prevents_past_published_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/posts', [
                'title' => 'Test Post',
                'content' => 'Content',
                'is_draft' => false,
                'published_at' => now()->subDay(),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['published_at']);
    }

    // 4-5. posts.show route tests
    public function test_show_returns_published_post_in_json(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'content',
                    'is_draft',
                    'published_at',
                    'user',
                ],
            ])
            ->assertJsonPath('data.id', $post->id);
    }

    public function test_show_returns_404_for_draft_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound()
            ->assertJson(['message' => 'Post not found']);
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

        $response->assertNotFound()
            ->assertJson(['message' => 'Post not found']);
    }

    // 4-6. posts.edit route tests
    public function test_edit_returns_string(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/posts/{$post->id}/edit");
        $response->assertOk()
            ->assertJson(['message' => 'posts.edit']);
    }

    // 4-7. posts.update route tests
    public function test_update_succeeds_for_post_author(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson("/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
                'is_draft' => false,
                'published_at' => now(),
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_update_fails_for_non_author(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)
            ->putJson("/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertForbidden();
    }

    public function test_update_validates_submitted_data(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson("/posts/{$post->id}", [
                'title' => '',
                'content' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);
    }

    // 4-8. posts.destroy route tests
    public function test_destroy_succeeds_for_post_author(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/posts/{$post->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_destroy_fails_for_non_author(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)
            ->deleteJson("/posts/{$post->id}");

        $response->assertForbidden();
    }

    public function test_destroy_requires_authentication(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/posts/{$post->id}");

        $response->assertUnauthorized();
    }

    // Additional tests for scheduled publishing
    public function test_scheduled_posts_become_published_automatically(): void
    {
        $user = User::factory()->create();

        // Create a scheduled post that should now be published
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subMinute(),
        ]);

        $this->assertTrue($post->isPublished());
        $this->assertFalse($post->isScheduled());
    }

    public function test_posts_index_orders_by_published_date_desc(): void
    {
        $user = User::factory()->create();

        $older_post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subWeek(),
        ]);

        $newer_post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $newer_post->id)
            ->assertJsonPath('data.1.id', $older_post->id);
    }
}
