<?php

namespace App\Services\Otp;

use App\Exceptions\OtpException;

class PhoneNormalizer
{
    protected string $defaultRegion;

    public function __construct()
    {
        $this->defaultRegion = strtoupper((string) config('services.whatsapp.default_region', 'IN'));
    }

    /**
     * Normalize a phone number to strict E.164 format without external dependencies.
     *
     * @param string $phone Raw phone number.
     * @return string Normalized phone number (E.164).
     * @throws OtpException if the phone number is invalid.
     */
    public function normalize(string $phone): string
    {
        // 1. Reject inputs containing alphabetic characters
        if (preg_match('/[a-zA-Z]/', $phone)) {
            throw new OtpException('The phone number must not contain letters.', 422);
        }

        // 2. Sanitize: remove any characters that aren't digits or '+'
        $clean = preg_replace('/[^0-9+]/', '', $phone);

        if (empty($clean)) {
            throw new OtpException('The phone number is empty or contains no valid digits.', 422);
        }

        // 3. Normalize to E.164
        if (str_starts_with($clean, '+')) {
            $digits = substr($clean, 1);
            if (strlen($digits) >= 7 && strlen($digits) <= 15) {
                return $clean;
            }
        } else {
            // Heuristic for 10-digit number without country code
            if (strlen($clean) === 10) {
                $prefix = ($this->defaultRegion === 'NG') ? '+234' : '+91';
                return $prefix . $clean;
            }
            
            // Heuristic for Nigeria 11-digit local format (e.g. 08012345678)
            if (str_starts_with($clean, '0') && strlen($clean) === 11) {
                return '+234' . substr($clean, 1);
            }

            // India with 12 digits starting with 91
            if (str_starts_with($clean, '91') && strlen($clean) === 12) {
                return '+' . $clean;
            }

            // Nigeria with 13 digits starting with 234
            if (str_starts_with($clean, '234') && strlen($clean) === 13) {
                return '+' . $clean;
            }

            // Default fallback: prepend + if not present and check length
            if (strlen($clean) >= 7 && strlen($clean) <= 15) {
                return '+' . $clean;
            }
        }

        throw new OtpException('The phone number provided is not valid.', 422);
    }
}
