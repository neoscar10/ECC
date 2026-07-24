<div>
    <div class="position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--ecc-surface), var(--ecc-bg-page)); padding: 3rem 0 1.5rem;">
        <div class="w-100 position-relative z-1">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-2" style="color: var(--ecc-primary);">Privacy Policy</h1>
                    <p class="lead mb-0" style="color: var(--ecc-text-secondary);">Your privacy and the security of your data are paramount to the Executive Club Cricket.</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <span class="badge px-3 py-2" style="background: rgba(199,167,90,0.15); color: var(--ecc-primary); border: 1px solid var(--ecc-primary-border); font-size: 0.85rem;">
                        Last Updated: {{ date('F j, Y') }}
                    </span>
                </div>
            </div>
        </div>
        <div class="position-absolute top-0 start-0" style="width: 400px; height: 400px; background: radial-gradient(circle, rgba(199,167,90,0.1) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; z-index: 0; pointer-events: none; transform: translate(-30%, -30%);"></div>
    </div>

    <div class="w-100" style="padding: 2rem 0 5rem;">
        <div class="row g-4 g-xl-5" style="color: var(--ecc-text-secondary); line-height: 1.8; font-size: 1.025rem;">
            {{-- Column 1 --}}
            <div class="col-lg-6">
                <div class="p-4 rounded-4 h-100" style="background: var(--ecc-surface); border: 1px solid var(--ecc-border-soft);">
                    <h4 class="fw-bold mb-3" style="color: var(--ecc-primary);">1. Introduction</h4>
                    <p class="mb-4">Welcome to the Executive Club Cricket (ECC). We are committed to protecting your privacy and providing a secure, premium experience for our members and guests. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website, apply for membership, or engage with our services, including our archive and vault.</p>

                    <h4 class="fw-bold mt-4 mb-3" style="color: var(--ecc-primary);">2. Information We Collect</h4>
                    <p>We may collect information about you in a variety of ways. The information we may collect includes:</p>
                    <ul class="mb-4 ps-3">
                        <li class="mb-2"><strong>Personal Data:</strong> Personally identifiable information, such as your name, shipping address, email address, and telephone number, demographic information, and interest in cricket memorabilia, that you voluntarily give to us when registering for a membership or applying for vault services.</li>
                        <li><strong>Financial Data:</strong> Data related to your payment method (e.g., valid credit card number, card brand, expiration date) collected during purchases or service requests. Financial info is stored securely by our payment processors (e.g., Razorpay, Cashfree).</li>
                    </ul>

                    <h4 class="fw-bold mt-4 mb-3" style="color: var(--ecc-primary);">3. Use of Your Information</h4>
                    <p>Having accurate information permits us to provide a smooth, efficient, and customized experience. Specifically, we may use information collected to:</p>
                    <ul class="mb-0 ps-3">
                        <li>Create and manage your account and membership tier.</li>
                        <li>Process your transactions and send purchase confirmations and invoices.</li>
                        <li>Manage your Vault access and item submissions.</li>
                        <li>Send notifications about exclusive upcoming auctions and rare archive additions.</li>
                        <li>Respond to customer service requests and support needs.</li>
                    </ul>
                </div>
            </div>

            {{-- Column 2 --}}
            <div class="col-lg-6">
                <div class="p-4 rounded-4 h-100 d-flex flex-column justify-content-between" style="background: var(--ecc-surface); border: 1px solid var(--ecc-border-soft);">
                    <div>
                        <h4 class="fw-bold mb-3" style="color: var(--ecc-primary);">4. Disclosure of Your Information</h4>
                        <p class="mb-4">We do not sell, trade, or rent your personal identification information to others. We may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners, trusted affiliates, and advertisers for the purposes outlined above.</p>

                        <h4 class="fw-bold mt-4 mb-3" style="color: var(--ecc-primary);">5. Security of Your Information</h4>
                        <p class="mb-4">We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable, and no method of data transmission can be guaranteed against any interception or other type of misuse.</p>
                    </div>

                    {{-- Contact Us Block --}}
                    <div class="mt-4 pt-4 border-top" style="border-color: var(--ecc-border-soft) !important;">
                        <h4 class="fw-bold mb-3" style="color: var(--ecc-primary);">6. Contact Us</h4>
                        <p class="mb-3">If you have questions or comments about this Privacy Policy, please contact us:</p>
                        
                        <div class="p-3 rounded-3" style="background: var(--ecc-bg-page); border: 1px solid var(--ecc-border-soft);">
                            <p class="mb-1 fw-bold" style="color: var(--ecc-text-primary);">Executive Club Cricket</p>
                            @if($contactConfig?->contact_address)
                                <p class="mb-2 fs-14" style="color: var(--ecc-text-secondary);">{!! nl2br(e($contactConfig->contact_address)) !!}</p>
                            @else
                                <p class="mb-2 fs-14" style="color: var(--ecc-text-secondary);">123 Heritage Lane, Cricket Avenue, London, UK SW1A 1AA</p>
                            @endif

                            <div class="d-flex flex-wrap gap-3 fs-14 mt-2 pt-2 border-top border-dashed" style="border-color: var(--ecc-border-soft) !important;">
                                @if($contactConfig?->support_email)
                                    <div>
                                        <span class="text-muted me-1">Email:</span>
                                        <a href="mailto:{{ $contactConfig->support_email }}" style="color: var(--ecc-primary); text-decoration: none;" class="fw-medium">
                                            {{ $contactConfig->support_email }}
                                        </a>
                                    </div>
                                @else
                                    <div>
                                        <span class="text-muted me-1">Email:</span>
                                        <a href="mailto:concierge@executivecricketclub.com" style="color: var(--ecc-primary); text-decoration: none;" class="fw-medium">
                                            concierge@executivecricketclub.com
                                        </a>
                                    </div>
                                @endif

                                @if($contactConfig?->concierge_phone)
                                    <div>
                                        <span class="text-muted me-1">Phone:</span>
                                        <a href="tel:{{ $contactConfig->concierge_phone }}" style="color: var(--ecc-primary); text-decoration: none;" class="fw-medium">
                                            {{ $contactConfig->concierge_phone }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
