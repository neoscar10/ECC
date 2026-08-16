<?php

namespace App\Livewire\Admin\Vault;

use App\Models\User;
use App\Models\UserVaultItem;
use App\Services\VaultService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class Show extends Component
{
    use WithPagination;

    public User $user;
    public $notes;
    public $selectedItem = null;

    protected $paginationTheme = 'bootstrap';

    public function mount(User $user)
    {
        $this->user = $user;
    }

    public function confirmRemoval($itemId)
    {
        $this->selectedItem = UserVaultItem::find($itemId);
        $this->notes = '';
        $this->dispatch('open-remove-modal');
    }

    public function markRemoved(VaultService $service)
    {
        if (!$this->selectedItem) return;

        $service->markRemoved($this->selectedItem, auth()->user(), $this->notes);

        session()->flash('success', 'Item marked as removed from vault.');
        $this->dispatch('close-modals');
        $this->selectedItem = null;
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $items = $this->user->vaultItems()
            ->with('removalRequests')
            ->latest('locked_at')
            ->paginate(10);

        return view('livewire.admin.vault.show', [
            'items' => $items
        ]);
    }
}
