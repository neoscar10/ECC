<?php

namespace App\Services\Otp;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;
use App\Exceptions\OtpException;

class PhoneNormalizer
{
    protected PhoneNumberUtil $phoneUtil;
    protected string $defaultRegion;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
        $this->defaultRegion = strtoupper((string) config('services.whatsapp.default_region', 'IN'));
    }

    /**
     * Normalize a phone number to strict E.164 format.
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

        // 3. Deduce region based on patterns/heuristics
        $region = $this->deduceRegion($clean);

        try {
            // 4. Parse phone number
            // For numbers starting with '+', parse uses region 'ZZ' which extracts country code from number.
            $phoneNumberInstance = $this->phoneUtil->parse($clean, $region);

            // 5. Validate
            if ($this->phoneUtil->isValidNumber($phoneNumberInstance)) {
                // 6. Format to E.164
                return $this->phoneUtil->format($phoneNumberInstance, PhoneNumberFormat::E164);
            }
        } catch (NumberParseException $e) {
            // Suppress and try fallback
        }

        // 7. Fallback: If it didn't start with '+' and failed local validation, try prepending '+' and parsing as international
        if (!str_starts_with($clean, '+')) {
            try {
                $phoneNumberInstance = $this->phoneUtil->parse('+' . $clean, 'ZZ');
                if ($this->phoneUtil->isValidNumber($phoneNumberInstance)) {
                    return $this->phoneUtil->format($phoneNumberInstance, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException $e) {
                // Suppress and throw the main validation exception below
            }
        }

        throw new OtpException('The phone number provided is not valid.', 422);
    }

    /**
     * Deduce region code (e.g. IN, NG) based on input string patterns.
     */
    private function deduceRegion(string $phone): string
    {
        // If it starts with '+', use 'ZZ' to force libphonenumber to parse international code
        if (str_starts_with($phone, '+')) {
            return 'ZZ';
        }

        // Nigeria heuristic:
        // - Starts with '0' and has exactly 11 digits (e.g., 08012345678)
        // - Starts with '234' and has exactly 13 digits (e.g., 2348012345678)
        if (str_starts_with($phone, '0') && strlen($phone) === 11) {
            return 'NG';
        }
        if (str_starts_with($phone, '234') && strlen($phone) === 13) {
            return 'NG';
        }

        // India heuristic:
        // - Exactly 10 digits (e.g., 9876543210)
        // - Starts with '91' and has exactly 12 digits (e.g., 919876543210)
        if (strlen($phone) === 10) {
            return $this->defaultRegion === 'NG' ? 'NG' : 'IN';
        }
        if (str_starts_with($phone, '91') && strlen($phone) === 12) {
            return 'IN';
        }

        // Default region fallback
        return $this->defaultRegion;
    }
}
