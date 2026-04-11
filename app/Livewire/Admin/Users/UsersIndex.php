<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\MembershipTier;
use Livewire\Attributes\Title;

#[Title('User Management')]
class UsersIndex extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $membershipFilter = '';
    
    // User Modal properties
    public $isEditMode = false;
    public $userId;
    public $name;
    public $email;
    public $phone;
    public $role;
    
    // View Modal Enhancements
    public $tierInfo;
    public $applications;
    public $complete_tier_id;
    public $complete_expires_at;

    // --- Create User Wizard Properties ---
    public $createStep = 1;
    
    // Step 1: Essentials
    public $create_name;
    public $create_email;
    public $create_phone;
    public $create_tier_id;
    public $password_option = 'auto'; // auto, manual
    public $create_password;
    public $create_password_confirmation;
    public $create_expires_at;

    // Step 2: Application Data (Optional)
    // Personal Detail
    public $app_full_name;
    public $app_dob;
    public $app_country;
    public $app_city;
    // Cricket Profile
    public $app_preferred_formats = [];
    public $app_eras = [];
    // Collector Intent
    public $app_has_acquired_before = 'no';
    public $app_focus = 'legacy';
    public $app_investment_horizon = 5;
    public $app_interests = [];

    // --- Bulk Import Properties ---
    public $bulkUploadFile;
    public $bulkPreview = [];
    public $bulkPreviewRows = []; // [{data, errors, is_valid, is_corrected, status}]
    public $bulkResults = null;
    public $editingRowIndex = null;
    public $editingRowData = []; // Temporary storage for the row being edited

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'close-modal' => 'closeModal',
        'deleteUserConfirmed' => 'deleteUser',
    ];

    #[\Livewire\Attributes\On('operation-success')]
    public function showSuccessAlert($message)
    {
        session()->flash('success', $message);
    }

    public function mount()
    {
        \Illuminate\Support\Facades\Log::info('UsersIndex mounted');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->membershipFilter, function ($query) {
                $query->whereHas('currentMembership', function ($q) {
                    $q->where('membership_tier_id', $this->membershipFilter);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.admin.users.users-index', [
            'users' => $users,
            'membershipTiers' => MembershipTier::all(),
        ])->layout('layouts.admin');
    }

    // --- Create User Wizard Methods ---

    public function openCreateModeModal()
    {
        $this->dispatch('show-modal', id: 'createModeModal');
    }

    public function openTierCodesModal()
    {
        $this->dispatch('show-modal', id: 'tierCodesModal');
    }

    public function selectCreateMode($mode)
    {
        $this->dispatch('close-modal');
        if ($mode === 'single') {
            $this->openCreateUserModal();
        } else if ($mode === 'bulk') {
            $this->resetBulkUpload();
            $this->dispatch('show-modal', id: 'bulkUploadModal');
        }
    }

    public function openCreateUserModal()
    {
        $this->resetWizard();
        $this->dispatch('show-modal', id: 'createUserModal');
    }

    public function nextStep()
    {
        if ($this->createStep === 1) {
            $this->validateStep1();
            $this->createStep = 2;
        }
    }

    public function prevStep()
    {
        if ($this->createStep > 1) {
            $this->createStep--;
        }
    }

    public function storeUser(\App\Services\Admin\AdminUserCreationService $service)
    {
        if ($this->createStep === 1) {
            $this->validateStep1();
        } else {
            $this->validateStep2();
        }

        try {
            $userData = [
                'name' => $this->create_name,
                'email' => $this->create_email,
                'phone' => $this->create_phone,
            ];

            $applicationData = [
                'personal' => array_filter([
                    'full_name' => $this->app_full_name,
                    'dob' => $this->app_dob,
                    'country' => $this->app_country,
                    'city' => $this->app_city,
                ]),
                'cricket' => array_filter([
                    'preferred_formats' => $this->app_preferred_formats,
                    'eras' => $this->app_eras,
                ]),
                'collector' => array_filter([
                    'has_acquired_memorabilia_before' => $this->app_has_acquired_before,
                    'focus' => $this->app_focus,
                    'investment_horizon' => $this->app_investment_horizon,
                    'interests' => $this->app_interests,
                ]),
            ];

            $manualPassword = $this->password_option === 'manual' ? $this->create_password : null;

            $service->createAdminUser($userData, $this->create_tier_id, $applicationData, $manualPassword, $this->create_expires_at);

            session()->flash('success', 'User created successfully and notification sent.');
            $this->dispatch('close-modal');
            $this->resetWizard();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->addError('create_user_error', 'Error creating user: ' . $e->getMessage());
        }
    }

    protected function validateStep1()
    {
        $rules = [
            'create_name' => 'required|string|min:2|max:120',
            'create_email' => 'required|email|unique:users,email',
            'create_phone' => 'required|string|unique:users,phone',
            'create_tier_id' => 'required|exists:membership_tiers,id',
            'create_expires_at' => 'nullable|date',
            'password_option' => 'required|in:auto,manual',
        ];

        if ($this->password_option === 'manual') {
            $rules['create_password'] = 'required|string|min:6|confirmed';
        }

        $this->validate($rules, [], [
            'create_name' => 'name',
            'create_email' => 'email',
            'create_phone' => 'phone',
            'create_tier_id' => 'membership tier',
            'create_expires_at' => 'membership expiry date',
            'create_password' => 'password',
        ]);
    }

    protected function validateStep2()
    {
        // Step 2 is optional, but if fields are filled, we validate them partially
        // Using existing rules as reference but making them optional
        $rules = [];
        
        if ($this->app_full_name) $rules['app_full_name'] = 'string|min:3|max:120';
        if ($this->app_dob) $rules['app_dob'] = 'date|before:today';
        if ($this->app_country) $rules['app_country'] = 'string|max:80';
        if ($this->app_city) $rules['app_city'] = 'string|max:80';
        
        if (!empty($this->app_preferred_formats)) {
            $rules['app_preferred_formats'] = 'array';
            $rules['app_preferred_formats.*'] = 'in:test,odi,t20,leagues';
        }

        if (!empty($this->app_eras)) {
            $rules['app_eras'] = 'array';
            $rules['app_eras.*'] = 'in:golden_age,post_war_50s,west_indies,odi_90s,modern,womens';
        }

        // etc.
        if (! empty($rules)) {
            $this->validate($rules);
        }
    }

    protected function resetWizard()
    {
        $this->reset([
            'createStep', 'create_name', 'create_email', 'create_phone', 'create_tier_id', 'create_expires_at',
            'password_option', 'create_password', 'create_password_confirmation',
            'app_full_name', 'app_dob', 'app_country', 'app_city',
            'app_preferred_formats', 'app_eras',
            'app_has_acquired_before', 'app_focus', 'app_investment_horizon', 'app_interests'
        ]);
        $this->resetValidation();
    }
    
    public function updatedMembershipFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function confirmDeleteUser($id)
    {
        $this->dispatch('show-delete-confirmation', type: 'user', id: $id);
    }
    
    public function deleteUser($id)
    {
        $this->delete($id);
    }
    
    public function delete($id)
    {
        if ($id == auth()->id()) {
             session()->flash('error', 'You cannot delete yourself.');
             return;
        }
        User::find($id)->delete();
        session()->flash('success', 'User deleted successfully.');
    }
    
    public function viewUser($id)
    {
         $this->isEditMode = false;
         $this->loadUser($id);
         $this->dispatch('show-modal', id: 'userModal');
    }
    
    private function loadUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        
        $this->tierInfo = \Illuminate\Support\Facades\DB::table('memberships')
            ->join('membership_tiers', 'memberships.membership_tier_id', '=', 'membership_tiers.id')
            ->where('memberships.user_id', $user->id)
            ->whereIn('memberships.status', ['active', 'pending', 'expired']) 
            ->orderBy('memberships.created_at', 'desc')
            ->select('membership_tiers.name as tier_name', 'memberships.status', 'memberships.expires_at', 'memberships.started_at')
            ->first();
            
        $this->applications = \Illuminate\Support\Facades\DB::table('membership_applications')
            ->leftJoin('membership_tiers', 'membership_applications.selected_tier_id', '=', 'membership_tiers.id')
            ->where('membership_applications.user_id', $user->id)
            ->orderBy('membership_applications.created_at', 'desc')
            ->limit(5)
            ->select(
                'membership_applications.id',
                'membership_applications.status', 
                'membership_applications.submitted_at', 
                'membership_applications.reviewed_at',
                'membership_tiers.name as tier_name'
            )
            ->get();
    }

    public function completeRegistration(\App\Services\Admin\MembershipAdminService $service)
    {
        $this->validate([
            'complete_tier_id' => 'required|exists:membership_tiers,id',
            'complete_expires_at' => 'nullable|date|after:today',
        ], [], [
            'complete_tier_id' => 'membership tier',
            'complete_expires_at' => 'expiry date',
        ]);

        try {
            $user = User::findOrFail($this->userId);
            $service->completeUserRegistration($user, $this->complete_tier_id, $this->complete_expires_at);

            session()->flash('success', 'Registration completed and membership assigned successfully.');
            $this->loadUser($this->userId); // Refresh data
            $this->reset(['complete_tier_id', 'complete_expires_at']);
            
        } catch (\Exception $e) {
            $this->addError('complete_registration_error', $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->reset(['name', 'email', 'phone', 'userId', 'isEditMode', 'tierInfo', 'applications']);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    // --- Bulk Import Methods ---
    
    public function resetBulkUpload()
    {
        $this->reset(['bulkUploadFile', 'bulkPreview', 'bulkPreviewRows', 'bulkResults', 'editingRowIndex', 'editingRowData']);
    }

    public function downloadTemplate($format = 'xlsx')
    {
        $filename = 'ecc_users_import_template.' . $format;

        if ($format === 'xlsx') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\Admin\UserImportTemplateExport(), 
                $filename
            );
        }

        // CSV Fallback
        $headers = [
            'full_name', 'email', 'phone', 'membership_tier_code', 'membership_expiry_date',
            'dob', 'country', 'city', 'state',
            'preferred_formats', 'eras', 'has_acquired_memorabilia_before', 
            'focus', 'investment_horizon', 'interests', 'postal_code'
        ];

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, $headers);
        
        // Add sample row (India-based)
        fputcsv($handle, [
            'Aryan Sharma', 'aryan@example.com', '+919876543210', 'PAVILION', '2030-12-31',
            '1985-06-15', 'India', 'Mumbai', 'Maharashtra',
            'test,odi', 'modern', 'no', 'legacy', '5', 'bats,balls', '400001'
        ]);

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function updatedBulkUploadFile()
    {
        $this->validate([
            'bulkUploadFile' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $this->previewImport();
    }

    public function previewImport()
    {
        if (!$this->bulkUploadFile) return;

        try {
            $path = $this->bulkUploadFile->getRealPath();
            $extension = $this->bulkUploadFile->getClientOriginalExtension();
            $importData = [];
            $headers = [];

            if (in_array(strtolower($extension), ['xlsx', 'xls'])) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                
                if (empty($sheet)) {
                    $this->addError('bulkUploadFile', 'The uploaded Excel file appears to be empty.');
                    return;
                }

                $headers = array_shift($sheet);
                $importData = $sheet;
            } else {
                $handle = fopen($path, 'r');
                $headers = fgetcsv($handle);
                if (!$headers) {
                    fclose($handle);
                    return;
                }

                while (($row = fgetcsv($handle)) !== false) {
                    $importData[] = $row;
                }
                fclose($handle);
            }
            
            // Normalize headers (handle both numeric indices from CSV and letter indices from Excel)
            $headers = array_map(function($h) { return trim(strtolower((string)$h)); }, $headers);
            
            $previewRows = [];
            $importService = app(\App\Services\Admin\AdminUserBulkImportService::class);

            foreach ($importData as $row) {
                // Skip completely empty rows
                if (empty(array_filter($row, function($value) { return trim((string)$value) !== ''; }))) {
                    continue;
                }

                $mappedRow = [];
                foreach ($headers as $index => $header) {
                    // $index will be 'A', 'B'... for Excel or 0, 1, 2... for CSV
                    $mappedRow[$header] = isset($row[$index]) ? trim((string)$row[$index]) : null;
                }

                $validation = $importService->validateRowData($mappedRow);

                $previewRows[] = [
                    'data' => $mappedRow,
                    'errors' => $validation['errors'],
                    'is_valid' => $validation['is_valid'],
                    'is_corrected' => false,
                    'status' => $validation['is_valid'] ? 'Ready' : 'Needs Fix'
                ];
            }

            $this->bulkPreviewRows = $previewRows;
            $this->bulkPreview = [
                'total_rows' => count($previewRows),
                'headers' => $headers,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Bulk import preview error: ' . $e->getMessage(), ['exception' => $e]);
            $this->addError('bulkUploadFile', 'Failed to parse file: ' . $e->getMessage());
            $this->resetBulkUpload();
        }
    }

    public function editRow($index)
    {
        $this->editingRowIndex = $index;
        $this->editingRowData = $this->bulkPreviewRows[$index]['data'];
    }

    public function cancelEdit()
    {
        $this->editingRowIndex = null;
        $this->editingRowData = [];
    }

    public function updateRow()
    {
        if ($this->editingRowIndex === null) return;

        $importService = app(\App\Services\Admin\AdminUserBulkImportService::class);
        $validation = $importService->validateRowData($this->editingRowData);

        $this->bulkPreviewRows[$this->editingRowIndex]['data'] = $this->editingRowData;
        $this->bulkPreviewRows[$this->editingRowIndex]['errors'] = $validation['errors'];
        $this->bulkPreviewRows[$this->editingRowIndex]['is_valid'] = $validation['is_valid'];
        $this->bulkPreviewRows[$this->editingRowIndex]['is_corrected'] = true;
        $this->bulkPreviewRows[$this->editingRowIndex]['status'] = $validation['is_valid'] ? 'Corrected' : 'Needs Fix';

        $this->cancelEdit();
        $this->dispatch('operation-success', 'Row updated and re-validated.');
    }

    public function removeRow($index)
    {
        unset($this->bulkPreviewRows[$index]);
        $this->bulkPreviewRows = array_values($this->bulkPreviewRows); // Re-index
        $this->bulkPreview['total_rows'] = count($this->bulkPreviewRows);
    }
    
    public function processImport(\App\Services\Admin\AdminUserBulkImportService $importService)
    {
        if (empty($this->bulkPreviewRows)) {
             $this->previewImport();
        }
        
        if (empty($this->bulkPreviewRows)) return;
        
        $results = [
            'total' => count($this->bulkPreviewRows),
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'failed_rows' => []
        ];

        foreach ($this->bulkPreviewRows as $index => $rowMeta) {
            if (!$rowMeta['is_valid']) {
                $isDuplicate = collect($rowMeta['errors'])->contains(fn($e) => stripos($e, 'already exists') !== false);
                
                if ($isDuplicate) {
                    $results['skipped']++;
                    $results['failed_rows'][] = [
                        'row_number' => $index + 1,
                        'data' => $rowMeta['data'],
                        'error' => implode(', ', $rowMeta['errors']),
                        'type' => 'duplicate'
                    ];
                } else {
                    $results['failed']++;
                    $results['failed_rows'][] = [
                        'row_number' => $index + 1,
                        'data' => $rowMeta['data'],
                        'error' => implode(', ', $rowMeta['errors']),
                        'type' => 'validation'
                    ];
                }
                continue;
            }

            try {
                $importService->processRow($rowMeta['data'], $index + 1);
                $results['created']++;
            } catch (\App\Exceptions\BulkImportDuplicateException $e) {
                $results['skipped']++;
                $results['failed_rows'][] = [
                    'row_number' => $index + 1,
                    'data' => $rowMeta['data'],
                    'error' => $e->getMessage(),
                    'type' => 'duplicate'
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['failed_rows'][] = [
                    'row_number' => $index + 1,
                    'data' => $rowMeta['data'],
                    'error' => $e->getMessage(),
                    'type' => 'error'
                ];
            }
        }

        $this->bulkResults = $results;
        $this->dispatch('operation-success', 'Import completed successfully.');
        $this->resetPage();
    }

    public function downloadErrorReport()
    {
        if (empty($this->bulkResults['failed_rows'])) return;

        $failedRows = $this->bulkResults['failed_rows'];
        $headers = ['Row Number', 'Error Type', 'Error Message'];
        $dataKeys = [];
        
        foreach ($failedRows as $row) {
            if (isset($row['data']) && is_array($row['data'])) {
                foreach (array_keys($row['data']) as $key) {
                    if (!in_array($key, $dataKeys)) $dataKeys[] = $key;
                }
            }
        }
        
        $headers = array_merge($headers, $dataKeys);
        $filename = 'import_error_report_' . date('Y_m_d_His') . '.csv';
        
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, $headers);
        
        foreach ($failedRows as $row) {
            $csvRow = [
                $row['row_number'],
                $row['type'],
                $row['error']
            ];
            foreach ($dataKeys as $key) {
                $csvRow[] = $row['data'][$key] ?? '';
            }
            fputcsv($handle, $csvRow);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
