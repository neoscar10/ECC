@section('title', 'Archive Categories')

<div>
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Categories</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item">The Archive</li>
                            <li class="breadcrumb-item active">Categories</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Card -->
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Category List</h5>
                    <div class="flex-shrink-0">
                        <button type="button" wire:click="create" class="btn btn-success add-btn">
                            <i class="ri-add-line align-bottom me-1"></i> Create Category
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-body border border-dashed border-end-0 border-start-0">
                @include('livewire.admin.archive.categories.partials._filters')
            </div>

            <!-- Table -->
            <div class="card-body">
                @include('livewire.admin.archive.categories.partials._table')
            </div>
        </div>

    </div>

    <!-- Modal -->
    @include('livewire.admin.archive.categories.partials._modal')

</div>

@script
<script>
    $wire.on('show-modal', () => {
        var modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        modal.show();
    });

    $wire.on('hide-modal', () => {
        var modalEl = document.getElementById('categoryModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });
</script>
@endscript
