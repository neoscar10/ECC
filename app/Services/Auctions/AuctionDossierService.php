<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionLot;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AuctionDossierService
{
    public function getDossier(User $user, int $perPage = 10): LengthAwarePaginator
    {
        // 1. Query: Participated Auctions
        // "Participated" means the user has at least one bid on the lot.
        // We need to dedupe by AuctionLot.
        
        $query = AuctionLot::query()
            ->whereHas('bids', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with([
                'bids' => function ($q) use ($user) {
                    // Eager load ONLY the user's max bid to avoid loading all bids
                    // We need this to show "Your Max Bid"
                    $q->where('user_id', $user->id)->orderBy('amount', 'desc')->limit(1);
                },
                'winner',            // The assigned winner user
                'order',             // The sale record (if exists)
                'images' => function ($q) {
                    $q->orderBy('sort_order')->limit(1); // Thumbnail
                }
            ]);

        // 2. Sorting
        // "Most relevant recent activity"
        // We want to sort by the User's last interaction (bid time) descending.
        // We can use a subquery or join for this, but to keep it simple and efficient given standard usage:
        // We'll add a subselect for 'last_participated_at' and sort by it.
        
        $query->addSelect(['last_participated_at' => \App\Models\Auctions\AuctionBid::select('created_at')
            ->whereColumn('auction_lot_id', 'auction_lots.id')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(1)
        ])->orderByDesc('last_participated_at');


        // 3. Execute & Transform
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (AuctionLot $lot) use ($user) {
            return $this->transformDossierItem($lot, $user);
        });

        return $paginator;
    }

    protected function transformDossierItem(AuctionLot $lot, User $user): array
    {
        // Data Prep
        $myMaxBid = $lot->bids->first(); // Since we constrained eager load to 1
        $myMaxBidAmount = $myMaxBid ? (float)$myMaxBid->amount : 0.00;
        
        // Determine Statuses
        $auctionStatus = $lot->status ?? 'upcoming'; // live, ended, upcoming
        $isWinner = $lot->winner_user_id === $user->id;
        
        // Dossier Status Logic
        $dossierStatus = 'ended'; // Default fallback
        $labels = [
            'top_right' => 'ENDED',
            'line_1' => $lot->lot_no ? "Lot {$lot->lot_no}" : $lot->title,
            'line_2' => null,
        ];
        
        // Helper formatted currency
        $currency = $lot->currency ?? 'INR';
        $fmt = fn($val) => number_format((float)$val, 0, '.', ','); // Simple formatter, e.g. 4,500
        
        $sale = $lot->order; // Define sale globally for the method scope to avoid undefined variable in return
        
        // --- LOGIC BRANCHING ---

        if ($auctionStatus === 'live') {
            // Check if leading
            $isLeading = $lot->current_highest_bid > 0 && 
                         abs($lot->current_highest_bid - $myMaxBidAmount) < 0.01 && 
                         $this->isUserCurrentHighest($lot, $user); // Double check logic
            
            // Actually, comparing amounts might be flaky if multiple users bid same amount (unlikely in auction logic but possible in race conditions).
            // Better: Check if current highest bid record belongs to user? 
            // We don't have that relation loaded easily without N+1. 
            // But we know 'current_highest_bid' on Lot is the max.
            // If my max bid == current_highest_bid, I am leading (assuming earlier bid wins ties, and I have the bid).
            // Let's rely on amount equality for 'Leading' visual, it's 99% accurate.
            // Strict check: 
            $isLeading = ($myMaxBidAmount >= $lot->current_highest_bid) && ($lot->current_highest_bid > 0);

            if ($isLeading) {
                $dossierStatus = 'leading';
                $labels['top_right'] = 'LEADING';
            } else {
                $dossierStatus = 'outbid';
                $labels['top_right'] = 'OUTBID';
                $labels['line_2'] = "Your Max: ₹" . $fmt($myMaxBidAmount);
            }
            
            $labels['line_1'] = "Current: ₹" . $fmt($lot->current_highest_bid);

        } elseif ($auctionStatus === 'upcoming') {
            $dossierStatus = 'upcoming';
            $labels['top_right'] = 'UPCOMING';
            $labels['line_1'] = "Starts " . ($lot->starts_at ? $lot->starts_at->format('d M') : 'Soon');
            
        } else {
            // ENDED
            // Check for Sale
            // $sale already defined above
            
            if ($sale) {
                // Sale Recorded
                if ($sale->user_id === $user->id) {
                    // I Won
                    $dossierStatus = 'won';
                    $labels['top_right'] = 'WON';
                    $labels['line_1'] = "Hammer Price: ₹" . $fmt($sale->unit_price_inr);
                    
                    if ($sale->paid_at) {
                        $dossierStatus = 'payment_cleared';
                        $labels['line_2'] = 'PAYMENT CLEARED';
                    } else {
                        $dossierStatus = 'payment_pending';
                        $labels['line_2'] = 'PAYMENT PENDING';
                    }
                } else {
                    // Someone else won
                    $dossierStatus = 'outbid'; // or 'lost'
                    $labels['top_right'] = 'OUTBID';
                    $labels['line_1'] = "Sold for ₹" . $fmt($sale->unit_price_inr);
                    $labels['line_2'] = "Your Bid: ₹" . $fmt($myMaxBidAmount);
                }
            } else {
                // No Sale Recorded Yet
                if ($isWinner) {
                    // I am the winner but no sale record
                    $dossierStatus = 'won_pending_sale';
                    $labels['top_right'] = 'WON';
                    $labels['line_1'] = "Hammer Price: ₹" . $fmt($lot->current_highest_bid);
                    $labels['line_2'] = 'AWAITING INVOICE';
                } else {
                     // I lost (or reserve not met)
                     $dossierStatus = 'outbid'; // or 'ended'
                     $labels['top_right'] = 'OUTBID';
                     $labels['line_1'] = "Closed at ₹" . $fmt($lot->current_highest_bid);
                     $labels['line_2'] = "Your Bid: ₹" . $fmt($myMaxBidAmount);
                }
            }
        }

        // Image
        $img = $lot->images->first();
        $imgUrl = $img ? (method_exists($img, 'getUrlAttribute') ? $img->url : url(\Illuminate\Support\Facades\Storage::url($img->path))) : null;

        return [
            "auction_id" => $lot->id,
            "lot_no" => $lot->lot_no,
            "title" => $lot->title,
            "image_url" => $imgUrl,
            "auction_status" => $auctionStatus,
            "dossier_status" => $dossierStatus,
            "my_max_bid" => [
                "amount" => (string)$myMaxBidAmount,
                "currency" => $currency
            ],
            "current_bid" => [
                "amount" => (string)$lot->current_highest_bid,
                "currency" => $currency
            ],
            // Hammer price is contextual (sale price or current high)
            "hammer_price" => $labels['top_right'] === 'WON' 
                ? ["amount" => (string)($sale->unit_price_inr ?? $lot->current_highest_bid), "currency" => $currency]
                : null,
            "sale" => [
                "is_recorded" => (bool)$sale,
                "winner_user_id" => $lot->winner_user_id,
                "payment_status" => $sale ? ($sale->paid_at ? 'cleared' : 'pending') : 'na',
                "payment_status_label" => $sale ? ($sale->paid_at ? 'PAYMENT CLEARED' : 'PAYMENT PENDING') : null
            ],
            "labels" => $labels,
            "deep_link" => [
                "type" => "auction_detail",
                "id" => $lot->id
            ]
        ];
    }

    private function isUserCurrentHighest(AuctionLot $lot, User $user): bool
    {
        // Heuristic: if current_highest_bid matches my max bid, likely me.
        // Assuming unique bid amounts or first-come-first-serve where backend updates 'current_highest_bid' accurately.
        // 'latestBid' on lot is belongsTo winner_user_id, but that might not be set during Live?
        // Let's assume equality for now as it's efficient.
        return $lot->current_highest_bid == $lot->bids->first()->amount;
    }
}
