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
    $access = $resolver->resolve($lot, $user);

    // If has visibility, allow subscription.
    // We allow subscription even if not "live" so they can see "upcoming" or "ended" updates.
    return $access['has_visibility'];
});
