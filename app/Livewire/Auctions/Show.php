<?php

namespace App\Livewire\Auctions;

use Livewire\Component;
use App\Models\Auctions\AuctionLot;
use App\Services\Auctions\AuctionAccessResolverService;
use App\Services\Auctions\AuctionBiddingService;
use App\Services\Membership\MembershipTierResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Show extends Component
{
    public $lot;
    public $bidAmount;

    public function mount($lot, AuctionAccessResolverService $resolver, MembershipTierResolver $tierResolver)
    {
        // Load the lot cleanly with necessary relations identical to the API logic.
        $this->lot = AuctionLot::with([
            'winner',
            'bids.user',
            'bids' => fn($q) => $q->latest('placed_at')->take(20), // Grab enough history
            'images',
            'attachments',
            'restrictedMinTier',
            'restrictedPrivateTier',
            'clearViewTiers',
            'visibilityTiers'
        ])
        ->where('id', $lot)
        ->orWhere('lot_no', $lot)
        ->firstOrFail();

        $user = auth('web')->user();
        $tier = $user ? $tierResolver->resolveForUser($user) : null;

        // Perform standard access resolution. This validates lockouts natively.
        $accessCheck = $resolver->resolve($this->lot, $user, $tier);

        // Load auto bid state
        $this->loadAutoBidState($user, $tier, $accessCheck);

        // API logic blocks early access strictly. Mirror this behavior.
        if (($accessCheck['view_mode'] ?? 'blocked') === 'blocked') {
            abort(403, 'You do not have permission to view this lot.');
        }

        // Initialize user inputs.
        $this->bidAmount = $this->lot->next_bid_display ?? $this->formatCurrency($this->lot->current_highest_bid ? $this->lot->current_highest_bid + $this->lot->min_increment : $this->lot->starting_price);
    }

    public function getListeners()
    {
        if (!$this->lot) return [];
        return [
            "echo-private:auctions.lot.{$this->lot->id},.bid.placed" => 'refreshLotData',
        ];
    }

    public function refreshLotData()
    {
        if (!$this->lot) return;

        $this->lot = AuctionLot::with([
            'winner',
            'bids.user',
            'bids' => fn($q) => $q->latest('placed_at')->take(20),
            'images',
            'attachments',
            'restrictedMinTier',
            'restrictedPrivateTier',
            'clearViewTiers',
            'visibilityTiers'
        ])->findOrFail($this->lot->id);

        $user = auth('web')->user();
        if ($user) {
            $resolver = app(\App\Services\Auctions\AuctionAccessResolverService::class);
            $tierResolver = app(\App\Services\Membership\MembershipTierResolver::class);
            $tier = $tierResolver->resolveForUser($user);
            $accessCheck = $resolver->resolve($this->lot, $user, $tier);
            $this->loadAutoBidState($user, $tier, $accessCheck);
        }
    }

    public function openAutoBidModal()
    {
        $this->resetErrorBag();
        $this->autoBidErrorMessage = null;

        if (!$this->canAutoBid) {
            return;
        }

        $this->currentHighestBidDisplay = $this->formatCurrency($this->lot->current_highest_bid ?? $this->lot->starting_price);
        $this->minIncrementDisplay = $this->formatCurrency($this->lot->min_increment);
        
        if ($this->hasAutoBidConfigured && $this->existingAutoBid) {
            $this->autoBidIncrementAmount = rtrim(rtrim((string) $this->existingAutoBid->increment_amount, '0'), '.');
            $this->autoBidMaxAmount = rtrim(rtrim((string) $this->existingAutoBid->max_bid, '0'), '.');
        } else {
            $this->autoBidIncrementAmount = rtrim(rtrim((string) $this->lot->min_increment, '0'), '.');
            $this->autoBidMaxAmount = null;
        }
        
        $this->showAutoBidModal = true;
    }

    public function confirmCancelAutoBidModal()
    {
        $this->showCancelAutoBidModal = true;
    }

    public function closeAutoBidModal()
    {
        $this->showAutoBidModal = false;
        $this->resetErrorBag();
        $this->autoBidErrorMessage = null;
    }

    public function saveAutoBid(\App\Services\Auctions\AuctionAutoBidService $autoBidService)
    {
        $this->resetErrorBag();
        $this->autoBidErrorMessage = null;

        $user = auth('web')->user();
        if (!$user || !$this->canAutoBid) {
             $this->autoBidErrorMessage = 'Access Denied: Auto-bidding is not enabled for your Membership Tier.';
             return;
        }

        $numericMax = (float) preg_replace('/[^0-9.]/', '', $this->autoBidMaxAmount);
        $numericInc = (float) preg_replace('/[^0-9.]/', '', $this->autoBidIncrementAmount);

        try {
            $autoBidService->setAutoBid($this->lot, $user, $numericMax, $numericInc);
            $this->showAutoBidModal = false;
            $this->refreshLotData();
            $this->dispatch('ui:notify', message: 'Auto bid configured successfully!', type: 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                 if ($field === 'max_bid') {
                     $this->addError('autoBidMaxAmount', $messages[0]);
                 } elseif ($field === 'increment_amount') {
                     $this->addError('autoBidIncrementAmount', $messages[0]);
                 } else {
                     $this->autoBidErrorMessage = $messages[0];
                 }
            }
        } catch (\Exception $e) {
            $this->autoBidErrorMessage = $e->getMessage();
        }
    }

    public function closeCancelAutoBidModal()
    {
        $this->showCancelAutoBidModal = false;
    }

    public function cancelAutoBid(\App\Services\Auctions\AuctionAutoBidService $autoBidService)
    {
        $user = auth('web')->user();
        if (!$user) return;

        try {
            $autoBidService->cancelAutoBid($this->lot, $user);
            $this->showCancelAutoBidModal = false;
            $this->refreshLotData();
            $this->dispatch('ui:notify', message: 'Auto bid cancelled.', type: 'info');
        } catch (\Exception $e) {
            $this->autoBidErrorMessage = 'Failed to cancel auto bid.';
        }
    }

    public function applyIncrement($incrementLabel)
    {
        // Extract integer amount from chip label like '+ ₹10k'
        $numericValue = (float) preg_replace('/[^0-9.]/', '', str_replace('k', '000', strtolower($incrementLabel)));
        
        if ($numericValue > 0 && is_numeric($numericValue)) {
            $baseAmount = $this->lot->current_highest_bid ?: $this->lot->starting_price;
            $this->bidAmount = $this->formatCurrency($baseAmount + $numericValue);
        }
    }

    public $showBidConfirmModal = false;
    public $bidErrorMessage = null;
    public $currentHighestBidDisplay = '';
    public $userBidDisplay = '';

    public $showAutoBidModal = false;
    public $showCancelAutoBidModal = false;
    public $autoBidMaxAmount;
    public $autoBidIncrementAmount;
    public $autoBidErrorMessage = null;
    public $canAutoBid = false;
    public $hasAutoBidConfigured = false;
    public $minIncrementDisplay = '';
    public $existingAutoBid = null;

    protected function loadAutoBidState($user, $tier, $accessCheck)
    {
        $this->canAutoBid = false;
        $this->hasAutoBidConfigured = false;
        $this->existingAutoBid = null;

        if ($user) {
            $resolver = app(\App\Services\Auctions\AuctionAccessResolverService::class);
            $this->canAutoBid = (($accessCheck['view_mode'] ?? 'blocked') === 'clear') 
                && ($tier?->is_auto_bidding_enabled ?? false)
                && $resolver->isBiddingOpenForUser($this->lot, $user);
            
            $autoBid = \App\Models\Auctions\AuctionAutoBid::where('auction_lot_id', $this->lot->id)
                ->where('user_id', $user->id)
                ->first();
                
            if ($autoBid) {
                $this->hasAutoBidConfigured = true;
                $this->existingAutoBid = $autoBid;
            }
        }
    }

    public function reviewBid(AuctionAccessResolverService $resolver, MembershipTierResolver $tierResolver)
    {
        $this->resetErrorBag();
        $this->bidErrorMessage = null;

        $user = auth('web')->user();
        if (!$user) {
            $this->bidErrorMessage = 'You must be logged in to bid.';
            $this->showBidConfirmModal = true;
            return;
        }

        $numericAmount = (float) preg_replace('/[^0-9.]/', '', $this->bidAmount);
        
        if ($numericAmount <= 0) {
            $this->addError('bidAmount', 'Please enter a valid bid amount.');
            return;
        }

        $currentHighest = $this->lot->current_highest_bid;
        $minRequired = $currentHighest ? ($currentHighest + $this->lot->min_increment) : $this->lot->starting_price;

        if ($numericAmount < $minRequired) {
            $this->addError('bidAmount', 'Bid must be at least ₹' . number_format($minRequired) . '.');
            return;
        }

        $tier = $tierResolver->resolveForUser($user);
        $access = $resolver->resolve($this->lot, $user, $tier);

        // Intelligently decide: Upgrade path first, then timing error
        if (!empty($access['actions'])) {
            return $this->triggerUpgradeFlow($access);
        }

        if (($access['view_mode'] ?? 'blocked') !== 'clear') {
            return $this->triggerUpgradeFlow($access);
        }

        if (!$resolver->isBiddingOpenForUser($this->lot, $user)) {
            $this->bidErrorMessage = 'Bidding is not open for this item yet.';
            $this->showBidConfirmModal = true;
            return;
        }

        $this->currentHighestBidDisplay = $this->formatCurrency($this->lot->current_highest_bid ?? $this->lot->starting_price);
        $this->userBidDisplay = $this->formatCurrency($numericAmount);
        $this->showBidConfirmModal = true;
    }

    public function closeBidConfirmModal()
    {
        $this->showBidConfirmModal = false;
        $this->bidErrorMessage = null;
        $this->resetErrorBag();
    }

    public function confirmBidSubmission(AuctionBiddingService $biddingService, \App\Services\Auctions\AuctionAutoBidService $autoBidService)
    {
        $this->resetErrorBag();
        $this->bidErrorMessage = null;

        $user = auth('web')->user();
        if (!$user) {
            $this->bidErrorMessage = 'You must be logged in to bid.';
            return;
        }

        $numericAmount = (float) preg_replace('/[^0-9.]/', '', $this->bidAmount);

        try {
            // Re-fetch lockable copy inside service
            $lot = $biddingService->placeBid($this->lot, $user, $numericAmount, 'web');
            $autoBidService->processAutoBids($lot);

            // Close modal on success
            $this->showBidConfirmModal = false;

            // Re-mount the component completely to pull all fresh relations
            $this->lot = AuctionLot::with([
                'winner', 'bids.user',
                'bids' => fn($q) => $q->latest('placed_at')->take(20),
                'images', 'attachments', 'restrictedMinTier',
                'restrictedPrivateTier', 'clearViewTiers', 'visibilityTiers'
            ])->findOrFail($this->lot->id);

            // Update local input to next tick safely
            $this->bidAmount = $this->formatCurrency($this->lot->current_highest_bid + $this->lot->min_increment);
            
            // Dispatch success to frontend
            $this->dispatch('ui:notify', message: 'Bid placed successfully!', type: 'success');

        } catch (\Exception $e) {
            $this->bidErrorMessage = $e->getMessage();
        }
    }

    private function getHistoryPrepared()
    {
        if (!$this->lot->bids) return [];

        $userId = auth('web')->id();
        $bids = $this->lot->bids->sortByDesc('placed_at')->values();

        return $bids->map(function ($bid, $index) use ($userId) {
            $formatted = \App\Support\Auctions\BidPresenter::present($bid, $userId);
            $formatted['is_highest_bid'] = ($index === 0);
            $formatted['amount_display'] = $this->formatCurrency($bid->amount);
            return $formatted;
        })->toArray();
    }

    private function getAttachmentsPrepared(AuctionAccessResolverService $resolver, $user, $tier)
    {
        if (!$this->lot->attachments) return [];

        $attachments = [];
        $lotAccess = $resolver->resolve($this->lot, $user, $tier);
        $canView = ($lotAccess['view_mode'] === 'clear');

        foreach ($this->lot->attachments->where('is_active', true) as $att) {
            // Check clear status based on lot inheritance
            if (!$canView) continue; // Soft-lock file downloads.
            
            $url = $att->file_path ? Storage::url($att->file_path) : '#';

            $attachments[] = [
                'name' => $att->title ?? 'Attachment',
                'url' => $url,
                'type' => strtoupper(pathinfo($att->file_path, PATHINFO_EXTENSION)) ?: 'FILE',
                'size_label' => $att->file_size ? number_format($att->file_size / 1024, 1) . ' KB' : null,
                'thumbnail_url' => null, // Provide default or compute
                'is_image' => in_array(strtolower(pathinfo($att->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']),
            ];
        }

        return $attachments;
    }

    private function formatCurrency($amount)
    {
        if (!$amount) return '₹0';
        return '₹' . number_format($amount);
    }

    public function render(AuctionAccessResolverService $resolver, MembershipTierResolver $tierResolver)
    {
        $user = auth('web')->user();
        $tier = $user ? $tierResolver->resolveForUser($user) : null;

        $accessState = $resolver->resolve($this->lot, $user, $tier);

        // Pre-compute visual props.
        $statusLabel = $this->lot->status === 'live' ? 'Live Auction' : ucfirst($this->lot->status);
        $timeRemaining = $this->lot->ends_at && $this->lot->ends_at->isFuture() 
            ? $this->lot->ends_at->diffForHumans(['parts' => 2, 'short' => true])
            : ($this->lot->status === 'ended' ? "Ended" : ($this->lot->starts_at ? "Starts " . $this->lot->starts_at->diffForHumans() : "TBA"));

        // Build base arrays requested by specific UI properties
        $suggestedIncrements = [
            ['label' => '+ ₹10k', 'recommended' => false],
            ['label' => '+ ₹25k', 'recommended' => false],
            ['label' => '+ ₹50k', 'recommended' => true],
        ];

        $viewData = [
            'lot_number' => $this->lot->lot_no,
            'title' => $this->lot->title,
            'subtitle' => $this->lot->subtitle,
            'description' => $this->lot->description,
            'hero_image_url' => $this->lot->image_url,
            'status_label' => $statusLabel,
            'rarity_label' => $accessState['is_star_lot'] ?? false ? 'Star Lot' : null,
            'gallery_images' => $this->lot->images->pluck('url')->filter()->values()->toArray(),
            'current_bid_display' => $this->formatCurrency($this->lot->current_highest_bid),
            'time_remaining_display' => $timeRemaining,
            'ends_at_iso' => $this->lot->ends_at ? $this->lot->ends_at->toIso8601String() : '',
            'ends_at_display' => $this->lot->ends_at ? $this->lot->ends_at->format('M j, g:i A') : '',
            'provenance_badges' => [], 
            'attachments' => $this->getAttachmentsPrepared($resolver, $user, $tier),
            'bid_history' => $this->getHistoryPrepared(),
            'suggested_increments' => $suggestedIncrements,
            'terms_url' => route('welcome'), // standard fallback
            'back_url' => route('auctions.index'),
            'can_bid' => $user && (($accessState['view_mode'] ?? 'blocked') === 'clear') && $resolver->isBiddingOpenForUser($this->lot, $user),
            'is_early_access_active' => $accessState['is_early_access_active'] ?? false,
            'is_effectively_live' => $this->lot->status === 'live' || ($accessState['is_early_access_active'] ?? false),
            'access_actions' => $accessState['actions'] ?? [],
            'access_message' => $accessState['message']['body'] ?? null,
        ];

        return view('livewire.auctions.show', [
            'lotPrepared' => (object)$viewData
        ])->layout('layouts.web-app')
          ->title($viewData['title'] ?? 'Auction Detail'); 
    }

    public ?bool $showAccessModal = false;
    public ?array $modalData = null;

    protected function triggerUpgradeFlow(array $access)
    {
        $user = auth('web')->user();
        $tierResolver = app(\App\Services\Membership\MembershipTierResolver::class);

        // Find the target tier from the access actions
        $targetTierId = null;
        if (!empty($access['actions'])) {
            foreach ($access['actions'] as $action) {
                if ($action['type'] === 'upgrade_membership' && !empty($action['target_tier']['id'])) {
                    $targetTierId = $action['target_tier']['id'];
                    break;
                }
            }
        }

        if (!$targetTierId) {
            return redirect('/membership/tiers');
        }

        $targetTierModel = $tierResolver->getTierWithDetails($targetTierId);
        
        if (!$targetTierModel) {
            return redirect('/membership/tiers');
        }

        $this->modalData = [
            'tier_id' => $targetTierModel->id,
            'tier_name' => $targetTierModel->name,
            'price_formatted' => $targetTierModel->price > 0 ? 'INR ' . number_format($targetTierModel->price) : 'Free',
            'duration_label' => 'Year',
            'icon' => \App\Support\Archive\AccessIconNormalizer::normalize($access['reason'] ?? null, $access['view_mode'] ?? 'blocked'),
            'privileges' => $targetTierModel->privileges->toArray(),
            'features' => $targetTierModel->features->toArray(),
            'product_title' => $this->lot->title,
        ];

        $this->showAccessModal = true;
    }

    public function closeAccessModal(): void
    {
        $this->showAccessModal = false;
        $this->modalData = null;
    }

    public function proceedToSubscribe(\App\Services\Membership\ApplicationWizardService $wiz)
    {
        if (!auth('web')->check()) {
            return redirect('/membership/apply-intro');
        }

        if (!$this->modalData || empty($this->modalData['tier_id'])) {
            return redirect(route('membership.application.step1'));
        }

        $draft = $wiz->getOrCreateDraft();
        
        if ($draft instanceof \App\Models\MembershipApplication) {
            $draft->update([
                'selected_tier_id' => $this->modalData['tier_id']
            ]);
        }

        return redirect()->route('membership.upgrade.payment');
    }
}
