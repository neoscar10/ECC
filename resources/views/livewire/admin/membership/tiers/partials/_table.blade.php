<div class="card-body">
    <div class="table-card mb-4">
        <table class="table align-middle table-nowrap mb-0" id="customerTable">
            <thead class="table-light text-muted">
                <tr>
                    <th>Sort</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody class="list form-check-all">
                @forelse ($tiers as $tier)
                    <tr>
                        <td>{{ $tier->sort_order }}</td>
                        <td><h5 class="fs-14 mb-1">{{ $tier->name }}</h5></td>
                        <td class="text-muted">{{ $tier->code }}</td>
                        <td>{{ $tier->currency }} {{ number_format($tier->price, 2) }}</td>
                        <td>{{ $tier->duration_days }} days</td>
                        <td>
                            @if($tier->is_active)
                                <span class="badge bg-success-subtle text-success text-uppercase">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger text-uppercase">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <a href="#" role="button" id="dropdownMenuLink{{ $tier->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-more-2-fill"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink{{ $tier->id }}">
                                    <li><a class="dropdown-item" href="#" wire:click.prevent="edit({{ $tier->id }})"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit/View</a></li>
                                    @role('super_admin')
                                    <li><a class="dropdown-item" href="#" wire:click.prevent="confirmDelete({{ $tier->id }})"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete</a></li>
                                    @endrole
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="noresult">
                                <div class="text-center">
                                    <h5 class="mt-2">No tiers found</h5>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-end">
        {{ $tiers->links() }}
    </div>
</div>
