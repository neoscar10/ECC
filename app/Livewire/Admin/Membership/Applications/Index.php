<?php

namespace App\Livewire\Admin\Membership\Applications;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\MembershipApplication;
use App\Models\Membership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\MembershipApplicationApprovedMail;
use App\Mail\MembershipApplicationRejectedMail;
use App\Services\Notifications\NotificationDedupe;
use App\Jobs\Notifications\SendFcmToUserJob;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    
    // Modal states
    public $selectedApplication = null;
    public $showViewModal = false;
    public $adminNote = '';
    public $rejectionReason = '';

    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function view($id)
    {
        $this->selectedApplication = MembershipApplication::with(['user', 'membershipTier', 'payments'])->findOrFail($id);
        $this->dispatch('open-view-modal');
    }

    public function confirmApprove($id)
    {
        $this->selectedApplication = MembershipApplication::with('membershipTier')->findOrFail($id);
        $this->adminNote = '';
        $this->dispatch('open-approve-modal');
    }

    public function approve()
    {
        if (!$this->selectedApplication) return;

        if (in_array($this->selectedApplication->status, ['approved', 'rejected'])) {
            session()->flash('error', 'This application has already been processed.');
            return;
        }

        $app = $this->selectedApplication;
        $tier = $app->membershipTier;

        // 1. Update Application
        $app->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);
        
        // 2. Create Membership Record
        // Check if user already has active membership? Ignoring for now, just creating new one as per flow.
        Membership::create([
            'user_id' => $app->user_id,
            'membership_tier_id' => $tier ? $tier->id : null, // Fallback if no tier (shouldnt happen)
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'started_at' => now(),
            'expires_at' => $tier ? now()->addDays($tier->duration_days) : null,
            'source_application_id' => $app->id,
            'notes' => $this->adminNote
        ]);

        // 3. Send Email
        if ($app->user && $app->user->email) {
            try {
                Mail::to($app->user->email)->send(new MembershipApplicationApprovedMail($app));
            } catch (\Exception $e) {
                // Log email failure but don't stop flow
                \Illuminate\Support\Facades\Log::error('Failed to send approval email: ' . $e->getMessage());
            }
        }

        // 4. Send Push Notification (Account Approved)
        if ($app->user) {
            $dedupeService = app(NotificationDedupe::class);
            $dedupeKey = "account_approved:user:{$app->user_id}";

            if (!$dedupeService->alreadySent($dedupeKey)) {
                $tokensCount = $app->user->deviceTokens()->where('is_active', true)->count();

                if ($tokensCount === 0) {
                     Log::info('ACCOUNT_APPROVED_SEND_SKIPPED_NO_TOKENS', ['user_id' => $app->user->id]);
                     // Mark as sent to prevent retrying loop, even though skipped
                     $dedupeService->markSent($dedupeKey, 'account_approved', null, $app->user_id, ['status' => 'skipped_no_tokens']);
                } else {
                    Log::info('ACCOUNT_APPROVED_SEND_START', [
                        'user_id' => $app->user->id,
                        'tokens_count' => $tokensCount
                    ]);

                    $payload = [
                        'type' => 'account_approved',
                        'user_id' => (string)$app->user_id,
                        'membership_tier_id' => $tier ? (string)$tier->id : null,
                        'status' => 'approved',
                        'approved_at' => now()->toIso8601String(),
                        'target_page' => 'account_status',
                        'target_id' => (string)$app->user_id,
                    ];

                    try {
                        SendFcmToUserJob::dispatchSync(
                            $app->user_id,
                            'Account Approved',
                            'Your ECC account has been approved. You can now access member features.',
                            $payload
                        );
                        
                        Log::info('ACCOUNT_APPROVED_SEND_SUCCESS', ['user_id' => $app->user->id]);
                        $dedupeService->markSent($dedupeKey, 'account_approved', null, $app->user_id, $payload);

                    } catch (\Exception $e) {
                         Log::error('ACCOUNT_APPROVED_SEND_FAILED', [
                            'user_id' => $app->user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        session()->flash('success', 'Application approved and membership activated.');
        $this->dispatch('close-modals');
    }

    public function confirmReject($id)
    {
        $this->selectedApplication = MembershipApplication::findOrFail($id);
        $this->rejectionReason = '';
        $this->dispatch('open-reject-modal');
    }

    public function reject()
    {
        if (!$this->selectedApplication) return;
        
        $this->validate([
            'rejectionReason' => 'required|string|min:5',
        ]);

        if (in_array($this->selectedApplication->status, ['approved', 'rejected'])) {
            session()->flash('error', 'This application has already been processed.');
            return;
        }

        $app = $this->selectedApplication;

        $app->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
            // Assuming no column for reason in app table, but we use it for email
        ]);

        // Send Email
        if ($app->user && $app->user->email) {
            try {
                Mail::to($app->user->email)->send(new MembershipApplicationRejectedMail($app, $this->rejectionReason));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send rejection email: ' . $e->getMessage());
            }
        }

        session()->flash('success', 'Application rejected.');
        $this->dispatch('close-modals');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $query = MembershipApplication::with(['user', 'membershipTier']);

        if ($this->search) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $applications = $query->latest('submitted_at')->paginate(10);

        return view('livewire.admin.membership.applications.index', [
            'applications' => $applications
        ]);
    }
}
