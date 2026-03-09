<script>
    (function() {
        // Safe getOrCreateInstance that doesn't throw if component uninitialized
        const safeHide = (el, Component) => {
            try {
                if (window.bootstrap && bootstrap[Component]) {
                    const instance = bootstrap[Component].getInstance(el) || bootstrap[Component].getOrCreateInstance(el);
                    if (instance) instance.hide();
                }
            } catch (e) {
                // Ignore initialization errors during cleanup
            }
        };

        const cleanupOverlays = () => {
            // 1. Remove stuck backdrops directly
            document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop, .swal2-container').forEach(el => el.remove());

            // 2. Hide any open modals that think they are open
            document.querySelectorAll('.modal.show').forEach(el => safeHide(el, 'Modal'));

            // 3. Hide any open offcanvas
            document.querySelectorAll('.offcanvas.show').forEach(el => safeHide(el, 'Offcanvas'));

            // 4. Force reset body state
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.overflow = '';
        };

        // Hook to standard page lifecycle events
        window.addEventListener('DOMContentLoaded', cleanupOverlays);
        window.addEventListener('pageshow', cleanupOverlays); // Handles bfcache

        // Hook to Livewire navigation events
        document.addEventListener('livewire:navigated', cleanupOverlays);

        // Optional: Catch leftover overlays after Livewire requests process (e.g. form submits)
        document.addEventListener('livewire:initialized', () => {
            try {
                if (window.Livewire) {
                    Livewire.hook('message.processed', () => cleanupOverlays());
                }
            } catch (e) {}
        });
    })();
</script>
