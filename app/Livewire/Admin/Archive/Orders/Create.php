<?php

namespace App\Livewire\Admin\Archive\Orders;

use App\Models\Order;
use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\User;
use App\Services\OrderService;
use Livewire\Component;

class Create extends Component
{
    public $showModal = false;

    // Form Data
    public $product_id;
    public $enquiry_id;
    public $user_id; // Buyer User ID
    public $buyer_type = 'registered'; // or 'external'
    
    // External Buyer
    public $external_name;
    public $external_phone;
    public $external_email;
    public $external_address;

    public $qty = 1;
    public $unit_price_inr;
    public $notes;
    public $fulfillment_method = 'delivery'; // delivery, vault
    public $can_vault = false;

    // UI State
    public $productSearch = '';
    public $userSearch = '';
    public $searchResults = [];
    public $userSearchResults = [];
    
    public $selectedProduct = null;

    protected $listeners = ['open-create-order-modal' => 'open', 'log-sale-from-enquiry' => 'openFromEnquiry'];

    public function render()
    {
        return view('livewire.admin.archive.orders.create');
    }

    // Dropdown State
    public $showProductDropdown = false;
    public $showUserDropdown = false;

    public function updatedProductSearch()
    {
        $this->showProductDropdown = true;
        
        $query = ArchiveProduct::where('quantity', '>', 0);
        
        if (!empty($this->productSearch)) {
            $query->where('title', 'like', '%' . $this->productSearch . '%');
        } else {
            $query->latest('created_at'); // Default order
        }
        
        $this->searchResults = $query->take(30)->get();
    }

    public function openProductDropdown()
    {
        $this->showProductDropdown = true;
        if (empty($this->searchResults)) {
            $this->updatedProductSearch();
        }
    }
    
    public function updatedUserSearch()
    {
        $this->showUserDropdown = true;
        
        $query = User::query();
        
        if (!empty($this->userSearch)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->userSearch . '%')
                  ->orWhere('email', 'like', '%' . $this->userSearch . '%');
            });
        } else {
            $query->latest('created_at');
        }
        
        $this->userSearchResults = $query->take(30)->get();
    }

    public function openUserDropdown()
    {
        $this->showUserDropdown = true;
        if (empty($this->userSearchResults)) {
            $this->updatedUserSearch();
        }
    }

    public function closeDropdowns()
    {
        $this->showProductDropdown = false;
        $this->showUserDropdown = false;
    }

    public function selectProduct($id)
    {
        $this->selectedProduct = ArchiveProduct::find($id);
        $this->product_id = $id;
        $this->productSearch = $this->selectedProduct->title;
        $this->searchResults = [];
        $this->showProductDropdown = false;
        
        // Auto-set price if not set or specific logic (e.g. min price)
        if (!$this->unit_price_inr) {
             $this->unit_price_inr = $this->selectedProduct->price_min_amount ?? 0;
        }
    }
    
    public function selectUser($id)
    {
        $user = User::find($id);
        $this->user_id = $id;
        $this->userSearch = $user->name . ' (' . $user->email . ')';
        $this->userSearchResults = [];
        $this->showUserDropdown = false;

        $this->checkVaultAccess($user);
    }

    public function checkVaultAccess($user)
    {
        $this->can_vault = app(\App\Services\VaultService::class)->userHasAccess($user);
        if (!$this->can_vault) {
            $this->fulfillment_method = 'delivery';
        }
    }

    public function open()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openFromEnquiry($enquiryId)
    {
        $this->resetForm();
        $this->showModal = true;
        
        $enquiry = ArchiveProductEnquiry::with('product', 'user')->find($enquiryId);
        
        if ($enquiry) {
            $this->enquiry_id = $enquiry->id;
            
            // Set Product
            if ($enquiry->product) {
                $this->selectProduct($enquiry->product->id);
            }
            
            // Set Buyer
            if ($enquiry->user) {
                $this->buyer_type = 'registered';
                $this->selectUser($enquiry->user->id);
            } else {
                $this->buyer_type = 'external';
                $this->external_name = $enquiry->contact_name;
                $this->external_email = $enquiry->contact_email;
                $this->external_phone = $enquiry->contact_phone;
            }
        }
    }

    public function store(OrderService $service)
    {
        $this->validate([
            'product_id' => 'required|exists:archive_products,id',
            'qty' => 'required|integer|min:1',
            'unit_price_inr' => 'required|numeric|min:0',
            'buyer_type' => 'required|in:registered,external',
            'user_id' => 'required_if:buyer_type,registered|nullable|exists:users,id',
            'external_name' => 'required_if:buyer_type,external|nullable|string',
            'fulfillment_method' => 'required|in:delivery,vault',
        ]);

        if ($this->fulfillment_method === 'vault' && !$this->can_vault) {
             $this->addError('fulfillment_method', "User does not have vault access.");
             return;
        }

        if ($this->selectedProduct && $this->qty > $this->selectedProduct->quantity) {
            $this->addError('qty', "Only {$this->selectedProduct->quantity} items available in stock.");
            return;
        }

        try {
            $service->createArchiveOrder([
                'archive_product_id' => $this->product_id,
                'archive_product_enquiry_id' => $this->enquiry_id,
                'user_id' => $this->user_id,
                'buyer_type' => $this->buyer_type,
                'external_name' => $this->external_name,
                'external_phone' => $this->external_phone,
                'external_email' => $this->external_email,
                'external_address' => $this->external_address,
                'qty' => $this->qty,
                'unit_price_inr' => $this->unit_price_inr,
                'notes' => $this->notes,
                'fulfillment_method' => $this->fulfillment_method,
            ], auth()->user());

            $this->showModal = false;
            $this->dispatch('order-created'); // Refresh parent list
            $this->dispatch('refresh-products'); // Refresh anything else if needed
            $this->dispatch('operation-success', message: 'Order created successfully.');
            session()->flash('success', 'Order created successfully.');
            
            // If redirected from enquiry page, maybe we should redirect back? 
            // For now, staying on page or refresh is fine.
            
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
            'product_id', 'enquiry_id', 'user_id', 'buyer_type', 
            'external_name', 'external_phone', 'external_email', 'external_address',
            'qty', 'unit_price_inr', 'notes', 'productSearch', 'userSearch', 'selectedProduct',
            'showProductDropdown', 'showUserDropdown', 'can_vault'
        ]);
        $this->fulfillment_method = 'delivery';
        $this->qty = 1;
        $this->buyer_type = 'registered';
    }
}
