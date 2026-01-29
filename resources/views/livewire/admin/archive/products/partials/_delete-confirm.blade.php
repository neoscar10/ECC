<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true" wire:ignore.self data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-5 text-center">
                <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px"></lord-icon>
                <div class="mt-4 text-center">
                    <h4 class="fs-semibold">You are about to delete a product?</h4>
                    <p class="text-muted fs-14 mb-4 pt-1">Deleting this product will remove all of its information from the database.</p>
                    <div class="hstack gap-2 justify-content-center remove">
                        <button class="btn btn-link link-success fw-medium text-decoration-none" data-bs-dismiss="modal"><i class="ri-close-line me-1 align-middle"></i> Close</button>
                        <button class="btn btn-danger" wire:click="deleteConfirmed" wire:loading.attr="disabled">Yes, Delete It</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
