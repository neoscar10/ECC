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

        // Safety Layer Status
        $this->line('');
        $this->info('Shiprocket Shipment Safety:');
        $testMode = config('shiprocket.test_mode');
        $liveShipment = config('shiprocket.live_shipment_enabled');
        
        $this->line('Test Mode: ' . ($testMode ? 'ENABLED' : 'DISABLED'));
        $this->line('Live Shipment Enabled: ' . ($liveShipment ? 'YES' : 'NO'));
        
        if (!$testMode && $liveShipment) {
            $this->warn('WARNING: Real Shiprocket shipment creation is enabled.');
            $this->warn('Live API Shipment Calls: ALLOWED');
        } else {
            $this->info('Live API Shipment Calls: BLOCKED');
        }
        $this->line('');

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
        $this->line('Timeout: ' . config('shiprocket.timeout') . 's');
        $this->line('Token Cache Key: ' . config('shiprocket.cache_token_key'));
        $this->line('Token TTL: ' . config('shiprocket.token_ttl_minutes') . 'm');
        $this->line('Auto Select Courier By: ' . config('shiprocket.auto_select_courier_by'));
        $this->line('Rate Quote TTL: ' . config('shiprocket.rate_quote_ttl_minutes') . 'm');

        $allSet = $baseUrl && $email && $password && $pickupLocation && $webhookUrl && $webhookToken;

        if ($allSet) {
            $this->info('Status: OK');
        } else {
            $this->error('Status: FAILED (Some values are missing)');
        }
    }
}
