<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Shop\ShopOrder;
use App\Models\Order;
use App\Support\Reports\CsvExporter;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('layouts.admin')]
class SalesReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $search = '';
    public $source = 'all';
    
    // KPI Details Modal State
    public $kpiModalTitle;
    public $kpiModalHeaders = [];
    public $kpiModalRows = [];
    public $kpiModalFooter;
    public $kpiModalAction;
    public $kpiFilterActive = false;
    public $kpiFilterName = '';

    protected $queryString = ['startDate', 'endDate', 'search', 'source'];

    protected $listeners = [
        'refreshReport' => '$refresh',
        'reports:request-charts' => 'refresh'
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'search', 'source'])) {
            $this->resetPage();
            $this->dispatch('reports:render-charts', report: 'sales', payload: $this->getMetrics()['charts']);
        }
    }

    public function refresh()
    {
        $this->resetPage();
        $this->dispatch('reports:render-charts', report: 'sales', payload: $this->getMetrics()['charts']);
    }

    public function viewKpiDetails($key)
    {
        $service = app(\App\Services\Reports\SalesReportMetricsService::class);
        $metrics = $service->getMetrics($this->startDate, $this->endDate, $this->search, $this->source);

        if ($key === 'total_orders') {
            $this->kpiModalTitle = "Orders Breakdown";
            $this->kpiModalHeaders = ["Source", "Orders Count", "% of Total"];
            
            $total = $metrics['total_orders'] ?: 1;
            $this->kpiModalRows = [
                ['Shop', $metrics['details']['shop']['count'], round(($metrics['details']['shop']['count'] / $total) * 100, 1) . '%'],
                ['Auction', $metrics['details']['auction']['count'], round(($metrics['details']['auction']['count'] / $total) * 100, 1) . '%'],
                ['Archive', $metrics['details']['archive']['count'], round(($metrics['details']['archive']['count'] / $total) * 100, 1) . '%'],
            ];
            $this->kpiModalFooter = "Total Orders: <strong>{$metrics['total_orders']}</strong>";
            $this->kpiModalAction = null;
            
            $this->dispatch('open-kpi-modal');
            $this->dispatch('report:scrollToTable');
        }

        if ($key === 'total_revenue') {
            $this->kpiModalTitle = "Revenue Breakdown";
            $this->kpiModalHeaders = ["Source", "Revenue (INR)", "% of Total"];
            
            $total = $metrics['total_revenue'] ?: 1;
            $this->kpiModalRows = [
                ['Shop', '₹' . number_format($metrics['details']['shop']['revenue'], 2), round(($metrics['details']['shop']['revenue'] / $total) * 100, 1) . '%'],
                ['Auction', '₹' . number_format($metrics['details']['auction']['revenue'], 2), round(($metrics['details']['auction']['revenue'] / $total) * 100, 1) . '%'],
                ['Archive', '₹' . number_format($metrics['details']['archive']['revenue'], 2), round(($metrics['details']['archive']['revenue'] / $total) * 100, 1) . '%'],
            ];
            $this->kpiModalFooter = "Total Revenue: <strong>₹" . number_format($metrics['total_revenue'], 2) . "</strong>";
            $this->kpiModalAction = null;

            $this->dispatch('open-kpi-modal');
        }
    }

    private function getMetrics()
    {
        $metrics = app(\App\Services\Reports\SalesReportMetricsService::class)->getMetrics(
            $this->startDate,
            $this->endDate,
            $this->search,
            $this->source
        );

        return [
            'kpis' => [
                'total_count' => $metrics['total_orders'],
                'total_revenue' => $metrics['total_revenue'],
            ],
            'charts' => $metrics['chart']
        ];
    }

    public function export($sourceType = 'all')
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $search = $this->search;
        $appliedSource = $sourceType === 'current' ? $this->source : $sourceType;

        $query = app(\App\Services\Reports\SalesReportMetricsService::class)->getBaseQuery(
            $startDate, $endDate, $search, $appliedSource
        )->orderBy('paid_at', 'desc');

        $fileName = "sales-report-{$appliedSource}-" . now()->format('Y-m-d') . ".csv";

        return response()->streamDownload(function () use ($query, $appliedSource, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // BOM for UTF-8 Excel support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Metadata
            fputcsv($file, ['Report:', 'Sales Report']);
            fputcsv($file, ['Source:', ucfirst($appliedSource)]);
            fputcsv($file, ['Date Range:', "{$startDate} to {$endDate}"]);
            fputcsv($file, ['Generated At:', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []); // Blank line

            // Headers
            fputcsv($file, ['Date', 'Order #', 'Source', 'Customer', 'Amount (INR)', 'Status']);

            // Data
            $query->chunk(500, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    fputcsv($file, [
                        $row->paid_at ? Carbon::parse($row->paid_at)->format('Y-m-d H:i') : 'N/A',
                        $row->order_number,
                        ucfirst($row->source),
                        $row->customer_name,
                        number_format($row->total_amount, 2, '.', ''),
                        ucfirst($row->status),
                    ]);
                }
            });

            fclose($file);
        }, $fileName);
    }

    private function getSalesDataQuery()
    {
        return app(\App\Services\Reports\SalesReportMetricsService::class)->getBaseQuery(
            $this->startDate,
            $this->endDate,
            $this->search,
            $this->source
        )->orderBy('paid_at', 'desc');
    }

    public function render()
    {
        $metrics = $this->getMetrics();
        $sales = $this->getSalesDataQuery()->paginate(15);

        return view('livewire.admin.reports.sales-report', [
            'sales' => $sales,
            'kpis' => $metrics['kpis'],
            'charts' => $metrics['charts']
        ]);
    }
}
