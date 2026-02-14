<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Membership;
use App\Models\MembershipTier;
use App\Support\Reports\CsvExporter;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.admin')]
class MembershipReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $status = 'active';
    public $tierId = '';
    public $search = '';

    protected $queryString = ['startDate', 'endDate', 'status', 'tierId', 'search'];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function refresh()
    {
        $this->resetPage();
    }

    public function export()
    {
        $data = $this->getMembershipQuery()->get();
        
        $headers = ['Member Name', 'Email', 'Tier', 'Status', 'Started At', 'Expires At'];
        
        $exportData = $data->map(function($m) {
            return [
                $m->user->name,
                $m->user->email,
                $m->membershipTier->name,
                ucfirst($m->status),
                $m->started_at ? $m->started_at->format('Y-m-d') : 'N/A',
                $m->expires_at ? $m->expires_at->format('Y-m-d') : 'Lifetime',
            ];
        });

        return CsvExporter::download($exportData, $headers, 'membership-report-' . now()->format('Y-m-d') . '.csv');
    }

    private function getMembershipQuery()
    {
        $query = Membership::with(['user', 'membershipTier'])
            ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->tierId) {
            $query->where('membership_tier_id', $this->tierId);
        }

        if ($this->search) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest('started_at');
    }

    public function render()
    {
        $memberships = $this->getMembershipQuery()->paginate(15);
        
        $tiers = MembershipTier::all();
        
        $statusOptions = [
            'active' => 'Active',
            'pending' => 'Pending',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
        ];

        return view('livewire.admin.reports.membership-report', [
            'memberships' => $memberships,
            'tiers' => $tiers,
            'statusOptions' => $statusOptions,
            'totalCount' => $this->getMembershipQuery()->count(),
        ]);
    }
}
