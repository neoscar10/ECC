<!-- Privileges -->
<section>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-2 mb-lg-3">
        <h2 class="ecc-section-title mb-0">
            <span class="material-symbols-outlined me-2">stars</span>
            Your Privileges
        </h2>

        @if($benefitsUrl)
            <a href="{{ $benefitsUrl }}" class="ecc-inline-link">
                View All Benefits
            </a>
        @endif
    </div>

    <div class="row g-3 g-lg-4">
        @foreach($priv as $p)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="ecc-privilege-card h-100">
                    <div class="ecc-privilege-icon">
                        <span class="material-symbols-outlined">{{ $p['icon'] ?? 'stars' }}</span>
                    </div>

                    <h3 class="ecc-privilege-title">{{ $p['title'] }}</h3>

                    @if(!empty($p['subtitle']))
                        <p class="ecc-privilege-text mb-0">{{ $p['subtitle'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
