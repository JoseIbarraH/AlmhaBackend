<?php

namespace App\Domains\Blog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domains\Blog\Models\Blog;
use App\Domains\Client\Models\Subscriber;
use App\Domains\Blog\Mail\NewsletterMail;
use Illuminate\Support\Facades\Mail;

class SendNewsletterJob implements ShouldQueue
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
        $subscribers = Subscriber::active()->get();

        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email)->queue(new NewsletterMail($this->blog, $subscriber));
        }
    }
}
