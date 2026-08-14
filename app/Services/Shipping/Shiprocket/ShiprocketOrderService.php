<?php

namespace App\Services\Shipping\Shiprocket;

use App\Models\Shipping\ShippingShipment;
use App\Services\Shipping\ShipmentService;
use Illuminate\Support\Facades\Log;

class ShiprocketOrderService
{
    protected ShiprocketClient $client;
    protected ShipmentService $shipmentService;

    public function __construct(ShiprocketClient $client, ShipmentService $shipmentService)
    {
        $this->client = $client;
        $this->shipmentService = $shipmentService;
    }

    public function canUseLiveShiprocket(): bool
    {
        return config('shiprocket.test_mode') === false
            && config('shiprocket.live_shipment_enabled') === true;
    }

    /**
     * Validate common prerequisites before initiating any shipment (live or test).
     */
    protected function validateShipmentCanBeInitiated(ShippingShipment $shipment): void
    {
        if (!$shipment->canInitiateShipment()) {
            throw new \Exception("Shipment cannot be initiated. Invalid status or already initiated.");
        }

        $order = $shipment->shippable;
        if (!$order) {
            throw new \Exception("Cannot initiate shipment: related order not found.");
        }

        if ($order->payment_status !== 'paid' && !in_array(strtolower($order->payment_status), ['unpaid', 'pending_payment'])) {
            throw new \Exception("Shipment cannot be initiated until payment is confirmed.");
        }

        if (empty($shipment->courier_company_id)) {
            throw new \Exception("Unable to initiate shipment: selected courier is missing.");
        }
    }

    /**
     * Orchestrate the full shipment initiation: create order, assign AWB.
     */
    public function initiateShipment(ShippingShipment $shipment): ShippingShipment
    {
        $this->validateShipmentCanBeInitiated($shipment);

        // Prevent duplicates
        if ($shipment->provider_order_id || $shipment->provider_shipment_id) {
            return $shipment;
        }

        if (!$this->canUseLiveShiprocket()) {
            return $this->simulateShipmentInitiation($shipment);
        }

        return $this->initiateLiveShipment($shipment);
    }

    /**
     * Test Mode: Simulate shipment initiation without calling real Shiprocket APIs.
     */
    public function simulateShipmentInitiation(ShippingShipment $shipment): ShippingShipment
    {
        $this->shipmentService->recordEvent($shipment, [
            'event_code' => 'initiation_started',
            'event_status' => 'initiating',
            'event_description' => 'Simulated shipment initiation started by admin.',
        ]);

        $timestamp = now()->timestamp;
        $random = rand(1000, 9999);

        // Simulated Create Order
        $shipment->update([
            'provider_order_id' => "TEST-SR-ORDER-{$shipment->id}-{$timestamp}",
            'provider_shipment_id' => "TEST-SR-SHIP-{$shipment->id}-{$timestamp}",
            'status' => 'created',
            'initiated_at' => now(),
        ]);

        // Fake payload for audit
        $metadata = $shipment->metadata ?? [];
        $metadata['is_test_mode'] = true;
        $metadata['simulated'] = true;
        $metadata['live_shiprocket_called'] = false;
        $metadata['provider_payload'] = ['simulated' => 'test payload'];
        $shipment->update(['metadata' => $metadata]);

        $this->shipmentService->recordEvent($shipment, [
            'event_code' => 'test_shiprocket_order_created',
            'event_status' => 'created',
            'event_description' => 'Test Shiprocket order created successfully.',
            'raw_payload' => ['simulated' => true],
        ]);

        // Simulated Assign AWB
        $shipment->update([
            'awb_code' => "TEST-AWB-{$shipment->id}-{$random}",
            'status' => 'awb_assigned',
        ]);

        $this->shipmentService->recordEvent($shipment, [
            'event_code' => 'test_awb_assigned',
            'event_status' => 'awb_assigned',
            'event_description' => 'Test AWB assigned successfully.',
            'raw_payload' => ['simulated' => true],
        ]);

        return $shipment->fresh();
    }

