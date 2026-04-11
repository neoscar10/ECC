<div>
    <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">User Management</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Users</a></li>
                                <li class="breadcrumb-item active">Users</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <!-- Session Alerts -->
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success:</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Users Section -->
            @include('livewire.admin.users.users.partials._users_table')

    </div>

    <!-- Modals -->
    @include('livewire.admin.users.users.partials._user_modal')
    @include('livewire.admin.users.users.partials._create_user_modal')
    @include('livewire.admin.users.users.partials._create_mode_modal')
    @include('livewire.admin.users.users.partials._bulk_upload_modal')
    @include('livewire.admin.users.users.partials._tier_codes_modal')
    <livewire:admin.members.update-tier-modal />
    
    @push('scripts')
    <script>
        document.addEventListener('close-modal', event => {
            document.querySelectorAll('.modal').forEach(modalEl => {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }
            });
        });

        document.addEventListener('show-modal', event => {
            var modalId = event.detail.id;
            var modalEl = document.getElementById(modalId);
            if (modalEl) {
                var myModal = bootstrap.Modal.getInstance(modalEl);
                if (!myModal) {
                    myModal = new bootstrap.Modal(modalEl);
                }
                myModal.show();
            }
        });
        
        // SweetAlert for Delete Confirmation
        document.addEventListener('show-delete-confirmation', event => {
            Swal.fire({
                html: '<div class="mt-3"><lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon><div class="mt-4 pt-2 fs-15 mx-5"><h4>Are you Sure ?</h4><p class="text-muted mx-4 mb-0">Are you Sure You want to Delete this Record ?</p></div></div>',
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary w-xs me-2 mb-1',
                    cancelButton: 'btn btn-danger w-xs mb-1'
                },
                confirmButtonText: 'Yes, Delete It!',
                buttonsStyling: false,
                showCloseButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const detail = event.detail[0] || event.detail; 
                    if (detail.type === 'user') {
                        Livewire.dispatch('deleteUserConfirmed', { id: detail.id });
                    }
                }
            });
        });
    </script>
    @endpush
</div>
