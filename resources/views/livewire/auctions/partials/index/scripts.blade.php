<script>
    (function () {
        function initLuxeRail() {
            const rail = document.getElementById('liveAuctionRail');
            const prev = document.getElementById('liveAuctionPrev');
            const next = document.getElementById('liveAuctionNext');

            if (!rail || !prev || !next) return;
            
            // Remove old listeners to avoid duplicates on Livewire refresh
            const newPrev = prev.cloneNode(true);
            const newNext = next.cloneNode(true);
            prev.parentNode.replaceChild(newPrev, prev);
            next.parentNode.replaceChild(newNext, next);

            const scrollAmount = 420;

            newPrev.addEventListener('click', function () {
                rail.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });

            newNext.addEventListener('click', function () {
                rail.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
        }

        document.addEventListener('DOMContentLoaded', initLuxeRail);
        document.addEventListener('livewire:navigated', initLuxeRail);
        document.addEventListener('livewire:navigating', () => { /* optional cleanup */ });
        
        // Handle Livewire V3 DOM updates
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', ({ el, component }) => {
                initLuxeRail();
            });
        }
    })();
</script>
