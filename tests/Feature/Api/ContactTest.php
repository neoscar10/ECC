<?php

namespace Tests\Feature\Api;

use App\Models\ContactEnquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_returns_contact_configuration_options()
    {
        $response = $this->getJson('/api/v1/contact/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'direct_lines',
                    'subjects'
                ],
                'message'
            ]);
            
        $response->assertJsonFragment(['key' => 'membership_upgrade']);
    }

    /** @test */
    public function authenticated_user_can_submit_contact_enquiry()
    {
        $payload = [
            'subject' => 'membership_upgrade',
            'message' => 'I would like to upgrade my membership.',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/contact/enquiries', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'subject',
                    'status',
                    'created_at',
                    'message'
                ]
            ]);

        $this->assertDatabaseHas('contact_enquiries', [
            'user_id' => $this->user->id,
            'subject' => 'membership_upgrade',
            'message' => 'I would like to upgrade my membership.',
            'status' => 'new'
        ]);
    }

    /** @test */
    public function validated_fields_are_required()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/contact/enquiries', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'message']);
    }

    /** @test */
    public function subject_must_be_valid_option()
    {
        $payload = [
            'subject' => 'invalid_subject',
            'message' => 'Some message'
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/contact/enquiries', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    /** @test */
    public function authenticated_user_can_list_their_enquiries()
    {
        ContactEnquiry::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        // Create one for another user
        ContactEnquiry::factory()->create([
            'user_id' => User::factory()->create()->id
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/me/contact-enquiries');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data'); // 3 for this user
    }
}
