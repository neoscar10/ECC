<div class="card h-100">
    <div class="card-header">
        <h5 class="card-title mb-0">Customer Details</h5>
    </div>
    <div class="card-body d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center mb-3">
            <div class="flex-shrink-0">
                <div class="avatar-sm">
                    <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-20">
                        <i class="ri-user-line"></i>
                    </div>
                </div>
            </div>
            <div class="flex-grow-1 ms-3">
                <h5 class="fs-14 mb-1">{{ $this->order->user->name ?? 'Guest User' }}</h5>
                <p class="text-muted mb-0">{{ $this->order->user->email ?? 'No Email' }}</p>
                @if($this->order->user)
                    <p class="text-muted mb-0 small">User ID: #{{ $this->order->user->id }}</p>
                @endif
            </div>
        </div>
        @if($this->order->user)
            <div class="text-center">
                <a href="{{ route('admin.users.admin', ['search' => $this->order->user->email]) }}" class="btn btn-sm btn-soft-primary w-100">View Profile</a>
            </div>
        @endif
    </div>
</div>
