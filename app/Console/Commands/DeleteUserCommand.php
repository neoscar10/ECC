<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:hard-delete {identifier : Email or Phone Number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard deletes a user and all related records by email or phone so the identifier can be reused for testing.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $identifier = $this->argument('identifier');
        $this->info("Searching for user with identifier: {$identifier}...");

        // Find user by email or phone (using LIKE to handle country codes or prefixes)
        $user = User::withTrashed()
            ->where('email', 'like', "%{$identifier}%")
            ->orWhere('phone', 'like', "%{$identifier}%")
            ->orWhere('email', 'like', "del\_%\_%{$identifier}%")
            ->orWhere('phone', 'like', "del\_%\_%{$identifier}%")
            ->first();

        // Also clean up pending tables even if user doesn't exist
        $this->info("Cleaning up pending registrations and OTPs for {$identifier}...");
        DB::table('pending_registrations')->where('phone', 'like', "%{$identifier}%")->orWhere('email', 'like', "%{$identifier}%")->delete();
        DB::table('otp_verifications')->where('phone', 'like', "%{$identifier}%")->delete();
        DB::table('password_reset_tokens')->where('email', $identifier)->delete();

        if (!$user) {
            $this->info("No actual User record found for identifier. Cleaned up pending registrations/OTPs.");
            return;
        }

        $this->warn("Found User: {$user->name} (ID: {$user->id})");
        $this->warn("Email: {$user->email}");
        $this->warn("Phone: {$user->phone}");

        if (!$this->confirm('Are you sure you want to completely destroy this user and ALL related data? This cannot be undone.', true)) {
            $this->info('Aborted.');
            return;
        }

        DB::transaction(function () use ($user) {
            $userId = $user->id;

            // Delete Devices
            DB::table('user_device_tokens')->where('user_id', $userId)->delete();

            // Cart (shop_orders might be used, carts)
            $carts = DB::table('carts')->where('user_id', $userId)->get();
            foreach ($carts as $cart) {
                DB::table('cart_item_variation_values')->whereIn('cart_item_id', function ($query) use ($cart) {
                    $query->select('id')->from('cart_items')->where('cart_id', $cart->id);
                })->delete();
                DB::table('cart_items')->where('cart_id', $cart->id)->delete();
                DB::table('carts')->where('id', $cart->id)->delete();
            }

            // Addresses
            DB::table('user_addresses')->where('user_id', $userId)->delete();

            // Vault
            $vaultItems = DB::table('user_vault_items')->where('user_id', $userId)->get();
            foreach ($vaultItems as $item) {
                DB::table('vault_removal_requests')->where('user_vault_item_id', $item->id)->delete();
                DB::table('user_vault_items')->where('id', $item->id)->delete();
            }

            // Bids
            DB::table('auction_bids')->where('user_id', $userId)->delete();
            DB::table('auction_notification_subscriptions')->where('user_id', $userId)->delete();

            // Memberships and Applications
            DB::table('membership_application_drafts')->where('user_id', $userId)->delete();
            DB::table('membership_applications')->where('user_id', $userId)->delete();
            DB::table('memberships')->where('user_id', $userId)->delete();
            DB::table('user_memberships')->where('user_id', $userId)->delete();
            DB::table('onboarding_profiles')->where('user_id', $userId)->delete();

            // Orders and payments (Assuming shop_orders is the main one based on migrations)
            $orders = DB::table('shop_orders')->where('user_id', $userId)->get();
            foreach ($orders as $order) {
                DB::table('shop_order_item_variation_values')->whereIn('shop_order_item_id', function ($query) use ($order) {
                    $query->select('id')->from('shop_order_items')->where('shop_order_id', $order->id);
                })->delete();
                DB::table('shop_order_items')->where('shop_order_id', $order->id)->delete();
                // If there are order status histories or payments linked to order
                DB::table('payments')->where('order_id', $order->id)->delete();
                DB::table('shop_orders')->where('id', $order->id)->delete();
            }

            // Orders table (if different from shop_orders)
            $orders2 = DB::table('orders')->where('user_id', $userId)->get();
            foreach ($orders2 as $order) {
                DB::table('payments')->where('order_id', $order->id)->delete();
                DB::table('orders')->where('id', $order->id)->delete();
            }

            // Any remaining payments
            DB::table('payments')->where('user_id', $userId)->delete();

            // Contact enquiries / messages
            DB::table('contact_messages')->where('user_id', $userId)->delete();
            DB::table('contact_enquiries')->where('email', $user->email)->delete();

            // Finally, force delete the user record
            DB::table('users')->where('id', $userId)->delete();
        });

        $this->info("User and all related records have been completely destroyed.");
    }
}
