<?php

namespace App\Livewire\Shop;

use App\Models\Shop\UserAddress;
use App\Services\Shop\CartService;
use App\Services\Shop\CheckoutService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\Url;

class CheckoutPage extends Component
{
    #[Url(as: 'vault_request_id')]
    public $vaultRequestId = null;
    
    public $isVaultDelivery = false;

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

    public $paymentGateway = null;

    // Razorpay is the sole payment method; no mock card state needed.

    public $summary = [];
    public $summaryItems = [];

    // Shipping Quote State
    public $shippingError = null;
    public $shippingCourierName = null;
    public $shippingEtd = null;
    public $canPlaceOrder = false;

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
        $this->paymentGateway = config('payments.default_gateway', 'razorpay');
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $this->addresses = $user->addresses()->latest()->get();
        if (!$this->vaultRequestId && $this->addresses->count() > 0 && is_null($this->selectedAddressId)) {
            $default = $this->addresses->where('is_default', true)->first() ?? $this->addresses->first();
            $this->selectedAddressId = $default->id;
        }

        if ($this->vaultRequestId) {
            $this->isVaultDelivery = true;
            $request = \App\Models\VaultRemovalRequest::with('vaultItem')->find($this->vaultRequestId);
            
            if (!$request || $request->user_id !== $user->id || !in_array($request->payment_status, ['pending_payment', 'payment_failed'])) {
                session()->flash('error', 'Invalid or expired vault delivery request.');
                return redirect()->route('vault.index');
            }

            if ($request->address_id) {
                $this->selectedAddressId = $request->address_id;
            }

            $shippingFee = (float) $request->delivery_fee;
            
            $this->summary = [
                'subtotal' => 0.0,
                'shipping_fee' => $shippingFee,
                'tax_amount' => 0.0,
                'discount_amount' => 0.0,
                'total_amount' => $shippingFee,
                'formatted_subtotal' => '₹0.00',
                'formatted_shipping' => '₹' . number_format($shippingFee, 2),
                'formatted_shipping_class' => '',
                'formatted_tax' => '₹0.00',
                'formatted_discount' => '₹0.00',
                'formatted_total' => '₹' . number_format($shippingFee, 2),
            ];

            $img = $request->vaultItem->display_image_url ?? 'https://placehold.co/100x100/17130b/d4af37?text=Secured+Asset';

            $this->summaryItems = collect([
                (object) [
                    'title' => $request->vaultItem->item_title ?? 'Secured Asset',
                    'quantity' => 1,
                    'image_url' => $img,
                    'formatted_total' => '₹' . number_format($shippingFee, 2),
                    'meta' => 'Physical Delivery Fee',
                ]
            ]);

            $this->canPlaceOrder = true;
            $this->shippingCourierName = $request->selected_courier_name ?? null;
            $this->shippingEtd = null;
        } else {
            $checkoutService = app(CheckoutService::class);
            try {
                $summaryData = $checkoutService->getCheckoutSummary($user, $this->selectedAddressId);
                
                if (empty($summaryData['items'])) {
                    return redirect()->route('shop.cart');
                }

                // Shipping state
                $this->shippingError = $summaryData['shipping_error'] ?? null;
                $this->canPlaceOrder = $summaryData['can_place_order'] ?? false;

                // Determine shipping display text
                $shippingFee = $summaryData['shipping_fee'];
                if (!$this->selectedAddressId) {
                    $formattedShipping = 'Select address';
                    $shippingDisplayClass = 'ecc-muted';
                } elseif ($this->shippingError) {
                    $formattedShipping = 'Unavailable';
                    $shippingDisplayClass = 'text-danger';
                } elseif ($shippingFee > 0) {
                    $formattedShipping = '₹' . number_format($shippingFee, 2);
                    $shippingDisplayClass = '';
                } else {
                    $formattedShipping = 'FREE';
                    $shippingDisplayClass = 'ecc-text-gold';
                }

                // Courier info from quote
                $shippingQuote = $summaryData['shipping_quote'] ?? null;
                $this->shippingCourierName = $shippingQuote['selected_courier']['courier_name'] ?? null;
                $this->shippingEtd = $shippingQuote['selected_courier']['etd'] ?? null;

                $this->summary = [
                    'subtotal' => $summaryData['subtotal'],
                    'shipping_fee' => $shippingFee,
                    'tax_amount' => $summaryData['tax_amount'],
                    'discount_amount' => $summaryData['discount_amount'],
                    'total_amount' => $summaryData['total_amount'],
                    'formatted_subtotal' => '₹' . number_format($summaryData['subtotal'], 2),
                    'formatted_shipping' => $formattedShipping,
                    'formatted_shipping_class' => $shippingDisplayClass,
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
        }
    }
    // Note: Payment method selection is handled by Razorpay Checkout popup.

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


    public function placeOrder()
    {
        if (!$this->selectedAddressId) {
            session()->flash('error', 'Please select a shipping address.');
            return;
        }

        try {
            $availabilityService = app(\App\Services\Payments\PaymentGatewayAvailabilityService::class);
            $gatewayName = $availabilityService->validateGateway($this->paymentGateway);
        } catch (\App\Exceptions\PaymentGatewayValidationException $e) {
            session()->flash('error', $e->getMessage());
            return;
        }

        $user = Auth::user();

        if ($this->isVaultDelivery) {
            $request = \App\Models\VaultRemovalRequest::find($this->vaultRequestId);
            
            if ($request && in_array($request->payment_status, [\App\Models\VaultRemovalRequest::PAYMENT_PENDING, \App\Models\VaultRemovalRequest::PAYMENT_FAILED])) {
                $request->update([
                    'address_id' => $this->selectedAddressId, // Update address if they changed it
                ]);

                $paymentManager = app(\App\Services\Payments\PaymentManager::class);

                $paymentInitiation = $paymentManager->initiatePayment(
                    payable: $request,
                    amount: $request->delivery_fee,
                    purpose: \App\Support\Payments\PaymentPurpose::VAULT_DELIVERY,
                    user: $user,
                    gateway: $gatewayName
                );

                return redirect()->route('payments.pay', $paymentInitiation['payment']->id);
            }
            
            session()->flash('error', 'Unable to process vault payment.');
            return;
        }

        $checkoutService = app(CheckoutService::class);
        $paymentManager = app(\App\Services\Payments\PaymentManager::class);

        try {
            Log::info('Checkout: Place order started.', [
                'user_id' => $user->id,
                'address_id' => $this->selectedAddressId,
            ]);

            // 1. Create Order (Atomic: Stock deduction + Order creation + Cart clearing)
            $order = $checkoutService->placeOrder($user, [
                'shipping_address_id' => $this->selectedAddressId,
                'billing_same_as_shipping' => true,
                'notes' => 'Placed via Web Storefront',
            ], null);

            Log::info('Checkout: Order created, initiating payment.', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $order->total_amount,
            ]);

            // 2. Initiate Payment via PaymentManager
            $paymentInitiation = $paymentManager->initiatePayment(
                payable: $order,
                amount: $order->total_amount,
                purpose: 'shop_order',
                user: $user,
                gateway: $gatewayName,
                context: [
                    'description' => 'ECC Shop Order #' . $order->order_number,
                ]
            );

            $payment = $paymentInitiation['payment'];

            Log::info('Checkout: Payment initiated, redirecting to pay page.', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'gateway_order_id' => $payment->gateway_order_id,
                'amount' => $payment->amount,
            ]);

            // 3. Redirect to payment page
            return redirect()->route('payments.pay', $payment->id);

        } catch (Exception $e) {
            Log::error('Checkout: Place order failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Order is NOT created if exception is thrown before placeOrder() completes.
            session()->flash('error', 'Checkout failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.shop.checkout')
            ->layout('layouts.web-app', ['title' => 'Checkout']);
    }
}
