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

            // 3. Activity Trend & Combo Chart
            $trendData = $this->getActivityTrend($startDate, $endDate, $status, $search);

            return [
                'kpis' => $kpis,
                'charts' => array_merge([
                    'statusDistribution' => [
                        'labels' => $statusStats->pluck('status')->map(fn($s) => ucfirst($s))->toArray(),
                        'series' => $statusStats->pluck('count')->toArray(),
                    ],
                ], $trendData)
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
     * Calculate starting net position before the report start date.
     */
    private function getStartingNetPosition(string $startDate, string $search): int
    {
        $query = UserVaultItem::where('locked_at', '<', $startDate . ' 00:00:00');

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

        $counts = $query->select(
            DB::raw('COUNT(CASE WHEN status = "locked" THEN 1 END) as locked_count'),
            DB::raw('COUNT(CASE WHEN status = "removed" THEN 1 END) as removed_count')
        )->first();

        return ($counts->locked_count ?? 0) - ($counts->removed_count ?? 0);
    }

    /**
     * Calculate activity trend based on date range (Combo Chart + Sparse Guard).
     */
    private function getActivityTrend(string $startDate, string $endDate, string $status, string $search): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end);

        // 1. Determine bucketing interval
        if ($days <= 31) {
            $interval = 'day';
            $format = '%Y-%m-%d';
            $displayFormat = 'd M';
        } elseif ($days <= 180) {
            $interval = 'week';
            $format = '%Y-%u';
            $displayFormat = '\W\k W, Y';
        } else {
            $interval = 'month';
            $format = '%Y-%m';
            $displayFormat = 'M Y';
        }

        // 2. Fetch raw activity within the range
        $stats = $this->getBaseQuery($startDate, $endDate, '', $search)
            ->select(
                DB::raw("DATE_FORMAT(locked_at, '{$format}') as period"),
                DB::raw('COUNT(CASE WHEN status = "locked" THEN 1 END) as locked_count'),
                DB::raw('COUNT(CASE WHEN status = "removed" THEN 1 END) as removed_count'),
                DB::raw('MIN(locked_at) as first_date'),
                DB::raw('MAX(locked_at) as last_date')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // 3. Sparse Data Guard Logic
        $activeBuckets = $stats->count();
        $totalPotentialBuckets = $this->estimateBuckets($start, $end, $interval);
        
        $focused = false;
        $focusNote = '';
        
        if ($totalPotentialBuckets > 24 && $activeBuckets > 0 && $activeBuckets <= 2) {
            $firstActivity = Carbon::parse($stats->min('first_date'));
            $lastActivity = Carbon::parse($stats->max('last_date'));
            
            // Focus range: [first - 14, last + 14] clamped to user range
            $focusStart = (clone $firstActivity)->subDays(14)->max($start);
            $focusEnd = (clone $lastActivity)->addDays(14)->min($end);
            
            $start = $focusStart;
            $end = $focusEnd;
            $focused = true;
            $focusNote = "Chart focused around activity (" . $firstActivity->format('d M') . " to " . $lastActivity->format('d M') . ")";
            
            // Re-fetch stats for the focused range
            $stats = $this->getBaseQuery($start->format('Y-m-d'), $end->format('Y-m-d'), '', $search)
                ->select(
                    DB::raw("DATE_FORMAT(locked_at, '{$format}') as period"),
                    DB::raw('COUNT(CASE WHEN status = "locked" THEN 1 END) as locked_count'),
                    DB::raw('COUNT(CASE WHEN status = "removed" THEN 1 END) as removed_count')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        }

        // 4. Generate Continuous Buckets
        $labels = [];
        $lockedSeries = [];
        $removedSeries = [];
        $netSeries = [];
        
        // Starting baseline (always respect the original startDate for cumulative accuracy)
        $cumulativeBalance = $this->getStartingNetPosition($startDate, $search);
        
        // If we focused, we need to account for net position change between original start and focus start
        if ($focused) {
            $preFocusNet = $this->getNetChangeInRange($startDate, $start->subSecond()->format('Y-m-d H:i:s'), $search);
            $cumulativeBalance += $preFocusNet;
        }

        $current = clone $start;
        $statsMap = $stats->keyBy('period');

        while ($current <= $end) {
            $periodKey = $current->format($interval === 'week' ? 'Y-W' : ($interval === 'month' ? 'Y-m' : 'Y-m-d'));
            // Fix for MySQL %Y-%u mismatch with Carbon Y-W (weeks starting on Sunday vs Monday)
            // MySQL %u is 00-53 (Monday start). Carbon format('W') is ISO week (Monday start).
            if ($interval === 'week') $periodKey = $current->format('Y-W'); // We'll just use a standardized key

            // For reliability, we'll re-key stats by a PHP-generated period key
            $mysqlPeriod = $current->format($interval === 'week' ? 'Y-m' : ($interval === 'month' ? 'Y-m' : 'Y-m-d')); 
            // This is getting complex. Let's simplify the period matching.
            
            $p = $current->format($interval === 'week' ? 'Y-W' : ($interval === 'month' ? 'Y-m' : 'Y-m-d'));
            
            // Re-map stats to ensure we match
            $matched = $stats->filter(function($s) use ($current, $interval) {
                if ($interval === 'day') return $s->period === $current->format('Y-m-d');
                if ($interval === 'month') return $s->period === $current->format('Y-m');
                if ($interval === 'week') {
                    // Match by week number
                    $sDate = Carbon::parse($s->period . '-1'); // MySQL %Y-%u needs careful parsing
                    return Carbon::parse($s->period)->format('Y-W') === $current->format('Y-W');
                }
                return false;
            })->first();

            $lCount = $matched ? $matched->locked_count : 0;
            $rCount = $matched ? $matched->removed_count : 0;
            
            $cumulativeBalance += ($lCount - $rCount);
            
            $labels[] = $this->formatDisplayLabel($current, $interval);
            $lockedSeries[] = $lCount;
            $removedSeries[] = $rCount;
            $netSeries[] = $cumulativeBalance;

            if ($interval === 'day') $current->addDay();
            elseif ($interval === 'week') $current->addWeek();
            else $current->addMonth();
        }

        return [
            'netCombo' => [
                'categories' => $labels,
                'locked' => $lockedSeries,
                'removed' => $removedSeries,
                'net' => $netSeries,
                'meta' => [
                    'focused' => $focused,
                    'focus_note' => $focusNote
                ]
            ]
        ];
    }

    private function estimateBuckets($start, $end, $interval): int
    {
        if ($interval === 'day') return $start->diffInDays($end);
        if ($interval === 'week') return $start->diffInWeeks($end);
        return $start->diffInMonths($end);
    }

    private function formatDisplayLabel($date, $interval): string
    {
        if ($interval === 'day') return $date->format('d M');
        if ($interval === 'week') return 'Wk ' . $date->format('W, Y');
        return $date->format('M Y');
    }

    private function getNetChangeInRange(string $start, string $end, string $search): int
    {
        $query = UserVaultItem::whereBetween('locked_at', [$start, $end]);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('item_title', 'like', '%' . $search . '%')
                    ->orWhere('item_ref', 'like', '%' . $search . '%');
            });
        }
        $counts = $query->select(
            DB::raw('COUNT(CASE WHEN status = "locked" THEN 1 END) as locked_count'),
            DB::raw('COUNT(CASE WHEN status = "removed" THEN 1 END) as removed_count')
        )->first();
        return ($counts->locked_count ?? 0) - ($counts->removed_count ?? 0);
    }
}
