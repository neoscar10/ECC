<?php

namespace App\Livewire\Admin\Enquiries;

use Livewire\Component;
use App\Models\ContactConfig;
use App\Models\ContactSubject;
use Illuminate\Support\Str;

class Config extends Component
{
    public $concierge_phone;
    public $support_email;
    public $subjects = [];
    
    // For adding new subject
    public $newSubjectLabel;

    public function mount()
    {
        $config = ContactConfig::firstOrCreate([], [
            'concierge_phone' => null,
            'support_email' => null,
        ]);

        $this->concierge_phone = $config->concierge_phone;
        $this->support_email = $config->support_email;

        $this->loadSubjects();
    }

    public function loadSubjects()
    {
        $this->subjects = ContactSubject::orderBy('sort_order', 'asc')->get()->toArray();
    }

    public function addSubject()
    {
        $this->validate([
            'newSubjectLabel' => 'required|string|max:80|unique:contact_subjects,label',
        ]);

        ContactSubject::create([
            'label' => $this->newSubjectLabel,
            'key' => Str::slug($this->newSubjectLabel),
            'sort_order' => ContactSubject::max('sort_order') + 1,
            'is_active' => true,
        ]);

        $this->newSubjectLabel = '';
        $this->loadSubjects();
        $this->dispatch('start-notification', type: 'success', message: 'Subject added successfully.');
    }

    public function deleteSubject($id)
    {
        ContactSubject::findOrFail($id)->delete();
        $this->loadSubjects();
        $this->dispatch('start-notification', type: 'success', message: 'Subject deleted.');
    }

    public function reorderSubjects($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            if ($subject = ContactSubject::find($id)) {
                $subject->update(['sort_order' => $index + 1]);
            }
        }
        $this->loadSubjects();
        // Optional: Dispatch notification if you want confirmation of reorder
        // $this->dispatch('start-notification', type: 'success', message: 'Subjects reordered.');
    }

    public function moveSubject($id, $direction)
    {
        $subject = ContactSubject::findOrFail($id);
        $currentOrder = $subject->sort_order;

        if ($direction === 'up') {
            $swap = ContactSubject::where('sort_order', '<', $currentOrder)->orderBy('sort_order', 'desc')->first();
        } else {
            $swap = ContactSubject::where('sort_order', '>', $currentOrder)->orderBy('sort_order', 'asc')->first();
        }

        if ($swap) {
            $subject->update(['sort_order' => $swap->sort_order]);
            $swap->update(['sort_order' => $currentOrder]);
            $this->loadSubjects();
        }
    }
    
    public function updateSubjectLabel($id, $label)
    {
         $subject = ContactSubject::findOrFail($id);
         $subject->update(['label' => $label]);
         // We do NOT update key to preserve relationships/references if any rely on stable keys
         $this->dispatch('start-notification', type: 'success', message: 'Subject updated.');
    }

    public function save()
    {
        $this->validate([
            'concierge_phone' => 'nullable|string|max:50',
            'support_email' => 'nullable|email|max:255',
        ]);

        $config = ContactConfig::first(); // Should exist from mount
        $config->update([
            'concierge_phone' => $this->concierge_phone,
            'support_email' => $this->support_email,
        ]);

        $this->dispatch('start-notification', type: 'success', message: 'Contact details saved.');
        $this->dispatch('close-config-modal'); 
    }

    public function render()
    {
        return view('livewire.admin.enquiries.config');
    }
}