    /**
     * Live Mode: Real Shiprocket API integration.
     */
    protected function initiateLiveShipment(ShippingShipment $shipment): ShippingShipment
    {



        $this->shipmentService->recordEvent($shipment, [
            'event_code' => 'initiation_started',
            'event_status' => 'initiating',
            'event_description' => 'Manual shipment initiation started by admin.',
        ]);

        try {
            // 2. Create Order
            $createResponse = $this->createOrderFromShipment($shipment);

            $shipment->update([
                'provider_order_id' => $createResponse['order_id'],
                'provider_shipment_id' => $createResponse['shipment_id'],
                'status' => 'created',
                'initiated_at' => now(),
            ]);

            $this->shipmentService->recordEvent($shipment, [
                'event_code' => 'shiprocket_order_created',
                'event_status' => 'created',
                'event_description' => 'Shiprocket order created successfully.',
                'raw_payload' => $createResponse,
            ]);

            // 3. Assign AWB (If not already provided in response)
            if (!empty($createResponse['awb_code'])) {
                $shipment->update([
                    'awb_code' => $createResponse['awb_code'],
                    'status' => 'awb_assigned',
                ]);
            } else {
                $this->assignAwb($shipment);
            }

            return $shipment->fresh();

        } catch (\Exception $e) {
            $this->shipmentService->recordEvent($shipment, [
                'event_code' => 'initiation_failed',
                'event_status' => 'failed',
                'event_description' => 'Failed to initiate shipment.',
                'raw_payload' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }

    /**
     * Create the order on Shiprocket.
     */
    /**
     * Create the order on Shiprocket.
     */
    public function createOrderFromShipment(ShippingShipment $shipment): array
    {
        $order = $shipment->shippable;
        $customer = $order->user;
        $address = $shipment->delivery_address_snapshot ?? (method_exists($order, 'shipping_address_snapshot') ? $order->shipping_address_snapshot : null);

        // Build Payload Polymorphically
        $orderItems = [];
        if ($order instanceof \App\Models\Shop\ShopOrder) {
            foreach ($order->items as $item) {
                $orderItems[] = [
                    'name' => $item->title_snapshot ?? $item->product?->title ?? 'Product',
                    'sku' => 'ECC-ITM-' . $item->id,
                    'units' => $item->quantity,
                    'selling_price' => $item->unit_price,
                    'discount' => 0,
                    'tax' => 0,
                    'hsn' => ''
                ];
            }
            $firstName = $address['full_name'] ?? $customer?->name ?? 'Customer';
            $firstNameParts = explode(' ', $firstName, 2);
            $orderId = $order->order_number ?? 'ECC-' . $order->id;
            $orderDate = $order->created_at->format('Y-m-d H:i');
            $paymentMethod = $order->payment_status === 'paid' ? 'Prepaid' : 'COD';
            $shippingCharges = $order->shipping_charge ?? 0;
            $discountAmount = $order->discount_amount ?? 0;
            $subTotal = $order->subtotal ?? 0;
        } elseif ($order instanceof \App\Models\VaultRemovalRequest) {
            $vaultItem = $order->vaultItem;
            // Declare value based on price or unit_price, fallback to delivery fee, fallback to 1.0
            $sellingPrice = (float)($vaultItem->source_item?->price ?? $vaultItem->source_item?->unit_price ?? $order->delivery_fee ?? 1.0);
            if ($sellingPrice <= 0) {
                $sellingPrice = 1.0;
            }

            $orderItems[] = [
                'name' => $vaultItem->item_title ?? 'Vault Item',
                'sku' => $vaultItem->item_ref ?? "VAULT-ITEM-" . $vaultItem->id,
                'units' => $vaultItem->quantity ?? 1,
                'selling_price' => $sellingPrice,
                'discount' => 0,
                'tax' => 0,
                'hsn' => ''
            ];

            $firstName = $order->delivery_name ?? $customer?->name ?? 'Customer';
            $firstNameParts = explode(' ', $firstName, 2);
            $orderId = 'ECC-VAULT-' . $order->id;
            $orderDate = ($order->paid_at ?? $order->created_at)->format('Y-m-d H:i');
            $paymentMethod = 'Prepaid';
            $shippingCharges = $order->delivery_fee ?? 0;
            $discountAmount = 0;
            $subTotal = $sellingPrice * ($vaultItem->quantity ?? 1);
        } else {
            throw new \RuntimeException('Unsupported shipment type.');
        }

        $payload = [
            'order_id' => $orderId,
            'order_date' => $orderDate,
            'pickup_location' => $shipment->pickup_location ?? config('shiprocket.pickup_location'),
            'billing_customer_name' => $firstNameParts[0] ?: 'Customer',
            'billing_last_name' => $firstNameParts[1] ?? $firstNameParts[0] ?? 'Name',
            'billing_address' => $address['line1'] ?? 'No Address Provided',
            'billing_address_2' => $address['line2'] ?? '',
            'billing_city' => $address['city'] ?? 'City',
            'billing_pincode' => $shipment->delivery_pincode ?? $address['postal_code'] ?? '110001',
            'billing_state' => $address['state'] ?? 'State',
            'billing_country' => $address['country'] ?? 'India',
            'billing_email' => $address['email'] ?? $customer?->email ?? 'noemail@example.com',
            'billing_phone' => substr(preg_replace('/[^0-9]/', '', $address['phone'] ?? $customer?->phone ?? '9999999999'), -10),
            'shipping_is_billing' => true,
            'order_items' => $orderItems,
            'payment_method' => $paymentMethod,
            'shipping_charges' => $shippingCharges,
            'giftwrap_charges' => 0,
            'transaction_charges' => 0,
            'total_discount' => $discountAmount,
            'sub_total' => $subTotal,
            'length' => $shipment->length_cm ?? 1,
            'breadth' => $shipment->breadth_cm ?? 1,
            'height' => $shipment->height_cm ?? 1,
            'weight' => $shipment->weight_kg ?? 0.5,
        ];

        // Save payload for auditing
        $metadata = $shipment->metadata ?? [];
        $metadata['provider_payload'] = $payload;
        $shipment->update(['metadata' => $metadata]);

        $response = $this->client->post('/orders/create/adhoc', $payload);

        if (empty($response['order_id']) || empty($response['shipment_id'])) {
            throw new \Exception("Invalid response from Shiprocket create order: " . json_encode($response));
        }

        return $response;
    }


    /**
     * Assign AWB.
     */
    public function assignAwb(ShippingShipment $shipment): array
    {
        if (!$this->canUseLiveShiprocket()) {
            return $this->simulateAwbAssignment($shipment);
        }

        if (!$shipment->provider_shipment_id) {
            throw new \Exception("Cannot assign AWB: missing provider_shipment_id.");
        }
        if (!$shipment->courier_company_id) {
            throw new \Exception("Cannot assign AWB: missing courier_company_id.");
        }

        $payload = [
            'shipment_id' => $shipment->provider_shipment_id,
            'courier_id' => $shipment->courier_company_id,
        ];

        try {
            $response = $this->client->post('/courier/assign/awb', $payload);

            $awbCode = $response['response']['data']['awb_code'] ?? $response['data']['awb_code'] ?? $response['awb_code'] ?? null;

            if (!$awbCode) {
                throw new \Exception("No AWB code found in response.");
            }

            $shipment->update([
                'awb_code' => $awbCode,
                'status' => 'awb_assigned',
                'tracking_url' => $response['response']['data']['routing_code'] ?? null, // Optional tracking link usually
            ]);

            $this->shipmentService->recordEvent($shipment, [
                'event_code' => 'awb_assigned',
                'event_status' => 'awb_assigned',
                'event_description' => 'AWB assigned successfully.',
                'raw_payload' => $response,
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->shipmentService->recordEvent($shipment, [
                'event_code' => 'awb_assignment_failed',
                'event_status' => 'created',
                'event_description' => 'AWB assignment failed: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Simulated AWB Assignment.
     */
    protected function simulateAwbAssignment(ShippingShipment $shipment): array
    {
        $random = rand(1000, 9999);
        $awbCode = "TEST-AWB-{$shipment->id}-{$random}";
        
        $shipment->update([
            'awb_code' => $awbCode,
            'status' => 'awb_assigned',
        ]);

        $this->shipmentService->recordEvent($shipment, [
            'event_code' => 'test_awb_assigned',
            'event_status' => 'awb_assigned',
            'event_description' => 'Test AWB assigned successfully.',
            'raw_payload' => ['simulated' => true],
        ]);

        return ['awb_code' => $awbCode, 'simulated' => true];
    }

    /**
     * Retry AWB Assignment.
     */
    public function retryAssignAwb(ShippingShipment $shipment): ShippingShipment
    {
        if (!$shipment->provider_shipment_id) {
            throw new \Exception("Cannot retry AWB: missing provider_shipment_id.");
        }
        if ($shipment->awb_code) {
            throw new \Exception("AWB already assigned.");
        }

        $this->assignAwb($shipment);
        return $shipment->fresh();
    }

    /**
     * Check if documents can be generated.
     */
    public function canGenerateDocuments(ShippingShipment $shipment): bool
    {
        return in_array($shipment->status, ['created', 'awb_assigned', 'pickup_scheduled', 'in_transit'])
            && (!empty($shipment->provider_shipment_id) || !empty($shipment->provider_order_id));
    }

    /**
     * Refresh tracking from Shiprocket.
     */
    public function refreshTracking(ShippingShipment $shipment): ShippingShipment
    {
        if (!$this->canUseLiveShiprocket()) {
            $shipment->update(['last_tracked_at' => now()]);
            return $shipment;
        }

        if (!$shipment->awb_code) {
            throw new \Exception("Cannot refresh tracking without an AWB code.");
        }

        $response = $this->client->get("/courier/track/awb/{$shipment->awb_code}");
        
        // Pass to webhook service to parse it
        app(\App\Services\Shipping\Shiprocket\ShiprocketTrackingWebhookService::class)->handle($response);

        return $shipment->fresh();
    }

    /**
     * Helper to safely extract deep URLs from variable Shiprocket responses.
     */
    protected function extractDocumentUrl(array $response, string $type): ?string
    {
        $keys = [
            "{$type}_url",
            "url",
            "file",
            "file_url",
            "download_url",
            "manifest_url", // Shiprocket specific
            "invoice_url",
            "label_url",
        ];

        foreach (['', 'data.', 'response.'] as $prefix) {
            foreach ($keys as $key) {
                $val = data_get($response, $prefix . $key);
                if (!empty($val) && is_string($val)) {
                    return $val;
                }
            }
        }
        return null;
    }

    /**
     * Generate Label
     */
    public function generateLabel(ShippingShipment $shipment): array
    {
        if (!$this->canGenerateDocuments($shipment)) {
            throw new \Exception("Shipment must be initiated before generating documents.");
        }

        if (!$this->canUseLiveShiprocket()) {
            return $this->simulateDocumentGeneration($shipment, 'label');
        }

        if (!$shipment->provider_shipment_id) {
            throw new \Exception("Cannot generate label: missing shipment id.");
        }

        $response = $this->client->post('/courier/generate/label', [
            'shipment_id' => [$shipment->provider_shipment_id]
        ]);

        $labelUrl = $this->extractDocumentUrl($response, 'label');

        $metadata = $shipment->metadata ?? [];
        if (!isset($metadata['documents'])) {
            $metadata['documents'] = [];
        }

        $metadata['documents']['label'] = [
            'generated' => true,
            'simulated' => false,
            'url' => $labelUrl,
            'generated_at' => now()->toDateTimeString(),
            'response' => $response,
        ];

        if ($labelUrl) {
            $shipment->update([
                'label_url' => $labelUrl,
                'metadata' => $metadata,
            ]);
        } else {
             $shipment->update(['metadata' => $metadata]);
             throw new \Exception("Label URL not found in response. Check Shiprocket panel.");
        }

        return $response;
    }

    /**
     * Generate Invoice
     */
    public function generateInvoice(ShippingShipment $shipment): array
    {
        if (!$this->canGenerateDocuments($shipment)) {
            throw new \Exception("Shipment must be initiated before generating documents.");
        }

        if (!$this->canUseLiveShiprocket()) {
            return $this->simulateDocumentGeneration($shipment, 'invoice');
        }

        if (!$shipment->provider_order_id) {
            throw new \Exception("Cannot generate invoice: missing order id.");
        }

        $response = $this->client->post('/orders/print/invoice', [
            'ids' => [$shipment->provider_order_id]
        ]);

        $invoiceUrl = $this->extractDocumentUrl($response, 'invoice');

        $metadata = $shipment->metadata ?? [];
        if (!isset($metadata['documents'])) {
            $metadata['documents'] = [];
        }

        $metadata['documents']['invoice'] = [
            'generated' => true,
            'simulated' => false,
            'url' => $invoiceUrl,
            'generated_at' => now()->toDateTimeString(),
            'response' => $response,
        ];

        if ($invoiceUrl) {
            $shipment->update([
                'invoice_url' => $invoiceUrl,
                'metadata' => $metadata,
            ]);
        } else {
             $shipment->update(['metadata' => $metadata]);
             throw new \Exception("Invoice URL not found in response. Check Shiprocket panel.");
        }

        return $response;
    }

    /**
     * Generate Manifest
     */
    public function generateManifest(ShippingShipment $shipment): array
    {
        if (!$this->canGenerateDocuments($shipment)) {
            throw new \Exception("Shipment must be initiated before generating documents.");
        }

        if (!$this->canUseLiveShiprocket()) {
            return $this->simulateDocumentGeneration($shipment, 'manifest');
        }

        if (!$shipment->provider_shipment_id) {
            throw new \Exception("Cannot generate manifest: missing shipment id.");
        }

        // Generate manifest
        try {
            $this->client->post('/manifests/generate', [
                'shipment_id' => [$shipment->provider_shipment_id]
            ]);
        } catch (\Exception $e) {
            // Manifest might already be generated, try printing directly
        }

        // Print manifest
        $response = $this->client->post('/manifests/print', [
            'shipment_id' => [$shipment->provider_shipment_id]
        ]);

        $manifestUrl = $this->extractDocumentUrl($response, 'manifest');

        $metadata = $shipment->metadata ?? [];
        if (!isset($metadata['documents'])) {
            $metadata['documents'] = [];
        }

        $metadata['documents']['manifest'] = [
            'generated' => true,
            'simulated' => false,
            'url' => $manifestUrl,
            'generated_at' => now()->toDateTimeString(),
            'response' => $response,
        ];

        if ($manifestUrl) {
            $shipment->update([
                'manifest_url' => $manifestUrl,
                'metadata' => $metadata,
            ]);
        } else {
             $shipment->update(['metadata' => $metadata]);
             throw new \Exception("Manifest URL not found in response. Check Shiprocket panel.");
        }

        return $response;
    }

    /**
     * Simulated Document Generation.
     */
    protected function simulateDocumentGeneration(ShippingShipment $shipment, string $type): array
    {
        $metadata = $shipment->metadata ?? [];
        if (!isset($metadata['documents'])) {
            $metadata['documents'] = [];
        }
        
        $metadata['documents'][$type] = [
            'generated' => true,
            'simulated' => true,
            'url' => null,
            'generated_at' => now()->toDateTimeString(),
            'response' => ['simulated' => true],
        ];

        $shipment->update(['metadata' => $metadata]);

        return ['simulated' => true, 'type' => $type];
    }
}
