<!-- Resolution Modal -->
<div wire:ignore.self class="modal fade" id="resolutionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
            <div class="modal-header bg-danger p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-white bg-opacity-20 rounded-3 p-2 me-3">
                        <i class="ri-shield-flash-line fs-24 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-white fw-bold mb-0">Restriction Resolution Center</h5>
                        <p class="text-white-50 small mb-0">Fixing orphaned product & content visibility</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0">
                <div class="p-4">
                    <div class="alert alert-soft-danger d-flex align-items-center border-0 mb-4" role="alert">
                        <i class="ri-information-line fs-20 me-2"></i>
                        <div>
                            These items are referencing membership tiers that no longer exist. This makes them **hidden from all users**. Select a new "Starting Point" tier to restore visibility.
                        </div>
                    </div>

                    <div class="row align-items-center mb-4">
                        <div class="col">
                            <label class="form-label fw-bold text-uppercase fs-11 text-muted mb-1">Global Re-assignment Tier</label>
                            <select class="form-select border-0 bg-light shadow-sm" wire:model="resolutionTargetTierId">
                                <option value="">Choose active tier...</option>
                                @foreach(App\Models\MembershipTier::where('is_active', true)->get() as $tier)
                                    <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-danger mt-3 px-4 fw-bold" 
                                    wire:click="resolveAllRestrictions" 
                                    wire:loading.attr="disabled"
                                    {{ !$resolutionTargetTierId ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="resolveAllRestrictions">
                                    <i class="ri-magic-line me-1"></i> Fix All Items
                                </span>
                                <span wire:loading wire:target="resolveAllRestrictions">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span> Restoring Visibility...
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3" style="max-height: 350px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="fs-12 text-muted text-uppercase fw-bold ps-3">Module</th>
                                    <th class="fs-12 text-muted text-uppercase fw-bold">Item Name / Title</th>
                                    <th class="fs-12 text-muted text-uppercase fw-bold text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orphanedItems as $item)
                                    <tr>
                                        <td class="ps-3">
                                            @if($item['module'] == 'auctions')
                                                <span class="badge bg-soft-info text-info">Auction</span>
                                            @elseif($item['module'] == 'archive')
                                                <span class="badge bg-soft-primary text-primary">Archive</span>
                                            @else
                                                <span class="badge bg-soft-secondary text-secondary">CMS</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $item['name'] }}</span>
                                            <div class="text-muted fs-11">ID: {{ $item['id'] }}</div>
                                        </td>
                                        <td class="text-end pe-3">
                                            @php
                                                $route = match($item['module']) {
                                                    'auctions' => route('admin.auctions.lots.index'),
                                                    'archive' => route('admin.archive.products'),
                                                    'cms' => route('admin.cms.blocks.index'),
                                                    default => '#'
                                                };
                                            @endphp
                                            <a href="{{ $route }}" class="btn btn-sm btn-ghost-primary">
                                                <i class="ri-external-link-line"></i> Manage
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <div class="text-muted">No broken restrictions found.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-3 border-0">
                <button type="button" class="btn btn-ghost-dark" data-bs-dismiss="modal">Close Resolution Center</button>
            </div>
        </div>
    </div>
</div>
