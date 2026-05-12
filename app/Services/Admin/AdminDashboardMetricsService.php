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
    public function getKpiMetrics(string $range = 'today', ?string $startDate = null, ?string $endDate = null, string $source = 'all'): array
    {
        return [
            'total_sales' => $this->calculateTotalSales(),
            'revenue_breakdown' => $this->getRevenueBreakdown(),
            'active_members' => Membership::where('status', 'active')->count(),
            'pending_applications' => MembershipApplication::whereIn('status', ['submitted', 'under_review'])->count(),
            'live_auctions' => AuctionLot::where('status', 'live')->count(),
            'total_auction_lots' => AuctionLot::count(),
            'total_shop_products' => \App\Models\Shop\ShopProduct::count(),
            'total_archive_items' => \App\Models\Archive\ArchiveProduct::count(),
            'new_enquiries' => $this->calculateNewEnquiriesCount(),
            'enquiry_breakdown' => $this->getEnquiryBreakdown(),
            'sales_trend' => $this->calculateSalesTrend($range, $startDate, $endDate, $source),
        ];
    }

    /**
     * Get operational queues that need admin attention.
     */
    public function getNeedsAttentionQueues(): array
    {
        return [
            'pending_applications' => MembershipApplication::with(['user' => fn($q) => $q->withTrashed(), 'membershipTier'])
                ->whereIn('status', ['submitted', 'under_review'])
                ->latest()
                ->limit(5)
                ->get(),
            'new_enquiries' => $this->getLatestNewEnquiries(5),
            'low_stock' => $this->getLowStockItems(5),
        ];
    }

    /**
     * Calculate unified sales from Shop and Orders table (Archive/Auction).
     */
    private function calculateTotalSales(): float
    {
        $shopSales = ShopOrder::where('payment_status', 'paid')->sum('total_amount');
        $otherSales = Order::where('status', 'completed')->sum('subtotal_inr');
        
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
        $archive = ArchiveProductEnquiry::with(['user' => fn($q) => $q->withTrashed()])->where('status', 'new')->latest()->limit($limit)->get()
            ->map(fn($e) => ['type' => 'Archive', 'subject' => $e->contact_name ?? $e->user?->name, 'date' => $e->created_at, 'id' => $e->id, 'route' => route('admin.archive.enquiries', ['viewId' => $e->id])])
            ->toBase();
            
        $auction = AuctionEnquiry::with(['user' => fn($q) => $q->withTrashed()])->where('status', 'new')->latest()->limit($limit)->get()
            ->map(fn($e) => ['type' => 'Auction', 'subject' => $e->contact_name ?? $e->user?->name, 'date' => $e->created_at, 'id' => $e->id, 'route' => route('admin.auctions.enquiries', ['viewId' => $e->id])])
            ->toBase();
            
        $contact = ContactEnquiry::with(['user' => fn($q) => $q->withTrashed()])->where('status', 'new')->latest()->limit($limit)->get()
            ->map(fn($e) => ['type' => 'General', 'subject' => $e->subject, 'date' => $e->created_at, 'id' => $e->id, 'route' => route('admin.enquiries.index', ['viewId' => $e->id])])
            ->toBase();

        return $archive->concat($auction)->concat($contact)->sortByDesc('date')->take($limit);
    }

    /**
     * Calculate sales trend based on range and source.
     */
    public function calculateSalesTrend(string $range = 'today', ?string $startDate = null, ?string $endDate = null, string $source = 'all'): array
    {
        $labels = [];
        $values = [];
        
        // Define start and end dates based on range
        $end = Carbon::now()->endOfDay();
        $start = match($range) {
            'today' => Carbon::today(),
            '1w' => $end->copy()->subDays(6)->startOfDay(),
            '1m' => $end->copy()->subMonth()->startOfDay(),
            'custom' => ($startDate && $endDate) ? Carbon::parse($startDate)->startOfDay() : $end->copy()->subMonth()->startOfDay(),
            default => $end->copy()->subMonth()->startOfDay(),
        };

        // Override end for custom if provided
        if ($range === 'custom' && $endDate) {
            $end = Carbon::parse($endDate)->endOfDay();
        }

        if ($range === 'today') {
            // Hourly breakdown for today
            for ($i = 0; $i <= 23; $i++) {
                $hour = $i;
                $labels[] = sprintf('%02d:00', $hour);
                
                $shopSales = 0;
                $otherSales = 0;

                if ($source === 'all' || $source === 'shop') {
                    $shopSales = ShopOrder::where('payment_status', 'paid')
                        ->whereDate('paid_at', Carbon::today())
                        ->whereRaw('HOUR(paid_at) = ?', [$hour])
                        ->sum('total_amount');
                }
                
                if ($source === 'all' || $source === 'other') {
                    $otherSales = Order::where('status', 'completed')
                        ->whereDate('sold_at', Carbon::today())
                        ->whereRaw('HOUR(sold_at) = ?', [$hour])
                        ->sum('subtotal_inr');
                }
                
                $values[] = (float) ($shopSales + $otherSales);
            }
        } else {
            // Daily breakdown for other ranges
            $current = $start->copy();
            while ($current->lte($end)) {
                $date = $current->format('Y-m-d');
                $labels[] = $date;
                
                $shopSales = 0;
                $otherSales = 0;

                if ($source === 'all' || $source === 'shop') {
                    $shopSales = ShopOrder::where('payment_status', 'paid')
                        ->whereDate('paid_at', $date)
                        ->sum('total_amount');
                }
                
                if ($source === 'all' || $source === 'other') {
                    $otherSales = Order::where('status', 'completed')
                        ->whereDate('sold_at', $date)
                        ->sum('subtotal_inr');
                }
                
                $values[] = (float) ($shopSales + $otherSales);
                $current->addDay();
            }
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'is_hourly' => $range === 'today'
        ];
    }

    /**
     * Get breakdown of new enquiries by source.
     */
    private function getEnquiryBreakdown(): array
    {
        return [
            'archive' => ArchiveProductEnquiry::where('status', 'new')->count(),
            'auction' => AuctionEnquiry::where('status', 'new')->count(),
            'general' => ContactEnquiry::where('status', 'new')->count(),
        ];
    }

    /**
     * Get breakdown of revenue by source.
     */
    private function getRevenueBreakdown(): array
    {
        return [
            'shop' => (float) ShopOrder::where('payment_status', 'paid')->sum('total_amount'),
            'archive' => (float) Order::where('status', 'completed')->whereNotNull('archive_product_id')->sum('subtotal_inr'),
            'auction' => (float) Order::where('status', 'completed')->whereNotNull('auction_lot_id')->sum('subtotal_inr'),
        ];
    }

    /**
     * Clear dashboard cache.
     */
    public function clearCache(): void
    {
        Cache::forget('admin_dashboard_kpis');
    }

    /**
     * Get combined low stock items (variants + simple products) using dynamic thresholds.
     */
    private function getLowStockItems(int $limit): \Illuminate\Support\Collection
    {
        // 1. Get Variants with low stock
        $variants = ShopProductVariationValue::join('shop_product_variation_groups', 'shop_product_variation_values.group_id', '=', 'shop_product_variation_groups.id')
            ->join('shop_products', 'shop_product_variation_groups.shop_product_id', '=', 'shop_products.id')
            ->whereNull('shop_products.deleted_at')
            ->whereColumn('shop_product_variation_values.stock_qty', '<', 'shop_products.low_stock_threshold')
            ->select('shop_product_variation_values.*')
            ->with(['group.product'])
            ->limit($limit)
            ->get();

        // 2. Get Simple Products with low stock
        $simple = \App\Models\Shop\ShopProduct::whereDoesntHave('variationGroups')
            ->whereNotNull('stock_qty')
            ->whereColumn('stock_qty', '<', 'low_stock_threshold')
            ->limit($limit)
            ->get();

        // 3. Combine and prepare for display (as models)
        return $variants->concat($simple)
            ->sortBy('stock_qty')
            ->take($limit)
            ->map(function($item) {
                // Add helper properties to the models
                if ($item instanceof ShopProductVariationValue) {
                    $item->display_product_title = $item->group?->product?->title;
                    $item->display_caption = $item->caption;
                    $item->restock_url = route('admin.shop.inventory', ['search' => $item->group?->product?->title]);
                } else {
                    $item->display_product_title = $item->title;
                    $item->display_caption = 'N/A';
                    $item->restock_url = route('admin.shop.inventory', ['search' => $item->title]);
                }
                return $item;
            });
    }
}
