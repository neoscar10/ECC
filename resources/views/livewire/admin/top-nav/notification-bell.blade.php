<div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
    <button type="button" class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
        <i class='bx bx-bell fs-22'></i>
        @if($totalCount > 0)
            <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger">{{ $totalCount }}<span class="visually-hidden">unread messages</span></span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">

        <div class="dropdown-head bg-primary bg-pattern rounded-top">
            <div class="p-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="m-0 fs-16 fw-semibold text-white"> Notifications </h5>
                    </div>
                    <div class="col-auto dropdown-tabs">
                        <span class="badge bg-light text-body fs-13"> {{ $totalCount }} New</span>
                    </div>
                </div>
            </div>

            <div class="px-2 pt-2">
                <ul class="nav nav-tabs dropdown-tabs nav-tabs-custom" data-dropdown-tabs="true" id="notificationItemsTab" role="tablist">
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link active" data-bs-toggle="tab" href="#all-noti-tab" role="tab" aria-selected="true">
                            All ({{ $totalCount }})
                        </a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" data-bs-toggle="tab" href="#enquiries-tab" role="tab" aria-selected="false">
                            Enquiries
                        </a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" data-bs-toggle="tab" href="#orders-tab" role="tab" aria-selected="false">
                            Orders
                        </a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" data-bs-toggle="tab" href="#requests-tab" role="tab" aria-selected="false">
                            Requests
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        <div class="tab-content position-relative" id="notificationItemsTabContent">
            <div class="tab-pane fade show active py-2 ps-2" id="all-noti-tab" role="tabpanel">
                <div data-simplebar style="max-height: 300px;" class="pe-2">
                    @forelse($items as $item)
                        <div class="text-reset notification-item d-block dropdown-item position-relative">
                            <div class="d-flex">
                                <div class="avatar-xs me-3 flex-shrink-0">
                                    <span class="avatar-title bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }} rounded-circle fs-16">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="{{ route($item['route']) }}" class="stretched-link">
                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">{{ $item['title'] }}</h6>
                                    </a>
                                    <div class="fs-13 text-muted">
                                        <p class="mb-1">{{ $item['description'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <div class="avatar-md mx-auto mb-3">
                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                                    <i class="ri-notification-off-line"></i>
                                </div>
                            </div>
                            <h5 class="fs-14 fw-semibold">No notifications today</h5>
                            <p class="text-muted mb-0">All clear! No pending items needing attention.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="tab-pane fade py-2 ps-2" id="enquiries-tab" role="tabpanel">
                <div data-simplebar style="max-height: 300px;" class="pe-2">
                    @forelse($grouped['enquiries'] as $item)
                        <div class="text-reset notification-item d-block dropdown-item position-relative">
                            <div class="d-flex">
                                <div class="avatar-xs me-3 flex-shrink-0">
                                    <span class="avatar-title bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }} rounded-circle fs-16">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="{{ route($item['route']) }}" class="stretched-link">
                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">{{ $item['title'] }}</h6>
                                    </a>
                                    <div class="fs-13 text-muted">
                                        <p class="mb-1">{{ $item['description'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <h5 class="fs-14 fw-semibold text-muted">No pending enquiries</h5>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="tab-pane fade py-2 ps-2" id="orders-tab" role="tabpanel">
                <div data-simplebar style="max-height: 300px;" class="pe-2">
                    @forelse($grouped['orders'] as $item)
                        <div class="text-reset notification-item d-block dropdown-item position-relative">
                            <div class="d-flex">
                                <div class="avatar-xs me-3 flex-shrink-0">
                                    <span class="avatar-title bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }} rounded-circle fs-16">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="{{ route($item['route']) }}" class="stretched-link">
                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">{{ $item['title'] }}</h6>
                                    </a>
                                    <div class="fs-13 text-muted">
                                        <p class="mb-1">{{ $item['description'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <h5 class="fs-14 fw-semibold text-muted">No pending orders</h5>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="tab-pane fade py-2 ps-2" id="requests-tab" role="tabpanel">
                <div data-simplebar style="max-height: 300px;" class="pe-2">
                    @forelse($grouped['requests'] as $item)
                        <div class="text-reset notification-item d-block dropdown-item position-relative">
                            <div class="d-flex">
                                <div class="avatar-xs me-3 flex-shrink-0">
                                    <span class="avatar-title bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }} rounded-circle fs-16">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="{{ route($item['route']) }}" class="stretched-link">
                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">{{ $item['title'] }}</h6>
                                    </a>
                                    <div class="fs-13 text-muted">
                                        <p class="mb-1">{{ $item['description'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <h5 class="fs-14 fw-semibold text-muted">No pending requests</h5>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="my-3 text-center view-all">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-soft-success waves-effect waves-light">
                    View Dashboard Summary <i class="ri-arrow-right-line align-middle"></i>
                </a>
            </div>
        </div>
    </div>
</div>

