<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SalesReportMetricsService
{
    /**
     * Get sales metrics (KPIs + Chart Data) with caching.
     */
    public function getMetrics(string $startDate, string $endDate, string $search = '', string $source = 'all'): array
    {
        $cacheKey = 'sales_report_metrics_' . md5($startDate . $endDate . $search . $source);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate, $search, $source) {
            $query = $this->getBaseQuery($startDate, $endDate, $search, $source);

            // 1. Calculate Totals
            $totals = (clone $query)->select([
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            ])->first();

            // 2. Calculate Stats by Source (for charts)
            $sourceStats = (clone $query)->select([
                'source',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as revenue')
            ])
            ->groupBy('source')
            ->get()
            ->keyBy('source');

            $sources = ['shop', 'auction', 'archive'];
            $chartData = [
                'orders' => [],
                'revenue' => [],
                'labels' => ['Shop', 'Auction', 'Archive']
            ];

            foreach ($sources as $s) {
                $stat = $sourceStats->get($s);
                $chartData['orders'][] = $stat ? (int) $stat->order_count : 0;
                $chartData['revenue'][] = $stat ? (float) $stat->revenue : 0;
            }

            return [
                'total_orders' => (int) ($totals->count ?? 0),
                'total_revenue' => (float) ($totals->revenue ?? 0),
                'chart' => $chartData
            ];
        });
    }

    /**
     * Build the base unified sales query.
     */
    public function getBaseQuery(string $startDate, string $endDate, string $search = '', string $source = 'all')
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

        $finalQuery = DB::table(DB::raw("({$query->toSql()}) as combined_sales"))
            ->mergeBindings($query);

        if ($startDate) {
            $finalQuery->where('paid_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $finalQuery->where('paid_at', '<=', $endDate . ' 23:59:59');
        }

        if ($source && $source !== 'all') {
            $finalQuery->where('source', $source);
        }

        if ($search) {
            $finalQuery->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%');
            });
        }

        return $finalQuery;
    }
}
