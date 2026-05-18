<?php
 
namespace App\Livewire\Admin\Vault;
 
use App\Models\VaultRemovalRequest;
use App\Services\VaultService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
 
class RemovalRequests extends Component
{
    use WithPagination;
 
    public $search = '';
    public $statusFilter = 'pending';
    public $selectedRequestId = null;
 
    protected $paginationTheme = 'bootstrap';
 
    public function updatingSearch()
    {
        $this->resetPage();
    }
 
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
 
    public function showDetails(int $id)
    {
        $this->selectedRequestId = $id;
        $this->dispatch('open-details-modal');
    }
 
    public function closeDetails()
    {
        $this->selectedRequestId = null;
        $this->dispatch('close-details-modal');
    }
 
    public function approveRequest(int $id, VaultService $service, ?string $note = null)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($id);
            $service->approveRemoval($request, auth()->user(), $note);
            session()->flash('success', 'Request approved. It is now ready for fulfillment.');
            
            // Refresh details if currently open
            if ($this->selectedRequestId === $id) {
                $this->selectedRequestId = $id;
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
 
    public function rejectRequest(int $id, string $note, VaultService $service)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($id);
            $service->rejectRemoval($request, auth()->user(), $note);
            session()->flash('success', 'Request rejected.');
            
            // Refresh details if currently open
            if ($this->selectedRequestId === $id) {
                $this->selectedRequestId = $id;
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
 
    public function markRefundHandled(int $id, string $refundReference, ?string $note, VaultService $service)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($id);
            $service->markRefundHandled($request, auth()->user(), $refundReference, $note);
            session()->flash('success', 'Refund marked as handled successfully.');
            
            // Refresh details if currently open
            if ($this->selectedRequestId === $id) {
                $this->selectedRequestId = $id;
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
 
    public function completeRequest(int $id, VaultService $service)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($id);
            $service->completeRemoval($request, auth()->user());
            session()->flash('success', 'Request completed. Item has been released from the vault.');
            
            // Refresh details if currently open
            if ($this->selectedRequestId === $id) {
                $this->selectedRequestId = $id;
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function initiateShipment(int $requestId, \App\Services\Shipping\ShipmentService $shipmentService, \App\Services\Shipping\Shiprocket\ShiprocketOrderService $shiprocketService)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($requestId);

            if (!$request->isReadyForFulfillment()) {
                throw new \Exception("Only paid and approved delivery requests can be fulfilled.");
            }

            if (!$request->vaultItem || $request->vaultItem->status !== 'locked') {
                throw new \Exception("Vault item is already removed or invalid.");
            }

            if (empty($request->selected_courier_company_id)) {
                throw new \Exception("Selected courier is missing. Refresh/recalculate delivery quote.");
            }

            if (empty($request->package_weight_kg)) {
                throw new \Exception("Package dimensions and weight are missing.");
            }

            if (empty($request->delivery_postal_code)) {
                throw new \Exception("Delivery pincode is missing.");
            }

            // Get or create ShippingShipment polymorphically linked to the VaultRemovalRequest
            $shipment = $shipmentService->getOrCreateForVaultRequest($request);

            // Initiate shipment via ShiprocketOrderService
            $shiprocketService->initiateShipment($shipment);

            $msg = config('shiprocket.test_mode') ? 'Vault shipment simulated successfully.' : 'Vault shipment initiated successfully.';
            session()->flash('success', $msg);
            
            // Refresh details if currently open
            if ($this->selectedRequestId === $requestId) {
                $this->selectedRequestId = $requestId;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to initiate shipment: ' . $e->getMessage());
        }
    }

    public function retryAssignAwb(int $requestId, \App\Services\Shipping\Shiprocket\ShiprocketOrderService $shiprocketService)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($requestId);
            $shipment = $request->shippingShipment;
            if (!$shipment) {
                throw new \Exception("No shipment found for this request.");
            }
            $shiprocketService->retryAssignAwb($shipment);

            $msg = config('shiprocket.test_mode') ? 'Test AWB assigned successfully.' : 'AWB assigned successfully.';
            session()->flash('success', $msg);

            // Refresh details if currently open
            if ($this->selectedRequestId === $requestId) {
                $this->selectedRequestId = $requestId;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to assign AWB: ' . $e->getMessage());
        }
    }

    public function generateDocument(int $requestId, string $type, \App\Services\Shipping\Shiprocket\ShiprocketOrderService $shiprocketService)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($requestId);
            $shipment = $request->shippingShipment;
            if (!$shipment) {
                throw new \Exception("No shipment found for this request.");
            }
            switch ($type) {
                case 'label':
                    $shiprocketService->generateLabel($shipment);
                    break;
                case 'invoice':
                    $shiprocketService->generateInvoice($shipment);
                    break;
                case 'manifest':
                    $shiprocketService->generateManifest($shipment);
                    break;
                default:
                    throw new \Exception("Unknown document type.");
            }

            $msg = config('shiprocket.test_mode') ? ucfirst($type) . ' generated in test mode.' : ucfirst($type) . ' generated successfully.';
            session()->flash('success', $msg);

            // Refresh details if currently open
            if ($this->selectedRequestId === $requestId) {
                $this->selectedRequestId = $requestId;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to generate ' . $type . ': ' . $e->getMessage());
        }
    }

    public function refreshTracking(int $requestId, \App\Services\Shipping\Shiprocket\ShiprocketOrderService $shiprocketService)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($requestId);
            $shipment = $request->shippingShipment;
            if (!$shipment) {
                throw new \Exception("No shipment found.");
            }
            $shiprocketService->refreshTracking($shipment);

            $msg = config('shiprocket.test_mode') ? 'Tracking refreshed in test mode.' : 'Tracking refreshed successfully.';
            session()->flash('success', $msg);

            // Refresh details if currently open
            if ($this->selectedRequestId === $requestId) {
                $this->selectedRequestId = $requestId;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to refresh tracking: ' . $e->getMessage());
        }
    }

