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
          Application<br>Submitted
        </h1>

        <p class="ecc-success__p mb-0">
          Thank you for your interest in the Private Cricket Lounge. Our committee is currently reviewing your profile.
        </p>

        <div class="mx-auto ecc-success__divider"></div>

        <p class="ecc-success__p2 mb-0">
          You will receive a notification regarding your membership status within 24-48 hours.
        </p>
      </div>
    </div>
  </div>

  <div class="px-4 pb-5 pt-3 w-100 mx-auto position-relative z-2" style="max-width: 520px;">
    <a href="{{ url('/home') }}"
       class="ecc-success__btn d-flex align-items-center justify-content-center gap-2 text-decoration-none">
      <span class="text-truncate">Explore Limited Preview</span>
      <span class="material-symbols-outlined">arrow_forward</span>
    </a>
    {{-- Back to Login REMOVED by requirement --}}
  </div>
</div>

@push('styles')
<style>
  :root{
    --ecc-primary: #C7A75A;
    --ecc-gold: var(--ecc-primary);
    --ecc-gold-pale:#E5C568;
    --ecc-black:#0A0A0A;
    --ecc-gray:#1C1C1C;
  }

  .ecc-success{
    background: var(--ecc-black);
    color: var(--ecc-gold);
    font-family: "Manrope", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  }

  .ecc-success__grad{
    height: 66%;
    background: linear-gradient(to bottom, rgba(199, 167, 90,.10), rgba(0,0,0,0));
    pointer-events:none;
  }

  .ecc-success__tex{
    background-image: radial-gradient(circle at center, rgba(199, 167, 90, 0.08) 0%, transparent 80%);
    pointer-events:none;
  }

  .ecc-success__glow{
    width: 120px; height: 120px;
    border-radius: 9999px;
    background: rgba(199, 167, 90,.20);
    filter: blur(26px);
    transform: translate(-50%,-50%) scale(1.1);
    pointer-events:none;
  }

  .ecc-success__icon{
    width: 112px; height: 112px;
    border-radius: 9999px;
    border: 1px solid rgba(199, 167, 90,.40);
    background: rgba(28,28,28,.80);
    box-shadow: 0 30px 80px rgba(0,0,0,.60);
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
    color: var(--ecc-gold);
  }

  .ecc-success__p{
    font-size: 16px;
    color: rgba(229,197,104,.92);
    line-height: 1.7;
    opacity: .92;
    font-weight: 500;
  }

  .ecc-success__divider{
    height: 1px;
    width: 80px;
    background: rgba(199, 167, 90,.30);
    margin: 10px auto;
  }

  .ecc-success__p2{
    font-size: 13px;
    color: rgba(229,197,104,.85);
    line-height: 1.6;
    opacity: .85;
    font-weight: 600;
  }

  .ecc-success__btn{
    height: 56px;
    border-radius: 12px;
    border: 1px solid rgba(199, 167, 90,.50);
    background: transparent;
    color: var(--ecc-gold);
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
    background: rgba(199, 167, 90,.05);
    transition: background 220ms ease;
  }
  .ecc-success__btn > *{ position: relative; z-index: 1; }

  .ecc-success__btn:hover{
    border-color: rgba(199, 167, 90,1);
    transform: translateY(-1px);
  }
  .ecc-success__btn:hover::before{
    background: rgba(199, 167, 90,.10);
  }

  @media (min-width: 992px){
    .ecc-success__title{ font-size: 38px; }
  }
</style>
@endpush
