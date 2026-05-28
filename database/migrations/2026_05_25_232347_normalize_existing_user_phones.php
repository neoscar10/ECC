<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use App\Services\Otp\PhoneNormalizer;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $normalizer = app(PhoneNormalizer::class);

        User::whereNotNull('phone')->chunkById(100, function ($users) use ($normalizer) {
            foreach ($users as $user) {
                $rawPhone = $user->phone;

                // Skip soft-deleted and anonymized phone numbers
                if (str_starts_with($rawPhone, 'del_')) {
                    continue;
                }

                // If already correctly formatted E.164, double check but skip if no changes
                try {
                    $normalized = $normalizer->normalize($rawPhone);
                    
                    if ($normalized === $rawPhone) {
                        continue;
                    }

                    // Check for duplicate normalized numbers in the DB
                    $duplicateExists = User::where('phone', $normalized)
                        ->where('id', '!=', $user->id)
                        ->exists();

                    if ($duplicateExists) {
                        $flagged = $rawPhone . '_dup_' . time();
                        Log::warning("Migration: Normalized phone conflict detected. Suffixing raw value.", [
                            'user_id' => $user->id,
                            'original' => $rawPhone,
                            'normalized' => $normalized,
                            'flagged' => $flagged,
                        ]);
                        
                        $user->update(['phone' => $flagged]);
                    } else {
                        $user->update(['phone' => $normalized]);
                    }

                } catch (\Exception $e) {
                    // Flag invalid numbers without breaking the migration
                    $flagged = $rawPhone . '_invalid';
                    Log::error("Migration: Failed to normalize phone format. Suffixing raw value.", [
                        'user_id' => $user->id,
                        'original' => $rawPhone,
                        'error' => $e->getMessage(),
                        'flagged' => $flagged,
                    ]);

                    $user->update(['phone' => $flagged]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration: cannot reliably undo phone number normalization.
    }
};
