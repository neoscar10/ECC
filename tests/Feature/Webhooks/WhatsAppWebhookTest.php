<?php

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\OtpVerification;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.enabled' => true]);
        config(['services.whatsapp.otp_method' => 'direct_message']);
        config(['services.whatsapp.phone_number_id' => 'test_phone_id']);
        config(['services.whatsapp.access_token' => 'test_token']);
        config(['services.whatsapp.webhook_verify_token' => 'test_verify_token']);
    }

    public function test_webhook_verification()
    {
        $response = $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=test_verify_token&hub_challenge=12345');

        $response->assertStatus(200);
        $this->assertEquals('12345', $response->getContent());
    }

    public function test_webhook_verification_fails_with_invalid_token()
    {
        $response = $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong_token&hub_challenge=12345');

        $response->assertStatus(403);
    }

    public function test_webhook_handles_incoming_message_and_sends_cached_otp()
    {
        Http::fake([
            'https://graph.facebook.com/v22.0/test_phone_id/messages' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        $phone = '+919876543210';
        Cache::put('otp_plaintext_' . $phone, '123456', now()->addMinutes(5));

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'WABA_ID',
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '919876543210',
                                        'id' => 'wamid.incoming',
                                        'timestamp' => '1602056683',
                                        'text' => ['body' => 'Request OTP'],
                                        'type' => 'text'
                                    ]
                                ]
                            ],
                            'field' => 'messages'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/whatsapp', $payload);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'test_phone_id/messages') &&
                   $request['to'] === '919876543210' &&
                   str_contains($request['text']['body'], '123456');
        });

        $this->assertNull(Cache::get('otp_plaintext_' . $phone));
    }

    public function test_webhook_handles_incoming_message_with_no_pending_otp()
    {
        Http::fake([
            'https://graph.facebook.com/v22.0/test_phone_id/messages' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'WABA_ID',
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '919876543210',
                                        'id' => 'wamid.incoming',
                                        'timestamp' => '1602056683',
                                        'text' => ['body' => 'Request OTP'],
                                        'type' => 'text'
                                    ]
                                ]
                            ],
                            'field' => 'messages'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/whatsapp', $payload);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'test_phone_id/messages') &&
                   $request['to'] === '919876543210' &&
                   str_contains($request['text']['body'], 'No pending OTP request found');
        });
    }
}
