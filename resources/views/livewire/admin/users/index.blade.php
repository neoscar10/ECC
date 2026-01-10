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

            <!-- Admin Section -->
            @include('livewire.admin.users.partials._admin_table')

            <!-- Users Section -->
            @include('livewire.admin.users.partials._users_table')

    </div>

    <!-- Modals -->
    @include('livewire.admin.users.partials._admin_modal')
    
    <!-- User View/Edit Modal (reused/refactored if needed, for now using existing userModal structure but simplified in partial if created) -->
    <!-- For simplicity, keeping the existing User Modal logic here or moving to partial if requested. 
         The prompt asked for separation. I will create a View/Edit User partial. -->
     
     @include('livewire.admin.users.partials._user_modal')

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
            var myModal = new bootstrap.Modal(document.getElementById(modalId));
            myModal.show();
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
                    // Check if event.detail exists (Livewire v3 standard)
                    const detail = event.detail[0] || event.detail; 
                    
                    if (detail.type === 'admin') {
                        Livewire.dispatch('deleteAdminConfirmed', { id: detail.id });
                    } else {
                        Livewire.dispatch('deleteUserConfirmed', { id: detail.id });
                    }
                }
            });
        });

        // SweetAlert for Password
        document.addEventListener('show-password-alert', event => {
             // Safe detail access
             const detail = event.detail[0] || event.detail; 
             
            Swal.fire({
                title: 'Admin Created!',
                html: 'The created user have been emailed their login details' ,
                icon: 'success',
                confirmButtonClass: 'btn btn-primary w-xs me-2 mt-2',
                buttonsStyling: true,
                showCloseButton: true
            });
        });
    </script>
    @endpush
</div>
