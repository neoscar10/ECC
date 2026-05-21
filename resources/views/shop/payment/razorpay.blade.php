<x-layouts.web-app title="Secure Payment">
    <div class="ecc-container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">

                {{-- Loading / Auto-open panel --}}
                <div id="rzp-loading-panel" class="card shadow p-4" style="background: linear-gradient(180deg, #1a1509, #110e04); border: 1px solid rgba(199,167,90,.25); border-radius: 1rem;">
                    <div class="mb-3">
                        <svg width="40" height="28" viewBox="0 0 38 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity:.85">
                            <path d="M18.8 0L0 14.4H8.4L12 6.4L10.8 14.4H19.6L22.4 0H18.8Z" fill="#2D81EF"/>
                            <path d="M20.4 9.6L17.6 24H21.2L24.4 9.6H20.4Z" fill="#A9CAFF"/>
                            <path d="M22 9.6L25.6 0H22L20.4 9.6H22Z" fill="#2D81EF"/>
                        </svg>
                    </div>
                    <h3 style="color: #d4af37; font-weight:900; letter-spacing:-.02em;" class="mb-2">Secure Payment</h3>
                    <p class="mb-4" style="color: rgba(245,239,225,.55); font-size:.9rem;">
                        Opening Razorpay Checkout. Please do not refresh or press back.
                    </p>

                    <div class="spinner-border text-warning" role="status" id="loading-spinner">
                        <span class="visually-hidden">Loading...</span>
                    </div>

                    <button id="rzp-button" class="btn btn-warning w-100 mt-4 fw-bold" style="display: none; border-radius:.75rem; letter-spacing:.1em;">
                        OPEN PAYMENT CHECKOUT
                    </button>

                    <form id="verification-form" method="POST" action="{{ route('payments.razorpay.verify') }}" style="display: none;">
                        @csrf
                        <input type="hidden" name="internal_payment_id" value="{{ $payment->id }}">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                    </form>
                </div>

                {{-- Dismissed / Not Completed panel (hidden by default) --}}
                <div id="rzp-dismissed-panel" class="card shadow p-5" style="display:none; background: linear-gradient(180deg, #1a1509, #110e04); border: 1px solid rgba(199,167,90,.25); border-radius: 1rem;">
                    <i class="mdi mdi-alert-circle-outline mb-3" style="font-size: 52px; color: #d4af37;"></i>
                    <h3 style="color: #d4af37; font-weight:900;" class="mb-2">Payment Not Completed</h3>
                    <p style="color: rgba(245,239,225,.55); font-size:.9rem;" class="mb-4">
                        You closed the payment window. Your order has been reserved — no payment was taken.
                    </p>
                    <div class="d-flex flex-column gap-3">
                        <button id="rzp-retry-btn" class="btn btn-warning w-100 fw-bold" style="border-radius:.75rem; letter-spacing:.1em;">
                            RETRY PAYMENT
                        </button>
                        <a href="{{ route('shop.cart') }}" class="btn btn-outline-secondary w-100" style="border-radius:.75rem;">
                            Return to Cart
                        </a>
                    </div>
                </div>

                {{-- Verifying panel (shown after Razorpay success, while backend verify runs) --}}
                <div id="rzp-verifying-panel" class="card shadow p-5" style="display:none; background: linear-gradient(180deg, #1a1509, #110e04); border: 1px solid rgba(199,167,90,.25); border-radius: 1rem;">
                    <div class="spinner-border text-warning mb-3" role="status"></div>
                    <h4 style="color: #d4af37; font-weight:900;" class="mb-2">Verifying Payment…</h4>
                    <p style="color: rgba(245,239,225,.55); font-size:.9rem;" class="mb-0">
                        Please wait while we confirm your payment with Razorpay.
                    </p>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var checkoutData = @json($checkoutData);

            var loadingPanel   = document.getElementById('rzp-loading-panel');
            var dismissedPanel = document.getElementById('rzp-dismissed-panel');
            var verifyingPanel = document.getElementById('rzp-verifying-panel');
            var spinner        = document.getElementById('loading-spinner');
            var openBtn        = document.getElementById('rzp-button');
            var retryBtn       = document.getElementById('rzp-retry-btn');

            var options = {
                "key":         checkoutData.key,
                "amount":      checkoutData.amount,
                "currency":    checkoutData.currency,
                "name":        checkoutData.name,
                "description": checkoutData.description,
                "order_id":    checkoutData.order_id,
                "prefill":     checkoutData.prefill  || {},
                "notes":       checkoutData.notes    || {},
                "theme": { "color": "#d4af37" },

                "handler": function (response) {
                    // Show verifying panel
                    loadingPanel.style.display   = 'none';
                    dismissedPanel.style.display = 'none';
                    verifyingPanel.style.display = 'block';

                    fetch('{{ route("payments.razorpay.verify") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            internal_payment_id:  '{{ $payment->id }}',
                            razorpay_payment_id:  response.razorpay_payment_id,
                            razorpay_order_id:    response.razorpay_order_id,
                            razorpay_signature:   response.razorpay_signature
                        })
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success && data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.href = data.redirect_url || '{{ route("payments.failed") }}?payment_id={{ $payment->id }}';
                        }
                    })
                    .catch(function (err) {
                        console.error('Razorpay verification error', err);
                        window.location.href = '{{ route("payments.failed") }}?payment_id={{ $payment->id }}';
                    });
                },

                "modal": {
                    "ondismiss": function () {
                        // Show the friendly "not completed" panel — do NOT hard-redirect to failed.
                        loadingPanel.style.display   = 'none';
                        dismissedPanel.style.display = 'block';
                    }
                }
            };

            var rzp = new Razorpay(options);

            function openCheckout() {
                dismissedPanel.style.display = 'none';
                loadingPanel.style.display   = 'block';
                rzp.open();
            }

            // Fallback "Open" button handler
            openBtn.onclick = function (e) {
                e.preventDefault();
                openCheckout();
            };

            // Retry button in dismissed panel reopens the same Razorpay instance
            retryBtn.onclick = function (e) {
                e.preventDefault();
                openCheckout();
            };

            // Auto-open on page load
            rzp.open();

            // After 2 s show the manual trigger button in case popup was blocked
            setTimeout(function () {
                spinner.style.display = 'none';
                openBtn.style.display = 'block';
            }, 2000);
        });
    </script>
    @endpush
</x-layouts.web-app>
