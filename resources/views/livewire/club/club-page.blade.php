@php
  $h = $vm['header'] ?? [];
  $priv = $vm['privileges'] ?? [];
  $concierge = $vm['concierge'] ?? [];
  $dossier = $vm['auction_dossier'] ?? [];
  $urls = $vm['urls'] ?? [];
@endphp

<div class="ecc-club-page-content">
  <main class="pb-5">
    {{-- Profile header --}}
    {{-- Profile header --}}
    <section class="pt-4 px-2 px-sm-3 position-relative overflow-hidden">
      <div class="ecc-radial-glow" aria-hidden="true"></div>

      <div class="d-flex flex-column align-items-center text-center gap-3 position-relative">
        <div class="ecc-avatar-ring">
          <div class="ecc-avatar" style="background-image:url('{{ $h['avatar_url'] ?? '' }}')"></div>
          @if(($h['is_verified'] ?? false) === true)
            <div class="ecc-verified">
              <span class="material-symbols-outlined" style="font-size:18px;">verified</span>
            </div>
          @endif
        </div>

        <div>
          <h1 class="ecc-name mb-1">{{ $h['member_name'] ?? 'Member' }}</h1>
          <div class="d-flex justify-content-center align-items-center gap-2">
            <span class="material-symbols-outlined text-ecc" style="font-size:16px;">workspace_premium</span>
            <div class="ecc-tier text-uppercase">{{ $h['tier_name'] ?? 'Membership Tier' }}</div>
          </div>
          <div class="ecc-member-meta mt-2">
            Member ID: {{ $h['member_id'] ?? '—' }} • Valid thru {{ $h['valid_thru'] ?? '—' }}
          </div>
        </div>
      </div>
    </section>

    {{-- Privileges --}}
    <section class="mt-4 px-1 px-sm-2">
      <div class="d-flex align-items-center justify-content-between px-2 mb-2">
        <h3 class="ecc-section-title mb-0">Your Privileges</h3>
        @if(!empty($urls['privileges_all']))
          <a class="ecc-link-sm text-uppercase" href="{{ $urls['privileges_all'] }}">View All</a>
        @else
          <span class="ecc-link-sm text-uppercase opacity-50">View All</span>
        @endif
      </div>

      <div class="row g-3">
        @foreach($priv as $p)
          <div class="col-6">
            <div class="ecc-priv-card h-100">
              <div class="ecc-priv-watermark">
                <span class="material-symbols-outlined">{{ $p['icon'] ?? 'stars' }}</span>
              </div>

              <div class="ecc-priv-icon">
                <span class="material-symbols-outlined">{{ $p['icon'] ?? 'stars' }}</span>
              </div>

              <div class="mt-2">
                <div class="ecc-priv-title">{{ $p['title'] ?? '' }}</div>
                <div class="ecc-priv-sub">{{ $p['subtitle'] ?? '' }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </section>

    {{-- Concierge Ledger --}}
    <section class="mt-4 px-1 px-sm-2">
      <h3 class="ecc-section-title px-2 mb-3">Concierge Ledger</h3>

      <div class="d-flex flex-column gap-3">
        @foreach($concierge as $c)
          <a href="{{ $c['url'] ?? '#' }}" class="ecc-ledger-item text-decoration-none">
            <div class="d-flex align-items-center gap-3">
              <div class="ecc-ledger-ic">
                <span class="material-symbols-outlined">{{ $c['icon'] ?? 'assignment' }}</span>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="ecc-ledger-title text-truncate">{{ $c['title'] ?? '' }}</div>
                <div class="ecc-ledger-meta">{{ $c['meta'] ?? '' }}</div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              @php
                $st = strtolower($c['status'] ?? '');
                $badgeClass = $st === 'completed' ? 'ecc-badge--green' : ($st === 'processing' ? 'ecc-badge--gold' : 'ecc-badge--muted');
              @endphp
              <span class="ecc-badge {{ $badgeClass }}">{{ $c['status_label'] ?? strtoupper($st) }}</span>
              <span class="material-symbols-outlined ecc-chevron">chevron_right</span>
            </div>
          </a>
        @endforeach
      </div>
    </section>

    {{-- Auction Dossier --}}
    <section class="mt-4 px-1 px-sm-2 mb-5">
      <h3 class="ecc-section-title px-2 mb-3">Auction Dossier</h3>

      <div class="ecc-timeline">
        @foreach($dossier as $i => $a)
          <div class="ecc-timeline-row {{ $i > 0 ? 'is-dim' : '' }}">
            <div class="ecc-dot"></div>

            <a href="{{ $a['url'] ?? '#' }}" class="ecc-dossier-card text-decoration-none">
              <div class="ecc-thumb" style="background-image:url('{{ $a['thumb_url'] ?? '' }}')"></div>

              <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="ecc-dossier-title text-truncate">{{ $a['title'] ?? '' }}</div>
                  @php $badge = strtolower($a['badge'] ?? ''); @endphp
                  <div class="ecc-mini-badge {{ $badge === 'won' ? 'won' : ($badge === 'outbid' ? 'outbid' : 'neutral') }}">
                    {{ $a['badge_label'] ?? strtoupper($badge) }}
                  </div>
                </div>

                <div class="ecc-dossier-meta">{{ $a['meta'] ?? '' }}</div>

                @if(!empty($a['substatus_label']))
                  <div class="ecc-dossier-sub mt-2">
                    <span class="material-symbols-outlined" style="font-size:16px;">check_circle</span>
                    <span>{{ $a['substatus_label'] }}</span>
                  </div>
                @endif
              </div>
            </a>
          </div>
        @endforeach
      </div>
    </section>

  </main>
</div>

@push('styles')
<style>
  .ecc-radial-glow{
    position:absolute;
    top: -30px; left: 50%;
    transform: translateX(-50%);
    width: 120%;
    height: 220px;
    background: radial-gradient(circle at top, rgba(242,185,13,.16), transparent 70%);
    opacity: .85;
    pointer-events:none;
  }

  .ecc-avatar-ring{
    position: relative;
    padding: 6px;
    border-radius: 9999px;
    border: 1px solid rgba(242,185,13,.40);
    box-shadow: 0 0 20px rgba(242,185,13,.18);
  }
  .ecc-avatar{
    width: 112px; height: 112px;
    border-radius: 9999px;
    background-color: #111;
    background-size: cover;
    background-position: center;
    border: 2px solid #050505;
  }
  .ecc-verified{
    position:absolute;
    right: 2px; bottom: 2px;
    width: 34px; height: 34px;
    display:flex; align-items:center; justify-content:center;
    border-radius: 9999px;
    background: var(--ecc-primary);
    color: #000;
    border: 4px solid #050505;
    box-shadow: 0 12px 30px rgba(0,0,0,.45);
  }

  .ecc-name{
    font-size: 26px;
    font-weight: 900;
    letter-spacing: -.02em;
    background: linear-gradient(to bottom, #f2b90d, #ffeebb);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin: 0;
  }
  .text-ecc{ color: #f2b90d; }
  .ecc-tier{
    color: #f2b90d;
    font-size: 12px;
    letter-spacing: .10em;
    font-weight: 700;
  }
  .ecc-member-meta{
    color: rgba(242,185,13,.55);
    font-size: 12px;
    font-weight: 600;
  }

  .ecc-section-title{
    font-size: 18px;
    font-weight: 800;
    letter-spacing: .04em;
    margin: 0;
  }
  .ecc-link-sm{
    font-size: 11px;
    letter-spacing: .14em;
    font-weight: 800;
    color: rgba(242,185,13,.75);
    text-decoration: none;
  }
  .ecc-link-sm:hover{ color: #f2b90d; text-decoration: underline; }

  .ecc-priv-card{
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    padding: 14px;
    border: 1px solid rgba(242,185,13,.20);
    background: linear-gradient(180deg, rgba(242,185,13,.08) 0%, rgba(0,0,0,0) 100%);
    transition: border-color 180ms ease, transform 180ms ease;
  }
  .ecc-priv-card:hover{ border-color: rgba(242,185,13,.45); transform: translateY(-1px); }

  .ecc-priv-watermark{
    position:absolute; top: 8px; right: 10px;
    opacity: .10;
    pointer-events:none;
  }
  .ecc-priv-watermark .material-symbols-outlined{ font-size: 44px; color: #f2b90d; }

  .ecc-priv-icon{
    width: 40px; height: 40px;
    border-radius: 9999px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(242,185,13,.10);
    color: #f2b90d;
    transition: all 180ms ease;
  }
  .ecc-priv-card:hover .ecc-priv-icon{
    background: #f2b90d;
    color: #000;
  }

  .ecc-priv-title{ font-size: 13px; font-weight: 700; color: #fffce1; }
  .ecc-priv-sub{ font-size: 12px; color: rgba(242,185,13,.50); margin-top: 4px; }

  .ecc-ledger-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 14px;
    padding: 14px;
    border-radius: 14px;
    background: #0f0f0f;
    border: 1px solid rgba(242,185,13,.10);
    transition: border-color 180ms ease, transform 180ms ease;
    color: #fffce1;
  }
  .ecc-ledger-item:hover{ border-color: rgba(242,185,13,.28); transform: translateY(-1px); }

  .ecc-ledger-ic{
    width: 42px; height: 42px;
    border-radius: 12px;
    background: rgba(242,185,13,.10);
    display:flex; align-items:center; justify-content:center;
    color: #f2b90d;
  }
  .ecc-ledger-title{ font-size: 14px; font-weight: 700; }
  .ecc-ledger-meta{ font-size: 12px; color: rgba(242,185,13,.50); margin-top: 2px; }

  .ecc-badge{
    font-size: 10px;
    letter-spacing: .12em;
    font-weight: 900;
    text-transform: uppercase;
    padding: 6px 10px;
    border-radius: 10px;
    border: 1px solid transparent;
  }
  .ecc-badge--green{ color: rgba(110,231,183,.95); background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.18); }
  .ecc-badge--gold{ color: #f2b90d; background: rgba(242,185,13,.10); border-color: rgba(242,185,13,.18); }
  .ecc-badge--muted{ color: rgba(242,185,13,.60); background: rgba(242,185,13,.06); border-color: rgba(242,185,13,.12); }

  .ecc-chevron{ color: rgba(242,185,13,.25); transition: color 160ms ease; }
  .ecc-ledger-item:hover .ecc-chevron{ color: rgba(242,185,13,.60); }

  .ecc-timeline{
    position: relative;
    margin-left: 10px;
    padding-left: 16px;
    border-left: 1px solid rgba(242,185,13,.12);
    display:flex;
    flex-direction:column;
    gap: 14px;
  }
  .ecc-timeline-row{
    position: relative;
    padding-left: 10px;
  }
  .ecc-timeline-row.is-dim{ opacity: .82; }
  .ecc-dot{
    position:absolute;
    left: -22px;
    top: 10px;
    width: 10px; height: 10px;
    border-radius: 9999px;
    background: rgba(242,185,13,.22);
  }
  .ecc-timeline-row:first-child .ecc-dot{
    background: #f2b90d;
    box-shadow: 0 0 10px rgba(242,185,13,.70);
  }

  .ecc-dossier-card{
    display:flex;
    gap: 12px;
    padding: 12px;
    border-radius: 14px;
    background: #0f0f0f;
    border: 1px solid rgba(242,185,13,.10);
    color: #fffce1;
    transition: border-color 180ms ease, transform 180ms ease;
  }
  .ecc-dossier-card:hover{ border-color: rgba(242,185,13,.28); transform: translateY(-1px); }

  .ecc-thumb{
    width: 64px; height: 64px;
    border-radius: 10px;
    background: #222;
    background-size: cover;
    background-position: center;
    flex-shrink: 0;
  }

  .ecc-dossier-title{ font-size: 14px; font-weight: 800; }
  .ecc-dossier-meta{ font-size: 12px; color: rgba(242,185,13,.50); margin-top: 4px; }

  .ecc-mini-badge{
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .10em;
    text-transform: uppercase;
    padding: 4px 8px;
    border-radius: 10px;
    white-space: nowrap;
  }
  .ecc-mini-badge.won{ color: #f2b90d; }
  .ecc-mini-badge.outbid{ color: rgba(248,113,113,.95); }
  .ecc-mini-badge.neutral{ color: rgba(242,185,13,.60); }

  .ecc-dossier-sub{
    display:flex;
    align-items:center;
    gap: 6px;
    font-size: 10px;
    letter-spacing: .10em;
    font-weight: 800;
    color: #f2b90d;
    text-transform: uppercase;
  }
</style>
@endpush
