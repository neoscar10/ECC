@props([
    'title',
    'value',
    'icon' => 'ri-shopping-cart-2-line',
    'color' => 'primary',
    'trend' => null,
    'trendColor' => 'success',
    'link' => '#',
    'prefix' => '',
])

<div class="card card-animate">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ $title }}</p>
            </div>
            @if($trend)
            <div class="flex-shrink-0">
                <h5 class="text-{{ $trendColor }} fs-14 mb-0">
                    <i class="ri-arrow-right-up-line fs-13 align-middle"></i> {{ $trend }}
                </h5>
            </div>
            @endif
        </div>
        <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
                <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $prefix }}{{ is_numeric($value) ? number_format($value) : $value }}</h4>
                <a href="{{ $link }}" class="text-decoration-underline text-muted">View details</a>
            </div>
            <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-{{ $color }}-subtle rounded fs-3">
                    <i class="{{ $icon }} text-{{ $color }}"></i>
                </span>
            </div>
        </div>
    </div><!-- end card body -->
</div><!-- end card -->
