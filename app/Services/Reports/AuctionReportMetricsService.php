<?php

namespace App\Services\Reports;

use App\Models\Auctions\AuctionLot;
use App\Models\Auctions\AuctionBid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AuctionReportMetricsService
{
    /**
     * Get auction metrics (KPIs + Chart Data) with caching.
     */
    public function getMetrics(string $startDate, string $endDate, string $status = '', string $search = ''): array
    {
        $cacheKey = 'auction_report_metrics_' . md5($startDate . $endDate . $status . $search);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate, $status, $search) {
            $baseQuery = $this->getBaseQuery($startDate, $endDate, $status, $search);

            // 1. KPI Metrics
            $kpis = [
                'total_lots' => (clone $baseQuery)->count(),
                'total_bids' => (clone $baseQuery)->join('auction_bids', 'auction_lots.id', '=', 'auction_bids.auction_lot_id')->count(),
                'total_revenue' => (clone $baseQuery)->where('status', 'ended')->sum('current_highest_bid'),
                'success_rate' => $this->calculateSuccessRate(clone $baseQuery),
            ];

            // 2. Status Breakdown
            $statusStats = (clone $baseQuery)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();

            // 3. Bids Trend
            $trendData = $this->getBidsTrend($startDate, $endDate, $status, $search);

            return [
                'kpis' => $kpis,
                'charts' => [
                    'status' => [
                        'labels' => $statusStats->pluck('status')->map(fn($s) => ucfirst($s))->toArray(),
                        'series' => $statusStats->pluck('count')->toArray(),
                    ],
                    'trend' => $trendData,
                ]
            ];
        });
    }

    /**
     * Build the base auction lot query.
     */
    public function getBaseQuery(string $startDate, string $endDate, string $status = '', string $search = '')
    {
        $query = AuctionLot::query()
            ->whereBetween('auction_lots.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($status) {
            $query->where('auction_lots.status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('reference_number', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    private function calculateSuccessRate($query): float
    {
        $totalEnded = (clone $query)->where('status', 'ended')->count();
        if ($totalEnded === 0) return 0;

        // A sale is successful if current_highest_bid >= reserve_price (conceptually, though admins can override)
        // Here we'll treat any 'ended' lot with current_highest_bid > 0 as a "soft" success for reporting
        $successful = (clone $query)->where('status', 'ended')->where('current_highest_bid', '>', 0)->count();
        
        return round(($successful / $totalEnded) * 100, 1);
    }

    /**
     * Calculate bids trend based on date range.
     */
    private function getBidsTrend(string $startDate, string $endDate, string $status, string $search): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end);

        if ($days <= 31) {
            $format = '%Y-%m-%d';
            $displayFormat = 'd M';
        } elseif ($days <= 90) {
            $format = '%Y-%u';
            $displayFormat = '\W\k W, Y';
        } else {
            $format = '%Y-%m';
            $displayFormat = 'M Y';
        }

        // We filter bids by the lots that match the search/date/status criteria
        $lotIds = $this->getBaseQuery($startDate, $endDate, $status, $search)->pluck('id');

        $stats = AuctionBid::whereIn('auction_lot_id', $lotIds)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'labels' => $stats->pluck('period')->map(function($p) use ($displayFormat, $days) {
                if ($days <= 31) return Carbon::parse($p)->format($displayFormat);
                if ($days <= 90) {
                    [$year, $week] = explode('-', $p);
                    return "Wk {$week}, {$year}";
                }
                return Carbon::parse($p . '-01')->format($displayFormat);
            })->toArray(),
            'series' => $stats->pluck('count')->toArray(),
        ];
    }
}
