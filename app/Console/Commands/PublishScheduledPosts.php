<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled {--dry-run : Show what would be published without actually publishing}';

    protected $description = 'Publish scheduled posts whose publish date has arrived';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        // Find posts that are scheduled and ready to be published
        $scheduledPosts = Post::where('is_draft', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        if ($scheduledPosts->isEmpty()) {
            $this->info('No scheduled posts found to publish.');

            return Command::SUCCESS;
        }

        $this->info("Found {$scheduledPosts->count()} scheduled posts ready to publish:");

        $publishedCount = 0;

        foreach ($scheduledPosts as $post) {
            if ($isDryRun) {
                $this->line("- Would publish: [{$post->id}] {$post->title} (scheduled for {$post->published_at})");
            } else {
                // Post is already considered published based on published_at being in the past
                // We just log this for audit trail
                $this->line("- Published: [{$post->id}] {$post->title}");
                Log::info('Scheduled post became published', [
                    'post_id' => $post->id,
                    'title' => $post->title,
                    'published_at' => $post->published_at,
                    'processed_at' => now(),
                ]);
                $publishedCount++;
            }
        }

        if ($isDryRun) {
            $this->info('Dry run completed. Use without --dry-run to process logs.');
        } else {
            $this->info("Processed {$publishedCount} scheduled posts.");
        }

        return Command::SUCCESS;
    }
}
