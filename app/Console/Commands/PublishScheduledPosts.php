<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Publish scheduled posts whose publish date has arrived';

    public function handle(): void
    {
        $posts = Post::where('is_draft', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        foreach ($posts as $post) {
            // Posts are already considered published when the published_at date is in the past
            // Just logging for visibility
            Log::info("Published scheduled post: {$post->id} - {$post->title}");
        }

        $this->info("Published {$posts->count()} scheduled posts.");
    }
}
