<?php

namespace App\Jobs\Notifications;

use App\Models\User;
use App\Services\Notifications\FcmSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmToUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $title;
    public $body;
    public $data;
    public $options;

    public $tries = 3;
    public $backoff = [10, 60, 180];

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $title, string $body, array $data = [], array $options = [])
    {
        $this->userId = $userId;
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
        $user = User::find($this->userId);
        if ($user) {
            $sender->sendToUser($user, $this->title, $this->body, $this->data, $this->options);
        }
    }
}
