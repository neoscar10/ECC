<style>
    :root {
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
    }

    html[data-theme="dark"] {
        color-scheme: dark;

        --ecc-bg-page: #0b0b08;
        --ecc-bg-page-rgb: 11, 11, 8;
        --ecc-bg-nav: rgba(13, 13, 9, 0.94);
        --ecc-bg-surface: #11110c;
        --ecc-bg-surface-2: #17170f;
        --ecc-bg-elevated: #1d1c13;
        --ecc-bg-input: rgba(255, 255, 255, 0.055);
        --ecc-bg-hover: rgba(199, 167, 90, 0.10);

        --ecc-text-primary: #ffffff;
        --ecc-text-secondary: rgba(255, 255, 255, 0.78);
        --ecc-text-muted: rgba(255, 255, 255, 0.58);
        --ecc-text-subtle: rgba(255, 255, 255, 0.42);
        --ecc-text-inverse: #130F07;

        --ecc-border: rgba(255, 255, 255, 0.10);
        --ecc-border-soft: rgba(255, 255, 255, 0.065);
        --ecc-border-strong: rgba(199, 167, 90, 0.35);

        --ecc-shadow-soft: 0 18px 50px rgba(0, 0, 0, 0.32);
        --ecc-shadow-card: 0 24px 70px rgba(0, 0, 0, 0.38);
        --ecc-overlay-dark: rgba(0, 0, 0, 0.62);
        --ecc-overlay-light: rgba(255, 255, 255, 0.08);
        --ecc-backdrop-blur: blur(18px);

        --ecc-success: #48c78e;
        --ecc-danger: #ff6b6b;
        --ecc-warning: #f4c95d;
        --ecc-info: #74c0fc;
    }

    html[data-theme="light"] {
        color-scheme: light;

        --ecc-bg-page: #F8F5EC;
        --ecc-bg-page-rgb: 248, 245, 236;
        --ecc-bg-nav: rgba(248, 245, 236, 0.94);
        --ecc-bg-surface: #FFFDF7;
        --ecc-bg-surface-2: #F3EBD8;
        --ecc-bg-elevated: #FFFFFF;
        --ecc-bg-input: rgba(19, 15, 7, 0.055);
        --ecc-bg-hover: rgba(199, 167, 90, 0.13);

        --ecc-text-primary: #17130A;
        --ecc-text-secondary: rgba(23, 19, 10, 0.82);
        --ecc-text-muted: rgba(23, 19, 10, 0.68);
        --ecc-text-subtle: rgba(23, 19, 10, 0.52);
        --ecc-text-inverse: #FFFFFF;

        --ecc-border: rgba(53, 43, 18, 0.15);
        --ecc-border-soft: rgba(53, 43, 18, 0.10);
        --ecc-border-strong: rgba(156, 125, 53, 0.38);

        --ecc-shadow-soft: 0 18px 45px rgba(53, 43, 18, 0.08);
        --ecc-shadow-card: 0 24px 65px rgba(53, 43, 18, 0.11);
        --ecc-overlay-dark: rgba(19, 15, 7, 0.45);
        --ecc-overlay-light: rgba(255, 255, 255, 0.75);
        --ecc-backdrop-blur: blur(18px);

        --ecc-success: #23885c;
        --ecc-danger: #c92a2a;
        --ecc-warning: #9C7D35;
        --ecc-info: #1864ab;
    }

    html[data-theme="dark"],
    html[data-theme="light"] {
        background: var(--ecc-bg-page);
    }

    body {
        background: var(--ecc-bg-page) !important;
        color: var(--ecc-text-primary) !important;
        transition: background-color 0.22s ease, color 0.22s ease;
    }

    a {
        color: inherit;
    }

    /* Global Bootstrap Utility Overrides for Light Mode */
    html[data-theme="light"] .ecc-text-primary { color: var(--ecc-text-primary) !important; }
    html[data-theme="light"] .ecc-text-secondary { color: var(--ecc-text-secondary) !important; }
    html[data-theme="light"] .ecc-text-muted { color: var(--ecc-text-muted) !important; }
    html[data-theme="light"] .ecc-text-subtle { color: var(--ecc-text-subtle) !important; }

    html[data-theme="light"] .text-white,
    html[data-theme="light"] .text-light,
    html[data-theme="light"] .text-white-50 {
        color: var(--ecc-text-primary) !important;
    }

    html[data-theme="light"] .text-muted {
        color: var(--ecc-text-muted) !important;
    }

    html[data-theme="light"] .text-secondary {
        color: var(--ecc-text-secondary) !important;
    }

    html[data-theme="light"] .bg-dark,
    html[data-theme="light"] .bg-black {
        background-color: var(--ecc-bg-surface) !important;
        color: var(--ecc-text-primary) !important;
    }

    html[data-theme="light"] .border-dark {
        border-color: var(--ecc-border) !important;
    }

    /* Heading overrides for light mode to ensure no invisible text */
    html[data-theme="light"] h1, 
    html[data-theme="light"] h2, 
    html[data-theme="light"] h3, 
    html[data-theme="light"] h4, 
    html[data-theme="light"] h5, 
    html[data-theme="light"] h6 {
        color: var(--ecc-text-primary) !important;
    }

    /* Override common chip/pill patterns that might be hardcoded to dark bg/white text */
    html[data-theme="light"] .luxe-chip,
    html[data-theme="light"] .archive-chip,
    html[data-theme="light"] .ecc-chip,
    html[data-theme="light"] .filter-pill {
        background: var(--ecc-bg-input) !important;
        color: var(--ecc-text-secondary) !important;
        border-color: var(--ecc-border) !important;
    }

    html[data-theme="light"] .luxe-chip.active,
    html[data-theme="light"] .archive-chip.active,
    html[data-theme="light"] .ecc-chip.active,
    html[data-theme="light"] .filter-pill.active {
        background: var(--ecc-primary) !important;
        color: #111 !important;
        border-color: var(--ecc-primary) !important;
    }

    html[data-theme="light"] .luxe-chip:hover,
    html[data-theme="light"] .archive-chip:hover {
        background: var(--ecc-bg-hover) !important;
        color: var(--ecc-text-primary) !important;
    }

    /* Force white text in intentionally dark areas (overlays, dark cards) even in Light Mode */
    html[data-theme="light"] .ecc-overlay-dark,
    html[data-theme="light"] .archive-restricted-overlay,
    html[data-theme="light"] .ecc-lock-overlay,
    html[data-theme="light"] .shop-detail-bento-overlay,
    html[data-theme="light"] .archive-card-gradient ~ .archive-card-body-overlay {
        color: #ffffff !important;
    }

    html[data-theme="light"] .ecc-overlay-dark h1, html[data-theme="light"] .ecc-overlay-dark h2,
    html[data-theme="light"] .ecc-overlay-dark h3, html[data-theme="light"] .ecc-overlay-dark p,
    html[data-theme="light"] .archive-restricted-overlay div, html[data-theme="light"] .archive-restricted-overlay p,
    html[data-theme="light"] .ecc-lock-overlay div, html[data-theme="light"] .ecc-lock-overlay p,
    html[data-theme="light"] .shop-detail-bento-overlay div, html[data-theme="light"] .shop-detail-bento-overlay h3 {
        color: #ffffff !important;
    }

    /* Keep footer dark if requested, but fix readability */
    .luxe-footer, footer {
        background: #0b0b08 !important;
        color: rgba(255,255,255,0.8) !important;
        border-top: 1px solid var(--ecc-primary-border) !important;
    }
    .luxe-footer a, footer a {
        color: rgba(255,255,255,0.6) !important;
        transition: color 0.2s ease;
    }
    .luxe-footer a:hover, footer a:hover {
        color: var(--ecc-primary) !important;
    }
    .luxe-footer .text-muted, footer .text-muted,
    .luxe-footer .luxe-footer-text, footer .luxe-footer-text {
        color: rgba(255,255,255,0.5) !important;
    }
    .luxe-footer .luxe-footer-title, footer .luxe-footer-title {
        color: #ffffff !important;
        font-weight: 800;
    }


    .ecc-user-shell,
    .ecc-page,
    .user-page,
    .main-content {
        background: var(--ecc-bg-page);
        color: var(--ecc-text-primary);
    }

    .ecc-surface,
    .ecc-card,
    .luxe-card,
    .archive-card,
    .shop-card,
    .order-card,
    .club-card,
    .settings-card,
    .checkout-card {
        background: var(--ecc-bg-surface);
        color: var(--ecc-text-primary);
        border-color: var(--ecc-border);
        box-shadow: var(--ecc-shadow-soft);
    }

    .ecc-text-primary {
        color: var(--ecc-text-primary) !important;
    }

    .ecc-text-secondary {
        color: var(--ecc-text-secondary) !important;
    }

    .ecc-text-muted {
        color: var(--ecc-text-muted) !important;
    }

    .ecc-brand-text,
    .ecc-text-gold,
    .ecc-text-accent {
        color: var(--ecc-primary) !important;
    }

    .ecc-border {
        border-color: var(--ecc-border) !important;
    }

    .ecc-brand-border {
        border-color: var(--ecc-primary-border) !important;
    }

    .ecc-bg-surface {
        background: var(--ecc-bg-surface) !important;
    }

    .ecc-bg-surface-2 {
        background: var(--ecc-bg-surface-2) !important;
    }

    .ecc-bg-elevated {
        background: var(--ecc-bg-elevated) !important;
    }

    .ecc-bg-brand-soft {
        background: var(--ecc-primary-soft) !important;
    }

    .ecc-btn-primary,
    .btn-ecc-primary {
        background: var(--ecc-primary);
        border-color: var(--ecc-primary);
        color: var(--ecc-text-inverse);
        font-weight: 700;
        transition: all 0.2s ease;
    }

    .ecc-btn-primary:hover,
    .ecc-btn-primary:focus,
    .btn-ecc-primary:hover,
    .btn-ecc-primary:focus {
        background: var(--ecc-primary-hover);
        border-color: var(--ecc-primary-hover);
        color: var(--ecc-text-inverse);
        box-shadow: 0 10px 28px var(--ecc-primary-shadow);
        transform: translateY(-1px);
    }

    .ecc-btn-outline-primary,
    .btn-ecc-outline {
        border: 1px solid var(--ecc-primary-border);
        color: var(--ecc-primary);
        background: var(--ecc-primary-soft);
        transition: all 0.2s ease;
    }

    .ecc-btn-outline-primary:hover,
    .ecc-btn-outline-primary:focus,
    .btn-ecc-outline:hover,
    .btn-ecc-outline:focus {
        background: var(--ecc-primary);
        border-color: var(--ecc-primary);
        color: var(--ecc-text-inverse);
    }

    .ecc-input,
    .form-control,
    .form-select,
    textarea.form-control {
        background-color: var(--ecc-bg-input);
        color: var(--ecc-text-primary);
        border-color: var(--ecc-border);
    }

    .ecc-input:focus,
    .form-control:focus,
    .form-select:focus,
    textarea.form-control:focus {
        background-color: var(--ecc-bg-input);
        color: var(--ecc-text-primary);
        border-color: var(--ecc-primary-border);
        box-shadow: 0 0 0 0.2rem rgba(199, 167, 90, 0.16);
    }

    .form-control::placeholder,
    textarea.form-control::placeholder {
        color: var(--ecc-text-subtle);
    }

    .dropdown-menu,
    .modal-content {
        background: var(--ecc-bg-elevated);
        color: var(--ecc-text-primary);
        border-color: var(--ecc-border);
        box-shadow: var(--ecc-shadow-card);
    }

    .dropdown-item {
        color: var(--ecc-text-secondary);
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
        background: var(--ecc-bg-hover);
        color: var(--ecc-text-primary);
    }

    .table {
        color: var(--ecc-text-primary);
        border-color: var(--ecc-border);
    }

    .badge.ecc-badge,
    .ecc-badge {
        background: var(--ecc-primary-soft);
        color: var(--ecc-primary);
        border: 1px solid var(--ecc-primary-border);
    }

    .ecc-theme-toggle {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        border-radius: 999px;
        border: 1px solid var(--ecc-border);
        background: var(--ecc-bg-surface);
        color: var(--ecc-text-primary);
        box-shadow: var(--ecc-shadow-soft);
        transition: all 0.2s ease;
    }

    .ecc-theme-toggle:hover,
    .ecc-theme-toggle:focus {
        border-color: var(--ecc-primary-border);
        background: var(--ecc-bg-hover);
        color: var(--ecc-primary);
        transform: translateY(-1px);
    }

    .ecc-theme-toggle .ecc-theme-icon {
        font-size: 1.25rem;
        line-height: 1;
        transition: opacity 0.18s ease, transform 0.18s ease;
    }

    html[data-theme="dark"] .ecc-theme-icon-light {
        display: none;
    }

    html[data-theme="dark"] .ecc-theme-icon-dark {
        display: inline-flex;
    }

    html[data-theme="light"] .ecc-theme-icon-dark {
        display: none;
    }

    html[data-theme="light"] .ecc-theme-icon-light {
        display: inline-flex;
    }

    html[data-theme="light"] .ecc-theme-toggle {
        background: var(--ecc-bg-elevated);
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
</style>
