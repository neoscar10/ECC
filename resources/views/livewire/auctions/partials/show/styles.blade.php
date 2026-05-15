@push('styles')
<style>
    .auction-detail-page {
        --auction-panel-bg: rgba(10, 18, 38, 0.88);
        --auction-panel-border: rgba(199, 167, 90, 0.14);
    }

    .auction-breadcrumb {
        color: var(--luxe-text-soft);
        font-size: .9rem;
    }

    .auction-breadcrumb a {
        color: var(--luxe-text-soft);
        text-decoration: none;
        transition: .2s ease;
    }

    .auction-breadcrumb a:hover {
        color: var(--luxe-gold);
    }

    .auction-breadcrumb-sep {
        opacity: .55;
        margin-inline: .4rem;
    }

    .auction-detail-title {
        font-size: clamp(2rem, 4vw, 3.5rem);
        line-height: 1.02;
        font-weight: 900;
        color: #fff;
        letter-spacing: -.04em;
        margin-bottom: .9rem;
    }

    .auction-meta-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem 1rem;
    }

    .auction-lot-pill {
        display: inline-flex;
        align-items: center;
        padding: .45rem .85rem;
        border-radius: 999px;
        background: rgba(199, 167, 90,.14);
        color: var(--luxe-gold);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .auction-cert-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: var(--luxe-text-soft);
        font-size: .92rem;
        font-weight: 600;
    }

    .auction-gallery-card,
    .auction-info-card,
    .auction-bid-card,
    .auction-history-card,
    .auction-seller-card,
    .auction-related-card {
        border-radius: 24px;
        border: 1px solid rgba(199, 167, 90,.12);
        background:
            linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
            var(--luxe-surface);
        box-shadow: 0 18px 40px rgba(0,0,0,.28);
    }

    .auction-gallery-stage {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        background: #0f0d09;
        border: 1px solid rgba(199, 167, 90,.1);
        aspect-ratio: 4 / 3;
    }

    .auction-gallery-stage img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .auction-stage-overlay-actions {
        position: absolute;
        right: 1rem;
        bottom: 1rem;
        display: flex;
        gap: .75rem;
        z-index: 2;
    }

    .auction-stage-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
    }

    .auction-stage-arrow.is-left {
        left: 1rem;
    }

    .auction-stage-arrow.is-right {
        right: 1rem;
    }

    .auction-stage-btn {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,.12);
        background: rgba(0,0,0,.45);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
        transition: .2s ease;
    }

    .auction-stage-btn:hover {
        background: var(--luxe-gold);
        color: #111;
        border-color: var(--luxe-gold);
    }

    .auction-thumb-strip {
        display: flex;
        gap: 1rem;
        overflow-x: auto;
        padding-bottom: .25rem;
        scrollbar-width: thin;
    }

    .auction-thumb-btn {
        width: 112px;
        min-width: 112px;
        aspect-ratio: 1 / 1;
        border-radius: 18px;
        overflow: hidden;
        background: #14110b;
        border: 1px solid rgba(199, 167, 90,.14);
        padding: 0;
        transition: .2s ease;
    }

    .auction-thumb-btn.active,
    .auction-thumb-btn:hover {
        border-color: var(--luxe-gold);
        box-shadow: 0 0 0 2px rgba(199, 167, 90,.18);
    }

    .auction-thumb-btn img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: .72;
        transition: .2s ease;
    }

    .auction-thumb-btn.active img,
    .auction-thumb-btn:hover img {
        opacity: 1;
    }

    .auction-sticky-col {
        position: sticky;
        top: 98px;
    }

    .auction-bid-head {
        background: rgba(255,255,255,.03);
        border-bottom: 1px solid rgba(199, 167, 90,.12);
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
    }

    .auction-kicker {
        color: var(--luxe-gold);
        text-transform: uppercase;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .12em;
    }

    .auction-highest-bid {
        font-size: clamp(2rem, 3vw, 3rem);
        line-height: 1;
        font-weight: 900;
        color: #fff;
        letter-spacing: -.04em;
    }

    .auction-live-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .35rem .6rem;
        border-radius: 999px;
        background: rgba(34,197,94,.12);
        color: #4ade80;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .auction-live-chip-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;
        display: inline-block;
    }

    .auction-bid-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        color: var(--luxe-text-soft);
        font-size: .92rem;
        font-weight: 600;
    }

    .auction-bid-meta strong {
        color: #fff;
    }

    .auction-user-bid-box,
    .auction-inline-box {
        border-radius: 18px;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(255,255,255,.03);
    }

    .auction-section-label {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        font-size: .88rem;
        font-weight: 800;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .auction-quick-bids {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
    }

    .auction-quick-bid-btn {
        height: 42px;
        border-radius: 14px;
        border: 1px solid rgba(199, 167, 90,.28);
        background: transparent;
        color: #fff;
        font-size: .88rem;
        font-weight: 800;
        transition: .2s ease;
    }

    .auction-quick-bid-btn:hover,
    .auction-quick-bid-btn.active {
        background: rgba(199, 167, 90,.12);
        border-color: var(--luxe-gold);
        color: var(--luxe-gold);
    }

    .auction-bid-input-wrap {
        position: relative;
    }

    .auction-bid-input-wrap .form-control {
        height: 72px;
        border-radius: 18px;
        background: rgba(255,255,255,.04);
        border: 2px solid rgba(199, 167, 90,.28);
        color: #fff;
        font-size: 1.55rem;
        font-weight: 900;
        padding-inline: 1.15rem 4.5rem;
        box-shadow: none;
    }

    .auction-bid-input-wrap .form-control::placeholder {
        color: rgba(255,255,255,.42);
    }

    .auction-bid-input-wrap .form-control:focus {
        border-color: var(--luxe-gold);
        box-shadow: 0 0 0 .2rem rgba(199, 167, 90,.08);
        background: rgba(255,255,255,.05);
        color: #fff;
    }

    .auction-bid-currency {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        color: var(--luxe-text-soft);
        font-size: .92rem;
        font-weight: 800;
    }

    .auction-place-bid-btn {
        width: 100%;
        min-height: 60px;
        border-radius: 18px;
        border: 0;
        background: var(--luxe-gold);
        color: #111;
        font-size: .98rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
        box-shadow: 0 14px 28px rgba(199, 167, 90,.18);
        transition: .2s ease;
    }

    .auction-place-bid-btn:hover {
        filter: brightness(1.04);
        transform: translateY(-1px);
    }

    .auction-place-bid-btn:disabled {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.3);
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
        filter: none;
    }

    .auction-micro-copy {
        font-size: .68rem;
        color: var(--luxe-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .auction-history-head {
        border-bottom: 1px solid rgba(199, 167, 90,.1);
        background: rgba(255,255,255,.03);
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
    }

    .auction-bid-row + .auction-bid-row {
        border-top: 1px solid rgba(199, 167, 90,.06);
    }

    .auction-bid-row-index {
        min-width: 40px;
        font-size: .8rem;
        font-weight: 900;
        color: var(--luxe-gold);
    }

    .auction-bid-row-muted {
        opacity: .68;
    }

    .auction-description-card h3,
    .auction-related-title {
        color: #fff;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .auction-description-card .auction-desc-divider {
        height: 1px;
        background: rgba(199, 167, 90,.16);
        margin: 1rem 0 1.25rem;
    }

    .auction-rich-text,
    .auction-rich-text p,
    .auction-rich-text li,
    .auction-rich-text span {
        color: var(--luxe-text-soft);
        line-height: 1.9;
    }

    .auction-feature-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .9rem 1.25rem;
        margin-top: 1.5rem;
    }

    .auction-feature-item {
        display: flex;
        align-items: center;
        gap: .65rem;
        color: #fff;
        min-width: 0;
    }

    .auction-feature-item i {
        color: var(--luxe-gold);
        flex: 0 0 auto;
    }

    .auction-house-badge {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: #15120c;
        color: var(--luxe-gold);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        letter-spacing: .04em;
        border: 1px solid rgba(199, 167, 90,.18);
    }

    .auction-related-nav-btn {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(255,255,255,.04);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: .2s ease;
    }

    .auction-related-nav-btn:hover {
        border-color: var(--luxe-gold);
        color: var(--luxe-gold);
    }

    .auction-related-item {
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(199, 167, 90,.12);
        background: rgba(255,255,255,.03);
        box-shadow: 0 14px 28px rgba(0,0,0,.22);
        transition: .2s ease;
        height: 100%;
    }

    .auction-related-item:hover {
        transform: translateY(-4px);
        border-color: rgba(199, 167, 90,.24);
    }

    .auction-related-media {
        position: relative;
        aspect-ratio: 1 / 1;
        background: #0f0d09;
    }

    .auction-related-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .auction-related-lot-badge {
        position: absolute;
        top: .6rem;
        left: .6rem;
        padding: .28rem .5rem;
        border-radius: 999px;
        background: rgba(0,0,0,.55);
        color: #fff;
        font-size: .6rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    @media (max-width: 991.98px) {
        .auction-sticky-col {
            position: static;
        }

        .auction-feature-list {
            grid-template-columns: 1fr;
        }

        .auction-quick-bids {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
