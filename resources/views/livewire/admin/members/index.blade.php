<div>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Members List</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Membership</a></li>
                        <li class="breadcrumb-item active">Members</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-12">
            
            @include('livewire.admin.members.partials._alerts')

            <div class="card" id="membersList">
                @include('livewire.admin.members.partials._filters')
                
                @include('livewire.admin.members.partials._table')
            </div>
        </div>
    </div>

    @include('livewire.admin.members.partials._view-modal')

    @include('livewire.admin.members.partials._action-modals')

    @include('livewire.admin.members.partials._scripts')
</div>
