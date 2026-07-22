<div>
    <div class="position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--ecc-surface), var(--ecc-bg-page)); padding: 6rem 0 4rem;">
        <div class="luxe-container position-relative z-1">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3" style="color: var(--ecc-primary);">Contact Us</h1>
                    <p class="lead" style="color: var(--ecc-text-secondary);">We are here to assist you with any inquiries regarding memberships, our vault, or the archive.</p>
                </div>
            </div>
        </div>
        <div class="position-absolute top-0 start-50 translate-middle" style="width: 600px; height: 600px; background: radial-gradient(circle, rgba(199,167,90,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; z-index: 0; pointer-events: none;"></div>
    </div>

    <div class="luxe-container" style="padding: 5rem 0;">
        <div class="row g-5">
            <div class="col-lg-5">
                <div class="pe-lg-4">
                    <h3 class="fw-bold mb-4" style="color: var(--ecc-text-primary);">Get in Touch</h3>
                    <p class="mb-5" style="color: var(--ecc-text-secondary); line-height: 1.8;">
                        Whether you're looking to acquire a rare piece of cricket history, inquire about our secure vault services, or have questions regarding your executive membership, our dedicated team is at your service.
                    </p>

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 48px; height: 48px; background: rgba(199, 167, 90, 0.1); color: var(--ecc-primary);">
                            <span class="material-symbols-outlined">location_on</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1" style="color: var(--ecc-text-primary);">Headquarters</h5>
                            <p class="mb-0" style="color: var(--ecc-text-secondary);">123 Heritage Lane, Cricket Avenue<br>London, UK SW1A 1AA</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 48px; height: 48px; background: rgba(199, 167, 90, 0.1); color: var(--ecc-primary);">
                            <span class="material-symbols-outlined">mail</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1" style="color: var(--ecc-text-primary);">Email Support</h5>
                            <p class="mb-0" style="color: var(--ecc-text-secondary);">concierge@executivecricketclub.com</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 48px; height: 48px; background: rgba(199, 167, 90, 0.1); color: var(--ecc-primary);">
                            <span class="material-symbols-outlined">call</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1" style="color: var(--ecc-text-primary);">Phone</h5>
                            <p class="mb-0" style="color: var(--ecc-text-secondary);">+44 (0) 20 7946 0123</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm" style="background: var(--ecc-surface); border-radius: 16px;">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="fw-bold mb-4" style="color: var(--ecc-text-primary);">Send a Message</h4>

                        @if($successMessage)
                            <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="background: rgba(40, 167, 69, 0.1); border-color: rgba(40, 167, 69, 0.2); color: #28a745;">
                                <span class="material-symbols-outlined me-2">check_circle</span>
                                <div>Thank you! Your message has been sent successfully. We will get back to you shortly.</div>
                            </div>
                        @endif

                        <form wire:submit.prevent="submit">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold" style="color: var(--ecc-text-primary);">Full Name</label>
                                    <input type="text" wire:model="name" class="form-control" placeholder="John Doe" style="background-color: var(--ecc-bg-input); border-color: var(--ecc-border); color: var(--ecc-text-primary); padding: 0.75rem 1rem;">
                                    @error('name') <span class="text-danger small mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold" style="color: var(--ecc-text-primary);">Email Address</label>
                                    <input type="email" wire:model="email" class="form-control" placeholder="john@example.com" style="background-color: var(--ecc-bg-input); border-color: var(--ecc-border); color: var(--ecc-text-primary); padding: 0.75rem 1rem;">
                                    @error('email') <span class="text-danger small mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold" style="color: var(--ecc-text-primary);">Subject (Optional)</label>
                                    <input type="text" wire:model="subject" class="form-control" placeholder="How can we help?" style="background-color: var(--ecc-bg-input); border-color: var(--ecc-border); color: var(--ecc-text-primary); padding: 0.75rem 1rem;">
                                    @error('subject') <span class="text-danger small mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold" style="color: var(--ecc-text-primary);">Message</label>
                                    <textarea wire:model="message" class="form-control" rows="5" placeholder="Write your message here..." style="background-color: var(--ecc-bg-input); border-color: var(--ecc-border); color: var(--ecc-text-primary); padding: 0.75rem 1rem;"></textarea>
                                    @error('message') <span class="text-danger small mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="luxe-btn d-inline-flex align-items-center gap-2" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="submit">Send Message</span>
                                        <span wire:loading wire:target="submit">Sending...</span>
                                        <span class="material-symbols-outlined" wire:loading.remove wire:target="submit">send</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
