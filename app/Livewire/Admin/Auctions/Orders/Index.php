<?php

namespace App\Livewire\Admin\Auctions\Orders;

use App\Models\Order;
use App\Models\Auctions\AuctionLot;
use App\Services\OrderService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $status = '';
    public $dateFrom;
    public $dateTo;

    // Modal State
    protected $inputParams = []; 
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    protected $listeners = ['order-created' => '$refresh'];

    public function render()
    {
        $query = Order::with(['auctionLot', 'buyer', 'logger'])
            ->where('source', 'auction')
            ->latest('sold_at');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('order_number', 'like', '%'.$this->search.'%')
                  ->orWhere('external_name', 'like', '%'.$this->search.'%')
                  ->orWhereHas('buyer', function($u) {
                      $u->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                  })
                  ->orWhereHas('auctionLot', function($p) {
                      $p->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('lot_no', 'like', '%'.$this->search.'%');
                  });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom) {
            $query->whereDate('sold_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('sold_at', '<=', $this->dateTo);
        }

        return view('livewire.admin.auctions.orders.index', [
            'orders' => $query->paginate(15)
        ]);
    }

    public function openRecordSaleModal()
    {
        $this->dispatch('open-record-sale-modal');
    }
}