    public function completeDelivery(int $requestId, VaultService $service)
    {
        try {
            $request = VaultRemovalRequest::findOrFail($requestId);
            $shipment = $request->shippingShipment;

            if (!$request->isReadyForFulfillment()) {
                throw new \Exception("Request is not ready for completion.");
            }

            if (!$shipment) {
                throw new \Exception("Fulfillment shipment has not been initiated yet.");
            }

            $service->completeRemoval($request, auth()->user());
            session()->flash('success', 'Fulfillment completed. Vault item has been released.');

            // Refresh details if currently open
            if ($this->selectedRequestId === $requestId) {
                $this->selectedRequestId = $requestId;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to complete delivery: ' . $e->getMessage());
        }
    }
 
    #[Layout('layouts.admin')]
    public function render()
    {
        $query = VaultRemovalRequest::query()
            ->with(['user', 'vaultItem', 'address', 'shippingShipment'])
            ->latest('requested_at');
 
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                })->orWhereHas('vaultItem', function ($ivq) {
                    $ivq->where('item_title', 'like', '%' . $this->search . '%')
                        ->orWhere('item_ref', 'like', '%' . $this->search . '%');
                });
            });
        }
 
        if ($this->statusFilter) {
            if ($this->statusFilter === 'pending_review') {
                $query->where('status', 'pending')->where('payment_status', 'paid');
            } elseif ($this->statusFilter === 'ready_for_fulfillment') {
                $query->where('status', 'approved')->where('payment_status', 'paid');
            } elseif ($this->statusFilter === 'refund_required') {
                $query->where('payment_status', 'refund_required');
            } else {
                $query->where('status', $this->statusFilter);
            }
        }
 
        $requests = $query->paginate(15);
        $selectedRequest = $this->selectedRequestId 
            ? VaultRemovalRequest::with(['user', 'vaultItem', 'address', 'shippingShipment'])->find($this->selectedRequestId) 
            : null;
 
        return view('livewire.admin.vault.removal-requests', [
            'requests' => $requests,
            'selectedRequest' => $selectedRequest
        ]);
    }
}
