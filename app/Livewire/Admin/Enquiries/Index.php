<?php

namespace App\Livewire\Admin\Enquiries;

use App\Models\ContactEnquiry;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class Index extends Component
{
    use WithPagination;

    // Filter properties
    #[Url]
    public $search = '';
    #[Url]
    public $status = '';
    
    // Action properties
    public $selectedEnquiry = null;
    
    #[Url]
    public $viewId = null;

    public function mount()
    {
        if ($this->viewId) {
            $this->viewEnquiry($this->viewId);
            $this->viewId = null;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingViewId()
    {
        $this->resetPage();
    }

    public function viewEnquiry($id)
    {
        $this->selectedEnquiry = ContactEnquiry::with(['user'])->find($id);
        $this->dispatch('show-view-modal');
    }

    public function updateStatus($id, $newStatus)
    {
        $enquiry = ContactEnquiry::find($id);
        if ($enquiry) {
            $updateData = ['status' => $newStatus];
            
            // Should probably track who updated it too, if we had auth available easily here
            // $updateData['handled_by_admin_id'] = auth()->id();
            // $updateData['handled_at'] = now();

            $enquiry->update($updateData);
            session()->flash('success', 'Enquiry status updated successfully.');
            
            // Refresh selected enquiry if open
            if ($this->selectedEnquiry && $this->selectedEnquiry->id == $id) {
                $this->selectedEnquiry = $enquiry->fresh(['user']);
            }
        }
    }
    
    public function render()
    {
        $query = ContactEnquiry::with(['user'])
            ->latest();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('contact_name', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_email', 'like', '%' . $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->viewId) {
            $query->where('id', $this->viewId);
        }

        $enquiries = $query->paginate(10);

        return view('livewire.admin.enquiries.index', [
            'enquiries' => $enquiries
        ])->layout('layouts.admin');
    }
}
