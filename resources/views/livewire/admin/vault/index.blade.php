<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Vault Access</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Vault</a></li>
                        <li class="breadcrumb-item active">Members</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="vaultList">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm-auto">
                            <div>
                                <h5 class="card-title mb-0">Members with Vault Access</h5>
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="d-flex justify-content-sm-end gap-2">
                                <div class="search-box ms-2">
                                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search members...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                                
                                <select wire:model.live="tierFilter" class="form-control" style="width: 200px;">
                                    <option value="">All Tiers</option>
                                    @foreach($tiers as $tier)
                                        <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive table-card mb-3">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Member</th>
                                    <th scope="col">Tier</th>
                                    <th scope="col" class="text-center">Items in Vault</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse ($users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 mb-1">{{ $user->name }}</h5>
                                                    <p class="text-muted mb-0">{{ $user->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary text-uppercase">{{ $user->currentMembership->membershipTier->name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary fs-12">{{ $user->vault_items_count }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.vault-access.show', $user->id) }}" class="btn btn-sm btn-soft-primary">
                                                <i class="ri-eye-fill align-bottom me-1"></i> View Vault
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="noresult">
                                                <div class="text-center">
                                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                                    <h5 class="mt-2">Sorry! No Result Found</h5>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
