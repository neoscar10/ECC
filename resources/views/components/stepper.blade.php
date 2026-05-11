@props(['step' => 1, 'totalSteps' => 3, 'steps' => []])

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-body p-0">
        <div class="d-flex align-items-center text-center">
            @foreach($steps as $index => $label)
                @php 
                    $stepNum = $index + 1;
                    $isActive = $step >= $stepNum;
                    $isCurrent = $step == $stepNum;
                @endphp
                <div class="flex-grow-1 p-3 position-relative {{ $isActive ? 'bg-primary text-white' : 'bg-light text-muted' }}" 
                     style="{{ $index > 0 ? 'border-left: 1px solid rgba(255,255,255,0.15)' : '' }}">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <div class="fs-18 fw-bold mb-0" style="line-height: 1;">{{ str_pad($stepNum, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="fs-10 text-uppercase fw-bold mt-1" style="letter-spacing: 0.5px; opacity: 0.8;">{{ $label }}</div>
                    </div>
                    @if($isCurrent)
                        <div class="position-absolute bottom-0 start-0 end-0 bg-white" style="height: 3px; opacity: 0.5;"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
