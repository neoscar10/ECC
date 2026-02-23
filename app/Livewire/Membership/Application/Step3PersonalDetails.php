<?php

namespace App\Livewire\Membership\Application;

use App\Services\Membership\ApplicationWizardService;
use App\Validation\Membership\MembershipRules;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.blank')]
class Step3PersonalDetails extends Component
{
    public string $full_name = '';
    public string $dob = '';
    public string $country = '';
    public string $city = '';
    public string $errorMessage = '';

    public function mount(ApplicationWizardService $svc): void
    {
        $draft = $svc->getOrCreateDraft();
        
        if ($draft instanceof MembershipApplication) {
            $data = $draft->personal_details_json ?? [];
        } else {
            $data = $draft->payload_json['personal_details'] ?? [];
        }

        $this->full_name = $data['full_name'] ?? '';
        $this->dob = $data['dob'] ?? '';
        $this->country = $data['country'] ?? '';
        $this->city = $data['city'] ?? '';
    }

    public function submit(ApplicationWizardService $svc)
    {
        $this->errorMessage = '';

        try {
            $validated = $this->validate(MembershipRules::personalDetails());
            
            $svc->saveStep3PersonalDetails($validated);

            return redirect()->route('membership.application.step4');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while saving. Please try again.';
        }
    }

    public function render()
    {
        return view('livewire.membership.application.step3-personal-details');
    }
}
