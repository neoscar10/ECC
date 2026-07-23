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
        <div class="cms-gate-overlay w-100 py-4">
            <div class="cms-gate-card p-4 p-md-5 rounded-4 shadow-lg border ecc-text-primary w-100 text-center" style="background: var(--ecc-bg-surface-2); border-color: var(--ecc-border) !important; box-shadow: 0 10px 30px var(--ecc-shadow) !important;">
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
                    <span class="material-symbols-outlined fs-1" style="color: var(--ecc-primary);">{{ $iconName }}</span>
                </div>

                {{-- Message --}}
                <h4 class="fw-bold mb-2" style="color: var(--ecc-primary);">{{ $message['title'] ?? 'Access Restricted' }}</h4>
                <p class="small mb-4 mx-auto" style="max-width: 500px; color: var(--ecc-text-secondary);">{{ $message['body'] ?? 'This content is exclusive to specific membership tiers.' }}</p>

                {{-- Action --}}
                <div class="d-flex justify-content-center">
                    @foreach($actions as $action)
                        @if($action['type'] === 'upgrade_membership' || $action['type'] === 'subscribe')
                            <a href="{{ $action['deeplink'] ?? '/membership/tiers' }}" class="btn fw-bold rounded-pill py-2 px-5" style="background: linear-gradient(180deg, var(--ecc-primary), var(--ecc-gold-500)); color: #16110a; border: none; box-shadow: 0 4px 15px rgba(199, 167, 90, 0.2);">
                                {{ $action['label'] ?? 'Upgrade Now' }}
                            </a>
                        @endif
                    @endforeach
                </div>
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
        border: 1px solid var(--ecc-border) !important;
        box-shadow: 0 15px 35px var(--ecc-shadow) !important;
    }
</style>
