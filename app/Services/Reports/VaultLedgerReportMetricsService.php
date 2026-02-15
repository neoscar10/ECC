<?php

namespace App\Services\Reports;

use App\Models\UserVaultItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VaultLedgerReportMetricsService
{
    /**
     * Get vault metrics (KPIs + Chart Data) with caching.
     */
    public function getMetrics(string $startDate, string $endDate, string $status = '', string $search = ''): array
    {
        $cacheKey = 'vault_ledger_report_metrics_' . md5($startDate . $endDate . $status . $search);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate, $status, $search) {
            $baseQuery = $this->getBaseQuery($startDate, $endDate, $status, $search);

            // 1. KPI Metrics
            $kpis = [
                'total_transactions' => (clone $baseQuery)->count(),
                'currently_locked' => (clone $baseQuery)->where('status', 'locked')->count(),
                'total_removed' => (clone $baseQuery)->where('status', 'removed')->count(),
                'unique_users' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
                'total_value_locked' => (clone $baseQuery)->where('status', 'locked')->sum('price'),
            ];

            // 2. Status Breakdown
            $statusStats = (clone $baseQuery)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();

            // 3. Activity Trend
            $trendData = $this->getActivityTrend($startDate, $endDate, $status, $search);

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
     * Build the base vault item query.
     */
    public function getBaseQuery(string $startDate, string $endDate, string $status = '', string $search = '')
    {
        $query = UserVaultItem::query()
            ->whereBetween('locked_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('item_title', 'like', '%' . $search . '%')
                    ->orWhere('item_ref', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%')
                          ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }

    /**
     * Calculate activity trend based on date range.
     */
    private function getActivityTrend(string $startDate, string $endDate, string $status, string $search): array
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

        $stats = $this->getBaseQuery($startDate, $endDate, $status, $search)
            ->select(
                DB::raw("DATE_FORMAT(locked_at, '{$format}') as period"),
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
