@section('title', 'Archive Products')

<div class="page-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Products</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item">The Archive</li>
                            <li class="breadcrumb-item active">Products</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card mt-5">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <div class="avatar-lg mx-auto">
                                <div class="avatar-title bg-light text-primary display-5 rounded-circle">
                                    <i class="ri-archive-line"></i>
                                </div>
                            </div>
                        </div>
                        <h3 class="mb-3">Coming Soon!</h3>
                        <p class="text-muted mb-4">The Archive Products management is under construction. Please start by configuring your <a href="{{ route('admin.archive.categories') }}" class="text-decoration-underline">Categories</a>.</p>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
