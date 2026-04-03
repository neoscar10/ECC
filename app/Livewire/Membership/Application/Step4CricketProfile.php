<?php

namespace App\Livewire\Membership\Application;

use App\Services\Membership\ApplicationWizardService;
use App\Validation\Membership\MembershipRules;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.blank')]
class Step4CricketProfile extends Component
{
    public array $preferred_formats = [];
    public array $eras = [];
    public ?string $errorMessage = null;

    public function mount(ApplicationWizardService $svc): void
    {
        $draft = $svc->getDraft();
        if ($draft) {
            if ($draft instanceof MembershipApplication) {
                $payload = $draft->cricket_profile_json ?? [];
                $this->preferred_formats = $payload['preferred_formats'] ?? $payload['formats'] ?? [];
                $this->eras = $payload['eras'] ?? [];
            } else {
                $payload = $draft->payload_json;
                $this->preferred_formats = $payload['preferred_formats'] ?? $payload['cricket_formats'] ?? [];
                $this->eras = $payload['cricket_eras'] ?? [];
            }
        }
    }

    public function toggleFormat(string $key): void
    {
        $key = strtoupper($key);
        if (in_array($key, $this->preferred_formats, true)) {
            $this->preferred_formats = array_diff($this->preferred_formats, [$key]);
        } else {
            $this->preferred_formats[] = $key;
        }
        $this->preferred_formats = array_values($this->preferred_formats);
    }

    public function toggleEra(string $key): void
    {
        $key = strtoupper($key);
        if (in_array($key, $this->eras, true)) {
            $this->eras = array_diff($this->eras, [$key]);
        } else {
            $this->eras[] = $key;
        }
        $this->eras = array_values($this->eras);
    }

    public function formatOptions(): array
    {
        return [
            [
                'key' => 'TEST',
                'title' => 'Test Match',
                'sub' => 'The Classic',
                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCQ9n5tVLbI8VJNmMwo_jA1VKJNB0wrEBlGKRyaOGQQWkguipQSVGDqxqr5BC7x4JORBTS50VqtYg2lBkO3BLnvOWxAEsTsbIb_j1WxDsVXTWSn7y1ksJAsVrJZc9C18AjKUR2S7cOTy2vmK4xyTMJENtjW2bisJOld6vokRdtQzyTP7xWOU3Y5HjxiP5xUPQNx8O5UgJQVuhIN8Oi63uGn795lJogUAE7xPkjo1A4bo_ULoZVRgEgMpeAu_-218GnSx-YDTjtAZiYw',
            ],
            [
                'key' => 'ODI',
                'title' => 'ODI',
                'sub' => '50 Overs',
                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDF-2XiJp2F-xDebremd-mAlS2bAEMslJZZd2ZzhI11Inipgd22e31TMqrI_4CvG8DZRfrPib3ACWrVf4pN2rcYt6uC_S92MKXBhiVqS83e06sCIt2SvyWQS4Z0Z2Ac0e-uNtFucG9ydVn8FCe0aJaeQ6O4vVp1bLHDBlEkddKSWL1jJ19VvFQXqVvZPM06p_B2Wpm1PrvOUY3IBr98MLiZRZBhPk8O3q7irLVL4VGhB47SbsFAOTiIiIlfLsuhpkgfFyo1oeQNvubT',
            ],
            [
                'key' => 'T20',
                'title' => 'T20',
                'sub' => 'Fast Paced',
                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBwkzWYyj1s9VfLQdF2YB62w4iRlyVLTuZT1AiMgGKxx1bvWKVjE45rftoHKxMKr5oupSvHj8-y3lSublsbFNQhuvYhEnUEVfgRSql7wIrcuv2rqxZFDDTC1WPD1BQ9toynoZemx7c-rlntGZo_--VkchU2FhvdHaVfZJ6KNjE7B0o3-toTfYzs0Y1dRZ4QHoEYOHB3q0Pl_b8ZE0KI-zVbJYruSFdxSbL9EfwQ0upj0m7uIhcouVjOMoMsQJmiZ3W5KfoF1gWf8B1A',
            ],
            [
                'key' => 'LEAGUES',
                'title' => 'Leagues',
                'sub' => 'Global T20',
                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCSM1UguE1YBbxHL011sW0A-ajyQxIfOYLcqry0I94hy-OIzdiA7KCic9d8dqOWr9tkBsR1O3nP9mqcSaKPdDpAQ-lWV38gF219OcsH_h835LVxnidExayEUeySOo8uGgq_Ppq2mk8eVExu7kiBp1g9HTLqlcdPV_fbTebF9YUKi_TGTrdusPUuqnnhrvkQFPJf2RGzodpGv5UM1ASd2zy-zc5WdRAN_aYRUPyI8MnFX0GMOMPXo9mApbO41CVLz54oB3PfnWB5Fi0h',
            ],
        ];
    }

    public function eraOptions(): array
    {
        return [
            ['key' => 'GOLDEN_AGE_1890_1914', 'label' => 'Golden Age (1890-1914)'],
            ['key' => 'POST_WAR_50S', 'label' => 'Post-War & 50s'],
            ['key' => 'WEST_INDIES_DOMINANCE', 'label' => 'West Indies Dominance'],
            ['key' => 'ODI_90S_ERA', 'label' => 'The 90s ODI Era'],
            ['key' => 'MODERN_ERA', 'label' => 'Modern Era'],
            ['key' => 'WOMENS_CRICKET', 'label' => 'Women’s Cricket'],
        ];
    }

    public function submit(ApplicationWizardService $svc)
    {
        $this->errorMessage = null;

        try {
            $validated = $this->validate(MembershipRules::cricketProfile());
            $svc->saveStep4CricketProfile([
                'formats' => $validated['preferred_formats'],
                'eras' => $validated['eras'],
                'skipped' => false
            ]);

            return redirect()->route('membership.application.step5');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while saving. Please try again.';
        }
    }

    public function chooseLater(ApplicationWizardService $svc)
    {
        $svc->saveStep4CricketProfile([
            'formats' => [],
            'eras' => [],
            'skipped' => true
        ]);

        return redirect()->route('membership.application.step5');
    }

    public function render()
    {
        return view('livewire.membership.application.step4-cricket-profile');
    }
}
