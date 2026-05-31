<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Gateway Control Center</h4>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Configured Payment Gateways</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>Gateway</th>
                                    <th>Status</th>
                                    <th>Default</th>
                                    <th>Sort Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gateways as $gateway)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 my-1"><a href="javascript:void(0);" class="text-reset">{{ $gateway->name }}</a></h5>
                                                    <span class="text-muted text-uppercase fs-11">Code: <code>{{ $gateway->code }}</code></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="enabledSwitch{{ $gateway->id }}" 
                                                    {{ $gateway->is_enabled ? 'checked' : '' }}
                                                    wire:click="toggleEnabled({{ $gateway->id }})">
                                                <label class="form-check-label" for="enabledSwitch{{ $gateway->id }}">
                                                    <span class="badge bg-{{ $gateway->is_enabled ? 'success' : 'danger' }}">
                                                        {{ $gateway->is_enabled ? 'Active' : 'Disabled' }}
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            @if($gateway->is_default)
                                                <span class="badge bg-primary fs-12"><i class="ri-star-fill me-1"></i> Default</span>
                                            @else
                                                <button wire:click="makeDefault({{ $gateway->id }})" class="btn btn-sm btn-soft-secondary">
                                                    Set Default
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <button wire:click="moveUp({{ $gateway->id }})" class="btn btn-sm btn-soft-info p-1"><i class="ri-arrow-up-line"></i></button>
                                                <button wire:click="moveDown({{ $gateway->id }})" class="btn btn-sm btn-soft-info p-1"><i class="ri-arrow-down-line"></i></button>
                                                <span class="ms-2">#{{ $gateway->display_order }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
