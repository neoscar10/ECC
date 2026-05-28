<?php

namespace App\Services\Otp;

class OtpGenerator
{
    /**
     * Generate a cryptographically secure 6-digit OTP.
     *
     * @return string
     * @throws \Exception
     */
    public function generate(): string
    {
        // Use random_int for cryptographically secure pseudo-random integers.
        $otp = random_int(100000, 999999);
        
        return (string) $otp;
    }
}
