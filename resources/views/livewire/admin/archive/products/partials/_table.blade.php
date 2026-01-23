<div class="table-responsive table-card mb-1">
    <table class="table align-middle" id="productTable">
        <thead class="table-light text-muted">
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price Range</th>
                <th class="text-center">Stock</th>
                <th>Visibility</th>
                <th>Status</th>
                <th>Early Access</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody class="list form-check-all">
            @forelse($products as $product)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            @php
                                $img = $product->images->first();
                                $p = $img ? preg_replace('#^public/#', '', str_replace('\\','/',$img->image_path)) : null;
                            @endphp
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar-sm bg-light rounded p-1">
                                    @if($p)
                                        <img src="{{ Storage::url($p) }}" class="img-fluid h-100 d-block" alt="">
                                    @else
                                        <div class="avatar-title bg-soft-light text-muted rounded fs-24">
                                            <i class="ri-image-2-line"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <h5 class="fs-14 mb-1">{{ $product->title }}</h5>
                                <p class="text-muted mb-0">{{ Str::limit(strip_tags($product->description_unlocked), 30) }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($product->category)
                            <span class="badge bg-info-subtle text-info">{{ $product->category->title }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        {{ $product->currency }} {{ number_format($product->price_min_amount) }} - {{ number_format($product->price_max_amount) }}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info-subtle text-info">{{ $product->quantity ?? 1 }}</span>
                    </td>
                    <td>
                        @if($product->restriction_mode == 'public')
                             <span class="badge bg-success-subtle text-success">Public</span>
                        @else
                             <span class="badge bg-warning-subtle text-warning">Restricted</span>
                             <small class="d-block text-muted" style="font-size: 10px;">{{ ucfirst($product->restriction_type) }}</small>
                        @endif
                    </td>
                    <td>
                         @php
                            $isLive = $product->go_live_now || ($product->go_live_at && now()->gte($product->go_live_at));
                         @endphp
                         @if($isLive)
                             <span class="badge bg-success-subtle text-success">Live</span>
                         @else
                             <span class="badge bg-secondary-subtle text-secondary">Scheduled</span>
                             <small class="d-block text-muted" style="font-size: 10px;">{{ $product->go_live_at ? $product->go_live_at->format('d M H:i') : '' }}</small>
                         @endif
                    </td>
                    <td>
                        @if($product->early_access_enabled)
                            <span class="badge bg-primary-subtle text-primary">Yes</span>
                        @else
                            <span class="text-muted">No</span>
                        @endif
                    </td>
                    <td>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item edit-item-btn" wire:click="edit({{ $product->id }})">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                    </button>
                                </li>
                                @if($product->early_access_enabled)
                                <li>
                                    <button class="dropdown-item" wire:click="configureEarlyAccess({{ $product->id }})">
                                        <i class="ri-calendar-event-fill align-bottom me-2 text-muted"></i> Early Access
                                    </button>
                                </li>
                                @endif
                                <li>
                                    <button class="dropdown-item" wire:click="manageAttachments({{ $product->id }})">
                                        <i class="ri-attachment-2 align-bottom me-2 text-muted"></i> Attachments
                                        @if($product->attachments_count > 0)
                                            <span class="badge bg-secondary ms-1">{{ $product->attachments_count }}</span>
                                        @endif
                                    </button>
                                </li>
                                <div class="dropdown-divider"></div>
                                <li>
                                    <button class="dropdown-item remove-item-btn" wire:click="confirmDelete({{ $product->id }})">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                    </button>
                                </li>
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
                                    <h5 class="mt-2">No products found</h5>
                                    <p class="text-muted mb-0">Try adjusting your filters.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-end">
    {{ $products->links() }}
</div>
