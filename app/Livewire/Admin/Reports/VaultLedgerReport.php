<?php

namespace App\Livewire\Admin\Reports;

use App\Models\UserVaultItem;
use App\Support\Reports\CsvExporter;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.admin')]
class VaultLedgerReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $status = 'locked';
    public $search = '';

    protected $queryString = ['startDate', 'endDate', 'status', 'search'];

    public function mount()
    {
        $this->startDate = Carbon::now()->subMonths(6)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function refresh()
    {
        $this->resetPage();
    }

    public function export()
    {
        $data = $this->getVaultQuery()->get();
        
        $headers = ['User', 'Item Title', 'Ref #', 'Price', 'Status', 'Locked At', 'Removed At'];
        
        $exportData = $data->map(function($item) {
            return [
                $item->user?->name,
                $item->item_title,
                $item->item_ref,
                $item->price . ' ' . $item->currency,
                ucfirst($item->status),
                $item->locked_at ? $item->locked_at->format('Y-m-d H:i') : 'N/A',
                $item->removed_at ? $item->removed_at->format('Y-m-d H:i') : 'N/A',
            ];
        });

        return CsvExporter::download($exportData, $headers, 'vault-ledger-' . now()->format('Y-m-d') . '.csv');
    }

    private function getVaultQuery()
    {
        $query = UserVaultItem::with('user')
            ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('item_title', 'like', '%' . $this->search . '%')
                  ->orWhere('item_ref', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function($uq) {
                      $uq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->latest('locked_at');
    }

    public function render()
    {
        $items = $this->getVaultQuery()->paginate(15);
        
        $statusOptions = [
            'locked' => 'Locked',
            'removed' => 'Removed',
        ];

        return view('livewire.admin.reports.vault-ledger-report', [
            'items' => $items,
            'statusOptions' => $statusOptions,
            'totalItems' => $this->getVaultQuery()->count(),
        ]);
    }
}
