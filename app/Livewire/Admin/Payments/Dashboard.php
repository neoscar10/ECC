<?php

namespace App\Livewire\Admin\Payments;

use App\Services\Payments\PaymentReportingService;
use Livewire\Component;

class Dashboard extends Component
{
    public string $chartRange = 'month';

    public function refresh()
    {
        $this->dispatch('refreshChart');
    }

    public function render(PaymentReportingService $reportingService)
    {
        $metrics = $reportingService->getSummaryMetrics($this->chartRange);
        $revenueByGateway = $reportingService->getRevenueByGateway();
        $revenueByPurpose = $reportingService->getRevenueByPurpose();
        $trend = $reportingService->getDailyTrend();
        $conversions = $reportingService->getGatewayConversionRates();

        return view('livewire.admin.payments.dashboard', [
            'metrics' => $metrics,
            'revenueByGateway' => $revenueByGateway,
            'revenueByPurpose' => $revenueByPurpose,
            'trend' => $trend,
            'conversions' => $conversions,
        ])->layout('layouts.admin');
    }
}
