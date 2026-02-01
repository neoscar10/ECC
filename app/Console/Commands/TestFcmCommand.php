<?php

namespace App\Console\Commands;

use App\Jobs\Notifications\SendFcmToTopicJob;
use App\Jobs\Notifications\SendFcmToUserJob;
use Illuminate\Console\Command;

class TestFcmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test 
                            {--topic= : Topic to send to}
                            {--user= : User ID to send to}
                            {--title=Test Notification : Notification Title}
                            {--body=This is a test message : Notification Body}
                            {--data= : JSON data payload}
                            {--sync : Send immediately without queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test FCM notification to a topic or user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $topic = $this->option('topic');
        $userId = $this->option('user');
        $title = $this->option('title');
        $body = $this->option('body');
        $dataJson = $this->option('data');
        $sync = $this->option('sync');

        if (!$topic && !$userId) {
            $this->error('You must specify either --topic or --user.');
            return 1;
        }

        if ($topic && $userId) {
            $this->error('Specify only one target: --topic OR --user.');
            return 1;
        }

        $data = [];
        if ($dataJson) {
            $data = json_decode($dataJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON in --data option.');
                return 1;
            }
        }

        if ($topic) {
            $job = new SendFcmToTopicJob($topic, $title, $body, $data);
            if ($sync) {
                $this->info("Sending immediately to Topic: {$topic}");
                app(\App\Services\Notifications\FcmSender::class)->sendToTopic($topic, $title, $body, $data);
            } else {
                $this->info("Dispatching job to Topic: {$topic}");
                dispatch($job);
            }
        } elseif ($userId) {
            $job = new SendFcmToUserJob($userId, $title, $body, $data);
             if ($sync) {
                $this->info("Sending immediately to User ID: {$userId}");
                $user = \App\Models\User::find($userId);
                if ($user) {
                     app(\App\Services\Notifications\FcmSender::class)->sendToUser($user, $title, $body, $data);
                } else {
                    $this->error("User not found.");
                }
            } else {
                $this->info("Dispatching job to User ID: {$userId}");
                dispatch($job);
            }
        }

        $this->info('Done.');
        return 0;
    }
}
