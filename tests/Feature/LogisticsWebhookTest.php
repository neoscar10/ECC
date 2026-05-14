<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LogisticsWebhookTest extends TestCase
{
    public function test_webhook_health_endpoint()
    {
        $response = $this->getJson('/api/webhooks/logistics/health');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);
    }

    public function test_webhook_tracking_unauthorized()
    {
        Config::set('shiprocket.webhook_token', 'secret-token');

        $response = $this->postJson('/api/webhooks/logistics/tracking', [], [
            'x-api-key' => 'wrong-token'
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_tracking_authorized()
    {
        Config::set('shiprocket.webhook_token', 'secret-token');

        $response = $this->postJson('/api/webhooks/logistics/tracking', [
            'awb' => '123456789',
            'current_status' => 'Delivered'
        ], [
            'x-api-key' => 'secret-token'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}
