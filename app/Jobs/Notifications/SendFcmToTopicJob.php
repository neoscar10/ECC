<?php

namespace App\Jobs\Notifications;

use App\Services\Notifications\FcmSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmToTopicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $topic;
    public $title;
    public $body;
    public $data;
    public $options;

    public $tries = 3;
    public $backoff = [10, 60, 180];

    /**
     * Create a new job instance.
     */
    public function __construct(string $topic, string $title, string $body, array $data = [], array $options = [])
    {
        $this->topic = $topic;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->options = $options;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(FcmSender $sender): void
    {
        \Illuminate\Support\Facades\Log::info("Job [SendFcmToTopicJob] starting", [
            'topic' => $this->topic,
            'title' => $this->title
        ]);
        $sender->sendToTopic($this->topic, $this->title, $this->body, $this->data, $this->options);
    }
}
