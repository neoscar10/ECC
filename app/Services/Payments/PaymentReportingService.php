<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Support\Payments\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentReportingService
{
    /**
     * Get dynamic date constraints based on range string.
     */
    protected function getRangeDates(string $range): array
    {
        $now = Carbon::now();
        switch (strtolower($range)) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case '1w':
            case 'week':
                return [$now->copy()->subWeek()->startOfDay(), $now->copy()->endOfDay()];
            case '1m':
            case 'month':
            default:
                return [$now->copy()->subMonth()->startOfDay(), $now->copy()->endOfDay()];
        }
    }

    /**
     * Get KPI Summary Metrics.
     */
    public function getSummaryMetrics(string $range = 'month'): array
    {
        [$start, $end] = $this->getRangeDates($range);

        $totalRevenue = (float) Payment::where('status', PaymentStatus::PAID)->sum('amount');
        
        $revenueToday = (float) Payment::where('status', PaymentStatus::PAID)
            ->whereDate('paid_at', Carbon::today())
            ->sum('amount');

        $revenueThisMonth = (float) Payment::where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('amount');

        $successfulCount = Payment::where('status', PaymentStatus::PAID)->count();
        $failedCount = Payment::where('status', PaymentStatus::FAILED)->count();
        $pendingCount = Payment::whereIn('status', [PaymentStatus::PENDING, PaymentStatus::INITIATED])->count();
        
        $totalAttempts = $successfulCount + $failedCount;
        $successRate = $totalAttempts > 0 ? round(($successfulCount / $totalAttempts) * 100, 2) : 0;
        
        $avgTransaction = $successfulCount > 0 ? round($totalRevenue / $successfulCount, 2) : 0;

        return [
            'total_revenue' => $totalRevenue,
            'revenue_today' => $revenueToday,
            'revenue_this_month' => $revenueThisMonth,
            'successful_count' => $successfulCount,
            'failed_count' => $failedCount,
            'pending_count' => $pendingCount,
            'success_rate' => $successRate,
            'avg_transaction_value' => $avgTransaction,
        ];
    }

    /**
     * Revenue by Gateway.
     */
    public function getRevenueByGateway(): array
    {
        return Payment::where('status', PaymentStatus::PAID)
            ->select('gateway', DB::raw('SUM(amount) as total'))
            ->groupBy('gateway')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => ucfirst($item->gateway ?: 'unknown'),
                    'value' => (float) $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Revenue by Purpose.
     */
    public function getRevenueByPurpose(): array
    {
        return Payment::where('status', PaymentStatus::PAID)
            ->select('purpose', DB::raw('SUM(amount) as total'))
            ->groupBy('purpose')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                $label = match ($item->purpose) {
                    'shop_order' => 'Shop Orders',
                    'membership_upgrade' => 'Membership Upgrades',
                    'membership_renewal' => 'Membership Renewals',
                    'vault_delivery' => 'Vault Delivery',
                    'auction_settlement' => 'Auction Settlement',
                    default => ucfirst(str_replace('_', ' ', $item->purpose)),
                };
                return [
                    'label' => $label,
                    'value' => (float) $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Get Daily Revenue Trend.
     */
    public function getDailyTrend(int $days = 30): array
    {
        $start = Carbon::now()->subDays($days)->startOfDay();
        
        $data = Payment::where('status', PaymentStatus::PAID)
            ->where('paid_at', '>=', $start)
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $values = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::now()->subDays($i)->format('d M');
            $values[] = (float) ($data[$date] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Get Gateway Success rates.
     */
    public function getGatewayConversionRates(): array
    {
        return Payment::select('gateway', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('gateway', 'status')
            ->get()
            ->groupBy('gateway')
            ->map(function ($group, $gateway) {
                $success = $group->where('status', PaymentStatus::PAID)->sum('count');
                $failed = $group->where('status', PaymentStatus::FAILED)->sum('count');
                $total = $success + $failed;
                return [
                    'gateway' => ucfirst($gateway),
                    'success' => $success,
                    'failed' => $failed,
                    'rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
                ];
            })
            ->values()
            ->toArray();
    }
}
