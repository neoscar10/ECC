<div class="table-responsive table-card mb-1">
    <table class="table align-middle" id="customerTable">
        <thead class="table-light text-muted">
            <tr>
                <th class="sort" data-sort="image">Image</th>
                <th class="sort" data-sort="title">Title</th>
                <th class="sort" data-sort="visibility">Visibility</th>
                <th class="sort" data-sort="tiers">Allowed Tiers</th>
                <th class="sort" data-sort="status">Status</th>
                <th class="sort" data-sort="date">Created</th>
                <th class="sort" data-sort="action">Action</th>
            </tr>
        </thead>
        <tbody class="list form-check-all">
            @forelse($categories as $category)
                <tr wire:key="cat-{{ $category->id }}">
                    <td class="image">
                        @php
                            $p = $category->image_path ? preg_replace('#^public/#', '', str_replace('\\','/',$category->image_path)) : null;
                        @endphp
                        @if ($p)
                            <div class="avatar-sm">
                                <img src="{{ Storage::url($p) }}" class="img-fluid rounded-3" alt="{{ $category->title }}">
                            </div>
                        @else
                            <div class="avatar-sm">
                                <div class="avatar-title rounded-3 bg-light text-primary fs-20">
                                    <i class="ri-image-2-line"></i>
                                </div>
                            </div>
                        @endif
                    </td>
                    <td class="title">
                        <h5 class="fs-14 mb-1">{{ $category->title }}</h5>
                        <p class="text-muted mb-0">{{ Str::limit($category->description, 30) }}</p>
                    </td>
                    <td class="visibility">
                        @if ($category->visibility === 'public')
                            <span class="badge bg-success-subtle text-success text-uppercase">Public</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning text-uppercase">Restricted</span>
                        @endif
                    </td>
                    <td class="tiers">
                        @if ($category->visibility === 'restricted')
                            <div class="d-flex gap-1 align-items-center">
                                @foreach ($category->tiers->take(3) as $tier)
                                    <span class="badge border border-primary text-primary">{{ $tier->name }}</span>
                                @endforeach
                                @if ($category->tiers_count > 3)
                                    <span class="badge bg-light text-body">+{{ $category->tiers_count - 3 }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="status">
                        @if ($category->is_active)
                            <span class="badge bg-success-subtle text-success text-uppercase">Active</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger text-uppercase">Inactive</span>
                        @endif
                    </td>
                    <td class="date">{{ $category->created_at->format('d M, Y') }}</td>
                    <td>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item edit-item-btn" wire:click="edit({{ $category->id }})">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item remove-item-btn" wire:click="delete({{ $category->id }})" wire:confirm="Are you sure you want to delete this category?">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="noresult">
                            <div class="text-center">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                <h5 class="mt-2">Sorry! No Result Found</h5>
                                <p class="text-muted mb-0">We've searched more than 150+ Orders We did not find any orders for you search.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-end">
    {{ $categories->links() }}
</div>
