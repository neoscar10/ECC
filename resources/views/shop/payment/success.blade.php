<x-dynamic-component :component="request()->is('archive/enquiry/*') ? 'layouts.guest' : 'layouts.web-app'" :title="$title ?? 'Payment Successful'">
    @push('styles')
    <style>
        .party-card {
            background: linear-gradient(135deg, #16192b 0%, #0e101c 100%);
            border: 2px solid #08a88a;
            box-shadow: 0 15px 35px rgba(8, 168, 138, 0.25);
            border-radius: 1.5rem;
            animation: bounceInUp 0.85s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
            position: relative;
            overflow: hidden;
        }

        .party-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(8, 168, 138, 0.08) 0%, transparent 70%);
            transform: translate(-10%, -10%);
            pointer-events: none;
        }

        /* Success Checkmark Animation */
        .success-icon-wrap {
            width: 90px;
            height: 90px;
            background: rgba(8, 168, 138, 0.15);
            border: 3px solid #08a88a;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.75rem;
            color: #08a88a;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(8, 168, 138, 0.4);
            animation: pulseGlow 2s infinite, scalePop 0.6s 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.2) both;
            transition: transform 0.2s;
        }
        
        .success-icon-wrap:hover {
            transform: scale(1.1) rotate(15deg);
        }

        .success-icon-wrap i {
            font-size: 42px;
        }

        /* Text gradient */
        .celebrate-title {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(to right, #08a88a 0%, #10e0b0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.025em;
            margin-bottom: 1rem;
            animation: textFocus 0.8s 0.3s both;
        }

        /* Button styling */
        .party-btn {
            background: linear-gradient(135deg, #08a88a 0%, #067c66 100%) !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 999px !important;
            padding: 14px 36px !important;
            font-weight: 700 !important;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.82rem;
            box-shadow: 0 8px 20px rgba(8, 168, 138, 0.35) !important;
            transition: all 0.3s ease !important;
        }

        .party-btn:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 25px rgba(8, 168, 138, 0.5) !important;
        }

        /* Animations */
        @keyframes bounceInUp {
            0% {
                opacity: 0;
                transform: translateY(120px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes pulseGlow {
            0% {
                box-shadow: 0 0 0 0 rgba(8, 168, 138, 0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(8, 168, 138, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(8, 168, 138, 0);
            }
        }

        @keyframes scalePop {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        @keyframes textFocus {
            0% {
                filter: blur(8px);
                opacity: 0;
            }
            100% {
                filter: blur(0);
                opacity: 1;
            }
        }
    </style>
    @endpush

    <div class="ecc-container py-5 d-flex align-items-center" style="min-height: 80vh;">
        <div class="row justify-content-center w-100 m-0">
            <div class="col-md-6 col-lg-5 text-center">
                
                <div class="card party-card text-white p-5">
                    
                    {{-- Interactive Success Icon --}}
                    <div class="success-icon-wrap" id="celebrateIcon" title="Click for more celebration!">
                        <i class="mdi mdi-check-bold"></i>
                    </div>

                    <h2 class="celebrate-title">{{ $title ?? 'Payment Successful' }}</h2>
                    
                    <p class="mb-4 text-muted fs-15 lh-lg" style="color: rgba(245, 239, 225, 0.7) !important;">
                        {{ $message ?? 'Thank you for your payment. Your transaction has been successfully processed.' }}
                    </p>

                    <div class="d-flex justify-content-center mt-4">
                        <a href="{{ $redirectUrl ?? route('home') }}" class="btn party-btn">
                            {{ $redirectText ?? 'Continue' }}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Function to launch confetti burst
            function launchConfetti() {
                var duration = 4 * 1000;
                var end = Date.now() + duration;

                (function frame() {
                    // Left side burst
                    confetti({
                        particleCount: 4,
                        angle: 60,
                        spread: 55,
                        origin: { x: 0, y: 0.7 },
                        colors: ['#08a88a', '#10e0b0', '#ffc107', '#fd7e14']
                    });
                    
                    // Right side burst
                    confetti({
                        particleCount: 4,
                        angle: 120,
                        spread: 55,
                        origin: { x: 1, y: 0.7 },
                        colors: ['#08a88a', '#10e0b0', '#ffc107', '#fd7e14']
                    });

                    if (Date.now() < end) {
                        requestAnimationFrame(frame);
                    }
                }());
            }

            // Trigger immediately on load
            launchConfetti();

            // Re-trigger when clicking the checkmark icon
            var icon = document.getElementById('celebrateIcon');
            if (icon) {
                icon.addEventListener('click', function() {
                    // Instant heavy burst
                    confetti({
                        particleCount: 150,
                        spread: 80,
                        origin: { y: 0.65 },
                        colors: ['#08a88a', '#10e0b0', '#ffc107', '#fd7e14', '#20c997']
                    });
                });
            }
        });
    </script>
    @endpush
</x-dynamic-component>
