<?php

namespace App\Livewire\Admin\Archive\Enquiries;

use App\Models\Archive\ArchiveProductEnquiry; // Ensure you use the correct namespace
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // Filter properties
    public $search = '';
    public $status = '';
    
    // Action properties
    public $selectedEnquiry = null;
    
    // Clean URL query string
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function viewEnquiry($id)
    {
        $this->selectedEnquiry = ArchiveProductEnquiry::with(['product.images', 'user'])->find($id);
        $this->dispatch('show-view-modal');
    }

    public function updateStatus($id, $newStatus)
    {
        $enquiry = ArchiveProductEnquiry::find($id);
        if ($enquiry) {
            $enquiry->update(['status' => $newStatus]);
            session()->flash('success', 'Enquiry status updated successfully.');
            
            // Refresh selected enquiry if open
            if ($this->selectedEnquiry && $this->selectedEnquiry->id == $id) {
                $this->selectedEnquiry = $enquiry->fresh(['product.images', 'user']);
            }
        }
    }
    
    public function render()
    {
        $query = ArchiveProductEnquiry::with(['product', 'user'])
            ->latest();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('contact_name', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_email', 'like', '%' . $this->search . '%')
                  ->orWhereHas('product', function($subQ) {
                      $subQ->where('title', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $enquiries = $query->paginate(10);

        return view('livewire.admin.archive.enquiries.index', [
            'enquiries' => $enquiries
        ])->layout('layouts.admin');
    }
}
