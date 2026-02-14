<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Analytics & Reports</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Reports</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($reports as $report)
        <div class="col-xl-4 col-md-6">
            <div class="card card-height-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-{{ $report['color'] }}-subtle text-{{ $report['color'] }} rounded-circle fs-3">
                                <i class="{{ $report['icon'] }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-0 fs-16">{{ $report['title'] }}</h5>
                        </div>
                    </div>
                    <p class="text-muted mb-4">{{ $report['description'] }}</p>
                    <a href="{{ $report['route'] }}" class="btn btn-link link-{{ $report['color'] }} p-0">
                        Generate Report <i class="ri-arrow-right-line align-middle ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
