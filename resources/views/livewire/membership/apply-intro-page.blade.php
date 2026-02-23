<div class="ecc-apply-intro min-vh-100 d-flex flex-column position-relative overflow-hidden">
    {{-- dotted gold pattern --}}
    <div class="ecc-dot-grid position-absolute top-0 start-0 w-100 h-100" aria-hidden="true"></div>

    {{-- top label --}}
    <div class="container-fluid px-4 pt-3 pb-2 position-relative z-2">
        <div class="d-flex align-items-center justify-content-center">
            <div class="ecc-top-title text-uppercase text-center">
                Executive Cricket Club
            </div>
        </div>
    </div>

    {{-- content --}}
    <div class="container-fluid flex-grow-1 d-flex align-items-center justify-content-center px-3 px-md-4 pb-5 position-relative z-2">
        <div class="w-100 ecc-max ecc-desktop-grid">
            <div class="ecc-col-left">
                {{-- hero image card (unchanged on mobile) --}}
                <div class="ecc-hero mb-4" role="img" aria-label="Red cricket ball">
                    <div class="ecc-hero-img"></div>
                    <div class="ecc-hero-overlay"></div>
                </div>
            </div>

            <div class="ecc-col-right">
                {{-- pill --}}
                <div class="text-center text-lg-start mb-3">
                    <span class="ecc-pill d-inline-flex align-items-center justify-content-center">
                        Membership Application
                    </span>
                </div>

                {{-- title --}}
                <div class="text-center text-lg-start mb-3">
                    <h1 class="ecc-h1 mb-0">
                        <span class="ecc-italic">Apply for</span>
                        <span class="ecc-gold">Membership</span>
                    </h1>
                </div>

                {{-- copy --}}
                <div class="text-center text-lg-start ecc-copy mx-auto mx-lg-0 mb-4">
                    <p class="ecc-p mb-3">
                        Executive Cricket Club is a private members-only platform. Applications are reviewed to preserve exclusivity.
                    </p>
                    <p class="ecc-p2 mb-0">
                        <em>Please allow 10-15 minutes to complete your dossier.</em>
                    </p>
                </div>

                {{-- actions --}}
                <div class="d-flex flex-column align-items-center align-items-lg-start gap-3">
                    <a href="{{ $beginUrl }}"
                       class="ecc-cta btn w-100 d-flex align-items-center justify-content-center gap-2">
                        <span class="ecc-cta-text">Begin Application</span>
                        <span class="material-symbols-outlined ecc-cta-icon">arrow_forward</span>
                    </a>

                    <a href="{{ $tiersUrl }}" class="ecc-tier-link">
                        Learn About Membership Tiers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="py-2"></div>
</div>

