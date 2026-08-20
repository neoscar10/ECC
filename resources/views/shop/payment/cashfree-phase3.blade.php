<x-dynamic-component :component="(isset($payment) && $payment->purpose === \App\Support\Payments\PaymentPurpose::ARCHIVE_ENQUIRY_PAYMENT) ? 'layouts.guest' : 'layouts.web-app'" title="Secure Payment — ECC">
    <div class="container py-5" style="max-width: 680px;">

        {{-- ═══════════════════════════════════════════════
             ORDER / PAYMENT SUMMARY HEADER
        ═══════════════════════════════════════════════ --}}
        <div class="mb-4 text-center">
            <div class="d-inline-flex align-items-center gap-2 mb-3" style="opacity:.7;">
                <span style="font-size:.7rem; letter-spacing:.2em; font-weight:800; color:rgba(245,239,225,.45); text-transform:uppercase;">Cashfree Secure Checkout</span>
            </div>

            <div class="p-4 mb-0" style="background: var(--ecc-bg-input); border: 1px solid var(--ecc-border); border-radius: 1rem;">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="text-start">
                        <div style="font-size:.7rem; letter-spacing:.15em; font-weight:800; color: var(--ecc-text-secondary); text-transform:uppercase; margin-bottom:.3rem;">
                            Payment
                        </div>
                        <div style="font-weight:800; color: var(--ecc-text-primary); font-size:1rem;">
                            {{ 'Payment #' . $payment->id }}
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:.7rem; letter-spacing:.15em; font-weight:800; color: var(--ecc-text-secondary); text-transform:uppercase; margin-bottom:.3rem;">Amount</div>
                        <div style="font-size:1.6rem; font-weight:900; color: var(--ecc-primary); letter-spacing:-.02em;">
                            ₹{{ number_format($payment->amount, 2) }}
                        </div>
                    </div>
                </div>

                <hr style="border-color: var(--ecc-border); margin: 1rem 0 .75rem;">

                <div class="d-flex justify-content-between gap-3" style="font-size:.78rem;">
                    <span style="color: var(--ecc-text-secondary); font-weight:700; letter-spacing:.08em; text-transform:uppercase;">Gateway</span>
                    <span style="color: var(--ecc-text-primary); font-weight:600;">Cashfree</span>
                </div>
                <div class="d-flex justify-content-between gap-3 mt-1" style="font-size:.78rem;">
                    <span style="color: var(--ecc-text-secondary); font-weight:700; letter-spacing:.08em; text-transform:uppercase;">Payment Status</span>
                    <span style="color: var(--ecc-primary); font-weight:700; letter-spacing:.06em; text-transform:uppercase;">
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════
             LOADING / AUTO-OPEN PANEL
        ═══════════════════════════════════════════════ --}}
        <div id="cf-loading-panel" class="text-center p-4" style="background: var(--ecc-bg-input); border: 1px solid var(--ecc-border); border-radius: 1rem;">
            <div class="spinner-border text-warning mb-3" role="status" id="loading-spinner" style="width:2.5rem;height:2.5rem;">
                <span class="visually-hidden">Loading…</span>
            </div>
            <h4 style="color: var(--ecc-primary); font-weight:900; letter-spacing:-.01em;" class="mb-1">Opening Secure Checkout</h4>
            <p style="color: var(--ecc-text-secondary); font-size:.85rem;" class="mb-4">
                Please do not refresh or press back while the payment window is loading.
            </p>
            <button id="cf-button" class="btn btn-warning w-100 fw-bold py-3" style="display:none; border-radius:.75rem; letter-spacing:.12em; font-size:.85rem;">
                <i class="mdi mdi-lock-outline me-2"></i>OPEN PAYMENT CHECKOUT
            </button>
        </div>

        {{-- ═══════════════════════════════════════════════
             DISMISSED / FAILED PANEL
        ═══════════════════════════════════════════════ --}}
        <div id="cf-error-panel" style="display:none;">
            <div class="text-center p-5" style="background: var(--ecc-bg-input); border: 1px solid var(--ecc-border); border-radius: 1rem;">
                <i class="mdi mdi-alert-circle-outline mb-3" style="font-size:52px; color: var(--ecc-primary); display:block;"></i>
                <h3 style="color: var(--ecc-primary); font-weight:900;" class="mb-2">Checkout Error</h3>
                <p id="cf-error-message" style="color: var(--ecc-text-secondary); font-size:.9rem;" class="mb-4">
                    Could not open secure checkout session.
                </p>
                <div class="d-flex flex-column gap-3">
                    @if($payment && $payment->purpose === \App\Support\Payments\PaymentPurpose::ARCHIVE_ENQUIRY_PAYMENT)
                        <a href="{{ route('home') }}" class="btn btn-outline-light">
                            <i class="mdi mdi-home-outline me-1"></i>Return to ECC
                        </a>
                    @else
                        <a href="{{ route('shop.orders') }}" class="btn btn-outline-light">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Orders
                        </a>
                    @endif
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var paymentSessionId = "{{ $paymentSessionId }}";
            var environment = "{{ $environment }}";
            
            var loadingPanel = document.getElementById('cf-loading-panel');
            var errorPanel = document.getElementById('cf-error-panel');
            var errorMsg = document.getElementById('cf-error-message');
            var openBtn = document.getElementById('cf-button');
            var spinner = document.getElementById('loading-spinner');

            if (!paymentSessionId) {
                loadingPanel.style.display = 'none';
                errorPanel.style.display = 'block';
                errorMsg.innerText = 'Payment session ID is missing or invalid.';
                return;
            }

            try {
                // Initialize Cashfree SDK v3
                const cashfree = Cashfree({
                    mode: environment === 'production' ? 'production' : 'sandbox'
                });

                function doCheckout() {
                    cashfree.checkout({
                        paymentSessionId: paymentSessionId,
                        redirectTarget: "_self"
                    });
                }

                // Trigger automatically on load
                doCheckout();

                openBtn.onclick = function (e) {
                    e.preventDefault();
                    doCheckout();
                };

                // Fallback manual open button after 3 seconds in case of popup block
                setTimeout(function () {
                    if (loadingPanel.style.display !== 'none') {
                        spinner.style.display = 'none';
                        openBtn.style.display = 'block';
                    }
                }, 3000);

            } catch (err) {
                console.error('Cashfree SDK initialization error:', err);
                loadingPanel.style.display = 'none';
                errorPanel.style.display = 'block';
                errorMsg.innerText = 'Failed to load secure payment script: ' + err.message;
            }
        });
    </script>
    @endpush
</x-dynamic-component>
