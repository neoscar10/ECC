<?php

namespace App\Services\Shipping;

use App\Models\Shipping\ShippingShipment;
use Illuminate\Support\Arr;

class ShipmentTrackingPresenter
{
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
