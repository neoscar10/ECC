<div class="ecc-welcome-page d-flex flex-column min-vh-100 overflow-hidden position-relative">
    {{-- Top emblem --}}
    <header class="position-relative z-2 pt-4 pt-md-4 pb-2">
        {{-- Mobile icon stays exactly like before --}}
        <div class="d-flex justify-content-center d-md-none">
            <img src="{{ asset('ecc_logo_dark.png') }}" class="ecc-icon" alt="ECC Logo">
        </div>
    </header>

    {{-- Center content --}}
    <main class="flex-grow-1 d-flex align-items-center justify-content-center position-relative px-3 px-sm-4 px-lg-5">
        {{-- soft glow --}}
        <div class="ecc-glow position-absolute top-50 start-50 translate-middle"></div>

        <div class="ecc-wrap w-100 position-relative z-2">
            <div class="row g-4 align-items-center justify-content-center">
                {{-- Hero --}}
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="ecc-hero-card ecc-animate-up mx-auto">
                        <div class="ecc-hero-image" aria-label="Vintage red cricket ball"></div>
                        <div class="ecc-hero-overlay"></div>

                        {{-- Members only badge --}}
                        <div class="position-absolute top-0 end-0 p-3 p-md-4" style="z-index: 2;">
                            <div class="ecc-pill d-inline-flex align-items-center gap-2">
                                <span class="ecc-dot"></span>
                                <span class="text-uppercase ecc-pill-text">Members Only</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Text --}}
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="text-center text-md-start ecc-animate-up-delay">
                        {{-- Desktop emblem (icon moves closer to headline) --}}
                        <div class="d-none d-md-flex align-items-center mb-3">
                            <img src="{{ asset('ecc_logo_dark.png') }}" class="ecc-icon ecc-icon--desktop" alt="ECC Logo">
                        </div>

                        <h1 class="mb-3 mb-md-4 ecc-title">
                            <span class="d-block fw-light ecc-title-top">Welcome to</span>
                            <span class="d-block fw-bold ecc-gradient-text ecc-title-main">Executive Club Cricket</span>
                        </h1>

                        <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-3 gap-md-3 mb-3 mb-md-4">
                            <div class="ecc-divider"></div>
                            <p class="mb-0 ecc-subtitle">
                                A Private Collection of Cricket Legacy.
                            </p>
                        </div>

                        {{-- CTA inline on desktop for better balance --}}
                        <div class="d-none d-md-flex align-items-center gap-3 mt-3">
                            <a href="{{ $enterUrl }}" class="ecc-enter-btn ecc-enter-btn-lg d-inline-flex align-items-center justify-content-center gap-2 text-decoration-none">
                                <span class="text-uppercase ecc-enter-text">Enter the Club</span>
                                <span class="material-symbols-outlined ecc-arrow">arrow_forward</span>
                            </a>

                            <div class="text-uppercase ecc-established">
                                Established 2024
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Bottom CTA (mobile) --}}
    <footer class="position-relative z-2 px-4 px-md-5 pb-4 pt-3 ecc-bottom-fade d-md-none">
        <div class="d-flex flex-column align-items-center">
            <a href="{{ $enterUrl }}" class="ecc-enter-btn d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                <span class="text-uppercase ecc-enter-text">Enter the Club</span>
                <span class="material-symbols-outlined ecc-arrow">arrow_forward</span>
            </a>

            <div class="mt-3 text-uppercase ecc-established">
                Established 2024
            </div>
        </div>
    </footer>
</div>

