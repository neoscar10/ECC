<style>
    :root{
        --ecc-primary: #D4AF37; /* stitched splash gold */
        --ecc-bg: #050505;
        --ecc-surface: #2d281a;
        --ecc-surface-2: #3a331f;
        --ecc-border: rgba(255,255,255,.08);
        --ecc-text-muted: #cbbc90;
        --ecc-muted: rgba(255,255,255,.35);
        --ecc-text: #ffffff;
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
