<?php

namespace App\Livewire\Admin;

use App\Services\Admin\AdminDashboardMetricsService;
use Livewire\Component;
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

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public $kpis = [];
    public $queues = [];

    // Modal states
    public $selectedApplication = null;
    public $adminNote = '';
    public $rejectionReason = '';

    // Chart filters
    public $chartRange = 'today';
    public $chartSource = 'all';
    public $chartStartDate = null;
    public $chartEndDate = null;

    public function mount(AdminDashboardMetricsService $service)
    {
        $this->loadData($service);
    }

    public function updatedChartRange(AdminDashboardMetricsService $service)
    {
        $this->updateChart($service);
    }

    public function updatedChartSource(AdminDashboardMetricsService $service)
    {
        $this->updateChart($service);
    }

    public function updatedChartStartDate(AdminDashboardMetricsService $service)
    {
        if ($this->chartEndDate) {
            $this->updateChart($service);
        }
    }

    public function updatedChartEndDate(AdminDashboardMetricsService $service)
    {
        if ($this->chartStartDate) {
            $this->updateChart($service);
        }
    }

    public function updateChart(AdminDashboardMetricsService $service)
    {
        $this->kpis['sales_trend'] = $service->calculateSalesTrend(
            $this->chartRange,
            $this->chartStartDate,
            $this->chartEndDate,
            $this->chartSource
        );
        
        $this->dispatch('chartDataUpdated', $this->kpis['sales_trend']);
    }

    public function refresh(AdminDashboardMetricsService $service)
    {
        $service->clearCache();
        $this->loadData($service);
    }

    private function loadData(AdminDashboardMetricsService $service)
    {
        $this->kpis = $service->getKpiMetrics(
            $this->chartRange,
            $this->chartStartDate,
            $this->chartEndDate,
            $this->chartSource
        );
        $this->queues = $service->getNeedsAttentionQueues();
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
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

    public function approve(AdminDashboardMetricsService $service)
    {
        if (!$this->selectedApplication) return;

        if (in_array($this->selectedApplication->status, ['approved', 'rejected'])) {
            session()->flash('error', 'This application has already been processed.');
            return;
        }

        $app = $this->selectedApplication;
        $tier = $app->membershipTier;

        $app->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);
        
        Membership::create([
            'user_id' => $app->user_id,
            'membership_tier_id' => $tier ? $tier->id : null,
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'started_at' => now(),
            'expires_at' => $tier ? now()->addDays($tier->duration_days) : null,
            'source_application_id' => $app->id,
            'notes' => $this->adminNote
        ]);

        if ($app->user && $app->user->email) {
            try {
                Mail::to($app->user->email)->send(new MembershipApplicationApprovedMail($app));
            } catch (\Exception $e) {
                Log::error('Failed to send approval email: ' . $e->getMessage());
            }
        }

        // Push Notification logic... (simplified for brevity or copied fully)
        $this->sendApprovalNotification($app);

        session()->flash('success', 'Application approved and membership activated.');
        $this->dispatch('close-modals');
        $this->refresh($service);
    }

    public function confirmReject($id)
    {
        $this->selectedApplication = MembershipApplication::findOrFail($id);
        $this->rejectionReason = '';
        $this->dispatch('open-reject-modal');
    }

    public function reject(AdminDashboardMetricsService $service)
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
        ]);

        if ($app->user && $app->user->email) {
            try {
                Mail::to($app->user->email)->send(new MembershipApplicationRejectedMail($app, $this->rejectionReason));
            } catch (\Exception $e) {
                Log::error('Failed to send rejection email: ' . $e->getMessage());
            }
        }

        session()->flash('success', 'Application rejected.');
        $this->dispatch('close-modals');
        $this->refresh($service);
    }

    public function openRevenueModal()
    {
        $this->dispatch('open-revenue-modal');
    }

    public function openEnquiriesModal()
    {
        Log::info('Dashboard: Opening Enquiries Modal');
        $this->dispatch('open-enquiries-modal');
    }

    private function sendApprovalNotification($app)
    {
         if ($app->user) {
            $dedupeService = app(NotificationDedupe::class);
            $dedupeKey = "account_approved:user:{$app->user_id}";

            if (!$dedupeService->alreadySent($dedupeKey)) {
                try {
                    SendFcmToUserJob::dispatchSync(
                        $app->user_id,
                        'Account Approved',
                        'Your ECC account has been approved.',
                        ['type' => 'account_approved']
                    );
                    $dedupeService->markSent($dedupeKey, 'account_approved', null, $app->user_id);
                } catch (\Exception $e) {
                     Log::error('ACCOUNT_APPROVED_SEND_FAILED', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
