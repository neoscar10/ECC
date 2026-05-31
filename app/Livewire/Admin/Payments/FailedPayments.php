<?php

namespace App\Livewire\Admin\Payments;

use App\Models\Payment;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class FailedPayments extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        // 1. Most common failures
        $commonFailures = Payment::where('status', 'failed')
            ->whereNotNull('failure_message')
            ->select('failure_message', DB::raw('COUNT(*) as count'))
            ->groupBy('failure_message')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        // 2. Failure rate by gateway
        $failureRates = Payment::select('gateway', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('gateway', 'status')
            ->get()
            ->groupBy('gateway')
            ->map(function ($group, $gateway) {
                $failed = $group->where('status', 'failed')->sum('count');
                $total = $group->sum('count');
                return [
                    'gateway' => ucfirst($gateway),
                    'failed' => $failed,
                    'rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
                ];
            })
            ->values()
            ->toArray();

        $payments = Payment::where('status', 'failed')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.payments.failed-payments', [
            'commonFailures' => $commonFailures,
            'failureRates' => $failureRates,
            'payments' => $payments,
        ])->layout('layouts.admin');
    }
}
