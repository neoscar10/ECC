<?php
 
namespace App\Livewire\Admin\Vault;
 
use App\Models\VaultRemovalRequest;
use App\Services\VaultService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
 
class RemovalRequests extends Component
{
    use WithPagination;
 
    public $search = '';
    public $statusFilter = 'pending';
 
    protected $paginationTheme = 'bootstrap';
 
    public function updatingSearch()
    {
        $this->resetPage();
    }
 
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
 
    public function approveRequest(int $id, VaultService $service)
    {
        $request = VaultRemovalRequest::findOrFail($id);
        $service->approveRemoval($request, auth()->user());
        session()->flash('success', 'Request approved. It is now ready for completion.');
    }
 
    public function rejectRequest(int $id, string $note, VaultService $service)
    {
        $request = VaultRemovalRequest::findOrFail($id);
        $service->rejectRemoval($request, auth()->user(), $note);
        session()->flash('success', 'Request rejected.');
    }
 
    public function completeRequest(int $id, VaultService $service)
    {
        $request = VaultRemovalRequest::findOrFail($id);
        $service->completeRemoval($request, auth()->user());
        session()->flash('success', 'Request completed. Item has been released from the vault.');
    }
 
    #[Layout('layouts.admin')]
    public function render()
    {
        $query = VaultRemovalRequest::query()
            ->with(['user', 'vaultItem'])
            ->latest('requested_at');
 
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                })->orWhereHas('vaultItem', function ($ivq) {
                    $ivq->where('item_title', 'like', '%' . $this->search . '%')
                        ->orWhere('item_ref', 'like', '%' . $this->search . '%');
                });
            });
        }
 
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
 
        $requests = $query->paginate(15);
 
        return view('livewire.admin.vault.removal-requests', [
            'requests' => $requests
        ]);
    }
}
