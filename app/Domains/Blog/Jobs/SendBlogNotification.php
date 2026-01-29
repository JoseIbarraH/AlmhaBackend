<?php

namespace App\Domains\Blog\Jobs;

use App\Domains\Blog\Mail\NewBlogPost;
use App\Domains\Blog\Models\Blog;
use App\Domains\Client\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class SendBlogNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $blog;
    public $subscriber;

    /**
     * Create a new job instance.
     */
    public function __construct(Blog $blog, Subscriber $subscriber)
    {
        $this->blog = $blog;
        $this->subscriber = $subscriber;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $key = 'send-blog-notification';

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            $this->release($seconds);
            return;
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        Mail::to($this->subscriber->email)->send(new NewBlogPost($this->blog));
    }
}
