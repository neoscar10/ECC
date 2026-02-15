<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Auctions\AuctionLot;
use App\Support\Reports\CsvExporter;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.admin')]
class AuctionReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $status = 'ended';
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
        $this->startDate = Carbon::now()->subMonths(3)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'status', 'search'])) {
            $this->resetPage();
            $this->dispatch('reports:render-charts', report: 'auction', payload: $this->getMetrics()['charts']);
        }
    }

    public function refresh()
    {
        $this->resetPage();
        $this->dispatch('reports:render-charts', report: 'auction', payload: $this->getMetrics()['charts']);
    }

    public function viewKpiDetails($key)
    {
        if ($key === 'total_lots') {
            $this->status = '';
            $this->resetPage();
            $this->dispatch('report:scrollToTable');
        } elseif ($key === 'total_bids') {
            $this->dispatch('report:scrollToTrend');
        } elseif ($key === 'total_revenue') {
            $this->kpiModalTitle = "Top Revenue Lots";
            $this->kpiModalHeaders = ["Lot Title", "Final Bid (INR)", "Total Bids"];
            
            $topLots = $this->getAuctionQuery()
                ->where('status', 'ended')
                ->where('current_highest_bid', '>', 0)
                ->orderBy('current_highest_bid', 'desc')
                ->limit(10)
                ->get();

            $this->kpiModalRows = $topLots->map(fn($l) => [
                $l->title,
                '₹' . number_format($l->current_highest_bid, 2),
                $l->bids_count
            ])->toArray();

            $this->kpiModalFooter = "Showing top 10 revenue-generating completed lots.";
            $this->kpiModalAction = null;
            $this->dispatch('open-kpi-modal');
        } elseif ($key === 'success_rate') {
            $this->status = 'ended';
            $this->resetPage();
            $this->dispatch('report:scrollToTable');
        }
    }

    public function export()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $status = $this->status ?? '';
        $search = $this->search;

        $service = app(\App\Services\Reports\AuctionReportMetricsService::class);
        $query = $service->getBaseQuery($startDate, $endDate, $status, $search)
            ->withCount('bids')
            ->latest('ends_at');

        $fileName = "auction-report-" . now()->format('Y-m-d') . ".csv";

        return response()->streamDownload(function () use ($query, $startDate, $endDate, $status) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

            // Metadata
            fputcsv($file, ['Report:', 'Auction Performance Report']);
            fputcsv($file, ['Date Range:', "{$startDate} to {$endDate}"]);
            if ($status) fputcsv($file, ['Status:', ucfirst($status)]);
            fputcsv($file, ['Generated At:', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            // Headers
            fputcsv($file, ['Lot Title', 'Ref #', 'Status', 'Start Price', 'Final Bid', 'Bids Count', 'Ends At']);

            // Data
            $query->chunk(500, function ($lots) use ($file) {
                foreach ($lots as $lot) {
                    fputcsv($file, [
                        $lot->title,
                        $lot->reference_number,
                        ucfirst($lot->status),
                        $lot->starting_price,
                        $lot->current_highest_bid ?? '0',
                        $lot->bids_count,
                        $lot->ends_at ? $lot->ends_at->format('Y-m-d H:i') : 'N/A',
                    ]);
                }
            });

            fclose($file);
        }, $fileName);
    }

    private function getMetrics()
    {
        return app(\App\Services\Reports\AuctionReportMetricsService::class)->getMetrics(
            $this->startDate,
            $this->endDate,
            $this->status ?? '',
            $this->search
        );
    }

    private function getAuctionQuery()
    {
        return app(\App\Services\Reports\AuctionReportMetricsService::class)->getBaseQuery(
            $this->startDate,
            $this->endDate,
            $this->status ?? '',
            $this->search
        )->withCount('bids')->latest('ends_at');
    }

    public function render()
    {
        $metrics = $this->getMetrics();
        $lots = $this->getAuctionQuery()->paginate(15);
        
        $statusOptions = [
            'live' => 'Live',
            'ended' => 'Ended',
            'upcoming' => 'Upcoming',
            'unsold' => 'Unsold',
        ];

        return view('livewire.admin.reports.auction-report', [
            'lots' => $lots,
            'statusOptions' => $statusOptions,
            'kpis' => $metrics['kpis'],
            'charts' => $metrics['charts']
        ]);
    }
}
