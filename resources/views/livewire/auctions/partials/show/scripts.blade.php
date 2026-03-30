@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        setInterval(() => {
            const display = document.getElementById('ecc-countdown-display');
            if (!display) return;

            const endsAtRaw = display.getAttribute('data-ends-at');
            if (!endsAtRaw) return;
            
            const now = new Date().getTime();
            const end = new Date(endsAtRaw).getTime();
            const distance = end - now;

            if (distance < 0) {
                if (display.innerText !== 'Ended') display.innerText = 'Ended';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            let text = '';
            if (days > 0) text = `${days}d ${hours}h`;
            else if (hours > 0) text = `${hours}h ${minutes}m`;
            else text = `${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
            
            if (display.innerText !== text) display.innerText = text;
        }, 1000);
    });
</script>
<script>
    (function () {
        function initAuctionDetailGallery() {
            const mainImage = document.getElementById('auctionMainImage');
            const thumbButtons = Array.from(document.querySelectorAll('.auction-thumb-btn'));
            const prevBtn = document.getElementById('auctionGalleryPrev');
            const nextBtn = document.getElementById('auctionGalleryNext');

            if (!mainImage || !thumbButtons.length) return;

            let currentIndex = thumbButtons.findIndex(btn => btn.classList.contains('active'));
            if (currentIndex < 0) currentIndex = 0;

            function activate(index) {
                const safeIndex = (index + thumbButtons.length) % thumbButtons.length;
                currentIndex = safeIndex;

                thumbButtons.forEach((btn, idx) => {
                    btn.classList.toggle('active', idx === safeIndex);
                });

                const src = thumbButtons[safeIndex].getAttribute('data-full-src');
                if (src) mainImage.setAttribute('src', src);
            }

            thumbButtons.forEach((btn, idx) => {
                btn.addEventListener('click', function () {
                    activate(idx);
                });
            });

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    activate(currentIndex - 1);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    activate(currentIndex + 1);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initAuctionDetailGallery);
        document.addEventListener('livewire:navigated', initAuctionDetailGallery);
        
        // Re-init on Livewire update in case images change
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', ({ el, component }) => {
                initAuctionDetailGallery();
            });
        }
    })();
</script>
@endpush
