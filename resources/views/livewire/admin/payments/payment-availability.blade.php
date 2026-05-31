<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Purpose-Level Gateway Control</h4>
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
        <div class="col-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Payment Purpose Settings Matrix</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>Purpose</th>
                                    @foreach($gateways as $gateway)
                                        <th class="text-center">{{ $gateway->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purposesList as $purposeCode => $purposeLabel)
                                    <tr>
                                        <td><strong>{{ $purposeLabel }}</strong><br><small class="text-muted">Code: <code>{{ $purposeCode }}</code></small></td>
                                        @foreach($gateways as $gateway)
                                            <td class="text-center">
                                                @php
                                                    $mapping = $gateway->purposes->firstWhere('purpose', $purposeCode);
                                                    $enabled = $mapping ? $mapping->is_enabled : false;
                                                @endphp
                                                <button wire:click="togglePurpose({{ $gateway->id }}, '{{ $purposeCode }}')" 
                                                    class="btn btn-icon fs-20 {{ $enabled ? 'text-success' : 'text-danger' }}">
                                                    @if($enabled)
                                                        <i class="ri-checkbox-circle-fill"></i>
                                                    @else
                                                        <i class="ri-close-circle-fill"></i>
                                                    @endif
                                                </button>
                                            </td>
                                        @endforeach
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
