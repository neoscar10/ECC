@if($showAccessModal && $modalData)
  <div class="modal fade show" id="premiumAccessModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
      <div class="modal-content" style="background: var(--ecc-bg-surface); border: 1px solid var(--ecc-border-soft); border-radius: 16px; box-shadow: 0 20px 50px var(--ecc-shadow);">
        <div class="modal-body p-4 p-md-5 position-relative text-center">
          {{-- Close Button --}}
          <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-4" aria-label="Close" wire:click="closeAccessModal" onclick="document.getElementById('premiumAccessModal').style.display='none'" style="opacity: 0.8;"></button>

          {{-- Icon --}}
          <div class="ecc-lock-icon mx-auto mb-4" style="width: 64px; height: 64px; background: rgba(199, 167, 90, 0.1); border: 1px solid var(--ecc-primary-border); display: flex; align-items: center; justify-content: center; border-radius: 50%;">
            <span class="material-symbols-outlined" style="color: var(--ecc-primary); font-size: 32px;">
              @if($modalData['icon'] === 'time-lock') lock_clock
              @elseif($modalData['icon'] === 'diamond') diamond
              @else lock
              @endif
            </span>
          </div>

          {{-- Title --}}
          <h3 class="mb-2" style="color: var(--ecc-text-primary); font-weight: 800; letter-spacing: -0.01em;">Unlock Premium Access</h3>
          <p style="color: var(--ecc-text-secondary); font-size: 14px; margin-bottom: 30px;">
            You need <strong>{{ $modalData['tier_name'] }}</strong> membership tier to access this item.
          </p>

          {{-- Required Tier Card --}}
          <div class="ecc-tier-card text-start p-4 mb-4" style="background: var(--ecc-bg-surface-2); border: 1px solid var(--ecc-border-soft); border-radius: 12px;">
            <div class="mb-4">
              <h4 style="color: var(--ecc-primary); font-size: 16px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">{{ $modalData['tier_name'] }}</h4>
              <div class="text-end">
                @if(!empty($modalData['is_prorated']) && $modalData['is_prorated'])
                  {{-- User has an active membership — show the prorated breakdown --}}
                  <div style="color: var(--ecc-text-secondary); font-size: 13px; text-decoration: line-through; font-weight: 400; line-height: 1.2;">{{ $modalData['price_formatted'] }} / {{ $modalData['duration_label'] }}</div>
                  <div style="display: flex; align-items: center; gap: 6px; justify-content: flex-end; margin-top: 3px;">
                    <span style="background: rgba(74,222,128,0.12); color: #4ade80; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 20px; letter-spacing: 0.04em; white-space: nowrap;">- {{ $modalData['credit_formatted'] }} credit</span>
                  </div>
                  <div style="color: var(--ecc-text-primary); font-size: 22px; font-weight: 900; margin-top: 4px; line-height: 1.1;">{{ $modalData['payable_formatted'] }}</div>
                  <div style="color: #4ade80; font-size: 10px; font-weight: 600; letter-spacing: 0.04em; margin-top: 1px;">Your upgrade price</div>
                @else
                  {{-- No active membership — full price --}}
                  <span style="color: var(--ecc-text-primary); font-size: 18px; font-weight: 800;">{{ $modalData['price_formatted'] }}</span>
                  <span style="color: var(--ecc-text-secondary); font-size: 11px; text-transform: uppercase;">/ {{ $modalData['duration_label'] }}</span>
                @endif
              </div>
            </div>


            <div class="row text-start mt-2">
              @php 
                $hasFeatures = !empty($modalData['features']); 
                $colClass = $hasFeatures ? 'col-6' : 'col-12';
              @endphp
              <div class="{{ $colClass }}">
                {{-- Tier Privileges --}}
                @if(!empty($modalData['privileges']))
                  <div>
                    <h5 style="color: var(--ecc-text-primary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Privileges</h5>
                    <ul class="list-unstyled m-0" style="color: var(--ecc-text-secondary); font-size: 13px;">
                      @foreach(array_slice($modalData['privileges'], 0, 5) as $privilege)
                        <li class="d-flex align-items-start mb-2">
                          <span class="material-symbols-outlined me-2 mt-1" style="color: var(--ecc-primary); font-size: 16px; flex-shrink: 0;">check_circle</span>
                          <span>{{ $privilege['label'] ?? $privilege['name'] ?? '' }}</span>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endif
              </div>

              @if($hasFeatures)
              <div class="col-6">
                {{-- Premium Features --}}
                <div class="mb-3">
                  <h5 style="color: var(--ecc-text-primary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Key Features</h5>
                  <ul class="list-unstyled m-0" style="color: var(--ecc-text-secondary); font-size: 13px;">
                    @foreach(array_slice($modalData['features'], 0, 5) as $feature)
                      <li class="d-flex align-items-start mb-2">
                        <span class="material-symbols-outlined me-2 mt-1" style="color: var(--ecc-primary); font-size: 16px; flex-shrink: 0;">star</span>
                        <span>{{ $feature['feature'] ?? '' }}</span>
                      </li>
                    @endforeach
                  </ul>
                </div>
              </div>
              @endif
            </div>
          </div>

          {{-- CTA --}}
          <button class="btn w-100 py-3 mb-3 fw-bold text-uppercase" wire:click="proceedToSubscribe" onclick="document.getElementById('premiumAccessModal').style.display='none'" style="background: var(--ecc-primary); color: var(--ecc-text-primary); border: none; border-radius: 8px; letter-spacing: 0.05em; box-shadow: 0 4px 15px var(--ecc-primary-border); transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 6px 20px var(--ecc-primary-border)';" onmouseout="this.style.boxShadow='0 4px 15px var(--ecc-primary-border)';">
            @auth
              @if(auth()->user()->hasActiveMembership())
                Upgrade Membership
              @else
                Proceed to Subscribe
              @endif
            @else
              Proceed to Subscribe
            @endauth
          </button>
          <button class="btn btn-link text-decoration-none p-0" wire:click="closeAccessModal" onclick="document.getElementById('premiumAccessModal').style.display='none'" style="color: #cbbc90; font-size: 13px;">Not now, return</button>
        </div>
      </div>
    </div>
  </div>
@endif
