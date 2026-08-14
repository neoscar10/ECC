<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Navigation Links Settings</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item">Settings</li>
                        <li class="breadcrumb-item active">Navigation Links</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm mb-0">
                <div class="d-flex align-items-center">
                    <i class="ri-information-line fs-3 me-2"></i>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">About Navigation Links</h6>
                        <p class="mb-0">These labels control the names of the main navigation items in the user frontend and the mobile application. Changing them here will instantly reflect across the platform.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">App Navigation Labels</h5>
                </div>
                <div class="card-body">
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form wire:submit="save">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="explore" class="form-label">Explore Link Label <span class="text-muted fw-normal" style="font-size: 11px;">(Max 15 chars)</span></label>
                                <input type="text" class="form-control" id="explore" wire:model="explore" placeholder="Explore" maxlength="15">
                                @error('explore') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="archive" class="form-label">Archive Link Label <span class="text-muted fw-normal" style="font-size: 11px;">(Max 15 chars)</span></label>
                                <input type="text" class="form-control" id="archive" wire:model="archive" placeholder="Archive" maxlength="15">
                                @error('archive') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="auctions" class="form-label">Auctions Link Label <span class="text-muted fw-normal" style="font-size: 11px;">(Max 15 chars)</span></label>
                                <input type="text" class="form-control" id="auctions" wire:model="auctions" placeholder="Auctions" maxlength="15">
                                @error('auctions') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="club" class="form-label">Club Link Label <span class="text-muted fw-normal" style="font-size: 11px;">(Max 15 chars)</span></label>
                                <input type="text" class="form-control" id="club" wire:model="club" placeholder="Club" maxlength="15">
                                @error('club') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="shop" class="form-label">Shop Link Label <span class="text-muted fw-normal" style="font-size: 11px;">(Max 15 chars)</span></label>
                                <input type="text" class="form-control" id="shop" wire:model="shop" placeholder="Shop" maxlength="15">
                                @error('shop') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="profile" class="form-label">Profile Link Label <span class="text-muted fw-normal" style="font-size: 11px;">(Max 15 chars)</span></label>
                                <input type="text" class="form-control" id="profile" wire:model="profile" placeholder="Profile" maxlength="15">
                                @error('profile') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-5">
                                <span wire:loading.remove wire:target="save">Save Changes</span>
                                <span wire:loading wire:target="save">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
