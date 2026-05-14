<?php

namespace App\Console\Commands;

use App\Services\Shipping\ShippingCourierSelectionService;
use Illuminate\Console\Command;

class TestShiprocketServiceabilityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shiprocket:test-serviceability 
                            {--pickup= : Pickup pincode (default from config)} 
                            {--delivery= : Delivery pincode} 
                            {--weight=0.5 : Weight in kg} 
                            {--cod=0 : Is COD (1 for yes, 0 for no)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Shiprocket courier serviceability and rates';

    /**
     * Execute the console command.
     */
    public function handle(ShippingCourierSelectionService $courierService)
    {
        $this->info('Shiprocket Serviceability Test');

        $pickup = $this->option('pickup') ?: config('shiprocket.pickup_pincode');
        $delivery = $this->option('delivery');
        $weight = (float) $this->option('weight');
        $cod = (int) $this->option('cod');

        if (!$delivery) {
            $this->error('Delivery pincode is required. Use --delivery=XXXXXX');
            return 1;
        }

        $this->line("Pickup: " . ($pickup ?: '<not set>'));
        $this->line("Delivery: " . $delivery);
        $this->line("Weight: " . $weight . " kg");
        $this->line("COD: " . ($cod ? 'Yes' : 'No'));

        if (!$pickup) {
            $this->error('Pickup pincode is missing from config and option.');
            return 1;
        }

        $payload = [
            'pickup_pincode' => $pickup,
            'delivery_pincode' => $delivery,
            'weight_kg' => $weight,
            'length_cm' => 10, // Default for test
            'breadth_cm' => 10,
            'height_cm' => 10,
            'payment_mode' => $cod ? 'cod' : 'prepaid',
        ];

        try {
            $response = $courierService->fetchAvailableCouriers($payload);
            $availableCouriers = $courierService->extractAvailableCouriers($response);
            
            $this->line("Available Couriers: " . count($availableCouriers));

            $selected = $courierService->selectBestCourier($availableCouriers);

            if ($selected) {
                $this->line("Selected Courier: " . $selected['courier_name']);
                $this->line("Rating: " . $selected['rating']);
                $this->line("Freight Charge: INR " . $selected['freight_charge']);
                $this->line("COD Charge: INR " . $selected['cod_charge']);
                $this->line("Total Charge: INR " . $selected['total_charge']);
                $this->line("ETD: " . ($selected['etd'] ?: 'N/A') . " (" . $selected['estimated_delivery_days'] . " days)");
                $this->info('Status: OK');
            } else {
                $this->warn('No couriers available for this route/weight.');
                $this->line('Raw Response: ' . json_encode($response));
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Status: FAILED');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
