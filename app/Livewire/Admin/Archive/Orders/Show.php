<?php

namespace App\Livewire\Admin\Archive\Orders;

use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Show extends Component
{
    public $orderId;
    public $order;

    public function mount($id)
    {
        $this->orderId = $id;
        $this->order = Order::with(['product', 'buyer', 'enquiry', 'logger'])->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.admin.archive.orders.show');
    }
}
