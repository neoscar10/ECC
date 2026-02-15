<?php

namespace App\Livewire\Admin\Reports;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    public function render()
    {
        $reports = [
            [
                'title' => 'Sales Report',
                'description' => 'Comprehensive look at revenue across all modules (Shop, Archive, auctions).',
                'icon' => 'ri-money-dollar-circle-line',
                'color' => 'success',
                'route' => route('admin.reports.sales'),
            ],
            [
                'title' => 'Membership Report',
                'description' => 'Track membership growth, tier distribution, and renewals.',
                'icon' => 'ri-user-star-line',
                'color' => 'info',
                'route' => route('admin.reports.membership'),
            ],
            [
                'title' => 'Auction Performance',
                'description' => 'Analyze auction success rates, bidding activity, and revenue.',
                'icon' => 'ri-auction-line',
                'color' => 'primary',
                'route' => route('admin.reports.auctions'),
            ],
            [
                'title' => 'Vault Ledger',
                'description' => 'Historical log of items entering and leaving user vaults.',
                'icon' => 'ri-safe-2-line',
                'color' => 'warning',
                'route' => route('admin.reports.vault'),
            ],
        ];

        return view('livewire.admin.reports.index', compact('reports'));
    }
}
