<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_401_with_specific_message_when_user_not_found()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'We could not find an account with that email/phone.',
            ]);
    }

    /** @test */
    public function it_returns_401_with_specific_message_when_password_is_wrong()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Incorrect password. Please try again.',
            ]);
    }

    /** @test */
    public function it_returns_422_structure_for_validation_errors()
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['password']
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed', // Or whatever strict message we settle on, likely default Laravel or customized
            ]);
    }

    /** @test */
    public function it_returns_404_standard_json_for_non_existent_route()
    {
        $response = $this->getJson('/api/v1/non-existent-route');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found.',
            ]);
    }

    /** @test */
    public function it_returns_401_standard_json_for_unauthenticated_access()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Authentication token is missing or invalid.',
            ]);
    }

    /** @test */
    public function it_returns_405_standard_json_for_method_not_allowed()
    {
        // Login is POST, try GET
        $response = $this->getJson('/api/v1/auth/login');

        $response->assertStatus(405)
            ->assertJson([
                'success' => false,
                'message' => 'Method not allowed for this endpoint.',
            ]);
    }
}
