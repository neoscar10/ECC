<?php

namespace App\Livewire\Admin\Auctions\Lots;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Auctions\AuctionLot;
use Livewire\Attributes\On;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public $search = '';
    public $filterStatus = '';
    
    // Alerts
    public $successMessage = '';

    // Data handling
    public $lotId;
    public $membershipTiers = [];

    // --- Sub-Modals State (Attachments & Early Access kept here for now) ---
    public $showAttachmentsModal = false;
    public $attachmentRows = []; 
    public $attachmentAllowedTiers = [];

    public $showEarlyAccessModal = false;
    public $earlyAccessRows = [];
    public $earlyAccessAllowedTiers = [];
    public $earlyAccessGoLiveDate;

    // Delete Modal State
    public $confirmingDelete = false;
    public $deleteId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function mount()
    {
        $this->membershipTiers = \App\Models\MembershipTier::where('is_active', true)->orderBy('sort_order')->get();
    }

    // --- Render ---

    public function render()
    {
        $query = AuctionLot::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('lot_no', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $lots = $query->withCount(['bids', 'attachments'])
                      ->with(['images']) // optimizing for row display
                      ->orderBy('created_at', 'desc')
                      ->paginate(10);

        return view('livewire.admin.auctions.lots.index', [
            'lots' => $lots
        ])->layout('layouts.admin');
    }

    // --- Actions ---

    public function openCreateModal()
    {
        $this->dispatch('auction-lot:create');
    }

    public function dispatchEdit($id)
    {
        $this->dispatch('auction-lot:edit', lotId: $id);
    }
    
    // Message Handler
    #[On('operation-success')]
    public function showSuccessAlert($message)
    {
        $this->successMessage = $message;
    }

    // Kept solely for Delete/Sub-modals reloading
    #[On('auction-updated')] 
    #[On('auction-created')]
    public function refreshList() 
    {
        // Re-render
    } 

    // --- Attachment Actions (Kept Local) ---

    public function manageAttachments($id)
    {
        $this->lotId = $id;
        $lot = AuctionLot::with(['attachments', 'attachments.tiers', 'visibilityTiers'])->findOrFail($id);
        
        $this->attachmentAllowedTiers = $this->getAllowedTiersForLot($lot);
        
        $this->attachmentRows = $lot->attachments->map(function($att) {
            return [
                'id' => $att->id,
                'type' => $att->type,
                'line_text' => $att->line_text,
                'kv_key' => $att->kv_key,
                'kv_value' => $att->kv_value,
                'heading' => $att->heading,
                'body' => $att->body,
                'restriction_mode' => $att->restriction_mode,
                'restriction_type' => $att->restriction_type,
                'restricted_min_tier_id' => $att->restricted_min_tier_id,
                'restricted_private_tier_id' => $att->restricted_private_tier_id,
                'selected_tiers' => $att->tiers->pluck('id')->toArray(),
            ];
        })->toArray();

        $this->showAttachmentsModal = true;
        $this->dispatch('show-att-modal'); 
    }
    
    public function addAttachmentRow($type)
    {
        $this->attachmentRows[] = [
            'id' => null, 'type' => $type, 'line_text' => '', 'kv_key' => '', 'kv_value' => '', 'heading' => '', 'body' => '',
            'restriction_mode' => 'inherit', 'restriction_type' => null, 'restricted_min_tier_id' => null, 'restricted_private_tier_id' => null, 'selected_tiers' => [],
        ];
    }
    
    public function removeAttachmentRow($index) { unset($this->attachmentRows[$index]); $this->attachmentRows = array_values($this->attachmentRows); }
    
    public function saveAttachments()
    {
        $lot = AuctionLot::with('visibilityTiers')->findOrFail($this->lotId);
        $allowedTierIds = $this->getAllowedTiersForLot($lot)->pluck('id')->toArray();

        // (Simple validation stub - assume similar robust validation)
        $this->validate([
            'attachmentRows.*.type' => 'required',
        ]);

        $submittedIds = array_filter(array_column($this->attachmentRows, 'id'));
        $lot->attachments()->whereNotIn('id', $submittedIds)->delete();
        
        foreach ($this->attachmentRows as $index => $row) {
            $data = [
                'type' => $row['type'],
                'line_text' => $row['line_text'],
                'kv_key' => $row['kv_key'],
                'kv_value' => $row['kv_value'],
                'heading' => $row['heading'],
                'body' => $row['body'],
                'restriction_mode' => $row['restriction_mode'] ?? 'inherit',
                'restriction_type' => ($row['restriction_mode'] ?? 'inherit') == 'restricted' ? $row['restriction_type'] : null,
                'restricted_min_tier_id' => ($row['restriction_mode'] ?? 'inherit') == 'restricted' ? $row['restricted_min_tier_id'] : null,
                'restricted_private_tier_id' => ($row['restriction_mode'] ?? 'inherit') == 'restricted' ? $row['restricted_private_tier_id'] : null,
                'sort_order' => $index,
            ];
            
            $att = $lot->attachments()->updateOrCreate(['id' => $row['id']], $data);
            
            if (($row['restriction_mode'] ?? 'inherit') == 'restricted' && ($row['restriction_type'] ?? '') == 'random') {
                $att->tiers()->sync($row['selected_tiers'] ?? []);
            } else {
                $att->tiers()->detach();
            }
        }
        
        $this->showAttachmentsModal = false;
        $this->dispatch('hide-att-modal');
        $this->dispatch('auction-updated');
    }

    // --- Early Access Actions (Kept Local) ---

    public function configureEarlyAccess($id)
    {
        $this->lotId = $id;
        $lot = AuctionLot::with('earlyAccessWindows')->findOrFail($id);
        
        if ($lot->goLiveNow && $lot->status == 'live') {
              session()->flash('error', 'Early access can only be configured for scheduled auctions.');
              return;
        }

        $this->earlyAccessGoLiveDate = $lot->starts_at ? $lot->starts_at->format('d M Y, h:i A') : 'Not Scheduled';
        $this->earlyAccessAllowedTiers = $this->getAllowedTiersForLot($lot)->toArray(); // Array for view
        
        $this->earlyAccessRows = $lot->earlyAccessWindows->map(function($ea) {
            return [
                'tier_id' => $ea->membership_tier_id,
                'access_at' => $ea->access_at->format('Y-m-d\TH:i'),
            ];
        })->toArray();

        if (count($this->earlyAccessRows) === 0) $this->earlyAccessRows[] = ['tier_id' => '', 'access_at' => ''];

        $this->showEarlyAccessModal = true;
        $this->dispatch('show-ea-modal');
    }

    public function saveEarlyAccess()
    {
        $lot = AuctionLot::findOrFail($this->lotId);
        $lot->earlyAccessWindows()->delete();
            
        foreach ($this->earlyAccessRows as $row) {
            if (!empty($row['tier_id']) && !empty($row['access_at'])) {
                $lot->earlyAccessWindows()->create([
                    'membership_tier_id' => $row['tier_id'],
                    'access_at' => $row['access_at']
                ]);
            }
        }
        
        $this->showEarlyAccessModal = false;
        $this->dispatch('hide-ea-modal');
        session()->flash('success', 'Early access config saved.');
    }
    
    public function addEarlyAccessRow() { $this->earlyAccessRows[] = ['tier_id' => '', 'access_at' => '']; }
    public function removeEarlyAccessRow($index) { unset($this->earlyAccessRows[$index]); $this->earlyAccessRows = array_values($this->earlyAccessRows); }

    protected function getAllowedTiersForLot(AuctionLot $lot)
    {
        if ($lot->visibilityTiers->isEmpty()) return $this->membershipTiers;
        return $lot->visibilityTiers;
    }
    
    // --- Delete ---

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed()
    {
        if ($this->deleteId) {
            $lot = AuctionLot::find($this->deleteId);
            if ($lot) {
                $lot->delete();
                session()->flash('success', 'Auction lot deleted successfully.');
            }
        }
        $this->deleteId = null;
        $this->dispatch('hide-delete-modal');
    }
}
