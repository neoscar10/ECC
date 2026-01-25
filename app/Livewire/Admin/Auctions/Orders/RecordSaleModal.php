<?php

namespace App\Livewire\Admin\Auctions\Orders;

use App\Models\Auctions\AuctionLot;
use App\Models\User;
use App\Services\OrderService;
use Livewire\Component;

class RecordSaleModal extends Component
{
    public $showModal = false;

    // Form Data
    public $auction_lot_id;
    public $user_id; // Buyer User ID
    public $buyer_type = 'registered'; // or 'external'
    
    // External Buyer
    public $external_name;
    public $external_phone;
    public $external_email;
    public $external_address;

    public $unit_price_inr; // Selling Price (Winning Bid)
    
    // Payment
    public $payment_method = 'Offline'; // Offline, Cash, Transfer
    public $payment_reference;
    public $paid_at;
    public $notes;

    // UI State
    public $lotSearch = '';
    public $userSearch = '';
    public $lotSearchResults = [];
    public $userSearchResults = [];
    
    public $selectedLot = null;
    public $selectedUser = null;

    protected $listeners = ['open-record-sale-modal' => 'open'];

    public function mount()
    {
        $this->paid_at = now()->format('Y-m-d\TH:i');
    }

    public function render()
    {
        return view('livewire.admin.auctions.orders.record-sale-modal');
    }

    public function updatedLotSearch()
    {
        $this->searchLots();
    }

    public function searchLots()
    {
        if (strlen($this->lotSearch) < 1) {
            // Default to recent lots (e.g., live or ended recently)
            $this->lotSearchResults = AuctionLot::latest()
                ->take(10)
                ->get();
        } else {
            $this->lotSearchResults = AuctionLot::where('title', 'like', '%' . $this->lotSearch . '%')
                ->orWhere('lot_no', 'like', '%' . $this->lotSearch . '%')
                ->limit(10)
                ->get();
        }
    }
    
    public function updatedUserSearch()
    {
        $this->searchUsers();
    }

    public function searchUsers()
    {
        if (strlen($this->userSearch) < 1) {
            // Default to recent bidders
             $this->userSearchResults = User::whereHas('bids')
                ->withMax('bids', 'created_at')
                ->orderByDesc('bids_max_created_at')
                ->take(10)
                ->get();
        } else {
            $this->userSearchResults = User::where('name', 'like', '%' . $this->userSearch . '%')
                ->orWhere('email', 'like', '%' . $this->userSearch . '%')
                ->limit(10)
                ->get();
        }
    }

    public function selectLot($id)
    {
        $this->selectedLot = AuctionLot::with('winner')->find($id);
        if (!$this->selectedLot) return;

        $this->auction_lot_id = $id;
        $this->lotSearch = $this->selectedLot->title;
        $this->lotSearchResults = [];
        
        // Auto-set price to current highest bid or winner bid
        $this->unit_price_inr = $this->selectedLot->current_highest_bid ?? 0;
        
        // Auto-select winner if exists
        if ($this->selectedLot->winner_user_id) {
            $this->selectUser($this->selectedLot->winner_user_id);
        }
    }
    
    public function selectUser($id)
    {
        $user = User::find($id);
        if (!$user) return;
        
        $this->selectedUser = $user;
        $this->user_id = $id;
        $this->userSearch = $user->name;
        $this->userSearchResults = [];
        $this->buyer_type = 'registered';
    }

    public function open($lotId = null)
    {
        $this->resetForm();
        
        if ($lotId) {
            $this->selectLot($lotId);
        }
        
        $this->showModal = true;
    }

    public function store(OrderService $service)
    {
        $this->validate([
            'auction_lot_id' => 'required|exists:auction_lots,id',
            'unit_price_inr' => 'required|numeric|min:0',
            'buyer_type' => 'required|in:registered,external',
            'user_id' => 'required_if:buyer_type,registered|nullable|exists:users,id',
            'external_name' => 'required_if:buyer_type,external|nullable|string',
            'payment_method' => 'required|string',
        ]);

        try {
            $service->createAuctionOrder([
                'auction_lot_id' => $this->auction_lot_id,
                'user_id' => $this->user_id,
                'buyer_type' => $this->buyer_type,
                'external_name' => $this->external_name,
                'external_phone' => $this->external_phone,
                'external_email' => $this->external_email,
                'external_address' => $this->external_address,
                'unit_price_inr' => $this->unit_price_inr,
                'payment_method' => $this->payment_method,
                'payment_reference' => $this->payment_reference,
                'paid_at' => $this->paid_at,
                'notes' => $this->notes,
            ], auth()->user());

            $this->showModal = false;
            $this->dispatch('order-created'); // Refresh parent list
            // Also refresh lot details if on that page
            $this->dispatch('auction-lot-updated'); 
            
            session()->flash('success', 'Sale recorded successfully.');
            
        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }

    public function close()
    {
        $this->showModal = false;
    }

    private function resetForm()
    {
        $this->reset([
            'auction_lot_id', 'user_id', 'buyer_type', 
            'external_name', 'external_phone', 'external_email', 'external_address',
            'unit_price_inr', 'notes', 'lotSearch', 'userSearch', 
            'selectedLot', 'selectedUser', 'payment_reference'
        ]);
        $this->paid_at = now()->format('Y-m-d\TH:i');
        $this->payment_method = 'Offline';
        $this->buyer_type = 'registered';
    }
}
