<?php

namespace Tests\Feature\Auctions;

use App\Models\Auctions\AuctionBid;
use App\Models\Auctions\AuctionLot;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuctionDossierTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_cannot_access_dossier()
    {
        $response = $this->getJson('/api/v1/auctions/dossier');
        $response->assertStatus(401);
    }

    /** @test */
    public function user_sees_only_participated_auctions()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Auction 1: User participated
        $lot1 = AuctionLot::factory()->create(['status' => 'live']);
        AuctionBid::create([
            'auction_lot_id' => $lot1->id,
            'user_id' => $user->id,
            'amount' => 100,
            'placed_at' => now(),
        ]);

        // Auction 2: User did NOT participate
        $lot2 = AuctionLot::factory()->create(['status' => 'live']);
        AuctionBid::create([
            'auction_lot_id' => $lot2->id,
            'user_id' => $otherUser->id,
            'amount' => 100,
            'placed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/auctions/dossier');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['auction_id' => $lot1->id])
            ->assertJsonMissing(['auction_id' => $lot2->id]);
    }

    /** @test */
    public function it_returns_leading_status_when_live_and_highest_bidder()
    {
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create([
            'status' => 'live',
            'current_highest_bid' => 500,
            'currency' => 'INR'
        ]);

        // User is highest bidder (match amount)
        AuctionBid::create([
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'amount' => 500,
            'placed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/auctions/dossier');

        $response->assertStatus(200);
        $item = $response->json('data.0');

        $this->assertEquals('live', $item['auction_status']);
        $this->assertEquals('leading', $item['dossier_status']);
        $this->assertEquals('LEADING', $item['labels']['top_right']);
    }

    /** @test */
    public function it_returns_outbid_status_when_live_and_not_highest_bidder()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $lot = AuctionLot::factory()->create([
            'status' => 'live',
            'current_highest_bid' => 1000,
        ]);

        // My bid (lower)
        AuctionBid::create([
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'amount' => 500,
            'placed_at' => now()->subHour(),
        ]);

        // Other bid (higher)
        AuctionBid::create([
            'auction_lot_id' => $lot->id,
            'user_id' => $otherUser->id,
            'amount' => 1000,
            'placed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/auctions/dossier');

        $item = $response->json('data.0');
        $this->assertEquals('live', $item['auction_status']);
        $this->assertEquals('outbid', $item['dossier_status']);
        $this->assertEquals('OUTBID', $item['labels']['top_right']);
    }

    /** @test */
    public function it_returns_won_status_when_ended_and_sale_recorded()
    {
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create([
            'status' => 'ended',
            'winner_user_id' => $user->id,
            'current_highest_bid' => 5000,
        ]);

        // My winning bid
        AuctionBid::create([
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'amount' => 5000,
            'placed_at' => now(),
        ]);

        // Sale record
        Order::create([
             'order_number' => 'ORD-TEST-001',
             'user_id' => $user->id,
             'auction_lot_id' => $lot->id,
             'unit_price_inr' => 5000,
             'subtotal_inr' => 5000,
             'qty' => 1,
             'status' => 'pending',
             'sold_at' => now(),
             // payment pending (paid_at null)
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/auctions/dossier');

        $item = $response->json('data.0');
        $this->assertEquals('ended', $item['auction_status']);
        $this->assertEquals('payment_pending', $item['dossier_status']); // payment_pending substatus of won
        // Actually service returns 'payment_pending' as primary dossier_status when won & sale exists & not paid
        
        $this->assertEquals('WON', $item['labels']['top_right']);
        $this->assertEquals('PAYMENT PENDING', $item['labels']['line_2']);
        $this->assertTrue($item['sale']['is_recorded']);
        $this->assertEquals('pending', $item['sale']['payment_status']);
    }

    /** @test */
    public function it_returns_outbid_when_ended_and_someone_else_won()
    {
        $user = User::factory()->create();
        $winner = User::factory()->create();
        
        $lot = AuctionLot::factory()->create([
            'status' => 'ended',
            'winner_user_id' => $winner->id,
            'current_highest_bid' => 9000,
        ]);

        // My bid
        AuctionBid::create([
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'amount' => 5000,
            'placed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/auctions/dossier');
        
        $item = $response->json('data.0');
        $this->assertEquals('outbid', $item['dossier_status']);
        $this->assertEquals('OUTBID', $item['labels']['top_right']);
    }

    /** @test */
    public function it_deduplicates_multiple_bids_on_same_lot()
    {
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create(['status' => 'live']);

        // Bid 1
        AuctionBid::create([
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'amount' => 100,
            'placed_at' => now()->subMinutes(10),
        ]);

        // Bid 2
        AuctionBid::create([
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'amount' => 200,
            'placed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/auctions/dossier');

        $response->assertJsonCount(1, 'data');
        $this->assertEquals('200', $response->json('data.0.my_max_bid.amount')); // Should show max
    }
}
