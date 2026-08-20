<?php

namespace App\Livewire\Admin\Archive\Ownership;

use App\Models\Archive\ArchiveProduct;
use App\Models\Order;
use App\Models\User;
use App\Models\UserVaultItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Show extends Component
{
    public $product;
    public $orders;

    // Resale Modal State
    public $showResellModal = false;
    public $resellOrder = null;
    public $resaleQty = 1;
    public $resaleOwnerAskingPriceInr = null;
    public $resaleUnitPriceInr = null;
    public $resaleBuyerType = 'registered';
    public $resaleUserId = null;
    public $resaleUserSearch = '';
    public $showResaleUserDropdown = false;
    public $resaleUserSearchResults = [];
    
    public $resaleExternalName = '';
    public $resaleExternalEmail = '';
    public $resaleExternalPhone = '';
    
    public $resaleFulfillmentMethod = 'delivery';
    public $resaleNotes = '';
    public $canVault = false;

    public function mount($id)
    {
        $this->product = ArchiveProduct::withTrashed()->findOrFail($id);
        
        $this->orders = Order::with(['buyer', 'vaultItem'])
            ->where('source', 'archive')
            ->where('archive_product_id', $id)
            ->where('status', 'completed')
            ->orderBy('sold_at', 'desc')
            ->get();
            
        // Calculate dynamic properties
        $primaryOrders = $this->orders->where('is_resale', false);
        $this->product->total_sold = $primaryOrders->sum('qty');
        $this->product->total_owners = $this->orders->filter(function ($order) {
            return ($order->qty - $order->resold_qty) > 0;
        })->map(function ($order) {
            return $order->user_id ? 'user_' . $order->user_id : 'guest_' . ($order->external_email ?? $order->external_phone);
        })->unique()->count();
    }

    public function openResellModal($orderId)
    {
        $this->resellOrder = Order::with(['buyer'])->findOrFail($orderId);
        $this->resaleQty = 1;
        $this->resaleOwnerAskingPriceInr = $this->resellOrder->unit_price_inr;
        $this->resaleUnitPriceInr = $this->resellOrder->unit_price_inr;
        $this->resaleBuyerType = 'registered';
        $this->resaleUserId = null;
        $this->resaleUserSearch = '';
        $this->resaleExternalName = '';
        $this->resaleExternalEmail = '';
        $this->resaleExternalPhone = '';
        $this->resaleFulfillmentMethod = 'delivery';
        $this->resaleNotes = '';
        $this->canVault = false;
        
        $this->resetValidation();
        $this->showResellModal = true;
    }

    public function closeResellModal()
    {
        $this->showResellModal = false;
        $this->resellOrder = null;
    }

    public function updatingResaleUserSearch()
    {
        if (strlen($this->resaleUserSearch) > 1) {
            $this->resaleUserSearchResults = User::where('name', 'like', '%' . $this->resaleUserSearch . '%')
                ->orWhere('email', 'like', '%' . $this->resaleUserSearch . '%')
                ->take(5)
                ->get();
            $this->showResaleUserDropdown = true;
        } else {
            $this->showResaleUserDropdown = false;
        }
    }

    public function selectResaleUser($id, $name)
    {
        $this->resaleUserId = $id;
        $this->resaleUserSearch = $name;
        $this->showResaleUserDropdown = false;
        
        $user = User::find($id);
        $this->canVault = $user && $user->tier && $user->tier->has_vault_access;
        if (!$this->canVault) {
            $this->resaleFulfillmentMethod = 'delivery';
        }
    }

    public function openResaleUserDropdown()
    {
        if (strlen($this->resaleUserSearch) > 1) {
            $this->showResaleUserDropdown = true;
        }
    }

    public function closeResaleDropdowns()
    {
        $this->showResaleUserDropdown = false;
    }

    public function submitResale()
    {
        $this->resetValidation();

        if (!$this->resellOrder) {
            $this->addError('general', 'No order selected for resale.');
            return;
        }

        $availableQty = $this->resellOrder->qty - $this->resellOrder->resold_qty;
        
        if ($this->resaleQty > $availableQty || $this->resaleQty < 1) {
            $this->addError('resaleQty', 'Invalid quantity.');
            return;
        }

        if (empty($this->resaleOwnerAskingPriceInr) || $this->resaleOwnerAskingPriceInr <= 0) {
            $this->addError('resaleOwnerAskingPriceInr', 'Asking price must be greater than 0.');
            return;
        }

        if (empty($this->resaleUnitPriceInr) || $this->resaleUnitPriceInr <= 0) {
            $this->addError('resaleUnitPriceInr', 'Platform price must be greater than 0.');
            return;
        }

        if ($this->resaleBuyerType === 'registered') {
            if (empty($this->resaleUserId)) {
                $this->addError('resaleUserId', 'Please select a registered user.');
                return;
            }
        } else {
            if (empty($this->resaleExternalName)) {
                $this->addError('resaleExternalName', 'Name is required for external guest.');
                return;
            }
        }

        try {
            DB::beginTransaction();

            // 1. Increment resold_qty on original order
            $this->resellOrder->increment('resold_qty', $this->resaleQty);

            // 2. Adjust original owner's vault if applicable
            $originalVaultItem = UserVaultItem::where('sale_context_type', Order::class)
                ->where('sale_context_id', $this->resellOrder->id)
                ->first();

            if ($originalVaultItem && $originalVaultItem->status === 'locked') {
                if ($originalVaultItem->quantity > $this->resaleQty) {
                    $originalVaultItem->decrement('quantity', $this->resaleQty);
                } else {
                    $originalVaultItem->update(['status' => 'removed', 'quantity' => 0]);
                }
            }

            // 3. Create new Order for the new buyer
            $newOrder = Order::create([
                'source' => 'archive',
                'order_number' => 'RES-' . strtoupper(uniqid()),
                'archive_product_id' => $this->resellOrder->archive_product_id,
                'buyer_type' => $this->resaleBuyerType,
                'user_id' => $this->resaleUserId,
                'external_name' => $this->resaleBuyerType === 'external' ? $this->resaleExternalName : null,
                'external_email' => $this->resaleBuyerType === 'external' ? $this->resaleExternalEmail : null,
                'external_phone' => $this->resaleBuyerType === 'external' ? $this->resaleExternalPhone : null,
                'qty' => $this->resaleQty,
                'owner_asking_price_inr' => $this->resaleOwnerAskingPriceInr,
                'unit_price_inr' => $this->resaleUnitPriceInr,
                'subtotal_inr' => $this->resaleQty * $this->resaleUnitPriceInr,
                'status' => 'completed',
                'sold_at' => now(),
                'logged_by' => auth()->id(),
                'notes' => 'Resale of Order ' . $this->resellOrder->order_number . '. ' . $this->resaleNotes,
                'is_resale' => true,
            ]);

            // 4. Handle Vault for new buyer
            if ($this->resaleBuyerType === 'registered' && $this->resaleFulfillmentMethod === 'vault' && $this->canVault) {
                UserVaultItem::create([
                    'user_id' => $this->resaleUserId,
                    'item_type' => 'archive',
                    'item_id' => $this->resellOrder->archive_product_id,
                    'quantity' => $this->resaleQty,
                    'status' => 'locked',
                    'sale_context_type' => Order::class,
                    'sale_context_id' => $newOrder->id,
                ]);
            }

            DB::commit();

            $this->closeResellModal();
            $this->mount($this->product->id); // Refresh data
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Resale logged successfully!']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('general', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.archive.ownership.show');
    }
}
