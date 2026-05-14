<div>
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Order Details</h4>
                <div class="page-title-right">
                    <a href="{{ route('admin.shop.orders') }}" class="btn btn-sm btn-soft-secondary me-2">
                        <i class="ri-arrow-left-line align-middle me-1"></i> Back to Orders
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert --}}
    @include('livewire.admin.partials._alerts')

    {{-- Order Header --}}
    @include('livewire.admin.shop.orders.partials._header')

    <div class="row">
        {{-- Left Column (Main) --}}
        <div class="col-xl-9">
            @include('livewire.admin.shop.orders.partials._items-table')
            @include('livewire.admin.shop.orders.partials._timeline')
        </div>

        {{-- Right Column (Sidebar) --}}
        <div class="col-xl-3">
            @include('livewire.admin.shop.orders.partials._sidebar-status-manager')
            @include('livewire.admin.shop.orders.partials._sidebar-fulfillment')
            @include('livewire.admin.shop.orders.partials._sidebar-customer')
            @include('livewire.admin.shop.orders.partials._sidebar-shipping')
        </div>
    </div>

    {{-- Modals --}}
    @include('livewire.admin.shop.orders.partials._cancel-modal')
    @include('livewire.admin.shop.orders.partials._initiate-shipment-modal')
</div>
