<div class="card" id="userList">
    <div class="card-header border-0">
        <div class="row g-4 align-items-center">
            <div class="col-sm-3">
                <div class="search-box">
                    <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search users...">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="input-light">
                    <select class="form-control" wire:model.live="membershipFilter">
                        <option value="">All Membership Tiers</option>
                        @foreach($membershipTiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div>
            <div class="table-card">
                <table class="table align-middle table-nowrap" id="userTable">
                    <thead class="text-muted">
                        <tr>
                            <th class="sort" data-sort="name" wire:click="sortBy('name')" style="cursor: pointer;">User</th>
                            <th class="sort" data-sort="email" wire:click="sortBy('email')" style="cursor: pointer;">Email</th>
                            <th class="sort" data-sort="phone" wire:click="sortBy('phone')" style="cursor: pointer;">Phone</th>
                            <th class="sort" data-sort="tier">Membership Tier</th>
                            <th class="sort" data-sort="date" wire:click="sortBy('created_at')" style="cursor: pointer;">Joined Date</th>
                            <th class="sort" data-sort="action">Action</th>
                        </tr>
                    </thead>
                    <tbody class="list form-check-all">
                        @forelse($users as $user)
                        <tr>
                            <td class="name">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 avatar-xs me-2">
                                        <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-13">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="fs-14 m-0">{{ $user->name }}</h5>
                                        @if($user->full_name)
                                            <small class="text-muted">{{ $user->full_name }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="email">{{ $user->email }}</td>
                            <td class="phone">{{ $user->phone ?? '-' }}</td>
                            <td class="tier">
                                @if($user->currentMembership && $user->currentMembership->membershipTier)
                                    <span class="badge bg-info">{{ $user->currentMembership->membershipTier->name }}</span>
                                @else
                                    <span class="badge bg-light text-body">None</span>
                                @endif
                            </td>
                            <td class="date">{{ $user->created_at->format('d M, Y') }}</td>
                            <td>
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-fill align-middle"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" wire:click="viewUser({{ $user->id }})"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                        <!-- Edit disabled as per requirement -->
                                        <!-- <li><a class="dropdown-item edit-item-btn" wire:click="editUser({{ $user->id }})"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit</a></li> -->
                                        <li><a class="dropdown-item remove-item-btn" wire:click="confirmDeleteUser({{ $user->id }})"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                <h5 class="mt-2">Sorry! No Result Found</h5>
                                <p class="text-muted mb-0">No users found matching your search.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
