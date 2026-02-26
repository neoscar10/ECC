<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminUserBulkImportService
{
    protected $creationService;
    protected $tierCache = [];

    public function __construct(AdminUserCreationService $creationService)
    {
        $this->creationService = $creationService;
        
        // Cache tiers by code to avoid multiple DB queries
        $tiers = MembershipTier::where('is_active', true)->get();
        foreach ($tiers as $tier) {
            $this->tierCache[strtoupper($tier->code)] = $tier->id;
        }
    }

    /**
     * Import users from a CSV file
     *
     * @param string $filePath
     * @return array Results summary
     */
    public function importCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);
        
        if (!$headers) {
            throw new \Exception('Failed to read CSV headers.');
        }

        $headers = array_map(function($h) { return trim(strtolower($h)); }, $headers);

        $results = [
            'total' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'failed_rows' => []
        ];

        while (($row = fgetcsv($handle)) !== false) {
            $results['total']++;
            
            // Map row to headers
            $data = [];
            foreach ($headers as $index => $header) {
                // Ensure array key exists
                $data[$header] = isset($row[$index]) ? trim($row[$index]) : null;
            }

            try {
                $this->processRow($data, $results['total']);
                $results['created']++;
            } catch (\App\Exceptions\BulkImportDuplicateException $e) {
                $results['skipped']++;
                $results['failed_rows'][] = [
                    'row_number' => $results['total'],
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'type' => 'duplicate'
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['failed_rows'][] = [
                    'row_number' => $results['total'],
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'type' => 'error'
                ];
            }
        }

        fclose($handle);

        return $results;
    }

    /**
     * Process a single mapped row
     */
    protected function processRow(array $data, int $rowNumber)
    {
        // 1. Basic Validation
        $validator = Validator::make($data, [
            'full_name' => 'required|string|min:2|max:120',
            'email' => 'required|email',
            'phone' => 'required|string',
            'membership_tier_code' => 'required|string',
            'membership_expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validation Error: ' . implode(', ', $validator->errors()->all()));
        }

        // 2. Duplicate Check
        $emailExists = User::where('email', $data['email'])->exists();
        $phoneExists = User::where('phone', $data['phone'])->exists();

        if ($emailExists || $phoneExists) {
            $reason = [];
            if ($emailExists) $reason[] = 'Email already exists';
            if ($phoneExists) $reason[] = 'Phone already exists';
            throw new \App\Exceptions\BulkImportDuplicateException(implode(' and ', $reason));
        }

        // 3. Tier Validation
        $tierCode = strtoupper($data['membership_tier_code']);
        if (!isset($this->tierCache[$tierCode])) {
            throw new \Exception("Invalid or inactive Membership Tier Code: '{$tierCode}'");
        }
        $tierId = $this->tierCache[$tierCode];

        // 4. Passwords are ALWAYS auto-generated, regardless of template columns.
        $manualPassword = null;

        // 5. Structure data for AdminUserCreationService
        $userData = [
            'name' => explode(' ', $data['full_name'])[0], // Basic first name guess
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone']
        ];

        // Format commas directly to arrays
        $applicationData = [
            'personal' => array_filter([
                'full_name' => $data['full_name'],
                'dob' => $data['dob'] ?? null,
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
            ]),
            'cricket' => array_filter([
                'preferred_formats' => !empty($data['preferred_formats']) ? array_map('trim', explode(',', $data['preferred_formats'])) : [],
                'eras' => !empty($data['eras']) ? array_map('trim', explode(',', $data['eras'])) : [],
            ]),
            'collector' => array_filter([
                'has_acquired_memorabilia_before' => $data['has_acquired_memorabilia_before'] ?? 'no',
                'focus' => $data['focus'] ?? 'legacy',
                'investment_horizon' => isset($data['investment_horizon']) ? (int) $data['investment_horizon'] : 5,
                'interests' => !empty($data['interests']) ? array_map('trim', explode(',', $data['interests'])) : [],
            ])
        ];

        // Use a transaction per row
        $expiresAt = $data['membership_expiry_date'] ?? null;
        DB::transaction(function () use ($userData, $tierId, $applicationData, $manualPassword, $expiresAt) {
            $this->creationService->createAdminUser($userData, $tierId, $applicationData, $manualPassword, $expiresAt);
        });
    }

    /**
     * Generate a temporary CSV containing the failed rows
     */
    public function generateErrorReport(array $failedRows): array
    {
        $headers = ['Row Number', 'Error Type', 'Error Message'];
        $dataKeys = [];
        
        // Find all keys used in data to dynamically build remaining headers
        foreach ($failedRows as $row) {
            if (isset($row['data']) && is_array($row['data'])) {
                foreach (array_keys($row['data']) as $key) {
                    if (!in_array($key, $dataKeys)) {
                        $dataKeys[] = $key;
                    }
                }
            }
        }
        
        $headers = array_merge($headers, $dataKeys);

        return [
            'headers' => $headers,
            'rows' => $failedRows,
            'data_keys' => $dataKeys
        ];
    }
}
