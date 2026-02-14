<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\Auctions\AuctionEnquiry;
use App\Models\ContactEnquiry;
use App\Support\Reports\CsvExporter;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('layouts.admin')]
class EnquiryReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $search = '';

    protected $queryString = ['startDate', 'endDate', 'search'];

    public function mount()
    {
        $this->startDate = Carbon::now()->subMonths(1)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function refresh()
    {
        $this->resetPage();
    }

    public function export()
    {
        $data = $this->getEnquiryQuery()->get();
        
        $headers = ['Date', 'Type', 'Subject/Customer', 'Status'];
        
        $exportData = $data->map(function($e) {
            return [
                Carbon::parse($e->created_at)->format('Y-m-d H:i'),
                ucfirst($e->type),
                $e->subject,
                ucfirst($e->status),
            ];
        });

        return CsvExporter::download($exportData, $headers, 'enquiry-report-' . now()->format('Y-m-d') . '.csv');
    }

    private function getEnquiryQuery()
    {
        $archive = DB::table('archive_product_enquiries')
            ->select([
                'created_at',
                DB::raw("'archive' as type"),
                'contact_name as subject',
                'status',
            ]);

        $auction = DB::table('auction_enquiries')
            ->select([
                'created_at',
                DB::raw("'auction' as type"),
                'contact_name as subject',
                'status',
            ]);

        $contact = DB::table('contact_enquiries')
            ->select([
                'created_at',
                DB::raw("'general' as type"),
                'subject',
                'status',
            ]);

        $query = $archive->unionAll($auction)->unionAll($contact);

        $finalQuery = DB::table(DB::raw("({$query->toSql()}) as combined_enquiries"))
            ->mergeBindings($query);

        if ($this->startDate) {
            $finalQuery->where('created_at', '>=', $this->startDate . ' 00:00:00');
        }

        if ($this->endDate) {
            $finalQuery->where('created_at', '<=', $this->endDate . ' 23:59:59');
        }

        if ($this->search) {
            $finalQuery->where('subject', 'like', '%' . $this->search . '%');
        }

        return $finalQuery->orderBy('created_at', 'desc');
    }

    public function render()
    {
        $enquiries = $this->getEnquiryQuery()->paginate(15);
        
        $totalsQuery = $this->getEnquiryQuery();
        $totalsQuery->orders = null;
        
        $totals = $totalsQuery->select([
            DB::raw('COUNT(*) as count'),
        ])->first();

        return view('livewire.admin.reports.enquiry-report', [
            'enquiries' => $enquiries,
            'totalCount' => $totals->count,
        ]);
    }
}
