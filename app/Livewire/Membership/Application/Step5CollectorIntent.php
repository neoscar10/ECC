<?php

namespace App\Livewire\Membership\Application;

use App\Services\Membership\ApplicationWizardService;
use App\Validation\Membership\MembershipRules;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.blank')]
class Step5CollectorIntent extends Component
{
    public bool $has_acquired_memorabilia_before = true;
    public string $focus = 'RARITY';
    public int $horizon = 70;
    public string $investment_horizon = 'Y5_10';
    public array $interests = [];
    public ?string $errorMessage = null;

    protected $listeners = ['sliderChanged' => 'updateHorizon'];

    public function mount(ApplicationWizardService $svc): void
    {
        $draft = $svc->getDraft();
        if ($draft) {
            if ($draft instanceof MembershipApplication) {
                $payload = $draft->collector_intent_json ?? [];
                $this->has_acquired_memorabilia_before = $payload['has_acquired_memorabilia_before'] ?? (($payload['history'] ?? 'yes') === 'yes');
                $this->focus = strtoupper($payload['focus'] ?? 'RARITY');
                $this->horizon = (int)($payload['horizon_value'] ?? 70);
            } else {
                $payload = $draft->payload_json;
                $this->has_acquired_memorabilia_before = $payload['has_acquired_memorabilia_before'] ?? (($payload['collector_history'] ?? 'yes') === 'yes');
                $this->focus = strtoupper($payload['collector_focus'] ?? 'RARITY');
                $this->horizon = (int)($payload['collector_horizon_value'] ?? 70);
            }
        }
        $this->syncHorizonToInvestmentCode();
    }

    public function updatedHorizon(): void
    {
        $this->syncHorizonToInvestmentCode();
    }

    private function syncHorizonToInvestmentCode(): void
    {
        $label = $this->getHorizonLabelProperty();
        if ($this->horizon <= 60) {
            $this->investment_horizon = 'Y1_5';
        } elseif ($this->horizon <= 80) {
            $this->investment_horizon = 'Y5_10';
        } else {
            $this->investment_horizon = 'Y10_PLUS';
        }
    }

    public function getHorizonLabelProperty(): string
    {
        if ($this->horizon <= 20) return "0-1 Years";
        if ($this->horizon <= 40) return "1-3 Years";
        if ($this->horizon <= 60) return "3-5 Years";
        if ($this->horizon <= 80) return "5-10 Years";
        return "10+ Years";
    }

    public function submit(ApplicationWizardService $svc)
    {
        $this->errorMessage = null;

        try {
            $validated = $this->validate(MembershipRules::collectorIntent());
            
            $svc->saveStep5CollectorIntent([
                'history' => $this->has_acquired_memorabilia_before ? 'yes' : 'no',
                'focus' => $this->focus,
                'horizon' => $this->horizon,
                'horizon_label' => $this->horizonLabel,
            ]);

            return redirect()->route('membership.application.step6');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while saving. Please try again.';
        }
    }

    public function render()
    {
        return view('livewire.membership.application.step5-collector-intent');
    }
}