@push('styles')
<style>
    /* Base theme */
    .ecc-welcome-page{
        background: #050505;
        color: #fff;
        font-family: "Manrope", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        isolation: isolate;
    }

    /* Contained width that scales nicely on desktop */
    .ecc-wrap{
        max-width: 1100px;
    }

    /* Top icon */
    .ecc-icon{
        width: 108px;
        height: 84px;
        object-fit: contain;
        filter: drop-shadow(0 0 10px rgba(199, 167, 90,.25));
    }

    /* Desktop-only placement: same icon, slightly refined sizing */
    .ecc-icon--desktop{
        width: 97px;
        height: 76px;
        opacity: .95;
    }
    @media (min-width: 992px){
        .ecc-icon--desktop{
            width: 104px;
            height: 81px;
        }
    }

    /* Background glow */
    .ecc-glow{
        width: min(1200px, 120vw);
        height: min(700px, 70vh);
        background: rgba(199, 167, 90,.10);
        filter: blur(120px);
        border-radius: 9999px;
        pointer-events: none;
        z-index: 0;
    }

    /* Hero card */
    .ecc-hero-card{
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        background: #0a0a0a;
        border: 1px solid rgba(255,255,255,.10);
        box-shadow: 0 24px 90px rgba(0,0,0,.60);
        width: 100%;
        max-width: 420px;
        /* responsive height with safe floor/ceiling */
        height: clamp(360px, 55vh, 520px);
    }

    @media (min-width: 992px){
        .ecc-hero-card{
            max-width: 460px;
            height: clamp(420px, 58vh, 560px);
        }
    }

    .ecc-hero-image{
        position: absolute; inset: 0;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        transform: scale(1.0);
        transition: transform 900ms ease;
        background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCuwEV-kkpE3zt6NNgjrfo6-9QfXuon5u71I73aHG1S-UnhppmqfDXnWPMc_3d3E5MgK0Mfsb5SNcr-1Pw1otqt-loxIJ3_FPY1xwvSCK4XsCqhJjcRMddl4tvbJbe8WvbcmMAE88cC3foC95Oj05S4yDqKqUiAp2kYBmI0PCOSGLagB_rc95Ieiojzb9jnRUCCPd4nYru4abeOkRzq7-c9QprJrMjfsrZ2DJVx1rnnH9P3Zb1a4UGy8-rt1C5jARhSjjt5h7ZljLI3");
    }
    .ecc-hero-card:hover .ecc-hero-image{ transform: scale(1.06); }

    .ecc-hero-overlay{
        position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(5,5,5,.96), rgba(5,5,5,0) 55%);
        opacity: .90;
        z-index: 1;
        pointer-events:none;
    }

    /* Members pill */
    .ecc-pill{
        background: rgba(0,0,0,.40);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 9999px;
        padding: 8px 14px;
        backdrop-filter: blur(10px);
        color: rgba(255,255,255,.92);
        box-shadow: 0 10px 30px rgba(0,0,0,.35);
    }
    .ecc-pill-text{
        letter-spacing:.18em;
        font-size: 10px;
        font-weight: 800;
    }
    .ecc-dot{
        width: 7px; height: 7px;
        border-radius: 50%;
        background: var(--ecc-primary);
        box-shadow: 0 0 14px rgba(199, 167, 90,.75);
        animation: eccPulse 1.4s ease-in-out infinite;
    }

    /* Typography */
    .ecc-title{
        line-height: 1.10;
        margin: 0;
    }
    .ecc-title-top{
        font-size: clamp(28px, 2.8vw, 44px);
        opacity: .95;
    }
    .ecc-title-main{
        font-size: clamp(34px, 3.2vw, 52px);
        letter-spacing: .01em;
    }

    .ecc-gradient-text{
        background: var(--ecc-primary-gradient);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .ecc-divider{
        height: 1px;
        width: 56px;
        background: rgba(199, 167, 90,.60);
        margin-top: .25rem;
    }
    @media (min-width: 768px){
        .ecc-divider{ width: 64px; }
    }

    .ecc-subtitle{
        color: rgba(255,255,255,.58);
        font-size: 16px;
        line-height: 1.65;
        max-width: 420px;
    }

    /* CTA */
    .ecc-bottom-fade{
        background: linear-gradient(to top, rgba(5,5,5,1), rgba(5,5,5,1), rgba(5,5,5,0));
    }

    .ecc-enter-btn{
        width: 100%;
        max-width: 360px;
        height: 56px;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,.12);
        background: rgba(255,255,255,.03);
        color: #fff;
        font-weight: 800;
        transition: all 260ms ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 14px 44px rgba(0,0,0,.40);
    }
    .ecc-enter-btn-lg{
        max-width: 320px;
        height: 54px;
    }

    .ecc-enter-btn::before{
        content:"";
        position:absolute; inset:0;
        background: rgba(199, 167, 90,.08);
        opacity: 0;
        transition: opacity 260ms ease;
    }
    .ecc-enter-btn:hover{
        border-color: rgba(199, 167, 90,.55);
        color: var(--ecc-primary);
        transform: translateY(-1px);
    }
    .ecc-enter-btn:hover::before{ opacity: 1; }

    .ecc-enter-text{
        letter-spacing:.10em;
        font-size: 13px;
    }

    .ecc-arrow{
        font-size: 20px;
        color: rgba(255,255,255,.55);
        transition: all 260ms ease;
        position: relative;
        z-index: 1;
    }
    .ecc-enter-btn span{ position: relative; z-index: 1; }
    .ecc-enter-btn:hover .ecc-arrow{
        color: var(--ecc-primary);
        transform: translateX(4px);
    }

    .ecc-established{
        font-size: 11px;
        letter-spacing: .22em;
        color: rgba(255,255,255,.18);
        white-space: nowrap;
    }

    /* Animations */
    .ecc-animate-up{
        opacity: 0;
        transform: translateY(10px);
        animation: eccFadeUp .8s ease-out forwards;
    }
    .ecc-animate-up-delay{
        opacity: 0;
        transform: translateY(10px);
        animation: eccFadeUp .8s ease-out .2s forwards;
    }
    @keyframes eccFadeUp{
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes eccPulse{
        0%,100%{ opacity: .55; transform: scale(1); }
        50%{ opacity: 1; transform: scale(1.25); }
    }
</style>
@endpush
