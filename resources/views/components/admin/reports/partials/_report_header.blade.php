@props([
    'title' => 'Report',
    'backRoute' => route('admin.reports.index'),
    'breadcrumbs' => []
])

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a href="{{ $backRoute }}" class="btn btn-soft-secondary btn-sm waves-effect waves-light me-3">
                    <i class="ri-arrow-left-line align-bottom"></i> Back
                </a>
                <h4 class="mb-sm-0">{{ $title }}</h4>
            </div>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                    @foreach($breadcrumbs as $label => $link)
                        <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                            @if(!$loop->last)
                                <a href="{{ $link }}">{{ $label }}</a>
                            @else
                                {{ $label }}
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>
