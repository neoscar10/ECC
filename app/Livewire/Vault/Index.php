<?php

namespace App\Livewire\Vault;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Vault\VaultAccessResolver;
use App\Services\Membership\MembershipTierResolver;
use App\Services\Membership\MembershipUpgradeService;
use App\Services\Membership\ApplicationWizardService;

#[Layout('layouts.web-app')]
class Index extends Component
{
    public string $vaultViewMode = 'grid';
    
    // Premium Modal State
    public bool $showAccessModal = false;
    public ?array $modalData = null;

    // Address State
    public $addresses = [];
    public $selectedAddressId = null;
    public $showAddressForm = false;
    public $addressForm = [
        'full_name' => '', 'phone' => '', 'line1' => '', 'line2' => '',
        'city' => '', 'state' => '', 'postal_code' => '', 'country' => 'India',
        'label' => 'Home', 'is_default' => false,
    ];

    // Delivery Quote State
    public ?array $deliveryQuote = null;
    public bool $deliveryQuoteLoading = false;
    public ?string $deliveryQuoteError = null;
    public ?int $deliveryRateQuoteId = null;
    public ?float $deliveryFee = null;
    public ?array $selectedDeliveryCourier = null;
    public ?array $deliveryMeasurement = null;

    public function setVaultView(string $mode)
    {
        $this->vaultViewMode = in_array($mode, ['grid', 'list']) ? $mode : 'grid';
    }

    public function mount(VaultAccessResolver $resolver, MembershipTierResolver $tierResolver)
    {
        $user = auth('web')->user();

        if (!$user) {
            $this->redirectRoute('login', navigate: false);
            return;
        }

        $tier = $tierResolver->resolveForUser($user);

        // Calculate Vault Access using the standard resolver
        $access = $resolver->resolveVaultAccess($user, $tier);

        // Trigger upgrade prompt if access is denied via the exact same modal system
        if (!$user->has_vault_access) {
            $this->triggerAccessModal($access, $tierResolver);
        } else {
            $this->loadAddresses($user);
        }
    }

    public function loadAddresses($user)
    {
        $this->addresses = $user->addresses()->latest()->get();
        if ($this->addresses->count() > 0 && is_null($this->selectedAddressId)) {
            $default = $this->addresses->where('is_default', true)->first() ?? $this->addresses->first();
            $this->selectedAddressId = $default->id;
        }
    }

    protected function triggerAccessModal(array $access, MembershipTierResolver $tierResolver)
    {
        $user = auth('web')->user();
        $upgradeSvc = app(MembershipUpgradeService::class);

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
            $this->redirect('/membership/apply-intro');
            return;
        }

        $targetTierModel = $tierResolver->getTierWithDetails($targetTierId);
        
        if (!$targetTierModel) {
            $this->redirect('/membership/apply-intro');
            return;
        }

        // Fetch prorated quote so the modal can show the real payable amount
        $quote = $user ? $upgradeSvc->getUpgradeQuote($user, $targetTierId) : null;
        $unusedCredit  = $quote['unused_credit'] ?? 0.0;
        $payableAmount = $quote['payable_amount'] ?? (float)$targetTierModel->price;
        $isProrated    = $unusedCredit > 0;

        $this->modalData = [
            'tier_id'           => $targetTierModel->id,
            'tier_name'         => $targetTierModel->name,
            'price_formatted'   => $targetTierModel->price > 0 ? 'INR ' . number_format($targetTierModel->price) : 'Free',
            'duration_label'    => 'Year',
            'icon'              => \App\Support\Archive\AccessIconNormalizer::normalize($access['reason'] ?? 'vault_access_required', $access['view_mode'] ?? 'blocked'),
            'privileges'        => $targetTierModel->privileges->toArray(),
            'features'          => $targetTierModel->features->toArray(),
            'product_title'     => 'The Vault',
            // Prorated quote fields
            'is_prorated'       => $isProrated,
            'unused_credit'     => $unusedCredit,
            'payable_amount'    => $payableAmount,
            'payable_formatted' => 'INR ' . number_format($payableAmount, 2),
            'credit_formatted'  => 'INR ' . number_format($unusedCredit, 2),
        ];

