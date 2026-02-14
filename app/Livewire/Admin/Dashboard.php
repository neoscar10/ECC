<?php

namespace App\Livewire\Admin;

use App\Services\Admin\AdminDashboardMetricsService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public $kpis = [];
    public $queues = [];

    public function mount(AdminDashboardMetricsService $service)
    {
        $this->loadData($service);
    }

    public function refresh(AdminDashboardMetricsService $service)
    {
        $service->clearCache();
        $this->loadData($service);
    }

    private function loadData(AdminDashboardMetricsService $service)
    {
        $this->kpis = $service->getKpiMetrics();
        $this->queues = $service->getNeedsAttentionQueues();
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
