<?php

namespace App\Livewire\Admin\Payments;

use App\Models\Payment;
use App\Models\PaymentEvent;
use Livewire\Component;
use Livewire\WithPagination;

class Transactions extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $searchUser = '';
    public $filterGateway = '';
    public $filterPurpose = '';
    public $filterStatus = '';
    public $filterMinAmount = '';
    public $filterMaxAmount = '';
    public $filterDateStart = '';
    public $filterDateEnd = '';

    public $selectedPaymentId = null;

    public function selectPayment($paymentId)
    {
        $this->selectedPaymentId = $paymentId;
        $this->dispatch('open-payment-modal');
    }

    public function resetFilters()
    {
        $this->reset([
            'searchUser',
            'filterGateway',
            'filterPurpose',
            'filterStatus',
            'filterMinAmount',
            'filterMaxAmount',
            'filterDateStart',
            'filterDateEnd',
        ]);
        $this->resetPage();
    }

    public function updated($property)
    {
        if (in_array($property, [
            'searchUser', 'filterGateway', 'filterPurpose', 'filterStatus',
            'filterMinAmount', 'filterMaxAmount', 'filterDateStart', 'filterDateEnd'
        ])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Payment::query()->with('user');

        if ($this->searchUser) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->searchUser . '%')
                  ->orWhere('email', 'like', '%' . $this->searchUser . '%');
            });
        }

        if ($this->filterGateway) {
            $query->where('gateway', $this->filterGateway);
        }

        if ($this->filterPurpose) {
            $query->where('purpose', $this->filterPurpose);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterMinAmount !== '') {
            $query->where('amount', '>=', (float) $this->filterMinAmount);
        }

        if ($this->filterMaxAmount !== '') {
            $query->where('amount', '<=', (float) $this->filterMaxAmount);
        }

        if ($this->filterDateStart) {
            $query->where('created_at', '>=', $this->filterDateStart);
        }

        if ($this->filterDateEnd) {
            $query->where('created_at', '<=', $this->filterDateEnd . ' 23:59:59');
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        $selectedPayment = $this->selectedPaymentId ? Payment::with(['user', 'events'])->find($this->selectedPaymentId) : null;

        return view('livewire.admin.payments.transactions', [
            'payments' => $payments,
            'selectedPayment' => $selectedPayment,
        ])->layout('layouts.admin');
    }
}
