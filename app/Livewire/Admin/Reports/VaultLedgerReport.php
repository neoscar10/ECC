<?php

namespace App\Livewire\Admin\Reports;

use App\Models\UserVaultItem;
use App\Support\Reports\CsvExporter;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.admin')]
class VaultLedgerReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $status = 'locked';
    public $search = '';
    
    // KPI Details Modal State
    public $kpiModalTitle;
    public $kpiModalHeaders = [];
    public $kpiModalRows = [];
    public $kpiModalFooter;
    public $kpiModalAction;

    protected $listeners = [
        'refreshReport' => '$refresh',
        'reports:request-charts' => 'refresh'
    ];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'status' => ['except' => ''],
        'search' => ['except' => '']
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->subMonths(6)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        
        // Ensure charts render on first load
        $this->dispatch('reports:charts', report: 'vault', payload: $this->getMetrics()['charts']);
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'status', 'search'])) {
            $this->resetPage();
            $this->dispatch('reports:charts', report: 'vault', payload: $this->getMetrics()['charts']);
        }
    }

    public function refresh()
    {
        $this->resetPage();
        $this->dispatch('reports:charts', report: 'vault', payload: $this->getMetrics()['charts']);
    }

    public function viewKpiDetails($key)
    {
        if ($key === 'total_transactions') {
            $this->status = '';
            $this->resetPage();
            $this->dispatch('report:scrollToTable');
        } elseif ($key === 'currently_locked') {
            $this->status = 'locked';
            $this->resetPage();
            $this->dispatch('report:scrollToTable');
        } elseif ($key === 'total_removed') {
            $this->status = 'removed';
            $this->resetPage();
            $this->dispatch('report:scrollToTable');
        } elseif ($key === 'unique_users') {
            $this->kpiModalTitle = "Vault Activity by User";
            $this->kpiModalHeaders = ["User", "Email", "Items count"];
            
            $topUsers = $this->getVaultQuery()
                ->select('user_id', DB::raw('count(*) as count'))
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();
                
            $this->kpiModalRows = $topUsers->map(function($u) {
                return [
                    $u->user?->name ?? 'Unknown',
                    $u->user?->email ?? '-',
                    $u->count
                ];
            })->toArray();
            
            $this->kpiModalFooter = "Showing top 10 most active vault users.";
            $this->dispatch('open-kpi-modal');
        } elseif ($key === 'total_value_locked') {
            $this->kpiModalTitle = "Highest Value Locked Items";
            $this->kpiModalHeaders = ["Item", "User", "Value (INR)"];
            
            $topItems = $this->getVaultQuery()
                ->where('status', 'locked')
                ->orderBy('price', 'desc')
                ->limit(10)
                ->get();
                
            $this->kpiModalRows = $topItems->map(fn($i) => [
                $i->item_title,
                $i->user?->name ?? 'Unknown',
                '₹' . number_format($i->price, 2)
            ])->toArray();
            
            $this->kpiModalFooter = "Showing top 10 highest-value currently locked items.";
            $this->dispatch('open-kpi-modal');
        }
    }

    public function export($type = 'current')
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $search = $this->search;
        
        $statusFilter = $this->status;
        if ($type === 'locked') $statusFilter = 'locked';
        if ($type === 'removed') $statusFilter = 'removed';

        $service = app(\App\Services\Reports\VaultLedgerReportMetricsService::class);
        $query = $service->getBaseQuery($startDate, $endDate, $statusFilter, $search)
            ->with(['user', 'saleContext'])
            ->latest('locked_at');

        $fileName = "vault-ledger-{$type}-" . now()->format('Y-m-d') . ".csv";

        return response()->streamDownload(function () use ($query, $startDate, $endDate, $statusFilter, $type) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

            // Metadata
            fputcsv($file, ['Report:', 'Vault Ledger Report']);
            fputcsv($file, ['Type:', ucfirst($type)]);
            fputcsv($file, ['Date Range:', "{$startDate} to {$endDate}"]);
            if ($statusFilter) fputcsv($file, ['Status Filter:', ucfirst($statusFilter)]);
            fputcsv($file, ['Generated At:', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            // Headers
            fputcsv($file, ['User', 'Email', 'Item Title', 'Ref #', 'Price (INR)', 'Status', 'Locked At', 'Removed At']);

            // Data
            $query->chunk(500, function ($items) use ($file) {
                foreach ($items as $item) {
                    fputcsv($file, [
                        $item->user?->name,
                        $item->user?->email,
                        $item->item_title,
                        $item->item_ref,
                        $item->price,
                        ucfirst($item->status),
                        $item->locked_at ? $item->locked_at->format('Y-m-d H:i') : 'N/A',
                        $item->removed_at ? $item->removed_at->format('Y-m-d H:i') : '-',
                    ]);
                }
            });

            fclose($file);
        }, $fileName);
    }

    private function getMetrics()
    {
        return app(\App\Services\Reports\VaultLedgerReportMetricsService::class)->getMetrics(
            $this->startDate,
            $this->endDate,
            $this->status ?? '',
            $this->search
        );
    }

    private function getVaultQuery()
    {
        return app(\App\Services\Reports\VaultLedgerReportMetricsService::class)->getBaseQuery(
            $this->startDate,
            $this->endDate,
            $this->status ?? '',
            $this->search
        )->with(['user', 'saleContext'])->latest('locked_at');
    }

    public function render()
    {
        $metrics = $this->getMetrics();
        $items = $this->getVaultQuery()->paginate(15);
        
        $statusOptions = [
            'locked' => 'Locked',
            'removed' => 'Removed',
        ];

        return view('livewire.admin.reports.vault-ledger-report', [
            'items' => $items,
            'statusOptions' => $statusOptions,
            'kpis' => $metrics['kpis'],
            'charts' => $metrics['charts']
        ]);
    }
}
