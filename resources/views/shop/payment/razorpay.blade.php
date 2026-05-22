<x-layouts.web-app title="Secure Payment — ECC">
    <div class="container py-5" style="max-width: 680px;">

        {{-- ═══════════════════════════════════════════════
             ORDER / PAYMENT SUMMARY HEADER
        ═══════════════════════════════════════════════ --}}
        <div class="mb-4 text-center">
            <div class="d-inline-flex align-items-center gap-2 mb-3" style="opacity:.7;">
                <svg width="22" height="16" viewBox="0 0 38 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.8 0L0 14.4H8.4L12 6.4L10.8 14.4H19.6L22.4 0H18.8Z" fill="#2D81EF"/>
                    <path d="M20.4 9.6L17.6 24H21.2L24.4 9.6H20.4Z" fill="#A9CAFF"/>
                    <path d="M22 9.6L25.6 0H22L20.4 9.6H22Z" fill="#2D81EF"/>
                </svg>
                <span style="font-size:.7rem; letter-spacing:.2em; font-weight:800; color:rgba(245,239,225,.45); text-transform:uppercase;">Razorpay Secure Checkout</span>
            </div>

            <div class="p-4 mb-0" style="background: linear-gradient(180deg, #1a1509, #110e04); border: 1px solid rgba(199,167,90,.2); border-radius: 1rem;">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="text-start">
                        <div style="font-size:.7rem; letter-spacing:.15em; font-weight:800; color:rgba(245,239,225,.4); text-transform:uppercase; margin-bottom:.3rem;">
                            {{ strtoupper($payableSummary['label']) }}
                        </div>
                        <div style="font-weight:800; color:rgba(245,239,225,.9); font-size:1rem;">
                            {{ $payableSummary['reference'] ?? ('Payment #' . $payment->id) }}
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:.7rem; letter-spacing:.15em; font-weight:800; color:rgba(245,239,225,.4); text-transform:uppercase; margin-bottom:.3rem;">Amount</div>
                        <div style="font-size:1.6rem; font-weight:900; color:#d4af37; letter-spacing:-.02em;">
                            {{ $payableSummary['display_amount'] }}
                        </div>
                    </div>
                </div>

                <hr style="border-color: rgba(199,167,90,.12); margin: 1rem 0 .75rem;">

                <div class="d-flex justify-content-between gap-3" style="font-size:.78rem;">
                    <span style="color:rgba(245,239,225,.4); font-weight:700; letter-spacing:.08em; text-transform:uppercase;">Gateway</span>
                    <span style="color:rgba(245,239,225,.65); font-weight:600;">Razorpay</span>
                </div>
                <div class="d-flex justify-content-between gap-3 mt-1" style="font-size:.78rem;">
                    <span style="color:rgba(245,239,225,.4); font-weight:700; letter-spacing:.08em; text-transform:uppercase;">Payment Status</span>
                    <span style="color: #f0c040; font-weight:700; letter-spacing:.06em; text-transform:uppercase;">
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════
             TEST MODE HELPER (only shown in test / non-prod)
        ═══════════════════════════════════════════════ --}}
        @if($isTestMode)
        <div class="mb-4 p-4" style="background: rgba(45,129,239,.07); border: 1px solid rgba(45,129,239,.25); border-radius:.85rem;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="mdi mdi-flask-outline" style="color:#7db9ff; font-size:1.1rem;"></i>
                <span style="font-size:.72rem; font-weight:800; letter-spacing:.18em; color:#7db9ff; text-transform:uppercase;">Test Mode Payment Help</span>
            </div>
            <div style="font-size:.82rem; color:rgba(245,239,225,.65); line-height:1.75;">
                <div class="mb-1">
                    <span style="color:rgba(245,239,225,.4);">✅ Success test:</span>
                    Choose <strong style="color:rgba(245,239,225,.85);">UPI</strong> and enter
                    <code style="background:rgba(255,255,255,.07); padding:.1rem .4rem; border-radius:.3rem; color:#a9e34b;">success@razorpay</code>
                </div>
                <div class="mb-1">
                    <span style="color:rgba(245,239,225,.4);">❌ Failure test:</span>
                    Choose <strong style="color:rgba(245,239,225,.85);">UPI</strong> and enter
                    <code style="background:rgba(255,255,255,.07); padding:.1rem .4rem; border-radius:.3rem; color:#ff8e99;">failure@razorpay</code>
                </div>
                <div style="font-size:.75rem; color:rgba(245,239,225,.35); margin-top:.5rem;">
                    Card payments may fail in test mode due to Razorpay test account restrictions. UPI is the recommended method for testing.
                </div>
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════
             LOADING / AUTO-OPEN PANEL
        ═══════════════════════════════════════════════ --}}
        <div id="rzp-loading-panel" class="text-center p-4" style="background: linear-gradient(180deg, #1a1509, #110e04); border: 1px solid rgba(199,167,90,.2); border-radius: 1rem;">
            <div class="spinner-border text-warning mb-3" role="status" id="loading-spinner" style="width:2.5rem;height:2.5rem;">
                <span class="visually-hidden">Loading…</span>
            </div>
            <h4 style="color:#d4af37; font-weight:900; letter-spacing:-.01em;" class="mb-1">Opening Secure Checkout</h4>
            <p style="color:rgba(245,239,225,.45); font-size:.85rem;" class="mb-4">
                Please do not refresh or press back while the payment window is loading.
            </p>
            <button id="rzp-button" class="btn btn-warning w-100 fw-bold py-3" style="display:none; border-radius:.75rem; letter-spacing:.12em; font-size:.85rem;">
                <i class="mdi mdi-lock-outline me-2"></i>OPEN PAYMENT CHECKOUT
            </button>

            {{-- Hidden form for legacy fallback (not used in fetch flow) --}}
            <form id="verification-form" method="POST" action="{{ route('payments.razorpay.verify') }}" style="display:none;">
                @csrf
                <input type="hidden" name="internal_payment_id" value="{{ $payment->id }}">
                <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                <input type="hidden" name="razorpay_order_id"   id="razorpay_order_id">
                <input type="hidden" name="razorpay_signature"  id="razorpay_signature">
            </form>
        </div>

        {{-- ═══════════════════════════════════════════════
             DISMISSED — PAYMENT NOT COMPLETED PANEL
        ═══════════════════════════════════════════════ --}}
        <div id="rzp-dismissed-panel" style="display:none;">
            <div class="text-center p-5" style="background: linear-gradient(180deg, #1a1509, #110e04); border: 1px solid rgba(199,167,90,.2); border-radius: 1rem;">
                <i class="mdi mdi-alert-circle-outline mb-3" style="font-size:52px; color:#d4af37; display:block;"></i>
                <h3 style="color:#d4af37; font-weight:900;" class="mb-2">Payment Not Completed</h3>
                <p style="color:rgba(245,239,225,.5); font-size:.9rem;" class="mb-4">
                    You closed the payment window. Your order is reserved — <strong style="color:rgba(245,239,225,.7);">no payment was taken</strong>.
                    You can retry or return to your cart.
                </p>
                <div class="d-flex flex-column gap-3">
                    <button id="rzp-retry-btn" class="btn btn-warning w-100 fw-bold py-3" style="border-radius:.75rem; letter-spacing:.12em; font-size:.85rem;">
                        <i class="mdi mdi-refresh me-2"></i>RETRY PAYMENT
                    </button>
                    <a href="{{ route('shop.cart') }}" class="btn py-2" style="border-radius:.75rem; border:1px solid rgba(245,239,225,.15); color:rgba(245,239,225,.55); font-size:.85rem;">
                        Return to Cart
                    </a>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════
             VERIFYING PANEL (after Razorpay success callback)
        ═══════════════════════════════════════════════ --}}
        <div id="rzp-verifying-panel" style="display:none;">
            <div class="text-center p-5" style="background: linear-gradient(180deg, #1a1509, #110e04); border: 1px solid rgba(199,167,90,.2); border-radius: 1rem;">
                <div class="spinner-border text-warning mb-3" role="status" style="width:2.5rem;height:2.5rem;"></div>
                <h4 style="color:#d4af37; font-weight:900;" class="mb-2">Verifying Payment…</h4>
                <p style="color:rgba(245,239,225,.45); font-size:.85rem;" class="mb-0">
                    Confirming your payment with Razorpay. Please wait.
                </p>
            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Checkout payload (key_id only — never key_secret) ──
            var checkoutData = @json($checkoutData);

            var loadingPanel   = document.getElementById('rzp-loading-panel');
            var dismissedPanel = document.getElementById('rzp-dismissed-panel');
            var verifyingPanel = document.getElementById('rzp-verifying-panel');
            var spinner        = document.getElementById('loading-spinner');
            var openBtn        = document.getElementById('rzp-button');
            var retryBtn       = document.getElementById('rzp-retry-btn');

            // ── Razorpay Standard Checkout Options ──────────────────
            var options = {
                // Public key only — secret never sent to frontend
                "key":      checkoutData.key,
                "amount":   checkoutData.amount,       // paise
                "currency": checkoutData.currency,     // INR

                "name":        checkoutData.name,
                "description": checkoutData.description,

                // Gateway order ID created server-side
                "order_id": checkoutData.order_id,

                // Prefill from authenticated user (best-effort, user can change)
                "prefill": checkoutData.prefill || {},

                // Internal tracking notes (visible in Razorpay dashboard)
                "notes": checkoutData.notes || {},

                // ECC brand colour
                "theme": { "color": "#d4af37" },

                // ── Tokenization / Saved-card: DISABLED ─────────────
                // Prevents saved-card/recurring flows in test and live mode.
                // Set to false to avoid tokenization prompts and card-save UX.
                "remember_customer": false,

                // ── Payment success handler ──────────────────────────
                "handler": function (response) {
                    loadingPanel.style.display   = 'none';
                    dismissedPanel.style.display = 'none';
                    verifyingPanel.style.display = 'block';

                    // POST to backend verify — backend is the sole source of truth
                    fetch('{{ route("payments.razorpay.verify") }}', {
                        method:  'POST',
                        headers: {
                            'Content-Type':  'application/json',
                            'X-CSRF-TOKEN':  '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            internal_payment_id: '{{ $payment->id }}',
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id:   response.razorpay_order_id,
                            razorpay_signature:  response.razorpay_signature
                        })
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        // Redirect wherever backend says — success or failed
                        window.location.href = data.redirect_url
                            || '{{ route("payments.failed") }}?payment_id={{ $payment->id }}';
                    })
                    .catch(function (err) {
                        console.error('Razorpay verify fetch error:', err);
                        window.location.href = '{{ route("payments.failed") }}?payment_id={{ $payment->id }}';
                    });
                },

                // ── Modal dismiss handler ────────────────────────────
                // Do NOT redirect to failed — order stays unpaid, user can retry.
                "modal": {
                    "ondismiss": function () {
                        loadingPanel.style.display   = 'none';
                        dismissedPanel.style.display = 'block';
                    },
                    // Allow closing via escape / backdrop click (default true)
                    "escape":    true,
                    "backdropclose": false
                }
            };

            var rzp = new Razorpay(options);

            // ── Open helpers ─────────────────────────────────────────
            function openCheckout() {
                dismissedPanel.style.display = 'none';
                loadingPanel.style.display   = 'block';
                rzp.open();
            }

            openBtn.onclick   = function (e) { e.preventDefault(); openCheckout(); };
            retryBtn.onclick  = function (e) { e.preventDefault(); openCheckout(); };

            // ── Auto-open on page load ───────────────────────────────
            rzp.open();

            // After 2.5 s show manual open button (fallback for popup blockers)
            setTimeout(function () {
                if (loadingPanel.style.display !== 'none') {
                    spinner.style.display = 'none';
                    openBtn.style.display = 'block';
                }
            }, 2500);
        });
    </script>
    @endpush
</x-layouts.web-app>
