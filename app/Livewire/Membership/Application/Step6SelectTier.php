<?php

namespace App\Livewire\Membership\Application;

use App\Services\Membership\ApplicationWizardService;
use App\Services\Membership\MembershipService;
use App\Domain\Membership\TierRecommendationService;
use App\Models\MembershipApplication;
use App\Models\MembershipTier;
use App\Validation\Membership\MembershipRules;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.blank')]
class Step6SelectTier extends Component
{
    public ?int $selectedTierId = null;
    public array $tiers = [];
    public array $recommendation = [];
    public ?string $errorMessage = null;

    public function mount(
        ApplicationWizardService $wiz,
        TierRecommendationService $recommender
    ): void {
        $draft = $wiz->getDraft();
        
        // If not authenticated (though steps 3+ should be), redirect to step 1
        if (!$draft || !($draft instanceof MembershipApplication)) { // Updated this line
             $this->redirect(route('membership.application.step1'));
             return;
        }

        // Load Tiers with relations (Privileges and Features/Perks)
        $this->tiers = MembershipTier::where('is_active', true)
            ->with(['privileges' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->with('features')
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($t) {
                $allPrivileges = $t->privileges->map(fn($p) => $p->label ?: $p->name)->all();
                
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'price' => (float)$t->price,
                    'price_formatted' => $t->price > 0 ? 'INR ' . number_format($t->price) : 'Free',
                    'duration_label' => 'Year',
                    'short_desc' => $t->description ?: 'Standard membership benefits.',
                    'perks' => array_slice($allPrivileges, 0, 3), 
                    'benefits_list' => array_slice($allPrivileges, 0, 4),
                    'benefits_count' => count($allPrivileges)
                ];
            })->all();

        // Get Recommendation
        $this->recommendation = $recommender->getRecommendationForWizard($draft);

        // Preselect
        $this->selectedTierId = $draft->selected_tier_id ?: $this->recommendation['tier_id'];
    }

    public function submit(MembershipService $membershipSvc, ApplicationWizardService $wiz)
    {
        $this->errorMessage = null;

        try {
            $this->validate([
                'selectedTierId' => 'required|integer|exists:membership_tiers,id,is_active,1'
            ], [
                'selectedTierId.required' => 'Please select a membership tier.',
                'selectedTierId.exists' => 'The selected tier is invalid.'
            ]);

            $draft = $wiz->getDraft();
            $membershipSvc->selectTier($draft, $this->selectedTierId);

            return redirect()->route('membership.application.step7');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while saving. Please try again.';
        }
    }

    public function render()
    {
        return view('livewire.membership.application.step6-select-tier');
    }
}
