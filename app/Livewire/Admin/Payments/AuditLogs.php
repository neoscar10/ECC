<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentSettingAudit;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogs extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $audits = PaymentSettingAudit::with('admin')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.payments.audit-logs', [
            'audits' => $audits,
        ])->layout('layouts.admin');
    }
}
