<!-- Contact Concierge Modal -->
<div class="modal fade {{ $showConciergeModal ? 'show d-block' : '' }}"
     tabindex="-1"
     role="dialog"
     @if($showConciergeModal) style="background: rgba(0,0,0,.75); z-index: 1060;" @else style="display:none;" @endif>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content ecc-concierge-modal">

            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="ecc-modal-kicker mb-2">PRIVATE MEMBER ENQUIRY</div>
                    <h5 class="ecc-modal-title mb-1">Contact Concierge</h5>
                    <p class="ecc-modal-subtitle mb-0">
                        Submit a premium support or acquisition enquiry and our concierge team will respond promptly.
                    </p>
                </div>

                <button type="button"
                        class="btn-close"
                        aria-label="Close"
                        wire:click="closeConciergeModal"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label ecc-form-label">Enquiry Subject</label>
                        <select class="form-select ecc-form-control @error('conciergeForm.subject') is-invalid @enderror"
                                wire:model="conciergeForm.subject">
                            <option value="membership_upgrade">Membership Upgrade</option>
                            <option value="dining_reservations">Dining & Event Reservations</option>
                            <option value="general_feedback">General Feedback</option>
                            <option value="other">Other Inquiry</option>
                        </select>
                        @error('conciergeForm.subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label ecc-form-label">Message</label>
                        <textarea rows="5"
                                  placeholder="How can we assist you today?"
                                  class="form-control ecc-form-control @error('conciergeForm.message') is-invalid @enderror"
                                  wire:model="conciergeForm.message"></textarea>
                        @error('conciergeForm.message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>


                @if ($conciergeSubmissionError)
                    <div class="alert alert-danger mt-4 mb-0">
                         {{ $conciergeSubmissionError }}
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0 pt-3 pb-4">
                <button type="button"
                        class="btn ecc-btn-outline-light px-4 py-2"
                        wire:click="closeConciergeModal">
                    Cancel
                </button>

                <button type="button"
                        class="btn ecc-btn-primary px-5 py-2"
                        wire:click="submitConciergeEnquiry"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submitConciergeEnquiry">Submit Enquiry</span>
                    <div wire:loading wire:target="submitConciergeEnquiry" class="spinner-border spinner-border-sm text-dark" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </button>
            </div>

        </div>
    </div>
</div>
