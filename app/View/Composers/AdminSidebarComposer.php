<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use App\Domain\Membership\MembershipApplication;
use App\Models\Auctions\AuctionEnquiry;
use App\Models\Archive\ArchiveProductEnquiry;

class AdminSidebarComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $counts = Cache::remember('admin.sidebar.counts', 30, function () {
            return [
                'pendingMembershipApplicationsCount' => MembershipApplication::whereIn('status', ['pending', 'draft', 'submitted'])->count(),
                'newAuctionEnquiriesCount' => AuctionEnquiry::where('status', 'new')->count(),
                'newArchiveEnquiriesCount' => ArchiveProductEnquiry::where('status', 'new')->count(),
                'newContactEnquiriesCount' => \App\Models\ContactEnquiry::where('status', 'new')->count(),
            ];
        });

        $view->with($counts);
    }
}
