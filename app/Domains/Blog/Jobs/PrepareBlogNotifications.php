<?php

namespace App\Domains\Blog\Jobs;

use App\Domains\Blog\Models\Blog;
use App\Domains\Client\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareBlogNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $blog;

    /**
     * Create a new job instance.
     */
    public function __construct(Blog $blog)
    {
        $this->blog = $blog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Chunking to handle large numbers of subscribers without memory issues
        Subscriber::active()->chunk(100, function ($subscribers) {
            foreach ($subscribers as $subscriber) {
                // Dispatch individual job for each subscriber
                SendBlogNotification::dispatch($this->blog, $subscriber);
            }
        });
    }
}
