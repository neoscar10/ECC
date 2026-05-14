<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckShiprocketConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shiprocket:check-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Shiprocket configuration settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Shiprocket Config Check');

        $baseUrl = config('shiprocket.base_url');
        $email = config('shiprocket.email');
        $password = config('shiprocket.password');
        $pickupLocation = config('shiprocket.pickup_location');
        $webhookUrl = config('shiprocket.webhook_url');
        $webhookToken = config('shiprocket.webhook_token');

        $this->line('Base URL: ' . ($baseUrl ?: '<not set>'));
        
        if ($email) {
            $parts = explode('@', $email);
            $maskedEmail = Str::mask($parts[0], '*', 2) . '@' . ($parts[1] ?? '');
            $this->line('Email: ' . $maskedEmail);
        } else {
            $this->line('Email: <not set>');
        }

        $this->line('Password: ' . ($password ? 'SET' : '<not set>'));
        $this->line('Pickup Location: ' . ($pickupLocation ?: '<not set>'));
        $this->line('Webhook URL: ' . ($webhookUrl ?: '<not set>'));
        $this->line('Webhook Token: ' . ($webhookToken ? 'SET' : '<not set>'));

        $allSet = $baseUrl && $email && $password && $pickupLocation && $webhookUrl && $webhookToken;

        if ($allSet) {
            $this->info('Status: OK');
        } else {
            $this->error('Status: FAILED (Some values are missing)');
        }
    }
}
