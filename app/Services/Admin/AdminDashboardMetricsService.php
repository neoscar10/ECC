<?php

namespace App\Services\Admin;

use App\Models\Membership;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\Auctions\AuctionEnquiry;
use App\Models\ContactEnquiry;
use App\Models\MembershipApplication;
use App\Models\Shop\ShopOrder;
use App\Models\Order;
use App\Models\Shop\ShopProductVariationValue;
use App\Models\Auctions\AuctionLot;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AdminDashboardMetricsService
{
    /**
     * Get KPI metrics for the dashboard cards.
     */
    public function getKpiMetrics(): array
    {
        return Cache::remember('admin_dashboard_kpis', 900, function () { // 15 mins
            return [
                'total_sales' => $this->calculateTotalSales(),
                'active_members' => Membership::where('status', 'active')->count(),
                'pending_applications' => MembershipApplication::where('status', 'submitted')->count(),
                'live_auctions' => AuctionLot::where('status', 'live')->count(),
                'new_enquiries' => $this->calculateNewEnquiriesCount(),
                'sales_trend' => $this->calculateSalesTrend(),
            ];
        });
    }

    /**
     * Get operational queues that need admin attention.
     */
    public function getNeedsAttentionQueues(): array
    {
        return [
            'pending_applications' => MembershipApplication::with(['user', 'membershipTier'])
                ->where('status', 'submitted')
                ->latest()
                ->limit(5)
                ->get(),
            'new_enquiries' => $this->getLatestNewEnquiries(5),
            'low_stock' => ShopProductVariationValue::with(['group.product'])
                ->where('stock_qty', '<', 5) // Simple threshold for now
                ->orderBy('stock_qty', 'asc')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Calculate unified sales from Shop and Orders table (Archive/Auction).
     */
    private function calculateTotalSales(): float
    {
        $shopSales = ShopOrder::where('status', 'paid')->sum('total_amount');
        $otherSales = Order::whereNotNull('paid_at')->sum('subtotal_inr');
        
        return (float) ($shopSales + $otherSales);
    }

    /**
     * Calculate total new enquiries across different types.
     */
    private function calculateNewEnquiriesCount(): int
    {
        return (int) (
            ArchiveProductEnquiry::where('status', 'new')->count() +
            AuctionEnquiry::where('status', 'new')->count() +
            ContactEnquiry::where('status', 'new')->count()
        );
    }

    /**
     * Get combined latest new enquiries.
     */
    private function getLatestNewEnquiries(int $limit): \Illuminate\Support\Collection
    {
        $archive = ArchiveProductEnquiry::with('user')->where('status', 'new')->latest()->limit($limit)->get()
            ->map(fn($e) => ['type' => 'Archive', 'subject' => $e->contact_name ?? $e->user?->name, 'date' => $e->created_at, 'id' => $e->id, 'route' => route('admin.archive.enquiries')])
            ->toBase();
            
        $auction = AuctionEnquiry::with('user')->where('status', 'new')->latest()->limit($limit)->get()
            ->map(fn($e) => ['type' => 'Auction', 'subject' => $e->contact_name ?? $e->user?->name, 'date' => $e->created_at, 'id' => $e->id, 'route' => route('admin.auctions.enquiries')])
            ->toBase();
            
        $contact = ContactEnquiry::with('user')->where('status', 'new')->latest()->limit($limit)->get()
            ->map(fn($e) => ['type' => 'General', 'subject' => $e->subject, 'date' => $e->created_at, 'id' => $e->id, 'route' => route('admin.enquiries.index')])
            ->toBase();

        return $archive->concat($auction)->concat($contact)->sortByDesc('date')->take($limit);
    }

    /**
     * Calculate sales trend for the last 30 days.
     */
    private function calculateSalesTrend(): array
    {
        $days = 30;
        $labels = [];
        $values = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = $date;
            
            $shopSales = ShopOrder::where('payment_status', 'paid')
                ->whereDate('paid_at', $date)
                ->sum('total_amount');
                
            $otherSales = Order::whereNotNull('paid_at')
                ->whereDate('paid_at', $date)
                ->sum('subtotal_inr');
                
            $values[] = (float) ($shopSales + $otherSales);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Clear dashboard cache.
     */
    public function clearCache(): void
    {
        Cache::forget('admin_dashboard_kpis');
    }
}
