<!-- App Information -->
<section id="app-information" class="pt-4 pt-lg-5 border-top ecc-settings-divider">
    <div class="mb-4">
        <h2 class="ecc-settings-section-title mb-2">The Gilded Archive</h2>
        <p class="ecc-settings-subtitle mb-0">
            System information and legal governance documentation.
        </p>
    </div>

    <div class="d-flex flex-column gap-3">
        @if($aboutUsUrl)
            <a href="{{ $aboutUsUrl }}" class="ecc-settings-info-link">
                <span>About Us</span>
                <span class="material-symbols-outlined">open_in_new</span>
            </a>
        @endif

        @if($termsUrl && $termsUrl !== '#')
            <a href="{{ $termsUrl }}" class="ecc-settings-info-link">
                <span>Terms &amp; Conditions</span>
                <span class="material-symbols-outlined">open_in_new</span>
            </a>
        @endif

        @if($membershipDetailsUrl)
            <a href="{{ $membershipDetailsUrl }}" class="ecc-settings-info-link">
                <span>Membership Details</span>
                <span class="material-symbols-outlined">open_in_new</span>
            </a>
        @endif

        @if($supportUrl && $supportUrl !== '#')
            <a href="{{ $supportUrl }}" class="ecc-settings-info-link">
                <span>Support</span>
                <span class="material-symbols-outlined">open_in_new</span>
            </a>
        @endif
    </div>
</section>
