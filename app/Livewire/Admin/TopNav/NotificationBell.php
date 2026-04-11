<?php

namespace App\Livewire\Admin\TopNav;

use Livewire\Component;
use App\Services\Admin\AdminOperationalAttentionService;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    /**
     * Render the notification bell and dropdown.
     */
    public function render(AdminOperationalAttentionService $service)
    {
        $summary = $service->getAttentionSummary(Auth::user());

        return view('livewire.admin.top-nav.notification-bell', [
            'totalCount' => $summary['total_count'],
            'items' => $summary['items'],
            'grouped' => $summary['grouped'],
        ]);
    }
}
