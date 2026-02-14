<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Vault Details: {{ $this->user->name }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.vault-access.index') }}">Vault</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-start mb-3">
                         <a href="{{ route('admin.vault-access.index') }}" class="btn btn-sm btn-soft-secondary"><i class="ri-arrow-left-line align-bottom me-1"></i> Back to List</a>
                    </div>
                    
                    <h5 class="card-title mb-3">Member Profile</h5>
                    <img src="{{ $this->user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($this->user->name) }}" class="rounded-circle avatar-lg img-thumbnail mb-3" alt="user-profile-image">
                    <h5 class="mb-1">{{ $this->user->name }}</h5>
                    <p class="text-muted mb-2">{{ $this->user->email }}</p>
                    <span class="badge bg-primary text-uppercase mb-4">{{ $this->user->currentMembership->membershipTier->name }}</span>
                    
                    <div class="text-start mt-2">
                        <h6 class="fs-12 text-uppercase text-muted mb-3">Personal Details</h6>
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm table-nowrap mb-0">
                                <tbody>
                                    <tr>
                                        <th class="ps-0" scope="row">Member Code :</th>
                                        <td class="text-muted">{{ $this->user->member_code }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0" scope="row">Phone :</th>
                                        <td class="text-muted">{{ $this->user->phone ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0" scope="row">Location :</th>
                                        <td class="text-muted">{{ $this->user->display_location }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0" scope="row">Joined :</th>
                                        <td class="text-muted">{{ $this->user->created_at->format('d M, Y') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-3 border-top text-start">
                         <h6 class="fs-12 text-uppercase text-muted mb-3">Vault Stats</h6>
                         <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Locked Items</span>
                            <span class="badge bg-success-subtle text-success">{{ $this->user->vaultItems()->locked()->count() }}</span>
                         </div>
                         <div class="d-flex justify-content-between">
                            <span class="text-muted">Released Items</span>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $this->user->vaultItems()->removed()->count() }}</span>
                         </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Vault Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Locked At</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    @if($item->display_image_url)
                                                        <img src="{{ $item->display_image_url }}" alt="" class="avatar-sm rounded" onerror="this.onerror=null;this.src='https://placehold.co/100?text=No+Image';">
                                                    @else
                                                        <div class="avatar-sm bg-light rounded d-flex align-items-center justify-content-center">
                                                            <i class="ri-image-2-line fs-20 text-muted"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h5 class="fs-14 my-1">{{ $item->item_title }}</h5>
                                                    <p class="text-muted mb-0">{{ $item->currency }} {{ number_format($item->price, 2) }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $item->locked_at->format('d M, Y h:i A') }}</td>
                                        <td>
                                            @if($item->status === 'locked')
                                                <span class="badge bg-success-subtle text-success text-uppercase">Locked</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">Removed</span>
                                                <div class="small text-muted">{{ $item->removed_at?->format('d M, Y') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->status === 'locked')
                                                <button wire:click="confirmRemoval({{ $item->id }})" class="btn btn-sm btn-soft-danger">
                                                    <i class="ri-logout-box-r-line align-bottom me-1"></i> Release
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <p class="text-muted mb-0">No items in vault.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Remove Modal --}}
    <div wire:ignore.self class="modal fade" id="removeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Release Item from Vault</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this item as removed/delivered? This action cannot be undone.</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea wire:model="notes" class="form-control" rows="3" placeholder="e.g. Delivered personally to member"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="markRemoved">Confirm Release</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const removeModal = new bootstrap.Modal(document.getElementById('removeModal'));
            
            @this.on('open-remove-modal', () => {
                removeModal.show();
            });

            @this.on('close-modals', () => {
                removeModal.hide();
            });
        });
    </script>
</div>
