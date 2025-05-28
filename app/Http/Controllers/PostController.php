<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $posts = Post::published()
            ->with('user')
            ->latest('published_at')
            ->paginate(20);

        return PostResource::collection($posts);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        return response()->json(['message' => 'posts.create']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request): PostResource
    {
        $post = Auth::user()->posts()->create($request->validated());

        return new PostResource($post->load('user'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post): PostResource|JsonResponse
    {
        if (! $post->isPublished()) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return new PostResource($post->load('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post): JsonResponse
    {
        return response()->json(['message' => 'posts.edit']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $post->update($request->validated());

        return new PostResource($post->fresh()->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(null, 204);
    }
}
