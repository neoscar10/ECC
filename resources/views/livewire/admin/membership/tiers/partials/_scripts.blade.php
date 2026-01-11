<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-init-modal', () => {
            var modal = new bootstrap.Modal(document.getElementById('initModal'));
            modal.show();
        });
        Livewire.on('open-delete-modal', () => {
            var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
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
