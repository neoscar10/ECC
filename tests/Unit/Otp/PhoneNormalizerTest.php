<?php

namespace Tests\Unit\Otp;

use Tests\TestCase;
use App\Services\Otp\PhoneNormalizer;
use App\Exceptions\OtpException;

class PhoneNormalizerTest extends TestCase
{
    protected PhoneNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear env override so we use the default fallback IN
        config(['services.whatsapp.default_region' => 'IN']);
        $this->normalizer = new PhoneNormalizer();
    }

    public function test_keeps_valid_e164_format()
    {
        // Valid US number
        $this->assertEquals('+12025550143', $this->normalizer->normalize('+12025550143'));
        // Valid India number
        $this->assertEquals('+919876543210', $this->normalizer->normalize('+919876543210'));
        // Valid Nigeria number
        $this->assertEquals('+2348012345678', $this->normalizer->normalize('+2348012345678'));
    }

    public function test_cleans_special_characters()
    {
        $this->assertEquals('+919876543210', $this->normalizer->normalize('+91 98765-43210'));
        $this->assertEquals('+919876543210', $this->normalizer->normalize('+91 (987) 654-3210'));
        $this->assertEquals('+2348012345678', $this->normalizer->normalize('+234 801 234 5678'));
    }

    public function test_assumes_india_for_10_digits()
    {
        $this->assertEquals('+919876543210', $this->normalizer->normalize('9876543210'));
    }

    public function test_handles_india_without_plus()
    {
        $this->assertEquals('+919876543210', $this->normalizer->normalize('919876543210'));
    }

    public function test_assumes_nigeria_for_11_digits_starting_with_0()
    {
        $this->assertEquals('+2348012345678', $this->normalizer->normalize('08012345678'));
        $this->assertEquals('+2349012345678', $this->normalizer->normalize('09012345678'));
    }

    public function test_handles_nigeria_without_plus()
    {
        $this->assertEquals('+2348012345678', $this->normalizer->normalize('2348012345678'));
    }

    public function test_fallback_using_parsed_international_prefix()
    {
        // Valid UK number (11 digits after +44)
        $this->assertEquals('+447911123456', $this->normalizer->normalize('447911123456'));
        $this->assertEquals('+447911123456', $this->normalizer->normalize('+447911123456'));
    }

    public function test_configurable_default_region()
    {
        // If default region is set to NG
        config(['services.whatsapp.default_region' => 'NG']);
        $normalizer = new PhoneNormalizer();

        // 9876543210 starts with 98 which is invalid for Nigeria national mobile numbers.
        // Let's test if it fails since it's not valid for NG.
        $this->expectException(OtpException::class);
        $normalizer->normalize('9876543210');
    }

    public function test_rejects_too_short_numbers()
    {
        $this->expectException(OtpException::class);
        $this->normalizer->normalize('12345');
    }

    public function test_rejects_too_long_numbers()
    {
        $this->expectException(OtpException::class);
        $this->normalizer->normalize('98765432109876543210');
    }

    public function test_rejects_alphabetic_characters()
    {
        $this->expectException(OtpException::class);
        $this->normalizer->normalize('9876543210abc');
    }

    public function test_rejects_invalid_country_codes()
    {
        // +999 is not a valid country code assigned by ITU-T
        $this->expectException(OtpException::class);
        $this->normalizer->normalize('+999123456789');
    }
}
