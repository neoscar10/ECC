<?php

namespace App\Livewire\Admin\ContactMessages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ContactMessage;
use Livewire\Attributes\Title;

#[Title('Contact Messages')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function markAsRead($id)
    {
        $msg = ContactMessage::find($id);
        if ($msg) {
            $msg->update(['is_read' => true]);
            session()->flash('success', 'Message marked as read.');
        }
    }

    public function delete($id)
    {
        $msg = ContactMessage::find($id);
        if ($msg) {
            $msg->delete();
            session()->flash('success', 'Message deleted successfully.');
        }
    }

    public function render()
    {
        $messages = ContactMessage::where('name', 'like', '%'.$this->search.'%')
            ->orWhere('email', 'like', '%'.$this->search.'%')
            ->orWhere('subject', 'like', '%'.$this->search.'%')
            ->latest()
            ->paginate(15);

        return view('livewire.admin.contact-messages.index', compact('messages'))
            ->layout('layouts.admin');
    }
}
