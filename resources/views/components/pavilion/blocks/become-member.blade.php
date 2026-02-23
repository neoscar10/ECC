@props(['block'])

<div class="pavilion-card border-0 bg-primary bg-opacity-10 py-5 px-4 text-center">
    <div class="mb-4">
        <span class="material-symbols-outlined text-warning" style="font-size: 64px;">workspace_premium</span>
    </div>
    
    <h3 class="ecc-outfit fw-bold text-white mb-2 fs-3">{{ $block['title'] ?? 'The Ultimate Experience' }}</h3>
    <p class="text-white-50 mb-4 mx-auto" style="max-width: 500px;">
        {{ $block['subtitle'] ?? 'Apply for membership today and unlock exclusive access to the club artifacts, stories, and investment vault.' }}
    </p>
    
    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
        <a href="#" class="btn btn-ecc-primary px-5 py-2">
            Apply for Membership
        </a>
    </div>
</div>
