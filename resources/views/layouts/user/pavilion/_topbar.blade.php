<nav class="pavilion-topbar">
    <div class="pavilion-container px-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span class="material-symbols-outlined text-white" style="font-size: 28px;">stadium</span>
            <span class="ecc-outfit fw-bold text-white fs-5" style="letter-spacing: 0.5px;">The Pavilion</span>
        </div>
        
        @if(isset($joinClubUrl))
            <a href="{{ $joinClubUrl }}" class="btn btn-ecc-primary btn-sm px-3">Join Club</a>
        @endif
    </div>
</nav>
