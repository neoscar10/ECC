<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-view-modal', () => {
            var modal = new bootstrap.Modal(document.getElementById('viewMemberModal'));
            modal.show();
        });
        Livewire.on('open-deactivate-modal', () => {
            var modal = new bootstrap.Modal(document.getElementById('deactivateModal'));
            modal.show();
        });
            Livewire.on('open-activate-modal', () => {
            var modal = new bootstrap.Modal(document.getElementById('activateModal'));
            modal.show();
        });
        Livewire.on('close-modals', () => {
            var modals = document.querySelectorAll('.modal.show');
            modals.forEach(function(modalEl) {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            });
        });
    });
</script>
