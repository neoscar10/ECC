<?php

namespace Tests\Feature;

use App\Jobs\Notifications\SendFcmToTopicJob;
use App\Jobs\Notifications\SendFcmToUserJob;
use App\Models\User;
use App\Services\Notifications\FcmSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;

class FcmSendingTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_topic_job()
    {
        Bus::fake();

        $this->artisan('fcm:test', [
            '--topic' => 'ecc_test',
            '--title' => 'Test',
            '--body' => 'Body'
        ])->assertExitCode(0);

        Bus::assertDispatched(SendFcmToTopicJob::class, function ($job) {
            return $job->topic === 'ecc_test' && $job->title === 'Test';
        });
    }

    public function test_command_dispatches_user_job()
    {
        Bus::fake();
        $user = User::factory()->create();

        $this->artisan('fcm:test', [
            '--user' => $user->id,
            '--title' => 'Test',
            '--body' => 'Body'
        ])->assertExitCode(0);

        Bus::assertDispatched(SendFcmToUserJob::class, function ($job) use ($user) {
            return $job->userId === $user->id;
        });
    }

    public function test_send_to_user_filters_active_tokens()
    {
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 'active_token', 'platform' => 'android', 'is_active' => true]);
        $user->deviceTokens()->create(['token' => 'inactive_token', 'platform' => 'ios', 'is_active' => false]);


        
        // Simpler: Test FcmSender logic by instantiating it and mocking pure Http if needed, 
        // OR rely on the fact we are manual implementation.
        // Let's rely on Partial Mock of FcmSender to verify 'sendToTokens' is called with filtered list.
        
        $sender = \Mockery::mock(FcmSender::class)->makePartial();
        $sender->shouldAllowMockingProtectedMethods();
        
        $sender->shouldReceive('sendRaw')
            ->once()
            ->with(\Mockery::on(function($msg) {
                return $msg['token'] === 'active_token';
            }), \Mockery::any());
            
        $sender->shouldNotReceive('sendRaw')
            ->with(\Mockery::on(function($msg) {
                return $msg['token'] === 'inactive_token';
            }), \Mockery::any());
        
        $sender->sendToUser($user, 'T', 'B');
    }
    
    public function test_invalid_token_is_marked_inactive()
    {
        // This requires mocking HTTP responses inside FcmSender.
        // Since FcmSender uses Http facade, we can mock that.
        
        $user = User::factory()->create();
        $token = $user->deviceTokens()->create(['token' => 'bad_token', 'platform' => 'android', 'is_active' => true]);

        // Mock Http to return 404 for this token
        // FcmSender::sendToTokens loops.
        // We will test sendToTokens directly.
        
        // Note: FcmSender::sendRaw uses Http::withToken...
        // We need to ensure we can mock the chain.
        config(['services.firebase.project_id' => 'mock_project_id']);
        
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.'
                ]
            ], 404)
        ]);
        
        // We also need to mock getAccessToken to avoid real Google Auth call
        $sender = \Mockery::mock(FcmSender::class)->makePartial();
        $sender->shouldAllowMockingProtectedMethods();
        $sender->shouldReceive('getAccessToken')->andReturn('mock_access_token');
        
        // Ensure projectId is set (constructor might fail in test env or partial mock)
        $ref = new \ReflectionClass($sender);
        $prop = $ref->getProperty('projectId');
        $prop->setAccessible(true);
        $prop->setValue($sender, 'mock_project_id');
        
        // Disable the internal fake so we hit the Http::fake
        FcmSender::$fakeInTesting = false;
        
        $sender->sendToTokens(['bad_token'], 'T', 'B');
        
        // Restore
        FcmSender::$fakeInTesting = true;
        
        $this->assertDatabaseHas('user_device_tokens', [
            'id' => $token->id,
            'is_active' => false
        ]);
    }

    public function test_data_payload_is_normalized_to_strings()
    {
        $sender = \Mockery::mock(FcmSender::class)->makePartial();
        $sender->shouldAllowMockingProtectedMethods();
        
        $data = [
            'int' => 123,
            'bool' => true,
            'array' => ['a' => 1],
            'null' => null
        ];

        $sender->shouldReceive('sendRaw')
            ->once()
            ->with(\Mockery::on(function($msg) {
                // Ensure data exists
                if (!isset($msg['data'])) return false;
                
                $d = $msg['data'];
                
                // Assertions
                $intOk = ($d['int'] === '123');
                $boolOk = ($d['bool'] === 'true');
                $arrayOk = ($d['array'] === '{"a":1}');
                $nullOk = !array_key_exists('null', $d);
                
                return $intOk && $boolOk && $arrayOk && $nullOk;
            }), \Mockery::any());

        $sender->sendToTopic('test_topic', 'T', 'B', $data);
    }

    public function test_logs_generated_on_send()
    {
        Log::shouldReceive('info')->with('FCM MOCK SEND: topic:test_topic')->once(); // from internal mock
        
        Log::shouldReceive('info')
            ->once()
            ->with('FCM_SEND_TOPIC', \Mockery::on(function ($context) {
                return $context['action'] === 'FCM_SEND_TOPIC'
                    && $context['status'] === 'attempt'
                    && $context['topic'] === 'test_topic'
                    && isset($context['data']['key']);
            }));

        $sender = new FcmSender();
        $sender->sendToTopic('test_topic', 'T', 'B', ['key' => 'val']);
    }

    public function test_options_are_filtered_and_merged()
    {
        $sender = \Mockery::mock(FcmSender::class)->makePartial();
        $sender->shouldAllowMockingProtectedMethods();
        
        $options = [
            'android' => ['priority' => 'high'],
            'invalid_key' => 'should_be_removed'
        ];

        $sender->shouldReceive('sendRaw')
            ->once()
            ->with(\Mockery::on(function($msg) {
                // Assert invalid key removed
                if (isset($msg['invalid_key'])) return false;
                
                // Assert valid key merged
                if (!isset($msg['android'])) return false;
                if ($msg['android']['priority'] !== 'high') return false;
                
                return true;
            }), \Mockery::any());

        $sender->sendToTopic('test_topic', 'T', 'B', [], $options);
    }
}
