<?php

namespace App\Services\Reports;

use App\Models\Membership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MembershipReportMetricsService
{
    /**
     * Get membership metrics (KPIs + Chart Data) with caching.
     */
    public function getMetrics(string $startDate, string $endDate, string $status = '', string $tierId = '', string $search = ''): array
    {
        $cacheKey = 'membership_report_metrics_' . md5($startDate . $endDate . $status . $tierId . $search);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate, $status, $tierId, $search) {
            $baseQuery = $this->getBaseQuery($startDate, $endDate, $status, $tierId, $search);

            // 1. KPI Metrics
            $kpis = [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('status', 'active')->count(),
                'expired' => (clone $baseQuery)->where('status', 'expired')->count(),
                'expiring_soon' => (clone $baseQuery)
                    ->where('status', 'active')
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays(30)])
                    ->count(),
            ];

            // 2. Tiers Breakdown
            $tierStats = (clone $baseQuery)
                ->join('membership_tiers', 'memberships.membership_tier_id', '=', 'membership_tiers.id')
                ->select('membership_tiers.name', DB::raw('count(*) as count'))
                ->groupBy('membership_tiers.name')
                ->get();

            // 3. Status Breakdown
            $statusStats = (clone $baseQuery)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();

            // 4. Joins Trend
            $trendData = $this->getJoinsTrend($startDate, $endDate, $status, $tierId, $search);

            return [
                'kpis' => $kpis,
                'tier_chart' => [
                    'labels' => $tierStats->pluck('name')->toArray(),
                    'series' => $tierStats->pluck('count')->toArray(),
                ],
                'status_chart' => [
                    'labels' => $statusStats->pluck('status')->map(fn($s) => ucfirst($s))->toArray(),
                    'series' => $statusStats->pluck('count')->toArray(),
                ],
                'trend_chart' => $trendData,
            ];
        });
    }

    /**
     * Build the base membership query.
     */
    public function getBaseQuery(string $startDate, string $endDate, string $status = '', string $tierId = '', string $search = '')
    {
        $query = Membership::query()
            ->whereBetween('memberships.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($status) {
            $query->where('memberships.status', $status);
        }

        if ($tierId) {
            $query->where('memberships.membership_tier_id', $tierId);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    /**
     * Calculate signups trend based on date range.
     */
    private function getJoinsTrend(string $startDate, string $endDate, string $status, string $tierId, string $search): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end);

        $query = $this->getBaseQuery($startDate, $endDate, $status, $tierId, $search);

        if ($days <= 31) {
            // Daily
            $format = '%Y-%m-%d';
            $displayFormat = 'd M';
        } elseif ($days <= 90) {
            // Weekly
            $format = '%Y-%u'; // Year and week number
            $displayFormat = '\W\k W, Y';
        } else {
            // Monthly
            $format = '%Y-%m';
            $displayFormat = 'M Y';
        }

        $stats = $query->select(
            DB::raw("DATE_FORMAT(memberships.created_at, '{$format}') as period"),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('period')
        ->orderBy('period')
        ->get();

        return [
            'labels' => $stats->pluck('period')->map(function($p) use ($displayFormat, $days) {
                if ($days <= 31) return Carbon::parse($p)->format($displayFormat);
                if ($days <= 90) {
                    // For weekly, p is like "2023-45"
                    [$year, $week] = explode('-', $p);
                    return "Wk {$week}, {$year}";
                }
                // For monthly, p is like "2023-11"
                return Carbon::parse($p . '-01')->format($displayFormat);
            })->toArray(),
            'series' => $stats->pluck('count')->toArray(),
        ];
    }
}
