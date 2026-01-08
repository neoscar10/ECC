<?php

namespace App\Support;

class MetaOptionMapper
{
    /**
     * Normalize an input value to its corresponding code.
     * If the input is already a valid code, return it.
     * If it's a label (case-insensitive), map it to the code.
     * Otherwise return null (or the original if you prefer soft fail, but null allows validation fail).
     */
    public static function map(string $input, array $options): ?string
    {
        $inputUpper = trim(strtoupper($input));

        // 1. Check if it matches a key (Code) directly
        if (array_key_exists($inputUpper, $options)) {
            return $inputUpper;
        }

        // 2. Check if it matches a value (Label) case-insensitively
        // We iterate because array_search is case-sensitive usually or strict
        foreach ($options as $code => $label) {
            if (strtoupper($label) === $inputUpper) {
                return $code;
            }
            
            // Handle specific known variations if needed (e.g. "5-10 Years" vs "5-10 years")
            // The strtoupper comparison handles simple case diffs.
        }

        return null;
    }

    /**
     * Map an array of inputs
     */
    public static function mapArray(array $inputs, array $options): array
    {
        $mapped = [];
        foreach ($inputs as $input) {
            $code = self::map($input, $options);
            if ($code) {
                $mapped[] = $code;
            }
        }
        // Remove duplicates and re-index
        return array_values(array_unique($mapped));
    }
}
