<div>
    <div wire:ignore.self class="modal fade" id="contactConfigModal" tabindex="-1" aria-labelledby="contactConfigModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactConfigModalLabel">Configure Contact Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="concierge_phone" class="form-label">Club Concierge Phone</label>
                                <input type="text" class="form-control" id="concierge_phone" wire:model="concierge_phone" placeholder="+91 12345 12345">
                                @error('concierge_phone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="support_email" class="form-label">Membership Support Email</label>
                                <input type="email" class="form-control" id="support_email" wire:model="support_email" placeholder="members@executivecricket.club">
                                @error('support_email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fs-14 mb-0">Inquiry Subjects</h6>
                            <small class="text-muted">Drag to reorder subjects.</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" wire:model="newSubjectLabel" placeholder="New Subject Label (e.g. Events)" wire:keydown.enter.prevent="addSubject">
                                    <button class="btn btn-primary" type="button" wire:click="addSubject">
                                        <i class="ri-add-line align-bottom me-1"></i> Add
                                    </button>
                                </div>
                                @error('newSubjectLabel') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- SortableJS -->
                        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

                        <ul class="list-group" 
                            id="sortable-subjects"
                            x-data="{
                                initSortable() {
                                    if (typeof Sortable === 'undefined') return;
                                    new Sortable(this.$el, {
                                        animation: 150,
                                        handle: '.handle',
                                        ghostClass: 'bg-light',
                                        onEnd: (evt) => {
                                            let orderedIds = Array.from(evt.target.children).map(el => el.dataset.id);
                                            @this.reorderSubjects(orderedIds);
                                        }
                                    });
                                }
                            }"
                            x-init="initSortable()"
                        >
                            @forelse($subjects as $subject)
                                <li class="list-group-item d-flex align-items-center gap-3 p-2 bg-white" 
                                    wire:key="subject-{{ $subject['id'] }}" 
                                    data-id="{{ $subject['id'] }}">
                                    
                                    <div class="cursor-grab handle text-muted py-1">
                                        <i class="ri-drag-move-2-line fs-18"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div>
                                            <input type="text" class="form-control form-control-sm border-0 bg-transparent p-0 fw-medium" 
                                                   value="{{ $subject['label'] }}" 
                                                   wire:change="updateSubjectLabel({{ $subject['id'] }}, $event.target.value)">
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-1">
                                        <!-- Fallback Up/Down for accessibility/backup -->
                                        <div class="btn-group btn-group-sm me-2 opacity-50 hover-opacity-100">
                                            <button type="button" class="btn btn-ghost-secondary btn-icon btn-sm py-0" wire:click="moveSubject({{ $subject['id'] }}, 'up')" title="Move Up">
                                                <i class="ri-arrow-up-s-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-ghost-secondary btn-icon btn-sm py-0" wire:click="moveSubject({{ $subject['id'] }}, 'down')" title="Move Down">
                                                <i class="ri-arrow-down-s-line"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-ghost-danger btn-icon btn-sm" wire:click="deleteSubject({{ $subject['id'] }})" title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item text-center text-muted py-3 bg-light">
                                    No subjects defined. Add your first subject above.
                                </li>
                            @endforelse
                        </ul>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" wire:click="save">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
             var configModal = new bootstrap.Modal(document.getElementById('contactConfigModal'));
             
             Livewire.on('open-config-modal', () => {
                 configModal.show();
             });
 
             Livewire.on('close-config-modal', () => {
                 configModal.hide();
             });
        });
    </script>
</div>
