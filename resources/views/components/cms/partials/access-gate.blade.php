@props(['access'])

@php
    $state = $access['state'] ?? 'public'; // public (clear), teaser (blur), locked (blocked)
    $isAllowed = $access['is_allowed'] ?? true;
    $showTeaser = $access['show_teaser'] ?? false;
    $message = $access['message'] ?? [];
    $actions = $access['actions'] ?? [];
    
    // Determine CSS class for inner content
    $contentClass = match($state) {
        'teaser' => 'cms-content-blurred',
        'locked' => 'cms-content-hidden',
        default => ''
    };
@endphp

<div class="cms-access-gate position-relative overflow-hidden @if(!$isAllowed) cms-gate-active @endif">
    {{-- Main Content Slot --}}
    <div class="cms-gate-inner {{ $contentClass }}">
        {{ $slot }}
    </div>

    {{-- Locked Overlay / Teaser UI --}}
    @if(!$isAllowed)
        <div class="cms-gate-overlay d-flex flex-column align-items-center justify-content-center text-center p-4">
            <div class="cms-gate-card p-4 rounded-4 shadow-lg border border-white-5 shadow ecc-text-primary" style="background: rgba(45, 40, 26, 0.95); backdrop-filter: blur(5px); max-width: 320px;">
                {{-- Icon --}}
                {{-- Icon --}}
                <div class="mb-3">
                    @php
                        $icon = $message['icon'] ?? 'lock';
                        $iconName = match($icon) {
                            'lock' => 'lock',
                            'diamond' => 'workspace_premium',
                            'time-lock' => 'schedule',
                            default => 'lock_person'
                        };
                    @endphp
                    <span class="material-symbols-outlined fs-1" style="color: var(--ecc-gold, var(--ecc-primary));">{{ $iconName }}</span>
                </div>

                {{-- Message --}}
                <h5 class="fw-bold mb-2" style="color: var(--ecc-gold, var(--ecc-primary));">{{ $message['title'] ?? 'Access Restricted' }}</h5>
                <p class="small ecc-text-primary mb-4" style="opacity: 0.8;">{{ $message['body'] ?? 'This content is exclusive to specific membership tiers.' }}</p>

                {{-- Action --}}
                @foreach($actions as $action)
                    @if($action['type'] === 'upgrade_membership' || $action['type'] === 'subscribe')
                        <a href="{{ $action['deeplink'] ?? '/membership/tiers' }}" class="btn w-100 fw-bold rounded-pill py-2" style="background: linear-gradient(135deg, var(--ecc-primary) 0%, #B8961E 100%); color: var(--ecc-text-primary); border: none; box-shadow: 0 4px 15px rgba(199, 167, 90,0.2);">
                            {{ $action['label'] ?? 'Upgrade Now' }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>

<style>
    .cms-gate-active .cms-content-blurred {
        filter: blur(4px);
        user-select: none;
        pointer-events: none;
        opacity: 0.6;
    }
    .cms-gate-active .cms-content-hidden {
        display: none;
    }
    .cms-gate-overlay {
        position: relative;
        z-index: 10;
        min-height: 200px;
    }
    .cms-gate-card {
        border: 1px solid rgba(199, 167, 90,0.2) !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.5) !important;
    }
</style>
