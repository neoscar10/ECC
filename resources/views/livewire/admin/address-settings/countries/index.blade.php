<div>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Delivery Countries</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Address Settings</a></li>
                        <li class="breadcrumb-item active">Countries</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Delivery Countries</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary add-btn" wire:click="openCreateModal">
                                <i class="ri-add-line align-bottom me-1"></i> Add Country
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body border border-dashed border-end-0 border-start-0 mt-3">
                    <div class="row g-3">
                        <div class="col-xxl-5 col-sm-6">
                            <div class="search-box">
                                <input type="text" class="form-control search" placeholder="Search countries..." wire:model.live.debounce.500ms="search">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card mb-1">
                        <table class="table align-middle table-nowrap">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Country</th>
                                    <th>Code</th>
                                    <th>Address Group</th>
                                    <th>Delivery Type</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($countries as $country)
                                <tr>
                                    <td>
                                        <h5 class="fs-14 mb-1">{{ $country->name }}</h5>
                                    </td>
                                    <td>{{ $country->code ?: '-' }}</td>
                                    <td>
                                        @if($country->addressGroup)
                                            <span class="badge bg-info-subtle text-info">{{ $country->addressGroup->name }}</span>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($country->delivery_type === 'courier')
                                            <span class="badge bg-primary-subtle text-primary">Courier ({{ ucfirst($country->courier_name) }})</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Negotiated (To be discussed)</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($country->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <button type="button" class="btn btn-sm btn-soft-primary" wire:click="editCountry({{ $country->id }})">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                            </li>
                                            <li class="list-inline-item">
                                                <button type="button" class="btn btn-sm btn-soft-danger" 
                                                    wire:click="deleteCountry({{ $country->id }})" 
                                                    wire:confirm="Are you sure you want to delete this country?">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">
                                        <i class="ri-global-line fs-24 d-block mb-2"></i>
                                        No delivery countries found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        {{ $countries->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="countryModal" tabindex="-1" aria-labelledby="countryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form wire:submit.prevent="save">
                    <div class="modal-header">
                        <h5 class="modal-title" id="countryModalLabel">{{ $isEditMode ? 'Edit' : 'Add' }} Delivery Country</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Country Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" class="form-control" wire:model="name" placeholder="e.g. India">
                                @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="code" class="form-label">Country Code</label>
                                <input type="text" id="code" class="form-control" wire:model="code" placeholder="e.g. IN">
                                @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch form-switch-lg mt-1" dir="ltr">
                                    <input type="checkbox" class="form-check-input" id="is_active" wire:model="is_active">
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="shipping_address_group_id" class="form-label">Address Field Group <span class="text-danger">*</span></label>
                                <select id="shipping_address_group_id" class="form-select" wire:model="shipping_address_group_id">
                                    <option value="">Select an address group...</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('shipping_address_group_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="col-md-4">
                                <label for="delivery_type" class="form-label">Delivery Type <span class="text-danger">*</span></label>
                                <select id="delivery_type" class="form-select" wire:model.live="delivery_type">
                                    <option value="courier">Automated via Courier</option>
                                    <option value="negotiated">Negotiated</option>
                                </select>
                                @error('delivery_type') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            @if($delivery_type === 'courier')
                            <div class="col-md-4">
                                <label for="courier_name" class="form-label">Courier Service</label>
                                <select id="courier_name" class="form-select" wire:model="courier_name">
                                    <option value="shiprocket">Shiprocket</option>
                                    <!-- Add other couriers here if implemented -->
                                </select>
                                @error('courier_name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            @else
                            <div class="col-md-12 mt-2">
                                <div class="alert alert-info bg-soft-info border-0 mb-0">
                                    <i class="ri-information-line me-1 align-middle"></i> 
                                    Users selecting this country will not be charged a shipping fee at checkout. Instead, they will see a "To be discussed" message, and the admin will arrange shipping and collect payment offline.
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">Save Country</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-modal', (id) => {
                let modal = new bootstrap.Modal(document.getElementById(id[0]));
                modal.show();
            });

            Livewire.on('hide-modal', (id) => {
                let modalEl = document.getElementById(id[0]);
                let modal = bootstrap.Modal.getInstance(modalEl);
                if(modal) {
                    modal.hide();
                }
            });
            
            Livewire.on('notify', (event) => {
                const data = event[0];
                Toastify({
                    text: data.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: data.type === 'error' ? "#f06548" : "#0ab39c",
                }).showToast();
            });
        });
    </script>
    @endpush
</div>
