<?php

namespace App\Livewire\Club;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Club\ClubPageService;
use App\Services\Common\ContactEnquiryService;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.web-app')]
class ClubPage extends Component
{
    public array $vm = [];

    // Modal State
    public bool $showConciergeModal = false;
    public array $conciergeForm = [
        'subject' => '',
        'message' => '',
    ];
    public ?string $conciergeSubmissionError = null;

    protected $rules = [
        'conciergeForm.subject' => 'required|string|in:membership_upgrade,dining_reservations,general_feedback,other',
        'conciergeForm.message' => 'required|string|min:5|max:2000',
    ];

    protected $validationAttributes = [
        'conciergeForm.subject' => 'subject',
        'conciergeForm.message' => 'message',
    ];

    public function mount(ClubPageService $service): void
    {
        $this->loadData($service);
    }

    public function loadData(ClubPageService $service): void
    {
        $this->vm = $service->getViewModel(Auth::user());
    }

    public function openConciergeModal()
    {
        $this->resetErrorBag();
        $this->conciergeSubmissionError = null;
        $this->conciergeForm = [
            'subject' => 'membership_upgrade', // Default
            'message' => '',
        ];
        $this->showConciergeModal = true;
    }

    public function closeConciergeModal()
    {
        $this->showConciergeModal = false;
    }

    public function submitConciergeEnquiry(ContactEnquiryService $service, ClubPageService $pageService)
    {
        $this->validate();

        try {
            $service->submit(Auth::user(), $this->conciergeForm);
            
            session()->flash('concierge_success', 'Your enquiry has been submitted successfully. Our concierge team will reach out to you shortly.');
            
            // Re-load data to show new enquiry in ledger if applicable
            $this->loadData($pageService);

            // Close modal and reset form
            $this->showConciergeModal = false;
            $this->conciergeForm = ['subject' => '', 'message' => ''];
            
        } catch (\Exception $e) {
            $this->conciergeSubmissionError = 'An error occurred while submitting your enquiry. Please try again.';
        }
    }

    public function render()
    {
        return view('livewire.club.club-page')->layout('layouts.web-app', [
            'title' => 'Club',
            'activeNav' => 'club',
        ]);
    }
}
