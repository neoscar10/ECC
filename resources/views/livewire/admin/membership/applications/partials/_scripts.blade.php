<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-view-modal', () => {
            var modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();
        });
        Livewire.on('open-approve-modal', () => {
            var modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        });
        Livewire.on('open-reject-modal', () => {
            var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
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