        $this->showAccessModal = true;
    }

    public function closeAccessModal(): void
    {
        $this->showAccessModal = false;
        $this->modalData = null;

        if (!auth('web')->user()?->has_vault_access) {
            $prev = url()->previous();
            $redirect = ($prev && $prev !== url('/vault')) ? $prev : '/home';
            $this->redirect($redirect, navigate: false);
        }
    }

    public ?array $selectedArtifact = null;
    public $removalMessage = '';
    public bool $showRemovalModal = false;

    public function selectArtifact(int $id)
    {
        $user = auth('web')->user();
        $item = $user->vaultItems()->with(['latestDeliveryRequest.shippingShipment.events', 'pendingRemovalRequest'])->find($id);
        
        if (!$item) return;

        $img = 'https://placehold.co/800x600/17130b/d4af37?text=Secured+Asset';
        $img = $item->display_image_url ?? $img;

        $latestReq = $item->latestDeliveryRequest;
        $presenter = app(\App\Services\Shipping\ShipmentTrackingPresenter::class);
        $trackingData = $presenter->forVaultDeliveryRequest($latestReq);

        $this->selectedArtifact = [
            'id' => $item->id,
            'title' => $item->item_title ?? 'Secured Asset',
            'description' => $item->notes ?? '',
            'image_url' => $img,
            'status_badge_label' => strtoupper($item->status ?? 'LOCKED'),
            'reference_label' => $item->item_ref ?? null,
            'quantity' => $item->quantity ?? 1,
            'unit_price' => $item->unit_price ?? $item->price,
            'total_value' => $item->total_value,
            'currency' => $item->currency ?? 'INR',
            'locked_at_human' => $item->locked_at ? $item->locked_at->format('d M Y') : 'N/A',
            'has_pending_request' => $item->pendingRemovalRequest()->exists(),
            'tracking' => $trackingData,
        ];
    }

    public function closeArtifactModal()
    {
        $this->selectedArtifact = null;
        $this->removalMessage = '';
        $this->showRemovalModal = false;
        $this->showAddressForm = false;
        
        // Reset quote state
        $this->deliveryQuote = null;
        $this->deliveryQuoteLoading = false;
        $this->deliveryQuoteError = null;
        $this->deliveryRateQuoteId = null;
        $this->deliveryFee = null;
        $this->selectedDeliveryCourier = null;
        $this->deliveryMeasurement = null;

        $this->resetValidation();
    }

    public function openRemovalModal()
    {
        if (!$this->selectedArtifact) return;
        $this->showRemovalModal = true;
        
        // Reset quote state
        $this->deliveryQuote = null;
        $this->deliveryQuoteLoading = false;
        $this->deliveryQuoteError = null;
        $this->deliveryRateQuoteId = null;
        $this->deliveryFee = null;
        $this->selectedDeliveryCourier = null;
        $this->deliveryMeasurement = null;

        if(empty($this->addresses) && auth('web')->check()) {
            $this->showAddressForm = true;
        }

        // Auto-calculate if we have a selected address
        if ($this->selectedAddressId) {
            $this->calculateDeliveryQuote();
        }
    }

    public function toggleAddressForm()
    {
        $this->showAddressForm = !$this->showAddressForm;
        
        // Reset quote state
        $this->deliveryQuote = null;
        $this->deliveryQuoteLoading = false;
        $this->deliveryQuoteError = null;
        $this->deliveryRateQuoteId = null;
        $this->deliveryFee = null;
        $this->selectedDeliveryCourier = null;
        $this->deliveryMeasurement = null;

        if($this->showAddressForm) {
            $this->selectedAddressId = null;
        } else {
            if ($this->addresses->count() > 0) {
                $this->selectedAddressId = $this->addresses->first()->id;
            }
        }

        // Auto-calculate if switching back to address book with an address selected
        if (!$this->showAddressForm && $this->selectedAddressId) {
            $this->calculateDeliveryQuote();
        }
    }

    /**
     * Listen to Livewire property updates for reactive quote calculations.
     */
    public function updated($name)
    {
        if ($name === 'selectedAddressId') {
            $this->calculateDeliveryQuote();
        }
        if ($name === 'addressForm.postal_code') {
            $this->calculateDeliveryQuote();
        }
    }

    /**
     * Calculate delivery quote for the selected vault item and destination.
     */
    public function calculateDeliveryQuote(): void
    {
        $this->deliveryQuote = null;
        $this->deliveryQuoteLoading = true;
        $this->deliveryQuoteError = null;
        $this->deliveryRateQuoteId = null;
        $this->deliveryFee = null;
        $this->selectedDeliveryCourier = null;
        $this->deliveryMeasurement = null;

        if (!$this->selectedArtifact) {
            $this->deliveryQuoteLoading = false;
            return;
        }

        $user = auth('web')->user();
        if (!$user) {
            $this->deliveryQuoteLoading = false;
            return;
        }

        $vaultItem = $user->vaultItems()->find($this->selectedArtifact['id']);
        if (!$vaultItem) {
            $this->deliveryQuoteError = 'Selected vault item is invalid.';
            $this->deliveryQuoteLoading = false;
            return;
        }

        $postalCode = null;
        $address = null;

        if ($this->showAddressForm) {
            $postalCode = $this->addressForm['postal_code'] ?? null;
        } else {
            if ($this->selectedAddressId) {
                $address = $user->addresses()->find($this->selectedAddressId);
                if ($address) {
                    $postalCode = $address->postal_code;
                }
            }
        }

        if (empty($postalCode)) {
            $this->deliveryQuoteLoading = false;
            if (!$this->showAddressForm) {
                $this->deliveryQuoteError = 'Please select a delivery address.';
            }
            return;
        }

        try {
            $quoteService = app(\App\Services\Shipping\VaultDeliveryQuoteService::class);
            
            if ($address) {
                $result = $quoteService->quoteForVaultItem($vaultItem, $address, $user);
            } else {
                $result = $quoteService->quoteForVaultItemAndPincode($vaultItem, $postalCode, $user);
            }

            if ($result['success'] ?? false) {
                $this->deliveryQuote = $result;
                $this->deliveryFee = (float) $result['delivery_fee'];
                $this->selectedDeliveryCourier = $result['selected_courier'];
                $this->deliveryMeasurement = $result['measurement'];
                $this->deliveryRateQuoteId = $result['rate_quote_id'];
            } else {
                $this->deliveryQuoteError = $result['message'] ?? 'Delivery is not available for this address.';
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Livewire calculateDeliveryQuote failed', [
                'vault_item_id' => $vaultItem->id,
                'error' => $e->getMessage()
            ]);
            $this->deliveryQuoteError = 'Unable to calculate delivery fee at this time.';
        } finally {
            $this->deliveryQuoteLoading = false;
        }
    }

    /**
     * Determine if physical delivery request can be submitted.
     */
    public function canSubmitDeliveryRequest(): bool
    {
        if (!$this->selectedArtifact) {
            return false;
        }

        if ($this->showAddressForm) {
            if (empty($this->addressForm['full_name']) ||
                empty($this->addressForm['phone']) ||
                empty($this->addressForm['line1']) ||
                empty($this->addressForm['city']) ||
                empty($this->addressForm['state']) ||
                empty($this->addressForm['postal_code'])) {
                return false;
            }
        } else {
            if (!$this->selectedAddressId) {
                return false;
            }
        }

        return $this->deliveryQuote !== null &&
               ($this->deliveryQuote['success'] ?? false) === true &&
               $this->deliveryRateQuoteId !== null &&
               $this->deliveryFee !== null &&
               $this->selectedDeliveryCourier !== null &&
               $this->deliveryQuoteError === null &&
               !$this->deliveryQuoteLoading;
    }

    public function submitRemovalRequest(\App\Services\VaultService $service)
    {
        if (!$this->selectedArtifact) return;

        if (!$this->canSubmitDeliveryRequest()) {
            $this->addError('deliveryQuote', 'Please select a valid delivery address with a supported quote.');
            return;
        }

        $user = auth('web')->user();
        $item = $user->vaultItems()->find($this->selectedArtifact['id']);

        if (!$item) return;

        try {
            $quoteData = null;
            if ($this->deliveryQuote) {
                $quoteData = [
                    'delivery_fee' => $this->deliveryFee,
                    'delivery_currency' => $this->deliveryQuote['currency'] ?? 'INR',
                    'shipping_rate_quote_id' => $this->deliveryRateQuoteId,
                    'selected_courier_company_id' => $this->selectedDeliveryCourier['courier_company_id'] ?? null,
                    'selected_courier_name' => $this->selectedDeliveryCourier['courier_name'] ?? null,
                    'package_weight_kg' => $this->deliveryMeasurement['weight_kg'] ?? null,
                    'package_length_cm' => $this->deliveryMeasurement['length_cm'] ?? null,
                    'package_breadth_cm' => $this->deliveryMeasurement['breadth_cm'] ?? null,
                    'package_height_cm' => $this->deliveryMeasurement['height_cm'] ?? null,
                    'payment_status' => \App\Models\VaultRemovalRequest::PAYMENT_NONE,
                ];
            }

            if ($this->showAddressForm) {
                $this->validate([
                    'addressForm.full_name' => 'required|string|max:255',
                    'addressForm.phone' => 'required|string|max:20',
                    'addressForm.line1' => 'required|string|max:255',
                    'addressForm.city' => 'required|string|max:100',
                    'addressForm.state' => 'required|string|max:100',
                    'addressForm.postal_code' => 'required|string|max:20',
                    'addressForm.country' => 'required|string|max:100',
                ]);
                $service->requestRemoval($item, $user, $this->removalMessage, null, $this->addressForm, $quoteData);
            } else {
                $service->requestRemoval($item, $user, $this->removalMessage, $this->selectedAddressId, null, $quoteData);
            }

            session()->flash('success', 'Physical delivery request submitted successfully. Our team will review it shortly.');
            $this->closeArtifactModal();
            $this->loadAddresses($user); // Refresh addresses in case a new one was added
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function proceedToSubscribe(ApplicationWizardService $wiz)
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

    public function render(VaultAccessResolver $resolver, MembershipTierResolver $tierResolver, \App\Services\VaultService $vaultService)
    {
        $user = auth('web')->user();
        
        if (!$user || !$user->has_vault_access) {
            // Render the locked/empty state underlying the modal
             return view('livewire.vault.index', [
                'vaultTierLabel' => 'RESTRICTED',
                'vaultAccessLabel' => 'ACCESS DENIED',
                'vaultVerificationLabel' => 'Membership Upgrade Required',
                'vaultIntroText' => 'Your digital stronghold for authenticated assets and secured certificates of provenance.',
                'vaultProtocolVersion' => 'V4.2',
                'vaultSecurityItems' => [],
                'vaultSummary' => [
                    'total_items_count' => 0,
                    'total_value' => 0,
                    'pending_requests_count' => 0
                ],
                'mappedArtifacts' => [],
                'vaultArtifactCount' => 0,
                'supportsVaultViewToggle' => false,
             ])->layout('layouts.web-app', ['title' => 'The Vault', 'activeNav' => 'archive']);
        }

        $tier = $tierResolver->resolveForUser($user);
        $vaultSummary = $vaultService->getVaultSummary($user);

        // Fetch user's secured items
        $vaultArtifacts = $user->vaultItems()
            ->with(['latestDeliveryRequest.shippingShipment.events', 'pendingRemovalRequest'])
            ->locked()
            ->orderBy('locked_at', 'desc')
            ->get();
        
        $mappedArtifacts = $vaultArtifacts->map(function($item) {
            $img = 'https://placehold.co/800x600/17130b/d4af37?text=Secured+Asset';
            $img = $item->display_image_url ?? $img;

            $latestReq = $item->latestDeliveryRequest;
            $deliveryBadgeLabel = null;
            $deliveryBadgeClass = null;

            if ($latestReq) {
                $reqStatus = $latestReq->status;
                $payStatus = $latestReq->payment_status;
                $shipmentStatus = $latestReq->shippingShipment?->status;

                if ($reqStatus === 'pending' && $payStatus === 'pending_payment') {
                    $deliveryBadgeLabel = 'Payment Pending';
                    $deliveryBadgeClass = 'bg-warning-subtle text-warning border-warning-subtle';
                } elseif ($reqStatus === 'pending' && $payStatus === 'paid') {
                    $deliveryBadgeLabel = 'Pending Review';
                    $deliveryBadgeClass = 'bg-info-subtle text-info border-info-subtle';
                } elseif ($reqStatus === 'approved' && $payStatus === 'paid') {
                    if ($shipmentStatus) {
                        $pres = app(\App\Services\Shipping\ShipmentTrackingPresenter::class);
                        $shipmentData = $pres->forCustomer($latestReq->shippingShipment);
                        $deliveryBadgeLabel = $shipmentData['status_label'] ?? 'Approved';
                        $deliveryBadgeClass = $shipmentData['status_badge_class'] ?? 'bg-success-subtle text-success border-success-subtle';
                    } else {
                        $deliveryBadgeLabel = 'Approved';
                        $deliveryBadgeClass = 'bg-success-subtle text-success border-success-subtle';
                    }
                } elseif ($reqStatus === 'rejected' && $payStatus === 'refund_required') {
                    $deliveryBadgeLabel = 'Refund Required';
                    $deliveryBadgeClass = 'bg-danger text-white border-danger';
                } elseif ($reqStatus === 'completed') {
                    $deliveryBadgeLabel = 'Delivered';
                    $deliveryBadgeClass = 'bg-success text-white border-success';
                } elseif ($reqStatus === 'rejected') {
                    $deliveryBadgeLabel = 'Rejected';
                    $deliveryBadgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                }
            }

            return (object) [
                'id' => $item->id,
                'title' => $item->item_title ?? 'Secured Asset',
                'description' => $item->notes ?? '',
                'image_url' => $img,
                'status_badge_label' => strtoupper($item->status ?? 'LOCKED'),
                'reference_label' => $item->item_ref ?? null,
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_price ?? $item->price,
                'total_value' => $item->total_value,
                'currency' => $item->currency ?? 'INR',
                'locked_at_human' => $item->locked_at ? $item->locked_at->format('d M Y') : 'N/A',
                'has_pending_request' => (bool) $item->pendingRemovalRequest,
                'delivery_badge_label' => $deliveryBadgeLabel,
                'delivery_badge_class' => $deliveryBadgeClass,
            ];
        });

        $vaultSecurityItems = [
            [
                'icon' => 'mdi mdi-shield-lock-outline',
                'title' => 'End-to-End Encryption',
                'description' => 'Military-grade encryption securing your digital certificates.'
            ],
            [
                'icon' => 'mdi mdi-fingerprint',
                'title' => 'Immutable Provenance',
                'description' => 'Cryptographically verified ownership records.'
            ],
            [
                'icon' => 'mdi mdi-bank-outline',
                'title' => 'Physical Vaulting',
                'description' => 'Corresponding physical assets stored in secure climate-controlled facilities.'
            ]
        ];

        return view('livewire.vault.index', [
            'vaultTierLabel' => $tier ? $tier->name : 'No Tier',
            'vaultAccessLabel' => 'VAULT ACCESS: GRANTED',
            'vaultVerificationLabel' => 'Fully Encrypted & Authenticated',
            'vaultIntroText' => 'Your digital stronghold for authenticated assets and secured certificates of provenance.',
            'vaultProtocolVersion' => 'V4.2',
            'vaultSecurityItems' => $vaultSecurityItems,
            'insuredValueLabel' => null, 
            'policyStatusLabel' => null,
            'vaultArtifactCount' => $vaultArtifacts->count(),
            'mappedArtifacts' => $mappedArtifacts,
            'vaultSummary' => $vaultSummary,
            'supportsVaultViewToggle' => true,
        ])->layout('layouts.web-app', [
            'title' => 'The Vault',
            'activeNav' => 'archive'
        ]);
    }
}
