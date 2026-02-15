<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Membership;
use App\Models\MembershipTier;
use App\Support\Reports\CsvExporter;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.admin')]
class MembershipReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $status = 'active';
    public $tierId = '';
    public $search = '';
    public $expiringSoonOnly = false;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'status' => ['except' => ''],
        'tierId' => ['except' => ''],
        'search' => ['except' => ''],
        'expiringSoonOnly' => ['except' => false]
    ];

    protected $listeners = [
        'refreshReport' => '$refresh',
        'reports:request-charts' => 'refresh'
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'status', 'tierId', 'search'])) {
            $this->resetPage();
            $this->dispatch('reports:render-charts', report: 'membership', payload: $this->getMetrics());
        }
    }

    public function refresh()
    {
        $this->resetPage();
        $this->dispatch('reports:render-charts', report: 'membership', payload: $this->getMetrics());
    }

    public function viewKpiDetails($key)
    {
        if ($key === 'total') {
            $this->status = '';
            $this->tierId = '';
            $this->expiringSoonOnly = false;
        } elseif ($key === 'active') {
            $this->status = 'active';
            $this->expiringSoonOnly = false;
        } elseif ($key === 'expired') {
            $this->status = 'expired';
            $this->expiringSoonOnly = false;
        } elseif ($key === 'expiring_soon') {
            $this->status = 'active';
            $this->expiringSoonOnly = true;
        }

        $this->resetPage();
        $this->dispatch('report:scrollToTable');
    }

    public function clearKpiFilter()
    {
        $this->expiringSoonOnly = false;
        $this->resetPage();
    }

    private function getMetrics()
    {
        $metrics = app(\App\Services\Reports\MembershipReportMetricsService::class)->getMetrics(
            $this->startDate,
            $this->endDate,
            $this->status ?? '',
            $this->tierId ?? '',
            $this->search
        );

        return [
            'kpis' => $metrics['kpis'],
            'charts' => [
                'tier' => $metrics['tier_chart'],
                'status' => $metrics['status_chart'],
                'trend' => $metrics['trend_chart'],
            ]
        ];
    }

    public function export($type = 'current')
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $status = $this->status ?? '';
        $tierId = $this->tierId ?? '';
        $search = $this->search;

        $service = app(\App\Services\Reports\MembershipReportMetricsService::class);
        $query = $service->getBaseQuery($startDate, $endDate, $status, $tierId, $search)
            ->with(['user', 'membershipTier'])
            ->latest('started_at');

        $fileName = "membership-report-{$type}-" . now()->format('Y-m-d') . ".csv";

        return response()->streamDownload(function () use ($query, $type, $startDate, $endDate, $status, $tierId, $service) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

            // Metadata
            fputcsv($file, ['Report:', 'Membership Report']);
            fputcsv($file, ['Export Type:', ucfirst(str_replace('_', ' ', $type))]);
            fputcsv($file, ['Date Range:', "{$startDate} to {$endDate}"]);
            if ($status) fputcsv($file, ['Status:', ucfirst($status)]);
            if ($tierId) fputcsv($file, ['Tier ID:', $tierId]);
            fputcsv($file, ['Generated At:', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            if ($type === 'tier_summary') {
                fputcsv($file, ['Tier Name', 'Member Count']);
                $stats = (clone $query)->join('membership_tiers', 'memberships.membership_tier_id', '=', 'membership_tiers.id')
                    ->select('membership_tiers.name', DB::raw('count(*) as count'))
                    ->groupBy('membership_tiers.name')->get();
                foreach($stats as $s) fputcsv($file, [$s->name, $s->count]);
            } elseif ($type === 'status_summary') {
                fputcsv($file, ['Status', 'Member Count']);
                $stats = (clone $query)->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')->get();
                foreach($stats as $s) fputcsv($file, [ucfirst($s->status), $s->count]);
            } elseif ($type === 'expiring_soon') {
                fputcsv($file, ['Member', 'Email', 'Tier', 'Expires At', 'Days Left']);
                $expiring = (clone $query)->where('status', 'active')
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays(30)])->get();
                foreach($expiring as $m) {
                    fputcsv($file, [
                        $m->user->name, $m->user->email, $m->membershipTier->name,
                        $m->expires_at->format('Y-m-d'), $m->expires_at->diffInDays(now())
                    ]);
                }
            } else {
                // Default: Current View
                fputcsv($file, ['Member', 'Email', 'Tier', 'Status', 'Joined At', 'Expires At']);
                $query->chunk(500, function ($memberships) use ($file) {
                    foreach ($memberships as $m) {
                        fputcsv($file, [
                            $m->user->name,
                            $m->user->email,
                            $m->membershipTier?->name,
                            ucfirst($m->status),
                            $m->started_at ? $m->started_at->format('Y-m-d') : 'N/A',
                            $m->expires_at ? $m->expires_at->format('Y-m-d') : 'Lifetime',
                        ]);
                    }
                });
            }

            fclose($file);
        }, $fileName);
    }

    private function getMembershipQuery()
    {
        $query = app(\App\Services\Reports\MembershipReportMetricsService::class)->getBaseQuery(
            $this->startDate,
            $this->endDate,
            $this->status ?? '',
            $this->tierId ?? '',
            $this->search
        );

        if ($this->expiringSoonOnly) {
            $query->where('status', 'active')
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(30)]);
        }

        return $query->with(['user', 'membershipTier'])->latest('started_at');
    }

    public function render()
    {
        $metrics = $this->getMetrics();
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
            'kpis' => $metrics['kpis'],
            'charts' => $metrics['charts']
        ]);
    }
}
