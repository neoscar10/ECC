<?php

namespace App\Livewire\Shop;

use App\Models\Shop\UserAddress;
use App\Services\Shop\CartService;
use App\Services\Shop\CheckoutService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class CheckoutPage extends Component
{
    public $addresses = [];
    public $selectedAddressId = null;
    
    // Address Form
    public $showAddressForm = false;
    public $addressForm = [
        'full_name' => '',
        'phone' => '',
        'line1' => '',
        'line2' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'country' => 'India',
        'label' => 'Home',
        'is_default' => false,
    ];

    public $selectedPaymentMethod = 'card_mock_1';
    public $savedPaymentMethods = [];
    public $walletOptions = [];

    public $summary = [];
    public $summaryItems = [];

    protected $rules = [
        'addressForm.full_name' => 'required|string|max:255',
        'addressForm.phone' => 'required|string|max:20',
        'addressForm.line1' => 'required|string|max:255',
        'addressForm.line2' => 'nullable|string|max:255',
        'addressForm.city' => 'required|string|max:100',
        'addressForm.state' => 'required|string|max:100',
        'addressForm.postal_code' => 'required|string|max:20',
        'addressForm.country' => 'required|string|max:100',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $this->addresses = $user->addresses()->latest()->get();
        if ($this->addresses->count() > 0 && is_null($this->selectedAddressId)) {
            $default = $this->addresses->where('is_default', true)->first() ?? $this->addresses->first();
            $this->selectedAddressId = $default->id;
        }

        $checkoutService = app(CheckoutService::class);
        try {
            $summaryData = $checkoutService->getCheckoutSummary($user, $this->selectedAddressId);
            
            if (empty($summaryData['items'])) {
                return redirect()->route('shop.cart');
            }

            $this->summary = [
                'subtotal' => $summaryData['subtotal'],
                'shipping_fee' => $summaryData['shipping_fee'],
                'tax_amount' => $summaryData['tax_amount'],
                'discount_amount' => $summaryData['discount_amount'],
                'total_amount' => $summaryData['total_amount'],
                'formatted_subtotal' => '₹' . number_format($summaryData['subtotal'], 2),
                'formatted_shipping' => $summaryData['shipping_fee'] > 0 ? '₹' . number_format($summaryData['shipping_fee'], 2) : 'FREE',
                'formatted_tax' => '₹' . number_format($summaryData['tax_amount'], 2),
                'formatted_discount' => '₹' . number_format($summaryData['discount_amount'], 2),
                'formatted_total' => '₹' . number_format($summaryData['total_amount'], 2),
            ];

            $this->summaryItems = collect($summaryData['items'])->map(function($item) {
                $product = \App\Models\Shop\ShopProduct::find($item['shop_product_id']);
                $img = collect($product->images)->first();
                
                return (object) [
                    'title' => $item['title'],
                    'quantity' => $item['quantity'],
                    'image_url' => $img ? Storage::url($img->image_path) : 'https://placehold.co/100x100/17130b/d4af37?text=No+Image',
                    'formatted_total' => '₹' . number_format($item['line_total'], 2),
                    'meta' => collect($item['variation_values'])->pluck('caption')->implode(' / '),
                ];
            });

        } catch (Exception $e) {
            session()->flash('error', 'Unable to load checkout summary.');
            return redirect()->route('shop.cart');
        }

        // Mock Payment Methods
        $this->savedPaymentMethods = [
            (object) [
                'id' => 'card_mock_1',
                'brand_label' => 'VISA',
                'display_name' => '•••• 4242',
                'expiry_label' => '12/26',
                'is_default' => true,
            ],
            (object) [
                'id' => 'card_mock_2',
                'brand_label' => 'MC',
                'display_name' => '•••• 5555',
                'expiry_label' => '08/25',
                'is_default' => false,
            ],
        ];

        $this->walletOptions = [
            ['label' => 'Apple Pay', 'value' => 'apple_pay', 'icon' => 'mdi mdi-apple'],
            ['label' => 'Google Pay', 'value' => 'google_pay', 'icon' => 'mdi mdi-google'],
        ];
    }

    public function selectAddress($id)
    {
        $this->selectedAddressId = $id;
        $this->loadData();
    }

    public function openAddressForm()
    {
        $this->showAddressForm = true;
    }

    public function saveAddress()
    {
        $this->validate();

        $user = Auth::user();
        
        if ($this->addressForm['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create([
            'label' => $this->addressForm['label'],
            'full_name' => $this->addressForm['full_name'],
            'phone' => $this->addressForm['phone'],
            'line1' => $this->addressForm['line1'],
            'line2' => $this->addressForm['line2'],
            'city' => $this->addressForm['city'],
            'state' => $this->addressForm['state'],
            'postal_code' => $this->addressForm['postal_code'],
            'country' => $this->addressForm['country'],
            'is_default' => $this->addressForm['is_default'],
            'type' => 'shipping',
        ]);

        $this->selectedAddressId = $address->id;
        $this->showAddressForm = false;
        $this->reset('addressForm');
        $this->addressForm['country'] = 'India';
        $this->addressForm['label'] = 'Home';

        $this->loadData();
        session()->flash('success', 'Address added successfully.');
    }

    public function handleAddPaymentMethod()
    {
        // Placeholder
        session()->flash('info', 'Secure payment gateway integration is currently in preview mode.');
    }

    public function placeOrder()
    {
        if (!$this->selectedAddressId) {
            session()->flash('error', 'Please select a shipping address.');
            return;
        }

        $user = Auth::user();
        $checkoutService = app(CheckoutService::class);

        try {
            // 1. Prepare Mock/Dummy Payment Details
            // This mimics the post-success data structure from the tier payment pattern
            $paymentDetails = [
                'gateway' => 'dummy',
                'method' => $this->selectedPaymentMethod,
                'last4' => '4242', // Mocked for the dummy flow
                'brand' => 'Visa',
                'currency' => 'INR',
                'amount' => $this->summary['total_amount'],
                'confirmed_at' => now()->toDateTimeString(),
            ];

            // 2. Finalize Order (Atomic: Stock deduction + Order creation + Cart clearing)
            // This only happens AFTER the mock payment "success" orchestration
            $order = $checkoutService->placeOrder($user, [
                'shipping_address_id' => $this->selectedAddressId,
                'billing_same_as_shipping' => true,
                'notes' => 'Placed via Web Storefront',
            ], $paymentDetails);

            // 3. Clear session/local state if any (Service already clears the database cart)
            
            return redirect()->route('shop.order-success', ['orderId' => $order->id]);

        } catch (Exception $e) {
            // If payment/finalization fails (e.g. stock goes out of sync at last second), 
            // the order is NOT created and the cart is NOT cleared.
            session()->flash('error', 'Checkout failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.shop.checkout')
            ->layout('layouts.web-app', ['title' => 'Checkout']);
    }
}
