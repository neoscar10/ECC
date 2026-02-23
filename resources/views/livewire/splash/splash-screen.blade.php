@php
    // Duration in seconds for CSS animation
    $durationSec = max(0.8, ($delayMs ?? 1800) / 1000);
@endphp

<div class="position-relative overflow-hidden" style="min-height: max(884px, 100dvh); height: 100dvh; width: 100%;">
    {{-- Background layers --}}
    <div class="position-fixed top-0 start-0 w-100 h-100" style="z-index:0; background: var(--ecc-bg); pointer-events:none;">
        {{-- Soft gold glow --}}
        <div class="position-absolute"
             style="
                top: 33%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 600px;
                height: 600px;
                background: rgba(212,175,55,.05);
                border-radius: 9999px;
                filter: blur(120px);
                animation: eccSubtlePulse 4s ease-in-out infinite;
             "></div>

        {{-- Textured overlay (same spirit as stitched) --}}
        <div class="position-absolute top-0 start-0 w-100 h-100"
             style="
                opacity: .20;
                mix-blend-mode: overlay;
                background-size: cover;
                background-position: center;
                background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAYFyWwA4wJDkBYEx0LZPPZizi4Vcs8axAGO4ziGEpJmwCtWXgkjzs5U-dMXwxfeIGLKJ4oEeWbJgaDqkGWa8kJtS9Eai0VO7T3TAfkqRK5Tbn9jytQBooXQtcQ-WoBN4vmP5N7ccLz_s1tQKXI5HWCIhMwF05rl_jbGxXNjxhQv8lnSEnpYz-2Z9RZqEpTp9F7M81pAC7zko79hyV3q6OEC68wwXSlLBgnqDfUN18lXW13YPAl-mkWiRLTaYw2eUBCuN4mipJaL5Z3');
             "></div>

        {{-- Dark radial vignette --}}
        <div class="position-absolute top-0 start-0 w-100 h-100"
             style="background: radial-gradient(circle at center, rgba(0,0,0,0) 0%, rgba(5,5,5,.90) 70%);"></div>
    </div>

    {{-- Foreground --}}
    <div class="position-relative d-flex flex-column justify-content-between h-100 px-4 px-md-5 py-4 py-md-5" style="z-index:1;">
        <div class="flex-grow-1"></div>

        {{-- Center logo area --}}
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center">
            <div class="text-center position-relative select-none" style="cursor: default;">
                {{-- Diamond icon --}}
                <div class="mb-4">
                    <span class="material-symbols-outlined"
                          style="
                            font-size: 40px;
                            color: var(--ecc-primary);
                            opacity: .85;
                            filter: drop-shadow(0 0 8px rgba(212,175,55,.40));
                          ">diamond</span>
                </div>

                {{-- ECC + shadow ECC --}}
                <div class="position-relative d-inline-block">
                    <div class="fw-bold text-uppercase"
                         style="
                            font-size: clamp(56px, 10vw, 92px);
                            letter-spacing: .20em;
                            color: var(--ecc-primary);
                            line-height: 1;
                            padding-left: .20em; /* optical alignment like stitched */
                         ">
                        ECC
                    </div>

                    <div class="fw-bold text-uppercase position-absolute top-0 start-0"
                         style="
                            transform: translate(.20em, 18px);
                            font-size: clamp(56px, 10vw, 92px);
                            letter-spacing: .20em;
                            color: rgba(212,175,55,.10);
                            filter: blur(2px);
                            line-height: 1;
                            pointer-events: none;
                         ">
                        ECC
                    </div>
                </div>

                {{-- Divider line --}}
                <div class="mx-auto my-4"
                     style="height:1px; width:64px; background: linear-gradient(90deg, transparent, rgba(212,175,55,.50), transparent);"></div>

                {{-- Subtitles --}}
                <div class="d-flex flex-column align-items-center gap-1">
                    <div class="text-uppercase fw-semibold"
                         style="
                            color: rgba(212,175,55,.90);
                            font-size: clamp(11px, 1.6vw, 14px);
                            letter-spacing: .40em;
                         ">
                        Private Museum
                    </div>
                    <div class="text-uppercase fw-medium"
                         style="
                            color: rgba(255,255,255,.32);
                            font-size: 10px;
                            letter-spacing: .30em;
                         ">
                        Est. 2024 • Investment Lounge
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom loader --}}
        <div class="flex-grow-1 d-flex flex-column justify-content-end align-items-center pb-4 gap-3 position-relative">
            <div class="w-100" style="max-width: 140px; opacity: .85;">
                <div class="progress" style="height:2px; background: rgba(255,255,255,.10);">
                    <div class="progress-bar"
                         role="progressbar"
                         style="
                            background: var(--ecc-primary);
                            width: 0%;
                            animation: eccLoadingBar {{ $durationSec }}s ease-in-out forwards;
                            box-shadow: 0 0 10px rgba(212,175,55,.80);
                         "></div>
                </div>

                <div class="d-flex justify-content-between align-items-center px-1 mt-2">
                    <span class="text-uppercase"
                          style="font-size: 9px; letter-spacing: .20em; color: rgba(255,255,255,.30);">
                        Loading
                    </span>
                </div>
            </div>

            {{-- Version bottom-right --}}
            <div class="position-absolute bottom-0 end-0 me-3 mb-2">
                <span style="font-size: 10px; color: rgba(255,255,255,.10); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">
                    {{ $version }}
                </span>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            @keyframes eccSubtlePulse{
                0%, 100% { opacity: .50; transform: translate(-50%, -50%) scale(1); }
                50%      { opacity: .80; transform: translate(-50%, -50%) scale(1.05); }
            }
            @keyframes eccLoadingBar{
                0%   { width: 0%; }
                100% { width: 35%; } /* matches stitched feel; change to 100% if you prefer full fill */
            }
        </style>
    @endpush

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const key = 'ecc_splash_seen';
            const to = @js($redirectTo ?? url('/home'));
            const delay = @js($delayMs ?? 1800);

            try {
                const seen = localStorage.getItem(key) === '1';
                if (seen) {
                    window.location.replace(to);
                    return;
                }
                // mark as seen so internal nav to "/" won't show splash again
                localStorage.setItem(key, '1');
            } catch (e) {
                // if storage blocked, still allow splash to proceed
            }

            setTimeout(() => window.location.replace(to), delay);
        });
        </script>
    @endpush
</div>
