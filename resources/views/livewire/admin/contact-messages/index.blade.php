<div>
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Contact Messages</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Contact Messages</li>
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

        <div class="card" id="messagesList">
            <div class="card-header border-0">
                <div class="row g-4 align-items-center">
                    <div class="col-sm-3">
                        <div class="search-box">
                            <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search messages...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div>
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap" id="messagesTable">
                            <thead class="text-muted">
                                <tr>
                                    <th>Sender</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($messages as $msg)
                                    <tr class="{{ $msg->is_read ? '' : 'bg-light' }}">
                                        <td>
                                            <h5 class="fs-14 m-0">{{ $msg->name }}</h5>
                                        </td>
                                        <td>{{ $msg->email }}</td>
                                        <td>{{ Str::limit($msg->subject ?? 'No Subject', 25) }}</td>
                                        <td>
                                            <div class="text-wrap" style="max-width: 300px; max-height: 50px; overflow: hidden; text-overflow: ellipsis;">
                                                {{ $msg->message }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($msg->is_read)
                                                <span class="badge bg-success-subtle text-success">Read</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Unread</span>
                                            @endif
                                        </td>
                                        <td>{{ $msg->created_at->format('d M, Y h:i A') }}</td>
                                        <td>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-2-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if(!$msg->is_read)
                                                        <li><a class="dropdown-item" wire:click="markAsRead({{ $msg->id }})"><i class="ri-check-line align-bottom me-2 text-muted"></i> Mark as Read</a></li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item text-danger" wire:click="delete({{ $msg->id }})">
                                                            <i class="ri-delete-bin-fill align-bottom me-2 text-danger"></i> Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h5 class="mt-2">No Messages Found</h5>
                                            <p class="text-muted mb-0">No contact messages received yet.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
