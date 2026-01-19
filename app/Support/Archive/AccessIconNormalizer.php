<?php

namespace App\Support\Archive;

final class AccessIconNormalizer
{
    public static function normalize(?string $reason, string $viewMode): ?string
    {
        $reason = $reason ? strtolower(trim($reason)) : null;
        $viewMode = strtolower(trim($viewMode));

        // Unrestricted clear access - strictly clear view with no reason
        if ($viewMode === 'clear' && empty($reason)) {
            return null;
        }

        // Early Access Lock
        if ($reason === 'early_access_locked') {
            return 'time-lock';
        }

        // Private collection reasons
        $private = [
            'private_collection',
            'private',
            'private_only',
            'product_private',
            'category_private',
            'attachment_private',
            'private_tier_only',
        ];

        if ($reason && in_array($reason, $private, true)) {
            return 'diamond';
        }

        // Everything else (blurred, standard restricted, etc.)
        return 'lock';
    }
}
