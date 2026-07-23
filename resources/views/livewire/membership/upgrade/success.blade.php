<div class="ecc-success position-relative min-vh-100 overflow-hidden d-flex flex-column justify-content-between">
  {{-- gradient + texture --}}
  <div class="ecc-success__grad position-absolute top-0 start-0 end-0"></div>
  <div class="ecc-success__tex position-absolute top-0 start-0 end-0 bottom-0"></div>

  <div class="flex-none" style="height:40px;"></div>

  <div class="flex-grow-1 d-flex align-items-center justify-content-center px-4 position-relative z-2">
    <div class="text-center w-100" style="max-width: 360px;">
      <div class="mb-4 position-relative d-inline-block">
        <div class="ecc-success__glow position-absolute top-50 start-50 translate-middle"></div>
        <div class="ecc-success__icon mx-auto">
          <span class="material-symbols-outlined ecc-success__check">check_circle</span>
        </div>
      </div>

      <div class="d-flex flex-column gap-3">
        <h1 class="ecc-success__title mb-0">
          Membership<br>Upgraded
        </h1>

        <p class="ecc-success__p mb-0">
          Your membership has been successfully upgraded. You now have immediate access to your new tier benefits.
        </p>

        <div class="mx-auto ecc-success__divider"></div>

        <p class="ecc-success__p2 mb-0">
          Thank you for exploring the Private Cricket Lounge at the next level.
        </p>
      </div>
    </div>
  </div>

  <div class="px-4 pb-5 pt-3 w-100 mx-auto position-relative z-2" style="max-width: 520px;">
    <a href="{{ url('/archive') }}"
       class="ecc-success__btn d-flex align-items-center justify-content-center gap-2 text-decoration-none">
      <span class="text-truncate">Return to Archive</span>
      <span class="material-symbols-outlined">arrow_forward</span>
    </a>
  </div>
</div>

@push('styles')
<style>
  :root{
    --ecc-gold:var(--ecc-primary);
  }

  .ecc-success{
    background: var(--ecc-bg-page);
    color: var(--ecc-text-primary);
    font-family: "Manrope", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  }

  .ecc-success__grad{
    height: 66%;
    background: linear-gradient(to bottom, rgba(199, 167, 90,.10), transparent);
    pointer-events:none;
  }
  html[data-theme="light"] .ecc-success__grad{
    background: linear-gradient(to bottom, rgba(199, 167, 90,.03), transparent);
  }

  .ecc-success__tex{
    background-image: radial-gradient(circle at center, rgba(199, 167, 90, 0.08) 0%, transparent 80%);
    pointer-events:none;
  }
  html[data-theme="light"] .ecc-success__tex {
    background-image: radial-gradient(circle at center, rgba(199, 167, 90, 0.03) 0%, transparent 80%);
  }

  .ecc-success__glow{
    width: 120px; height: 120px;
    border-radius: 9999px;
    background: rgba(199, 167, 90,.20);
    filter: blur(26px);
    transform: translate(-50%,-50%) scale(1.1);
    pointer-events:none;
  }
  html[data-theme="light"] .ecc-success__glow {
    background: rgba(199, 167, 90,.06);
  }

  .ecc-success__icon{
    width: 112px; height: 112px;
    border-radius: 9999px;
    border: 1px solid var(--ecc-primary-border);
    background: var(--ecc-bg-surface-2);
    box-shadow: var(--ecc-shadow-card);
    display:flex; align-items:center; justify-content:center;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    position: relative;
  }

  .ecc-success__check{
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    font-size: 56px;
    color: var(--ecc-gold);
  }

  .ecc-success__title{
    font-size: 34px;
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.02em;
    color: var(--ecc-text-primary);
  }

  .ecc-success__p{
    font-size: 16px;
    color: var(--ecc-text-secondary);
    line-height: 1.7;
    font-weight: 500;
  }

  .ecc-success__divider{
    height: 1px;
    width: 80px;
    background: var(--ecc-primary-border);
    margin: 10px auto;
  }

  .ecc-success__p2{
    font-size: 13px;
    color: var(--ecc-text-secondary);
    line-height: 1.6;
    font-weight: 600;
  }

  .ecc-success__btn{
    height: 56px;
    border-radius: 12px;
    border: 1px solid var(--ecc-primary-border);
    background: var(--ecc-primary-soft);
    color: var(--ecc-primary);
    font-weight: 800;
    letter-spacing: .02em;
    box-shadow: 0 0 15px rgba(199, 167, 90,0.10);
    position: relative;
    overflow: hidden;
    transition: all 220ms ease;
  }

  .ecc-success__btn::before{
    content:"";
    position:absolute; inset:0;
    background: transparent;
    transition: background 220ms ease;
  }
  .ecc-success__btn > *{ position: relative; z-index: 1; }

  .ecc-success__btn:hover{
    border-color: var(--ecc-primary);
    transform: translateY(-1px);
    color: var(--ecc-primary);
  }
  .ecc-success__btn:hover::before{
    background: var(--ecc-primary-soft-2);
  }

  @media (min-width: 992px){
    .ecc-success__title{ font-size: 38px; }
  }
</style>
@endpush
