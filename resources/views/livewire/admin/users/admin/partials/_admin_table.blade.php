<div class="card mb-4">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">Admin Users</h4>
        @if(auth()->user()->hasRole('super_admin'))
        <div class="flex-shrink-0">
            <button type="button" class="btn btn-primary btn-sm" wire:click="createAdmin">
                <i class="ri-add-line align-middle"></i> Add Admin
            </button>
        </div>
        @endif
    </div>
    <div class="card-body">
        <div class="table-card">
            <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adminUsers as $admin)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 avatar-xs me-2">
                                    <div class="avatar-title bg-soft-info text-info rounded-circle fs-13">
                                        {{ strtoupper(substr($admin->name ?? $admin->email, 0, 1)) }}
                                    </div>
                                </div>
                                <h5 class="fs-14 m-0">{{ $admin->name ?? 'N/A' }}</h5>
                            </div>
                        </td>
                        <td>{{ $admin->email }}</td>
                        <td>
                            @foreach($admin->roles as $role)
                                @if($role->name === 'super_admin')
                                    <span class="badge bg-danger">{{ $role->name }}</span>
                                @else
                                    <span class="badge bg-success">{{ $role->name }}</span>
                                @endif
                            @endforeach
                        </td>
                        <td>
                            <!-- Restrict Actions to Super Admin only (or self if needed, but requirements say restrict admin actions) -->
                            @if(auth()->user()->hasRole('super_admin'))
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-more-2-fill align-middle"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <!-- Edit disabled as per requirement -->
                                    <!-- <li><a class="dropdown-item edit-item-btn" wire:click="editAdmin({{ $admin->id }})"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit</a></li> -->
                                    <li><a class="dropdown-item remove-item-btn" wire:click="confirmDeleteAdmin({{ $admin->id }})"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete</a></li>
                                </ul>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No admin users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
