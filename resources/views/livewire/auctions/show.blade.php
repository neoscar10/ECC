<div class="ecc-auction-lot-page">
    @push('styles')
        <style>
            .ecc-auction-lot-page {
                background:
                    radial-gradient(circle at top, rgba(212, 175, 55, 0.08), transparent 26%),
                    linear-gradient(180deg, #050505 0%, #0a0a0a 100%);
                min-height: 100%;
                color: #f5e6c4;
            }

            .ecc-auction-lot-shell {
                width: 100%;
                max-width: 920px;
                margin: 0 auto;
                padding: 1rem 1rem 2.5rem;
            }

            @media (min-width: 768px) {
                .ecc-auction-lot-shell {
                    padding: 1.25rem 1.25rem 3rem;
                }
            }

            .ecc-lot-topbar {
                position: sticky;
                top: 0;
                z-index: 30;
                background: rgba(5, 5, 5, 0.82);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(212, 175, 55, 0.10);
                margin: -1rem -1rem 1rem;
                padding: 0.85rem 1rem;
            }

            @media (min-width: 768px) {
                .ecc-lot-topbar {
                    margin: -1.25rem -1.25rem 1.25rem;
                    padding: 1rem 1.25rem;
                }
            }

            .ecc-lot-icon-btn {
                width: 42px;
                height: 42px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(212, 175, 55, 0.20);
                background: rgba(212, 175, 55, 0.08);
                color: #d4af37;
                transition: all .2s ease;
                text-decoration: none;
                box-shadow: none;
            }

            .ecc-lot-icon-btn:hover,
            .ecc-lot-icon-btn:focus {
                background: rgba(212, 175, 55, 0.16);
                color: #f2c94c;
            }

            .ecc-lot-number-title {
                color: rgba(212, 175, 55, 0.90);
                font-size: 0.92rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                margin: 0;
                text-align: center;
            }

            .ecc-hero-card {
                position: relative;
                border-radius: 1.25rem;
                overflow: hidden;
                border: 1px solid rgba(212, 175, 55, 0.16);
                background: #121212;
                box-shadow: 0 18px 36px rgba(0, 0, 0, 0.28);
            }

            .ecc-hero-image-wrap {
                position: relative;
                width: 100%;
                background: #121212;
                min-height: 320px;
                max-height: 560px;
            }

            @media (min-width: 768px) {
                .ecc-hero-image-wrap {
                    min-height: 420px;
                    max-height: 580px;
                }
            }

            @media (min-width: 1200px) {
                .ecc-hero-image-wrap {
                    max-height: 600px;
                }
            }

            .ecc-hero-image {
                width: 100%;
                height: 100%;
                min-height: 320px;
                max-height: 560px;
                object-fit: cover;
                object-position: center;
                display: block;
                background: #121212;
            }

            @media (min-width: 768px) {
                .ecc-hero-image {
                    min-height: 420px;
                    max-height: 580px;
                }
            }

            @media (min-width: 1200px) {
                .ecc-hero-image {
                    max-height: 600px;
                }
            }

            .ecc-hero-placeholder {
                min-height: 320px;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                color: rgba(245, 230, 196, 0.50);
                padding: 1.5rem;
            }

            @media (min-width: 768px) {
                .ecc-hero-placeholder {
                    min-height: 420px;
                }
            }

            .ecc-zoom-badge {
                position: absolute;
                top: 1rem;
                right: 1rem;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.48rem 0.85rem;
                border-radius: 999px;
                background: rgba(0, 0, 0, 0.55);
                border: 1px solid rgba(212, 175, 55, 0.22);
                color: #f5e6c4;
                font-size: 0.84rem;
                font-weight: 500;
                backdrop-filter: blur(8px);
            }

            .ecc-hero-gradient {
                position: absolute;
                inset: 0;
                background: linear-gradient(to top, rgba(5, 5, 5, 0.96) 12%, rgba(5, 5, 5, 0.18) 52%, rgba(5, 5, 5, 0.03) 100%);
            }

            .ecc-hero-caption {
                position: absolute;
                left: 1rem;
                right: 1rem;
                bottom: 1rem;
                z-index: 2;
            }

            @media (min-width: 768px) {
                .ecc-hero-caption {
                    left: 1.25rem;
                    right: 1.25rem;
                    bottom: 1.25rem;
                }
            }

            .ecc-badges-row {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 0.75rem;
            }

            .ecc-badge-primary {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.38rem 0.7rem;
                border-radius: 0.4rem;
                background: #d4af37;
                color: #050505;
                font-size: 0.72rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.06em;
            }

            .ecc-badge-dark {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.38rem 0.7rem;
                border-radius: 0.4rem;
                background: rgba(18, 18, 18, 0.88);
                color: #d4af37;
                border: 1px solid rgba(212, 175, 55, 0.25);
                font-size: 0.72rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.06em;
            }

            .ecc-hero-title {
                margin: 0 0 0.35rem;
                font-size: clamp(2rem, 3vw, 2.85rem);
                line-height: 1.04;
                font-weight: 800;
                letter-spacing: -0.03em;
                color: #f2d37b;
                text-wrap: balance;
            }

            .ecc-hero-subtitle {
                margin: 0;
                color: rgba(245, 230, 196, 0.84);
                font-size: 1rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
            }

            .ecc-stats-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.9rem;
                margin-top: 1.35rem;
                margin-bottom: 1.5rem;
            }

            .ecc-stat-card {
                position: relative;
                overflow: hidden;
                padding: 1rem;
                border-radius: 1rem;
                background: #121212;
                border: 1px solid rgba(212, 175, 55, 0.12);
                min-height: 118px;
            }

            .ecc-stat-icon {
                position: absolute;
                top: 0.6rem;
                right: 0.7rem;
                color: rgba(212, 175, 55, 0.10);
                font-size: 2.1rem;
            }

            .ecc-stat-label {
                display: block;
                color: rgba(212, 175, 55, 0.60);
                font-size: 0.76rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                margin-bottom: 0.42rem;
            }

            .ecc-stat-main {
                color: #ffffff;
                font-size: clamp(1.3rem, 2vw, 1.9rem);
                line-height: 1.1;
                font-weight: 800;
                letter-spacing: -0.02em;
            }

            .ecc-stat-note {
                margin-top: 0.42rem;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                font-size: 0.78rem;
                color: #d4af37;
            }

            .ecc-stat-note.muted {
                color: rgba(245, 230, 196, 0.48);
            }

            .ecc-section {
                margin-bottom: 1.4rem;
            }

            .ecc-section-heading {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 0.85rem;
            }

            .ecc-section-title-wrap {
                display: flex;
                align-items: center;
                gap: 0.55rem;
            }

            .ecc-section-title-wrap i {
                color: #d4af37;
                font-size: 1.2rem;
            }

            .ecc-section-title {
                font-size: 1.45rem;
                line-height: 1.1;
                font-weight: 800;
                margin: 0;
                color: #f5e6c4;
            }

            .ecc-section-link {
                color: #d4af37;
                font-size: 0.88rem;
                font-weight: 600;
                text-decoration: underline;
                text-underline-offset: 4px;
                background: none;
                border: 0;
            }

            .ecc-panel {
                padding: 1.15rem;
                border-radius: 1rem;
                background: #121212;
                border: 1px solid rgba(212, 175, 55, 0.10);
            }

            .ecc-detail-text {
                color: rgba(245, 230, 196, 0.84);
                font-size: 1rem;
                line-height: 1.72;
                margin-bottom: 1rem;
            }

            .ecc-meta-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 0.65rem;
            }

            .ecc-meta-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.42rem;
                padding: 0.55rem 0.85rem;
                border-radius: 0.7rem;
                background: #1c1c1c;
                border: 1px solid rgba(212, 175, 55, 0.18);
                color: #d4af37;
                font-size: 0.82rem;
            }

            .ecc-attachments-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 0.9rem;
            }

            @media (min-width: 768px) {
                .ecc-attachments-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            .ecc-attachment-card {
                display: block;
                text-decoration: none;
                border-radius: 1rem;
                overflow: hidden;
                background: #121212;
                border: 1px solid rgba(212, 175, 55, 0.10);
                transition: all .2s ease;
                height: 100%;
            }

            .ecc-attachment-card:hover {
                border-color: rgba(212, 175, 55, 0.24);
                transform: translateY(-1px);
            }

            .ecc-attachment-preview {
                width: 100%;
                aspect-ratio: 16 / 10;
                object-fit: cover;
                display: block;
                background: #1e1e1e;
            }

            .ecc-attachment-file {
                display: flex;
                align-items: center;
                gap: 0.8rem;
                padding: 1rem;
                min-height: 92px;
            }

            .ecc-attachment-icon {
                width: 48px;
                height: 48px;
                border-radius: 0.9rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(212, 175, 55, 0.10);
                border: 1px solid rgba(212, 175, 55, 0.18);
                color: #d4af37;
                flex-shrink: 0;
            }

            .ecc-attachment-title {
                color: #f5e6c4;
                font-weight: 700;
                line-height: 1.3;
                margin-bottom: 0.2rem;
            }

            .ecc-attachment-meta {
                color: rgba(245, 230, 196, 0.55);
                font-size: 0.82rem;
            }

            .ecc-history-table {
                border-radius: 1rem;
                overflow: hidden;
                background: #121212;
                border: 1px solid rgba(212, 175, 55, 0.10);
            }

            .ecc-history-head,
            .ecc-history-row {
                display: grid;
                grid-template-columns: 1.35fr .8fr 1fr;
                gap: 0.6rem;
                align-items: center;
            }

            .ecc-history-head {
                padding: 0.95rem 1rem;
                background: rgba(212, 175, 55, 0.05);
                border-bottom: 1px solid rgba(212, 175, 55, 0.10);
            }

            .ecc-history-head span {
                font-size: 0.72rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: rgba(212, 175, 55, 0.60);
            }

            .ecc-history-row {
                padding: 0.9rem 1rem;
                border-bottom: 1px solid rgba(212, 175, 55, 0.06);
            }

            .ecc-history-row:last-child {
                border-bottom: 0;
            }

            .ecc-bidder-cell {
                display: flex;
                align-items: center;
                gap: 0.65rem;
                min-width: 0;
            }

            .ecc-avatar {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(212, 175, 55, 0.12);
                border: 1px solid rgba(212, 175, 55, 0.22);
                color: #d4af37;
                font-size: 0.72rem;
                font-weight: 800;
                flex-shrink: 0;
            }

            .ecc-bidder-name {
                color: #f5e6c4;
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .ecc-bidder-subtext {
                color: rgba(245, 230, 196, 0.48);
                font-size: 0.78rem;
                line-height: 1.2;
                margin-top: 0.12rem;
            }

            .ecc-history-time {
                color: rgba(245, 230, 196, 0.48);
                font-family: monospace;
                font-size: 0.85rem;
            }

            .ecc-history-amount {
                color: #d4af37;
                font-weight: 800;
                font-family: monospace;
                text-align: right;
            }

            .ecc-bid-panel {
                margin-top: 1.5rem;
                margin-bottom: 1rem;
            }

            .ecc-bid-panel-inner {
                background: #0d0d0d;
                border: 1px solid rgba(212, 175, 55, 0.14);
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.32);
                border-radius: 1.15rem;
                padding: 1rem 1rem 1.1rem;
            }

            .ecc-increments-row {
                display: flex;
                gap: 0.65rem;
                overflow-x: auto;
                padding-bottom: 0.35rem;
                scrollbar-width: none;
            }

            .ecc-increments-row::-webkit-scrollbar {
                display: none;
            }

            .ecc-increment-chip {
                flex: 0 0 auto;
                border-radius: 999px;
                padding: 0.5rem 0.95rem;
                border: 1px solid rgba(212, 175, 55, 0.20);
                background: rgba(212, 175, 55, 0.05);
                color: #d4af37;
                font-size: 0.82rem;
                font-weight: 600;
            }

            .ecc-increment-chip.active {
                background: #d4af37;
                color: #050505;
                border-color: #d4af37;
                font-weight: 800;
            }

            .ecc-bid-input-row {
                display: flex;
                gap: 0.85rem;
                align-items: stretch;
                margin-top: 1rem;
            }

            .ecc-bid-input-wrap {
                position: relative;
                flex: 1;
            }

            .ecc-bid-input-label {
                position: absolute;
                top: -0.48rem;
                left: 0.8rem;
                padding: 0 0.3rem;
                background: #0d0d0d;
                color: #d4af37;
                font-size: 0.7rem;
                z-index: 2;
            }

            .ecc-bid-currency {
                position: absolute;
                left: 0.95rem;
                top: 50%;
                transform: translateY(-50%);
                color: #d4af37;
                font-weight: 800;
            }

            .ecc-bid-input {
                width: 100%;
                border-radius: 0.8rem;
                border: 1px solid rgba(212, 175, 55, 0.30);
                background: #121212;
                color: #f5e6c4;
                padding: 0.95rem 0.95rem 0.95rem 2.2rem;
                font-size: 1.2rem;
                font-weight: 800;
                font-family: monospace;
                outline: none;
                box-shadow: none;
            }

            .ecc-bid-input:focus {
                border-color: #d4af37;
                box-shadow: 0 0 0 0.18rem rgba(212, 175, 55, 0.10);
            }

            .ecc-auto-btn {
                min-width: 72px;
                border-radius: 0.8rem;
                border: 1px solid rgba(212, 175, 55, 0.30);
                background: #121212;
                color: rgba(212, 175, 55, 0.70);
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.18rem;
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                font-weight: 700;
                transition: all .25s ease;
            }

            .ecc-auto-btn:hover:not(:disabled),
            .ecc-auto-btn:focus:not(:disabled) {
                background: #1a1a1a;
                border-color: rgba(212, 175, 55, 0.80);
                color: #d4af37;
                box-shadow: 0 4px 14px rgba(212, 175, 55, 0.15);
                transform: translateY(-2px);
            }

            .ecc-review-btn {
                width: 100%;
                margin-top: 1rem;
                border: 0;
                border-radius: 0.9rem;
                padding: 1rem 1.1rem;
                background: linear-gradient(90deg, #d4af37 0%, #f2c94c 100%);
                color: #050505;
                font-size: 1.15rem;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                box-shadow: 0 10px 24px rgba(212, 175, 55, 0.16);
            }

            .ecc-review-btn:hover,
            .ecc-review-btn:focus {
                color: #050505;
            }

            .ecc-bid-note {
                text-align: center;
                margin-top: 0.7rem;
                color: rgba(212, 175, 55, 0.45);
                font-size: 0.72rem;
            }

            .ecc-bid-note a {
                color: rgba(212, 175, 55, 0.68);
                text-decoration: underline;
            }

            @media (max-width: 575.98px) {
                .ecc-history-head,
                .ecc-history-row {
                    grid-template-columns: 1.2fr .7fr .95fr;
                }

                .ecc-section-title {
                    font-size: 1.28rem;
                }

                .ecc-bid-input-row {
                    gap: 0.65rem;
                }

                .ecc-auto-btn {
                    min-width: 64px;
                }
            }
        </style>
    @endpush

    @php
        // The Show.php Livewire component passes these tightly bound and resolved fields directly.
        $resolvedHeroImage = $lotPrepared->hero_image_url ?? null;

        $metaBadges = $lotPrepared->provenance_badges ?? [];
        $lotAttachments = $lotPrepared->attachments ?? [];
        $history = $lotPrepared->bid_history ?? [];
        $increments = $lotPrepared->suggested_increments ?? [];
    @endphp

    <div class="ecc-auction-lot-shell">
        <div class="ecc-lot-topbar">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <a href="{{ $lotPrepared->back_url ?? url()->previous() }}" class="ecc-lot-icon-btn" aria-label="Go back">
                    <span class="material-symbols-outlined" style="font-size: 1.35rem; line-height: 1;">arrow_back</span>
                </a>

                <h1 class="ecc-lot-number-title">
                    LOT #{{ $lotPrepared->lot_number ?? $lotPrepared->lot_no ?? '---' }}
                </h1>

                <div style="width: 42px; height: 42px;"></div>
            </div>
        </div>

        <section class="mb-4">
            <div class="ecc-hero-card">
                <div class="ecc-hero-image-wrap">
                    @if(!empty($resolvedHeroImage))
                        <img
                            src="{{ $resolvedHeroImage }}"
                            alt="{{ $lotPrepared->title ?? 'Auction lot image' }}"
                            class="ecc-hero-image"
                        >
                    @else
                        <div class="ecc-hero-placeholder">
                            <span>No image available</span>
                        </div>
                    @endif

                   

                    <div class="ecc-hero-gradient"></div>

                    <div class="ecc-hero-caption">
                        <div class="ecc-badges-row">
                            @if(!empty($lotPrepared->status_label ?? null))
                                <span class="ecc-badge-primary">{{ $lotPrepared->status_label }}</span>
                            @endif

                            @if(!empty($lotPrepared->rarity_label ?? null))
                                <span class="ecc-badge-dark">{{ $lotPrepared->rarity_label }}</span>
                            @endif
                        </div>

                        <h2 class="ecc-hero-title">{{ $lotPrepared->title ?? 'Untitled Lot' }}</h2>

                        @if(!empty($lotPrepared->subtitle ?? null))
                            <p class="ecc-hero-subtitle">
                                <i class="mdi mdi-fountain-pen-tip"></i>
                                <span>{{ $lotPrepared->subtitle }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="ecc-stats-grid">
            <div class="ecc-stat-card">
                <i class="mdi mdi-cash-multiple ecc-stat-icon"></i>
                <span class="ecc-stat-label">Current Bid</span>
                <div class="ecc-stat-main">
                    {{ $lotPrepared->current_bid_display ?? (isset($lotPrepared->current_bid) ? '₹' . number_format((float) $lotPrepared->current_bid) : '₹0') }}
                </div>
                @if(!empty($lotPrepared->recent_increment_note ?? null))
                    <div class="ecc-stat-note">
                        <i class="mdi mdi-trending-up"></i>
                        <span>{{ $lotPrepared->recent_increment_note }}</span>
                    </div>
                @endif
            </div>

            <div class="ecc-stat-card">
                <i class="mdi mdi-timer-outline ecc-stat-icon"></i>
                <span class="ecc-stat-label">Time Remaining</span>
                <div class="ecc-stat-main">
                    {{ $lotPrepared->time_remaining_display ?? '--' }}
                </div>
                @if(!empty($lotPrepared->ends_at_display ?? null))
                    <div class="ecc-stat-note muted">
                        {{ $lotPrepared->ends_at_display }}
                    </div>
                @endif
            </div>
        </section>

        <section class="ecc-section">
            <div class="ecc-section-heading">
                <div class="ecc-section-title-wrap">
                    <i class="mdi mdi-history-edu"></i>
                    <h3 class="ecc-section-title">Provenance &amp; Details</h3>
                </div>
            </div>

            <div class="ecc-panel">
                @if(!empty($lotPrepared->description ?? null))
                    <div class="ecc-detail-text">
                        {!! nl2br(e($lotPrepared->description)) !!}
                    </div>
                @endif

                @if(!empty($metaBadges))
                    <div class="ecc-meta-chips">
                        @foreach($metaBadges as $badge)
                            <span class="ecc-meta-chip">
                                <i class="mdi {{ $badge['icon'] ?? 'mdi-check-decagram-outline' }}"></i>
                                <span>{{ $badge['label'] ?? '' }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @if(!empty($lotAttachments) && count($lotAttachments))
            <section class="ecc-section">
                <div class="ecc-section-heading">
                    <div class="ecc-section-title-wrap">
                        <i class="mdi mdi-paperclip"></i>
                        <h3 class="ecc-section-title">Attachments</h3>
                    </div>
                </div>

                <div class="ecc-attachments-grid">
                    @foreach($lotAttachments as $attachment)
                        @php
                            $attachmentUrl = is_array($attachment) ? ($attachment['url'] ?? '#') : ($attachment->url ?? '#');
                            $attachmentName = is_array($attachment) ? ($attachment['name'] ?? 'Attachment') : ($attachment->name ?? 'Attachment');
                            $attachmentSize = is_array($attachment) ? ($attachment['size_label'] ?? null) : ($attachment->size_label ?? null);
                            $attachmentThumb = is_array($attachment) ? ($attachment['thumbnail_url'] ?? $attachment['preview_url'] ?? null) : ($attachment->thumbnail_url ?? $attachment->preview_url ?? null);
                            $attachmentType = is_array($attachment) ? ($attachment['type'] ?? null) : ($attachment->type ?? null);
                            $isImage = (bool) (is_array($attachment) ? ($attachment['is_image'] ?? false) : ($attachment->is_image ?? false));
                        @endphp

                        <a href="{{ $attachmentUrl }}" target="_blank" class="ecc-attachment-card">
                            @if($isImage && $attachmentThumb)
                                <img src="{{ $attachmentThumb }}" alt="{{ $attachmentName }}" class="ecc-attachment-preview">
                                <div class="p-3">
                                    <div class="ecc-attachment-title">{{ $attachmentName }}</div>
                                    @if($attachmentSize || $attachmentType)
                                        <div class="ecc-attachment-meta">
                                            {{ $attachmentType ? strtoupper($attachmentType) : '' }}{{ ($attachmentType && $attachmentSize) ? ' • ' : '' }}{{ $attachmentSize ?? '' }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="ecc-attachment-file">
                                    <span class="ecc-attachment-icon">
                                        <i class="mdi mdi-file-document-outline fs-4"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="ecc-attachment-title text-truncate">{{ $attachmentName }}</div>
                                        @if($attachmentSize || $attachmentType)
                                            <div class="ecc-attachment-meta">
                                                {{ $attachmentType ? strtoupper($attachmentType) : 'FILE' }}{{ ($attachmentType && $attachmentSize) ? ' • ' : '' }}{{ $attachmentSize ?? '' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="ecc-section">
            <div class="ecc-section-heading">
                <div class="ecc-section-title-wrap">
                    <i class="mdi mdi-history"></i>
                    <h3 class="ecc-section-title">Bid History</h3>
                </div>

                @if(!empty($lotPrepared->bid_history_url ?? null))
                    <a href="{{ $lotPrepared->bid_history_url }}" class="ecc-section-link">View All</a>
                @endif
            </div>

            @if(!empty($history) && count($history))
                <div class="ecc-history-table">
                    <div class="ecc-history-head">
                        <span>Bidder</span>
                        <span>Time</span>
                        <span class="text-end">Amount</span>
                    </div>

                    @foreach($history as $entry)
                        @php
                            $bidderBadge = $entry['bidder_badge'] ?? $entry->bidder_badge ?? 'U';
                            $bidderLabel = $entry['bidder_label'] ?? $entry->bidder_label ?? 'User';
                            $bidderCode = $entry['bidder_code'] ?? $entry->bidder_code ?? null;
                            $timeAgo = $entry['time_human'] ?? $entry->time_human ?? '--';
                            $amount = $entry['amount_display'] ?? $entry->amount_display ?? (isset($entry['amount']) ? '₹' . number_format((float) $entry['amount']) : (isset($entry->amount) ? '₹' . number_format((float) $entry->amount) : '₹0'));
                        @endphp

                        <div class="ecc-history-row">
                            <div class="ecc-bidder-cell">
                                <span class="ecc-avatar">{{ $bidderBadge }}</span>

                                <div class="min-w-0">
                                    <div class="ecc-bidder-name">{{ $bidderLabel }}</div>

                                    @if(!empty($bidderCode))
                                        <div class="ecc-bidder-subtext">Bidder #{{ $bidderCode }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="ecc-history-time">{{ $timeAgo }}</div>

                            <div class="ecc-history-amount">{{ $amount }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ecc-panel text-light-emphasis">
                    No bids recorded yet.
                </div>
            @endif
        </section>

        <section class="ecc-bid-panel">
            <div class="ecc-bid-panel-inner">
                @if(!empty($increments) && count($increments))
                    <div class="ecc-increments-row">
                        @foreach($increments as $increment)
                            @php
                                $label = is_array($increment) ? ($increment['label'] ?? '') : ($increment->label ?? '');
                                $isRecommended = (bool) (is_array($increment) ? ($increment['recommended'] ?? false) : ($increment->recommended ?? false));
                            @endphp

                            <button
                                type="button"
                                class="ecc-increment-chip {{ $isRecommended ? 'active' : '' }}"
                                @if(!empty($label)) wire:click="applyIncrement('{{ $label }}')" @endif
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="ecc-bid-input-row">
                    <div class="ecc-bid-input-wrap">
                        <span class="ecc-bid-input-label">Your Bid</span>
                        <span class="ecc-bid-currency">₹</span>
                        <input
                            type="text"
                            class="ecc-bid-input"
                            wire:model.defer="bidAmount"
                            placeholder="0"
                        >
                    </div>

                    @if($canAutoBid)
                    <button type="button" class="ecc-auto-btn {{ $hasAutoBidConfigured ? 'border-warning text-warning' : '' }}" wire:click="openAutoBidModal" @if(empty($lotPrepared->can_bid)) disabled @endif>
                        <i class="mdi mdi-flash fs-5"></i>
                        <span>{{ $hasAutoBidConfigured ? 'Auto: ON' : 'Auto' }}</span>
                    </button>
                    @else
                    <button type="button" class="ecc-auto-btn" disabled>
                        <i class="mdi mdi-flash fs-5 opacity-50"></i>
                        <span class="opacity-50">Auto</span>
                    </button>
                    @endif
                </div>
                @error('bidAmount')
                    <div class="text-danger small mt-2 fw-medium px-2">{{ $message }}</div>
                @enderror

                <button type="button" class="ecc-review-btn" wire:click="reviewBid" @if(empty($lotPrepared->can_bid)) disabled @endif>
                    <span>{{ empty($lotPrepared->can_bid) ? 'Bidding Closed' : 'Review Bid' }}</span>
                    @if(!empty($lotPrepared->can_bid))
                        <i class="mdi mdi-arrow-right"></i>
                    @endif
                </button>

                <div class="ecc-bid-note">
                    Highest bidder must pay within 24h.
                    <a href="{{ $lotPrepared->terms_url ?? '#' }}">Terms apply</a>.
                </div>
            </div>
        </section>
    </div>

    <!-- Bid Confirmation Modal -->
    <div
        class="modal fade @if($showBidConfirmModal) show @endif"
        tabindex="-1"
        @if($showBidConfirmModal)
            style="display:block;"
            aria-modal="true"
            role="dialog"
        @endif
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-black text-light">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-warning fw-bold">Confirm Bid</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeBidConfirmModal"></button>
                </div>

                <div class="modal-body">
                    <p class="text-white mb-3">
                        Please confirm your bid before submitting.
                    </p>

                    <div class="rounded-4 border border-warning-subtle p-3 mb-3 bg-black">
                        <div class="small text-uppercase text-warning-emphasis mb-1">Current Highest Bid</div>
                        <div class="fs-4 fw-bold text-warning">{{ $currentHighestBidDisplay }}</div>
                    </div>

                    <div class="rounded-4 border border-warning-subtle p-3 bg-black">
                        <div class="small text-uppercase text-warning-emphasis mb-1">Your Bid</div>
                        <div class="fs-4 fw-bold text-warning">{{ $userBidDisplay }}</div>
                    </div>

                    <div class="small text-white mt-3">
                        Bids are binding once submitted.
                    </div>

                    @error('bidAmount')
                        <div class="alert alert-danger mt-3 mb-0">
                            {{ $message }}
                        </div>
                    @enderror

                    @if(!empty($bidErrorMessage))
                        <div class="alert alert-danger mt-3 mb-0">
                            {{ $bidErrorMessage }}
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-light" wire:click="closeBidConfirmModal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-warning fw-bold" wire:click="confirmBidSubmission" wire:loading.attr="disabled" wire:target="confirmBidSubmission">
                        <span wire:loading.remove wire:target="confirmBidSubmission">Place Bid</span>
                        <span wire:loading wire:target="confirmBidSubmission">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Placing Bid...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($showBidConfirmModal || $showAutoBidModal || $showCancelAutoBidModal)
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Auto Bid Configuration Modal -->
    <div
        class="modal fade @if($showAutoBidModal) show @endif"
        tabindex="-1"
        @if($showAutoBidModal)
            style="display: block;"
            aria-modal="true"
            role="dialog"
        @endif
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-black text-light"> <!-- Match Review Bid Modal Style -->
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-warning fw-bold">
                        {{ $hasAutoBidConfigured ? 'Update Auto Bid' : 'Configure Auto Bid' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeAutoBidModal"></button>
                </div>

                <div class="modal-body">
                    <p class="text-white mb-3">
                        Set your maximum bid limit and increment amount.
                    </p>

                    <div class="rounded-4 border border-warning-subtle p-3 mb-3 bg-black">
                        <div class="small text-uppercase text-warning-emphasis mb-1">Current Highest Bid</div>
                        <div class="fs-4 fw-bold text-warning">{{ $currentHighestBidDisplay }}</div>
                    </div>

                    <div class="rounded-4 border border-warning-subtle p-3 mb-3 bg-black">
                        <div class="small text-uppercase text-warning-emphasis mb-1">Minimum Increment</div>
                        <div class="fs-4 fw-bold text-white">{{ $minIncrementDisplay }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small">Increment Amount (₹)</label>
                        <input type="text" class="form-control bg-black text-white border-secondary" wire:model.defer="autoBidIncrementAmount" placeholder="e.g. 10000">
                        @error('autoBidIncrementAmount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small">Maximum Bid Limit (₹)</label>
                        <input type="text" class="form-control bg-black text-white border-secondary" wire:model.defer="autoBidMaxAmount" placeholder="e.g. 500000">
                        @error('autoBidMaxAmount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    @if(!empty($autoBidErrorMessage))
                        <div class="alert alert-danger mt-3 mb-0">
                            {{ $autoBidErrorMessage }}
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 pt-0 d-flex justify-content-between align-items-center">
                    <div>
                        @if($hasAutoBidConfigured)
                            <button type="button" class="btn btn-link text-danger text-decoration-none px-0 fw-bold" wire:click="confirmCancelAutoBidModal">
                                Cancel Auto Bid
                            </button>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light" wire:click="closeAutoBidModal">
                            Close
                        </button>
                        <button type="button" class="btn btn-warning fw-bold" wire:click="saveAutoBid" wire:loading.attr="disabled" wire:target="saveAutoBid">
                            <span wire:loading.remove wire:target="saveAutoBid">Save Auto Bid</span>
                            <span wire:loading wire:target="saveAutoBid">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Auto Bid Confirm Modal -->
    <div
        class="modal fade @if($showCancelAutoBidModal) show @endif"
        tabindex="-1"
        @if($showCancelAutoBidModal)
            style="display: block;"
            aria-modal="true"
            role="dialog"
        @endif
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-black text-light border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-warning fw-bold">Cancel Auto Bid</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeCancelAutoBidModal"></button>
                </div>

                <div class="modal-body">
                    <p class="text-white mb-0">
                        Are you sure you want to cancel your auto bid for this lot?
                    </p>
                    
                    @if(!empty($autoBidErrorMessage))
                        <div class="alert alert-danger mt-3 mb-0">
                            {{ $autoBidErrorMessage }}
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 pt-3">
                    <button type="button" class="btn btn-warning fw-bold text-dark" wire:click="closeCancelAutoBidModal">
                        Keep Auto Bid
                    </button>
                    <button type="button" class="btn btn-danger text-white fw-bold" wire:click="cancelAutoBid" wire:loading.attr="disabled" wire:target="cancelAutoBid">
                        <span wire:loading.remove wire:target="cancelAutoBid">Cancel Auto Bid</span>
                        <span wire:loading wire:target="cancelAutoBid">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Cancelling...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <script>
            document.addEventListener('livewire:initialized', () => {
                setInterval(() => {
                    const display = document.getElementById('ecc-countdown-display');
                    if (!display) return;

                    const endsAtRaw = display.getAttribute('data-ends-at');
                    if (!endsAtRaw) return;
                    
                    const now = new Date().getTime();
                    const end = new Date(endsAtRaw).getTime();
                    const distance = end - now;

                    if (distance < 0) {
                        if (display.innerText !== 'Ended') display.innerText = 'Ended';
                        return;
                    }

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    let text = '';
                    if (days > 0) text = `${days}d ${hours}h`;
                    else if (hours > 0) text = `${hours}h ${minutes}m`;
                    else text = `${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
                    
                    if (display.innerText !== text) display.innerText = text;
                }, 1000);
            });
        </script>
    @endpush
</div>
