<div class="ecc-gate position-relative overflow-hidden">
    {{-- Background layers --}}
    <div class="ecc-gate-bg position-absolute top-0 start-0 w-100 h-100"></div>
    <div class="ecc-gate-overlay position-absolute top-0 start-0 w-100 h-100"></div>
    <div class="ecc-gate-noise position-absolute top-0 start-0 w-100 h-100"></div>
    <div class="ecc-gate-bottom-glow position-absolute start-50 translate-middle-x"></div>

    {{-- Centered shell (mobile card centered on desktop) --}}
    <div class="position-relative z-2 d-flex align-items-center justify-content-center min-vh-100 px-3 py-4 py-md-4 py-lg-3">
        <div class="ecc-gate-shell w-100">
            <div class="ecc-gate-inner d-flex flex-column justify-content-between">

                {{-- Top icon --}}
                <div class="pt-3 pt-md-4 d-flex justify-content-center">
                    <div class="ecc-gate-badge d-flex align-items-center justify-content-center">
                        <img src="{{ asset('ecc_logo_dark.png') }}" class="ecc-gate-diamond" alt="ECC Logo">
                    </div>
                </div>

                {{-- Content --}}
                <div class="pb-2">
                    <div class="text-center px-2">
                        <h1 class="ecc-gate-title mb-3">
                            The Pavilion <br>
                            <span class="ecc-gate-title-soft">Archives</span>
                        </h1>

                        <div class="d-flex align-items-center justify-content-center gap-2 mb-3" style="opacity:.85;">
                            <div class="ecc-gate-line"></div>
                            <div class="ecc-gate-kicker text-uppercase">
                                History. Heritage. Investment.
                            </div>
                            <div class="ecc-gate-line"></div>
                        </div>

                        <p class="ecc-gate-subtitle mx-auto mb-4">
                            Curating history for the discerning few.
                        </p>
                    </div>

                    {{-- Buttons --}}
                    <div class="d-grid gap-3 px-2">
                        <a href="{{ $applyUrl }}" class="ecc-gate-btn ecc-gate-btn-primary text-decoration-none">
                            <span class="ecc-gate-btn-text text-uppercase">Apply for Membership</span>
                            <span class="ecc-gate-sheen"></span>
                        </a>

                        <a href="{{ $loginUrl }}" class="ecc-gate-btn ecc-gate-btn-ghost text-decoration-none">
                            <span class="ecc-gate-btn-text">Existing Member Login</span>
                        </a>
                    </div>

                    {{-- Guest preview --}}
                    <div class="d-flex justify-content-center mt-4">
                        <a href="{{ $previewUrl }}" class="ecc-gate-preview text-decoration-none d-inline-flex align-items-center gap-2">
                            <span class="ecc-gate-preview-text text-uppercase">
                                Preview Collection (Guest Access)
                            </span>
                            <span class="material-symbols-outlined ecc-gate-preview-icon">arrow_forward</span>
                        </a>
                    </div>
                </div>

                {{-- bottom spacing (mobile only) --}}
                <div class="ecc-gate-spacer"></div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Page */
    .ecc-gate{
        min-height: max(884px, 100dvh);
        background: var(--ecc-bg-page);
        color: var(--ecc-text-primary);
        font-family: "Manrope", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        isolation: isolate;
    }

    /* Shell (keeps mobile look centered on desktop) */
    .ecc-gate-shell{
        max-width: 420px;
        min-height: 667px;
        height: min(92vh, 940px);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--ecc-shadow-card);
        border: 1px solid var(--ecc-border-soft);
        background: var(--ecc-bg-surface);
        position: relative;
    }
    .ecc-gate-inner{
        height: 100%;
        padding: 24px 22px 24px;
        position: relative;
        z-index: 2;
    }

    /* Background image + gradient overlay + noise */
    .ecc-gate-bg{
        z-index: 0;
        background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAB3YdoriYJ1VHRbdaAWE37ztBx2imlUiYDOfSmbcIGtPhoV4Tvewp4szHHqc8vsXZVemXbU8J1_RBj0sdXJOyLq33ICQ9EbvAYKPWRPjkYheqo1rMjiWlN5Y6yO6TAN8-xU-cSW3uC9PKZMqr6FRF9r8fe9SFXLVlBeWaNKyviR2QNdKv-vpOnqhdPm68hknupXWWdc9mB-vKYWnkJkpyXHvxOVdyH-rlg9_Z-a7F1Dic_LQWN5pjOhMTyjtSV2hvjnjfeO9Rz-nQC');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: .60;
        filter: saturate(1.05);
    }
    .ecc-gate-overlay{
        z-index: 1;
        background: linear-gradient(to bottom, rgba(var(--ecc-bg-page-rgb),.78), rgba(var(--ecc-bg-page-rgb),.90), var(--ecc-bg-page));
    }
    .ecc-gate-noise{
        z-index: 2;
        opacity: .035;
        pointer-events: none;
        background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuB-O5dwiV1hubFO9tM6qvA8hhy57MirhuUWixcZ2YXpoKokueSxD65EoQRhebgwHnW_YBhCbXDBbY6WaI6rkNJAT31mQTc-f5cfVCFv6dT9IOXF4PNECFLgA5xSnfuuHk-nxlXktZBK5S1r3yqByuYb4lszxFDttR7hE7wtvawHJo1qHplLF4x4yvnn6F__YdnbMEbr2DMrS5j36ebsd3_vaCtf9zTCg11kBLFbabgopOAfALhebhZHVg91FexwPaSPLgMElBh9hOKs');
        background-size: cover;
        background-position: center;
    }
    .ecc-gate-bottom-glow{
        z-index: 0;
        bottom: -40px;
        width: min(520px, 70vw);
        height: 180px;
        background: rgba(199, 167, 90,.10);
        filter: blur(85px);
        border-radius: 9999px;
        pointer-events: none;
    }

    /* Top diamond badge */
    .ecc-gate-badge{
        width: 56px;
        height: 56px;
        border-radius: 9999px;
        background: var(--ecc-bg-input);
        border: 1px solid var(--ecc-border);
        backdrop-filter: blur(10px);
        box-shadow: 0 0 15px var(--ecc-bg-input);
        animation: eccFadeIn .6s ease-out both;
    }
    .ecc-gate-diamond{
        width: 94px;
        height: 73px;
        object-fit: contain;
        filter: drop-shadow(0 0 10px var(--ecc-primary-soft));
    }

    /* Title + kicker */
    .ecc-gate-title{
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.10;
        font-size: 36px;
        text-shadow: 0 14px 40px rgba(0,0,0,.55);
    }
    .ecc-gate-title-soft{
        background: linear-gradient(90deg, rgba(255,255,255,1), var(--ecc-text-primary));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .ecc-gate-kicker{
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .22em;
        color: var(--ecc-text-primary);
        line-height: 1.2;
    }
    .ecc-gate-line{
        height: 1px;
        width: 16px;
        background: rgba(199, 167, 90,.55);
    }
    .ecc-gate-subtitle{
        max-width: 270px;
        color: var(--ecc-text-secondary);
        font-size: 16px;
        line-height: 1.7;
        margin-top: 6px;
    }

    /* Buttons */
    .ecc-gate-btn{
        height: 56px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        transition: all 260ms ease;
        border: 1px solid var(--ecc-border);
        backdrop-filter: blur(8px);
        -webkit-tap-highlight-color: transparent;
    }
    .ecc-gate-btn-text{
        position: relative;
        z-index: 2;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: .06em;
    }
    .ecc-gate-btn-primary{
        background: var(--ecc-primary);
        color: var(--ecc-text-primary);
    }
    .ecc-gate-btn-primary:hover{
        background: #EAC855;
        box-shadow: 0 0 20px rgba(199, 167, 90,.35);
        transform: translateY(-1px);
        color: var(--ecc-text-primary);
    }
    .ecc-gate-btn-primary:active{ transform: scale(.985); }

    .ecc-gate-btn-ghost{
        background: var(--ecc-bg-input);
        color: var(--ecc-text-primary);
    }
    .ecc-gate-btn-ghost:hover{
        background: var(--ecc-border);
        transform: translateY(-1px);
        color: var(--ecc-text-primary);
    }
    .ecc-gate-btn-ghost:active{ transform: scale(.985); }

    /* Sheen animation on primary button */
    .ecc-gate-sheen{
        position: absolute;
        inset: 0;
        transform: translateX(-120%);
        background: linear-gradient(90deg, transparent, var(--ecc-text-primary), transparent);
        transition: transform 900ms ease;
        z-index: 1;
    }
    .ecc-gate-btn-primary:hover .ecc-gate-sheen{
        transform: translateX(120%);
    }

    /* Preview link */
    .ecc-gate-preview{
        padding: 10px 14px;
        border-radius: 9999px;
        transition: all 200ms ease;
    }
    .ecc-gate-preview:hover{ background: var(--ecc-bg-input); }
    .ecc-gate-preview-text{
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .12em;
        color: var(--ecc-text-primary);
        transition: color 200ms ease;
    }
    .ecc-gate-preview-icon{
        font-size: 16px;
        color: rgba(199, 167, 90,.70);
        transition: all 200ms ease;
    }
    .ecc-gate-preview:hover .ecc-gate-preview-text{ color: var(--ecc-text-secondary); }
    .ecc-gate-preview:hover .ecc-gate-preview-icon{
        color: var(--ecc-primary);
        transform: translateX(2px);
    }

    /* Animations */
    @keyframes eccFadeIn{
        from{ opacity: 0; transform: translateY(8px); }
        to{ opacity: 1; transform: translateY(0); }
    }

    /* Keep mobile exactly as-is; fix desktop only */
    @media (min-width: 992px){
        /* Card should wrap content, not behave like a tall mobile screen */
        .ecc-gate-shell{
            max-width: 560px;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            border-radius: 22px;
        }

        /* Inner should also be content-fit */
        .ecc-gate-inner{
            height: auto !important;
            padding: 30px 42px 28px !important;
        }

        /* Remove the artificial bottom spacer on desktop */
        .ecc-gate-spacer{
            display: none !important;
        }

        /* Slightly tighten vertical rhythm so everything fits comfortably */
        .ecc-gate-title{
            font-size: 44px;
            line-height: 1.08;
            margin-bottom: 14px !important;
        }

        .ecc-gate-subtitle{
            font-size: 17px;
            margin-bottom: 18px !important;
            max-width: 360px;
        }

        .ecc-gate-btn{
            height: 58px;
            border-radius: 14px;
        }

        /* Bring preview link up a bit */
        .ecc-gate-preview{
            margin-top: 18px !important;
        }

        /* Background should support the card, not force vertical scrolling perception */
        .ecc-gate-bg{
            opacity: .45;
            filter: saturate(1.0) blur(1.2px);
            transform: scale(1.02);
        }
    }

    /* Extra-large screens: a little more breathing room horizontally, still no height forcing */
    @media (min-width: 1200px){
        .ecc-gate-shell{
            max-width: 600px;
        }
        .ecc-gate-inner{
            padding-left: 48px !important;
            padding-right: 48px !important;
        }
    }

    /* Mobile spacer default (keeps your mobile look) */
    .ecc-gate-spacer{
        padding-bottom: .5rem;
    }
</style>
@endpush
