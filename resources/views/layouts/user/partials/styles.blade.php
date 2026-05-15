<style>
    :root{
        --ecc-gold-50: #F8F5EC;
        --ecc-gold-100: #EDE2CA;
        --ecc-gold-200: #E1D0A8;
        --ecc-gold-300: #D5BD85;
        --ecc-gold-400: #C7A75A;
        --ecc-gold-500: #BE9841;
        --ecc-gold-600: #9C7D35;
        --ecc-gold-700: #7A622A;
        --ecc-gold-800: #57461E;
        --ecc-gold-900: #352B12;
        --ecc-gold-950: #130F07;

        --ecc-primary: var(--ecc-gold-400);
        --ecc-primary-hover: var(--ecc-gold-500);
        --ecc-primary-dark: var(--ecc-gold-600);
        --ecc-primary-soft: rgba(199, 167, 90, 0.12);
        --ecc-primary-soft-2: rgba(199, 167, 90, 0.18);
        --ecc-primary-border: rgba(199, 167, 90, 0.35);
        --ecc-primary-shadow: rgba(199, 167, 90, 0.28);
        --ecc-primary-gradient: linear-gradient(135deg, var(--ecc-gold-300), var(--ecc-gold-400), var(--ecc-gold-500));
        --ecc-primary-gradient-dark: linear-gradient(135deg, var(--ecc-gold-500), var(--ecc-gold-600), var(--ecc-gold-700));

        --ecc-bg: #050505;
        --ecc-surface: #2d281a;
        --ecc-surface-2: #3a331f;
        --ecc-border: rgba(255,255,255,.08);
        --ecc-text-muted: #cbbc90;
        --ecc-muted: rgba(255,255,255,.35);
        --ecc-text: #ffffff;
    }

    .ecc-text-primary { color: var(--ecc-primary) !important; }
    .ecc-bg-primary { background-color: var(--ecc-primary) !important; }
    .ecc-border-primary { border-color: var(--ecc-primary-border) !important; }

    .ecc-btn-primary {
        background: var(--ecc-primary);
        border-color: var(--ecc-primary);
        color: #130F07;
        font-weight: 700;
        transition: all 0.2s ease;
    }

    .ecc-btn-primary:hover,
    .ecc-btn-primary:focus {
        background: var(--ecc-primary-hover);
        border-color: var(--ecc-primary-hover);
        color: #130F07;
        box-shadow: 0 10px 28px var(--ecc-primary-shadow);
        transform: translateY(-1px);
    }

    .ecc-btn-outline-primary {
        border: 1px solid var(--ecc-primary-border);
        color: var(--ecc-primary);
        background: rgba(199, 167, 90, 0.06);
        transition: all 0.2s ease;
    }

    .ecc-btn-outline-primary:hover,
    .ecc-btn-outline-primary:focus {
        background: var(--ecc-primary);
        border-color: var(--ecc-primary);
        color: #130F07;
    }

    .ecc-icon-primary { color: var(--ecc-primary); }
    .ecc-icon-pill {
        background: var(--ecc-primary-soft);
        color: var(--ecc-primary);
        border: 1px solid var(--ecc-primary-border);
    }
    .ecc-price {
        color: var(--ecc-primary);
        font-weight: 800;
    }
    .ecc-gold-gradient {
        background: var(--ecc-primary-gradient);
    }

    .ecc-user-body{
        background: var(--ecc-bg);
        color: var(--ecc-text);
    }

    /* Topbar */
    .ecc-topbar{
        background: rgba(5, 5, 5, 0.92);
        border-bottom: 1px solid var(--ecc-border);
        backdrop-filter: blur(10px);
    }

    /* Content padding compensation for bottom nav on mobile */
    .ecc-content-inner{
        padding-bottom: 90px; /* keep content above bottom nav */
    }
    @media (min-width: 768px){
        .ecc-content-inner{
            padding-bottom: 24px; /* no bottom nav on desktop */
        }
    }

    /* Cards / surfaces */
</style>
