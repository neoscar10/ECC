<x-layouts.web-app title="Secure Payment">
    <div class="ecc-container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card bg-dark text-white shadow p-4">
                    <h3 class="ecc-text-gold mb-3">Processing Payment</h3>
                    <p class="mb-4">Please do not refresh the page or click back while the payment gateway is loading.</p>
                    
                    <div class="spinner-border text-warning" role="status" id="loading-spinner">
                        <span class="visually-hidden">Loading...</span>
                    </div>

                    <button id="rzp-button" class="btn btn-warning w-100 mt-4" style="display: none;">Pay Now</button>
                    
                    <form id="verification-form" method="POST" action="{{ route('payments.razorpay.verify') }}" style="display: none;">
                        @csrf
                        <input type="hidden" name="internal_payment_id" value="{{ $payment->id }}">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var checkoutData = @json($checkoutData);
            
            var options = {
                "key": checkoutData.key,
                "amount": checkoutData.amount,
                "currency": checkoutData.currency,
                "name": checkoutData.name,
                "description": checkoutData.description,
                "order_id": checkoutData.order_id,
                "prefill": checkoutData.prefill || {},
                "notes": checkoutData.notes || {},
                "theme": {
                    "color": "#d4af37" // ECC Gold
                },
                "handler": function (response){
                    // Show spinner while verifying
                    document.getElementById('loading-spinner').style.display = 'inline-block';
                    document.getElementById('rzp-button').style.display = 'none';

                    // Verify on backend using fetch
                    fetch('{{ route("payments.razorpay.verify") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            internal_payment_id: '{{ $payment->id }}',
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_signature: response.razorpay_signature
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.href = data.redirect_url || '{{ route("payments.failed") }}';
                        }
                    })
                    .catch(err => {
                        console.error('Verification failed', err);
                        window.location.href = '{{ route("payments.failed") }}';
                    });
                },
                "modal": {
                    "ondismiss": function(){
                        // Redirect to failed page if modal closed
                        window.location.href = '{{ route("payments.failed") }}';
                    }
                }
            };
            
            var rzp = new Razorpay(options);
            
            // Auto open the Razorpay Checkout
            rzp.open();
            
            // In case it closes or gets blocked, show a button to manually trigger
            document.getElementById('rzp-button').onclick = function(e){
                rzp.open();
                e.preventDefault();
            }
            
            // Hide spinner and show button after a slight delay
            setTimeout(function() {
                document.getElementById('loading-spinner').style.display = 'none';
                document.getElementById('rzp-button').style.display = 'block';
            }, 2000);
        });
    </script>
    @endpush
</x-layouts.web-app>
