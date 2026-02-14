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

    protected $queryString = ['startDate', 'endDate', 'search'];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function refresh()
    {
        $this->resetPage();
    }

    public function export()
    {
        $data = $this->getSalesDataQuery()->get();
        
        $headers = ['Date', 'Order #', 'Source', 'Customer', 'Amount (INR)', 'Status'];
        
        $exportData = $data->map(function($sale) {
            return [
                $sale->paid_at ? Carbon::parse($sale->paid_at)->format('Y-m-d H:i') : 'N/A',
                $sale->order_number,
                ucfirst($sale->source),
                $sale->customer_name,
                number_format($sale->total_amount, 2),
                ucfirst($sale->status),
            ];
        });

        return CsvExporter::download($exportData, $headers, 'sales-report-' . now()->format('Y-m-d') . '.csv');
    }

    private function getSalesDataQuery()
    {
        $shopOrders = DB::table('shop_orders')
            ->join('users', 'shop_orders.user_id', '=', 'users.id')
            ->where('shop_orders.payment_status', 'paid')
            ->select([
                'shop_orders.paid_at',
                'shop_orders.order_number',
                DB::raw("'shop' as source"),
                'users.name as customer_name',
                'shop_orders.total_amount',
                'shop_orders.status',
            ]);

        $otherOrders = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereNotNull('orders.paid_at')
            ->select([
                'orders.paid_at',
                DB::raw("CONCAT('ECC-', orders.id) as order_number"),
                'orders.source',
                'users.name as customer_name',
                'orders.subtotal_inr as total_amount',
                DB::raw("'paid' as status"),
            ]);

        $query = $shopOrders->unionAll($otherOrders);

        // Wrap in a subquery for filtering and ordering
        $finalQuery = DB::table(DB::raw("({$query->toSql()}) as combined_sales"))
            ->mergeBindings($query);

        if ($this->startDate) {
            $finalQuery->where('paid_at', '>=', $this->startDate . ' 00:00:00');
        }

        if ($this->endDate) {
            $finalQuery->where('paid_at', '<=', $this->endDate . ' 23:59:59');
        }

        if ($this->search) {
            $finalQuery->where(function($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $this->search . '%');
            });
        }

        return $finalQuery->orderBy('paid_at', 'desc');
    }

    public function render()
    {
        $sales = $this->getSalesDataQuery()->paginate(15);
        
        $totalsQuery = $this->getSalesDataQuery();
        $totalsQuery->orders = null; // Clear order by for aggregate
        
        $totals = $totalsQuery->select([
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_amount) as total_revenue')
        ])->first();

        return view('livewire.admin.reports.sales-report', [
            'sales' => $sales,
            'totalCount' => $totals->count,
            'totalRevenue' => $totals->total_revenue,
        ]);
    }
}
