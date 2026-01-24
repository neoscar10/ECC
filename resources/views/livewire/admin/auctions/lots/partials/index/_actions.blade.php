<div class="dropdown d-inline-block">
    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ri-more-fill align-middle"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a href="{{ route('admin.auctions.lots.show', $lot->id) }}" class="dropdown-item">
                <i class="ri-eye-fill align-bottom me-2 text-muted"></i> View Details
            </a>
        </li>
        <li>
            {{-- Edit dispatches to the Manager Modal via Index Method --}}
            <button class="dropdown-item edit-item-btn" wire:click="dispatchEdit({{ $lot->id }})">
                <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
            </button>
        </li>
        <li>
            <button class="dropdown-item" wire:click="manageAttachments({{ $lot->id }})">
                <i class="ri-attachment-2 align-bottom me-2 text-muted"></i> Attachments
                @if($lot->attachments_count > 0)
                    <span class="badge bg-success-subtle text-success ms-auto">{{ $lot->attachments_count }}</span>
                @endif
            </button>
        </li>
        @if(!$lot->goLiveNow && $lot->status != 'live' && $lot->early_access_enabled)
            <li>
                <button class="dropdown-item" wire:click="configureEarlyAccess({{ $lot->id }})">
                    <i class="ri-calendar-event-fill align-bottom me-2 text-muted"></i> Early Access
                </button>
            </li>
        @endif
        <div class="dropdown-divider"></div>
        <li>
            <button class="dropdown-item remove-item-btn" wire:click="confirmDelete({{ $lot->id }})">
                <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
            </button>
        </li>
    </ul>
</div>
