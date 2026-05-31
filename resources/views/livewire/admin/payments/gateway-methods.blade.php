<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Gateway Payment Methods</h4>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Gateway Selector Sidebar Card -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Select Gateway</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($gateways as $gateway)
                            <button type="button" 
                                wire:click="$set('selectedGatewayId', {{ $gateway->id }})" 
                                class="list-group-item list-group-item-action {{ $selectedGatewayId == $gateway->id ? 'active' : '' }}">
                                {{ $gateway->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Methods Toggle Matrix Card -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Payment Methods for {{ $selectedGateway ? $selectedGateway->name : 'Gateway' }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($selectedGateway)
                        <div class="table-responsive">
                            <table class="table table-centered align-middle table-nowrap mb-0">
                                <thead class="text-muted table-light">
                                    <tr>
                                        <th>Method</th>
                                        <th class="text-center">Allowed Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($methodsList as $methodCode => $methodLabel)
                                        <tr>
                                            <td>
                                                <strong>{{ $methodLabel }}</strong><br>
                                                <small class="text-muted">Code: <code>{{ $methodCode }}</code></small>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $mapping = $selectedGateway->methods->firstWhere('method', $methodCode);
                                                    $enabled = $mapping ? $mapping->is_enabled : false;
                                                @endphp
                                                <button wire:click="toggleMethod('{{ $methodCode }}')" 
                                                    class="btn btn-icon fs-20 {{ $enabled ? 'text-success' : 'text-danger' }}">
                                                    @if($enabled)
                                                        <i class="ri-checkbox-circle-fill"></i>
                                                    @else
                                                        <i class="ri-close-circle-fill"></i>
                                                    @endif
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No gateway selected.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
