<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-view-modal', () => {
            setTimeout(() => {
                var modalEl = document.getElementById('viewModal');
                if (modalEl) {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            }, 100);
        });
        Livewire.on('open-approve-modal', () => {
            var modalEl = document.getElementById('approveModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });
        Livewire.on('open-reject-modal', () => {
            var modalEl = document.getElementById('rejectModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });
        Livewire.on('close-modals', () => {
            // Close all known modals
            var modals = document.querySelectorAll('.modal.show');
            modals.forEach(function(modalEl) {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            });
        });
    });
</script>
