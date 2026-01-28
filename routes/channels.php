<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('auctions.lot.{lotId}', function ($user, $lotId) {
    // Admins always allowed
    if ($user->hasRole(['super_admin', 'ecc_admin'])) {
        return true;
    }

    $lot = \App\Models\Auctions\AuctionLot::find($lotId);
    if (!$lot) return false;

    // Use Access Resolver
    $resolver = new \App\Services\Auctions\AuctionAccessResolverService();
    try {
        return $resolver->canSubscribeToLotChannel($lot, $user);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("Channel Auth Error for Lot {$lotId}: " . $e->getMessage());
        return false;
    }
});

Broadcast::channel('admin.members', function ($user) {
    return $user->hasRole(['super_admin', 'ecc_admin']);
});
