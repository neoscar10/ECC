<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Auctions\AuctionLot;
use App\Support\Reports\CsvExporter;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.admin')]
class AuctionReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $status = 'ended';
    public $search = '';

    protected $queryString = ['startDate', 'endDate', 'status', 'search'];

    public function mount()
    {
        $this->startDate = Carbon::now()->subMonths(3)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function refresh()
    {
        $this->resetPage();
    }

    public function export()
    {
        $data = $this->getAuctionQuery()->get();
        
        $headers = ['Lot Title', 'Ref #', 'Status', 'Start Price', 'Final Bid', 'Bids Count', 'Ends At'];
        
        $exportData = $data->map(function($lot) {
            return [
                $lot->title,
                $lot->reference_number,
                ucfirst($lot->status),
                $lot->starting_price,
                $lot->current_highest_bid ?? 'No bits',
                $lot->bids_count,
                $lot->ends_at ? $lot->ends_at->format('Y-m-d H:i') : 'N/A',
            ];
        });

        return CsvExporter::download($exportData, $headers, 'auction-report-' . now()->format('Y-m-d') . '.csv');
    }

    private function getAuctionQuery()
    {
        $query = AuctionLot::withCount('bids')
            ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('reference_number', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest('ends_at');
    }

    public function render()
    {
        $lots = $this->getAuctionQuery()->paginate(15);
        
        $statusOptions = [
            'live' => 'Live',
            'ended' => 'Ended',
            'upcoming' => 'Upcoming',
            'unsold' => 'Unsold',
        ];

        return view('livewire.admin.reports.auction-report', [
            'lots' => $lots,
            'statusOptions' => $statusOptions,
            'totalLots' => $this->getAuctionQuery()->count(),
            'totalBids' => $this->getAuctionQuery()->sum('bids_count'),
        ]);
    }
}
