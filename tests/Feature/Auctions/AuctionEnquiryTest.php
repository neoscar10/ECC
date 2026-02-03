<?php

namespace Tests\Feature\Auctions;

use App\Models\Auctions\AuctionEnquiry;
use App\Models\Auctions\AuctionLot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuctionEnquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_enquiry()
    {
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/auctions/{$lot->id}/enquiries", [
                'message' => 'Is this cricket bat signed?',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['id', 'status', 'created_at']]);

        $this->assertDatabaseHas('auction_enquiries', [
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'message' => 'Is this cricket bat signed?',
            'status' => 'new',
        ]);
    }

    public function test_guest_cannot_submit_enquiry()
    {
        $lot = AuctionLot::factory()->create();

        $response = $this->postJson("/api/v1/auctions/{$lot->id}/enquiries", [
            'message' => 'Guest msg',
        ]);

        $response->assertStatus(401);
    }

    public function test_validation_requires_existing_lot()
    {
        $user = User::factory()->create();

        // 999999 is unlikely to exist
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/auctions/999999/enquiries", [
                'message' => 'msg',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auction_lot_id']);
    }

    public function test_admin_can_list_enquiries_via_api()
    {
        $user = User::factory()->create(); // Assuming any auth user can list for now based on 'auth:api' in routes, or is there a role check?
        // Route group: middleware 'auth:api'. No role check in route? 
        // Archive route group: middleware 'auth:api'.
        // So any user can technically hit it? 
        // Admin panel uses Livewire (web auth). 
        // API List: "LIST (admin/mobile admin usage)".
        // If the implementation doesn't have role check, I won't add it to keep parity (minimal change).
        // But for "admin" usage implies I should check generic auth success.
        
        $lot = AuctionLot::factory()->create();
        AuctionEnquiry::create([
            'user_id' => $user->id,
            'auction_lot_id' => $lot->id,
            'message' => 'Test List',
            'contact_email' => 'test@example.com',
            'contact_name' => 'Test User'
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/auctions/enquiries");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => [['id', 'message', 'lot']]])
            ->assertJsonFragment(['message' => 'Test List']);
    }

    public function test_enquiry_filters()
    {
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create(['title' => 'Alpha Bat']);
        
        AuctionEnquiry::create([
            'user_id' => $user->id,
            'auction_lot_id' => $lot->id,
            'message' => 'Msg 1',
            'status' => 'new',
            'contact_name' => 'Alice'
        ]);
        
        AuctionEnquiry::create([
            'user_id' => $user->id,
            'auction_lot_id' => $lot->id, // validation requires valid id
            'message' => 'Msg 2',
            'status' => 'closed',
            'contact_name' => 'Bob'
        ]);

        // Search 'Alice'
        $this->actingAs($user, 'api')
            ->getJson("/api/v1/auctions/enquiries?search=Alice")
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['contact_name' => 'Alice']);

        // Filter Status 'closed'
        $this->actingAs($user, 'api')
            ->getJson("/api/v1/auctions/enquiries?status=closed")
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['contact_name' => 'Bob']);
    }
}