@push('styles')
<style>
    .ecc-apply-intro{
        background: #050505;
        color: #ffffff;
        font-family: "Newsreader", ui-serif, Georgia, "Times New Roman", serif;
    }
    .ecc-max{ max-width: 460px; }

    .ecc-dot-grid{
        opacity: .06;
        background-image: radial-gradient(#ecc813 1px, transparent 1px);
        background-size: 32px 32px;
        pointer-events: none;
    }

    .ecc-top-title{
        color: rgba(242,185,13,.85);
        font-weight: 800;
        letter-spacing: .25em;
        font-size: 12px;
    }

    .ecc-hero{
        position: relative;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(242,185,13,.20);
        box-shadow: 0 28px 90px rgba(0,0,0,.55);
        aspect-ratio: 4 / 3;
        background: rgba(255,255,255,.02);
    }
    .ecc-hero-img{
        position:absolute; inset:0;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuB3CdAafWiRIGR84XGpSXcoJd7YD7NhJfpcTGsxOOUg7ZQ1bvdCXX3SocFXtL06PfGZqpKgiMM16VFbIkbXfIm2tiIdpRsummI70CPOsIKUMELbUh-iH5-0PpTfMa1Y9V4LuiKFNgk7v1SG8zYCn3ZKxWE9e3o3ojIT5mHs1WV9YUOvJeEly3cLrt8NPCkabHQ52AS8ZiaWLsl8EkOHgWI_ZRtczE3g_a6XAm1CUJ97v5FqN520RVRAN3ytpd7I2FSBW32Rzd89COUt");
        transform: scale(1.02);
    }
    .ecc-hero-overlay{
        position:absolute; inset:0;
        background: linear-gradient(to top, rgba(5,5,5,.92), rgba(5,5,5,0) 60%);
        opacity: .85;
        pointer-events:none;
    }

    .ecc-pill{
        padding: 8px 14px;
        border-radius: 9999px;
        border: 1px solid rgba(242,185,13,.30);
        background: rgba(242,185,13,.06);
        color: rgba(242,185,13,.90);
        font-size: 12px;
        letter-spacing: .14em;
        text-transform: uppercase;
        font-weight: 800;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .ecc-h1{
        font-size: clamp(34px, 4.2vw, 46px);
        line-height: 1.05;
        letter-spacing: -.02em;
    }
    .ecc-italic{
        font-style: italic;
        font-weight: 500;
        color: rgba(255,255,255,.92);
        margin-right: 8px;
    }
    .ecc-gold{
        color: #ecc813;
        font-style: normal;
        font-weight: 900;
    }

    .ecc-copy{ max-width: 420px; }
    .ecc-p{
        color: rgba(255,255,255,.70);
        font-size: 18px;
        line-height: 1.65;
        margin: 0;
        font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }
    .ecc-p2{
        color: rgba(255,255,255,.50);
        font-size: 14px;
        line-height: 1.65;
        font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    .ecc-cta{
        height: 58px;
        border-radius: 12px;
        border: 1px solid rgba(242,185,13,.55);
        background: #ecc813;
        color: #050505;
        font-weight: 900;
        letter-spacing: .06em;
        box-shadow: 0 0 22px rgba(236,200,19,.18);
        transition: all 220ms ease;
        max-width: 520px;
    }
    .ecc-cta:hover{
        background: #f2b90d;
        border-color: #f2b90d;
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(243, 186, 13, 0.35);
        color: #050505;
    }
    .ecc-cta:active{ transform: scale(.99); }

    .ecc-cta-text{ font-size: 20px; }
    .ecc-cta-icon{ font-size: 22px; transition: transform 220ms ease; }
    .ecc-cta:hover .ecc-cta-icon{ transform: translateX(3px); }

    .ecc-tier-link{
        color: rgba(236,200,19,.65);
        font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 700;
        font-size: 13px;
        letter-spacing: .02em;
        text-decoration: none;
        border-bottom: 1px solid transparent;
        padding-bottom: 2px;
        transition: all 180ms ease;
    }
    .ecc-tier-link:hover{
        color: rgba(236,200,19,.95);
        border-bottom-color: rgba(236,200,19,.45);
    }

    @media (min-width: 992px){
        .ecc-max{ max-width: 520px; }
        .ecc-top-title{ font-size: 13px; }

        /* desktop layout improvements (larger vertical presence) */
        .ecc-apply-intro{ min-height: 100dvh; }
        .ecc-max{ max-width: 1080px; }
        .ecc-desktop-grid{
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 48px;
            align-items: center;
        }
        .ecc-hero{ aspect-ratio: 3 / 2; margin-bottom: 0 !important; }
        .ecc-h1{ font-size: 52px; }
        .ecc-p{ font-size: 18px; }
        .ecc-copy{ max-width: 540px; }
        .ecc-cta{ height: 64px; max-width: 480px; }
        .ecc-cta-text{ font-size: 20px; }

        /* subtle padding for desktop */
        .ecc-apply-intro .container-fluid.px-4.pt-3.pb-2{ padding-top: 20px !important; padding-bottom: 10px !important; }
        .ecc-apply-intro .container-fluid.flex-grow-1{ padding-bottom: 40px !important; }
    }

    @media (max-width: 991.98px){
        .ecc-desktop-grid{ display: block; }
    }
</style>
@endpush
