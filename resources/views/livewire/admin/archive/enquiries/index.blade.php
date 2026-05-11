<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Archive Enquiries</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">The Archive</a></li>
                        <li class="breadcrumb-item active">Enquiries</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-check-line me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card" id="enquiryList">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm-auto">
                            <div>
                                <h5 class="card-title mb-0">Enquiries</h5>
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="d-flex justify-content-sm-end gap-2">
                                <div class="search-box ms-2">
                                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search enquiries...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                                
                                <select wire:model.live="status" class="form-control" style="width: 150px;">
                                    <option value="">All Statuses</option>
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive table-card mb-3">
                        <table class="table align-middle table-nowrap mb-0" id="customerTable">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 50px;">ID</th>
                                    <th class="sort">Date</th>
                                    <th class="sort">Contact</th>
                                    <th class="sort">Product</th>

                                    <th class="sort">Status</th>
                                    <th class="sort">Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse ($enquiries as $enquiry)
                                    <tr>
                                        <td><a href="#" wire:click.prevent="viewEnquiry({{ $enquiry->id }})" class="fw-medium link-primary">#{{ $enquiry->id }}</a></td>
                                        <td>{{ $enquiry->created_at->format('d M, Y h:i A') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 mb-1">{{ $enquiry->contact_name ?? 'N/A' }}</h5>
                                                    <p class="text-muted mb-0">{{ $enquiry->contact_email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($enquiry->product)
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 me-2">
                                                        @if($enquiry->product->images->first())
                                                            <img src="{{ Storage::url($enquiry->product->images->first()->image_path) }}" alt="" class="avatar-xs rounded-circle">
                                                        @else
                                                            <div class="avatar-xs bg-light rounded-circle"></div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        {{ Str::limit($enquiry->product->title, 20) }}
                                                        @if(method_exists($enquiry->product, 'trashed') && $enquiry->product->trashed())
                                                            <span class="badge bg-danger-subtle text-danger ms-1" style="font-size: 10px;">DELETED</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Product Deleted</span>
                                            @endif
                                        </td>

                                        <td class="status">
                                            @if($enquiry->status === 'new')
                                                <span class="badge bg-info-subtle text-info text-uppercase">New</span>
                                            @elseif($enquiry->status === 'contacted')
                                                <span class="badge bg-warning-subtle text-warning text-uppercase">Contacted</span>
                                            @elseif($enquiry->status === 'closed')
                                                <span class="badge bg-success-subtle text-success text-uppercase">Closed</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $enquiry->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a href="#" wire:click.prevent="viewEnquiry({{ $enquiry->id }})" class="dropdown-item"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                                    <li><a class="dropdown-item edit-item-btn" href="#" wire:click.prevent="updateStatus({{ $enquiry->id }}, 'contacted')"><i class="ri-mail-send-fill align-bottom me-2 text-muted"></i> Mark Contacted</a></li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" wire:click.prevent="attemptLogSale({{ $enquiry->id }})">
                                                            <i class="ri-shopping-cart-2-line align-bottom me-2 text-primary"></i> Log Sale
                                                        </a>
                                                    </li>
                                                    <li><a class="dropdown-item remove-item-btn" href="#" wire:click.prevent="updateStatus({{ $enquiry->id }}, 'closed')"><i class="ri-check-double-fill align-bottom me-2 text-muted"></i> Mark Closed</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="noresult">
                                                <div class="text-center">
                                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                                    <h5 class="mt-2">Sorry! No Result Found</h5>
                                                    <p class="text-muted mb-0">We've searched more than 150+ enquiries We did not find any enquiries for you search.</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {{ $enquiries->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Orders Create Modal (Hidden until triggered) --}}
    @livewire('admin.archive.orders.create')
    
    {{-- View Modal --}}
    <div wire:ignore.self class="modal fade" id="viewEnquiryModal" tabindex="-1" aria-labelledby="viewEnquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewEnquiryModalLabel">Enquiry Details #{{ $selectedEnquiry?->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($selectedEnquiry)
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6 class="text-muted text-uppercase fw-semibold mb-3">Customer Information</h6>
                                <p class="mb-2"><span class="fw-medium">Name:</span> {{ $selectedEnquiry->contact_name }}</p>
                                <p class="mb-2"><span class="fw-medium">Membership Tier:</span> {{ $selectedEnquiry->user?->currentMembership?->membershipTier?->name ?? 'N/A' }}</p>
                                <p class="mb-2"><span class="fw-medium">Email:</span> <a href="mailto:{{ $selectedEnquiry->contact_email }}">{{ $selectedEnquiry->contact_email }}</a></p>
                                <p class="mb-2"><span class="fw-medium">Phone:</span> {{ $selectedEnquiry->contact_phone ?? 'N/A' }}</p>

                                <p class="mb-2"><span class="fw-medium">User Account:</span> 
                                    @if($selectedEnquiry->user)
                                        <a href="{{ route('admin.users.index', ['search' => $selectedEnquiry->user->email]) }}" target="_blank">View Profile</a>
                                    @else
                                        Guest / Deleted
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase fw-semibold mb-3">Product Information</h6>
                                @if($selectedEnquiry->product)
                                    <div class="d-flex gap-3 mb-3">
                                        <div class="flex-shrink-0">
                                            @if($selectedEnquiry->product->images->first())
                                                <img src="{{ Storage::url($selectedEnquiry->product->images->first()->image_path) }}" alt="" class="avatar-sm rounded">
                                            @else
                                                <div class="avatar-sm bg-light rounded d-flex align-items-center justify-content-center">
                                                    <i class="ri-image-2-line fs-20 text-muted"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 mb-1">
                                                {{ $selectedEnquiry->product->title }}
                                                @if(method_exists($selectedEnquiry->product, 'trashed') && $selectedEnquiry->product->trashed())
                                                    <span class="badge bg-danger-subtle text-danger ms-1">Deleted</span>
                                                @endif
                                            </h6>
                                            <p class="text-muted mb-0">{{ $selectedEnquiry->product->category->title ?? 'Unknown Category' }}</p>
                                            <a href="#" class="text-primary fs-12">View Product</a>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-danger">Product has been deleted.</p>
                                @endif
                                
                                <h6 class="text-muted text-uppercase fw-semibold mb-2 mt-4">Enquiry Status</h6>
                                <div class="d-flex gap-2">
                                    <button wire:click="updateStatus({{ $selectedEnquiry->id }}, 'new')" class="btn btn-sm {{ $selectedEnquiry->status === 'new' ? 'btn-info' : 'btn-ghost-info' }}">New</button>
                                    <button wire:click="updateStatus({{ $selectedEnquiry->id }}, 'contacted')" class="btn btn-sm {{ $selectedEnquiry->status === 'contacted' ? 'btn-warning' : 'btn-ghost-warning' }}">Contacted</button>
                                    <button wire:click="updateStatus({{ $selectedEnquiry->id }}, 'closed')" class="btn btn-sm {{ $selectedEnquiry->status === 'closed' ? 'btn-success' : 'btn-ghost-success' }}">Closed</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h6 class="text-muted text-uppercase fw-semibold mb-3">Message</h6>
                            <div class="p-3 bg-light rounded">
                                <p class="mb-0 text-break">{{ $selectedEnquiry->message }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-2 text-end text-muted fs-11">
                            Submitted on {{ $selectedEnquiry->created_at->format('d M, Y h:i A') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Scripts for Modal --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            const myModal = new bootstrap.Modal(document.getElementById('viewEnquiryModal'));
            
            @this.on('show-view-modal', () => {
                myModal.show();
            });
        });
    </script>
</div>
