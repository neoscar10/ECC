<?php

namespace App\Services\Shipping;

use App\Models\Shipping\ShippingShipment;
use Illuminate\Support\Arr;

class ShipmentTrackingPresenter
{
    /**
     * Compile customer-safe tracking info for a vault physical delivery request.
     */
    public function forVaultDeliveryRequest(?\App\Models\VaultRemovalRequest $request): ?array
    {
        if (!$request) {
            return null;
        }

        $shipmentData = null;
        if ($request->shippingShipment) {
            $shipmentData = $this->forCustomer($request->shippingShipment);
        }

        // Build request-level status labels for timeline mapping
        $statusMapping = [
            'pending' => 'Awaiting Admin Review',
            'approved' => 'Approved, Preparing Shipment',
            'rejected' => 'Request Rejected',
            'completed' => 'Delivery Completed',
        ];

        $currentLabel = $statusMapping[$request->status] ?? ucfirst($request->status);

        if ($request->payment_status === 'pending_payment') {
            $currentLabel = 'Awaiting Delivery Payment';
        } elseif ($request->payment_status === 'refund_required') {
            $currentLabel = 'Rejected - Refund Required';
        } elseif ($shipmentData) {
            $currentLabel = $shipmentData['status_label'];
        }

        // Safe address snapshot
        $address = [
            'name' => $request->delivery_name,
            'phone' => $request->delivery_phone,
            'line1' => $request->delivery_line1,
            'line2' => $request->delivery_line2,
            'city' => $request->delivery_city,
            'state' => $request->delivery_state,
            'postal_code' => $request->delivery_postal_code,
            'country' => $request->delivery_country,
        ];

        // Safely map default chronological timeline milestones if shipment events aren't ready yet
        $events = [];
        
        // Milestone 1: Requested
        if ($request->requested_at) {
            $events[] = [
                'status' => 'requested',
                'status_label' => 'Delivery Requested',
                'description' => 'Delivery request submitted to the vault administration.',
                'location' => 'Digital Vault',
                'event_time' => $request->requested_at->toIso8601String(),
            ];
        }

        // Milestone 2: Paid
        if ($request->paid_at) {
            $events[] = [
                'status' => 'paid',
                'status_label' => 'Delivery Fee Paid',
                'description' => 'Payment has been processed successfully.',
                'location' => 'Secure Checkout',
                'event_time' => $request->paid_at->toIso8601String(),
            ];
        }

        // Milestone 3: Rejected / Refund Required / Approved / Completed
        if ($request->status === 'rejected') {
            $desc = 'Request rejected by admin.';
            if ($request->payment_status === 'refund_required') {
                $desc = 'Request rejected after payment. Refund pending administration action.';
            } elseif ($request->payment_status === 'refunded') {
                $desc = 'Request rejected after payment. Refund processed successfully.';
            }
            $events[] = [
                'status' => 'rejected',
                'status_label' => $request->payment_status === 'refund_required' ? 'Refund Required' : 'Request Rejected',
                'description' => $desc,
                'location' => 'Vault Administration',
                'event_time' => ($request->reviewed_at ?? $request->updated_at)->toIso8601String(),
            ];
        } elseif ($request->status === 'approved') {
            $events[] = [
                'status' => 'approved',
                'status_label' => 'Request Approved',
                'description' => 'Vault request approved. Preparing physical dispatch.',
                'location' => 'ECC Vault',
                'event_time' => ($request->reviewed_at ?? $request->updated_at)->toIso8601String(),
            ];
        }

        // If shipment events exist, merge/supersede request milestones with direct shipment tracking events
        if ($shipmentData && !empty($shipmentData['events'])) {
            // Prepend request milestones to shipment tracking events to show a single continuous timeline
            // Shipment events are sorted desc by default, let's keep all sorted chronologically desc
            $shipmentEvents = $shipmentData['events'];
            foreach ($events as $milestone) {
                // Ensure we don't duplicate
                $exists = false;
                foreach ($shipmentEvents as $se) {
                    if ($se['status'] === $milestone['status']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $shipmentEvents[] = $milestone;
                }
            }
            // Sort combined events chronologically desc
            usort($shipmentEvents, function($a, $b) {
                return strcmp($b['event_time'], $a['event_time']);
            });
            $events = $shipmentEvents;
        } else {
            // Sort default milestones desc
            usort($events, function($a, $b) {
                return strcmp($b['event_time'], $a['event_time']);
            });
        }

        return [
            'id' => $request->id,
            'status' => $shipmentData['status'] ?? $request->status,
            'status_label' => $currentLabel,
            'payment_status' => $request->payment_status,
            'payment_status_label' => $request->payment_status_label,
            'delivery_fee' => (float) $request->delivery_fee,
            'delivery_currency' => $request->delivery_currency ?? 'INR',
            'courier_name' => $request->selected_courier_name,
            'address' => $address,
            'awb_code' => $shipmentData['awb_code'] ?? null,
            'tracking_url' => $shipmentData['tracking_url'] ?? null,
            'is_test_mode' => $shipmentData['is_test_mode'] ?? false,
            'initiated_at' => $shipmentData['initiated_at'] ?? null,
            'last_tracked_at' => $shipmentData['last_tracked_at'] ?? null,
            'events' => $events,
        ];
    }

    /**
     * Expose shipment and tracking info safely to the customer/web/API.
     * Returns null if no shipment exists.
     */
    public function forCustomer(?ShippingShipment $shipment): ?array
    {
        if (!$shipment) {
            return null;
        }

        $status = strtolower($shipment->status);
        $statusMapping = $this->getStatusMapping($status);

        $events = $shipment->events ? $shipment->events->sortByDesc('event_time')->take(10)->map(function ($event) {
            $eventStatus = strtolower($event->event_code ?? $event->event_status);
            $mapping = $this->getStatusMapping($eventStatus);
            
            return [
                'status' => $eventStatus,
                'status_label' => $mapping['label'] !== 'Tracking Update' ? $mapping['label'] : ($event->event_status ?? 'Tracking Update'),
                'description' => $event->event_description,
                'location' => $event->location,
                'event_time' => $event->event_time ? $event->event_time->toIso8601String() : null,
            ];
        })->values()->toArray() : [];

        return [
            'available' => true,
            'status' => $status,
            'status_label' => $statusMapping['label'],
            'status_badge_class' => $statusMapping['badge'],
            'courier_name' => $shipment->courier_name,
            'awb_code' => $shipment->awb_code,
            'tracking_url' => $shipment->tracking_url,
            'shipping_charge' => (float) $shipment->shipping_charge,
            'currency' => $shipment->currency ?? 'INR',
            'estimated_delivery_days' => $shipment->courier_estimated_delivery_days,
            'etd' => $shipment->courier_etd ? \Carbon\Carbon::parse($shipment->courier_etd)->format('M d, Y') : null,
            'initiated_at' => $shipment->initiated_at ? $shipment->initiated_at->toIso8601String() : null,
            'last_tracked_at' => $shipment->last_tracked_at ? $shipment->last_tracked_at->toIso8601String() : null,
            'is_test_mode' => (bool) Arr::get($shipment->metadata ?? [], 'simulated', false),
            'documents' => [
                'invoice_available' => filled($shipment->invoice_url),
                'invoice_url' => $shipment->invoice_url,
                // Label and Manifest are intentionally excluded for customer security
            ],
            'events' => $events,
        ];
    }

    /**
     * Map internal shipment status to user-friendly label and badge class.
     */
    protected function getStatusMapping(string $status): array
    {
        $map = [
            'draft' => ['label' => 'Preparing Shipment', 'badge' => 'bg-secondary-subtle text-secondary'],
            'courier_selected' => ['label' => 'Courier Selected', 'badge' => 'bg-info-subtle text-info'],
            'ready_to_ship' => ['label' => 'Ready to Ship', 'badge' => 'bg-primary-subtle text-primary'],
            'created' => ['label' => 'Shipment Created', 'badge' => 'bg-success-subtle text-success'],
            'awb_assigned' => ['label' => 'AWB Assigned', 'badge' => 'bg-success-subtle text-success'],
            'pickup_scheduled' => ['label' => 'Pickup Scheduled', 'badge' => 'bg-primary-subtle text-primary'],
            'picked_up' => ['label' => 'Picked Up', 'badge' => 'bg-primary-subtle text-primary'],
            'in_transit' => ['label' => 'In Transit', 'badge' => 'bg-primary-subtle text-primary'],
            'out_for_delivery' => ['label' => 'Out for Delivery', 'badge' => 'bg-warning-subtle text-warning'],
            'delivered' => ['label' => 'Delivered', 'badge' => 'bg-success text-white'],
            'failed' => ['label' => 'Delivery Issue', 'badge' => 'bg-danger-subtle text-danger'],
            'rto' => ['label' => 'Return to Origin', 'badge' => 'bg-danger-subtle text-danger'],
            'cancelled' => ['label' => 'Cancelled', 'badge' => 'bg-dark-subtle text-dark'],
            'unknown' => ['label' => 'Tracking Update', 'badge' => 'bg-secondary-subtle text-secondary'],
        ];

        return $map[$status] ?? $map['unknown'];
    }
}
