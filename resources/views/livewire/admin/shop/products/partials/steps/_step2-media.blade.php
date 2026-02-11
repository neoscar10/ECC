<div class="row g-4">
    <div class="col-12">
        <div class="p-3 border border-dashed rounded text-center bg-light">
            <i class="ri-upload-cloud-2-line fs-24 text-muted"></i>
            <h5 class="mt-2 mb-1">Product Gallery</h5>
            <p class="text-muted mb-3">Upload high-quality images for the product.</p>
            
            <div class="d-flex justify-content-center">
                <label class="btn btn-primary btn-sm position-relative overflow-hidden me-2">
                    <span wire:loading.remove wire:target="newImages">Add Images</span>
                    <span wire:loading wire:target="newImages">Processing...</span>
                    <input type="file" wire:model="newImages" multiple accept="image/png,image/jpeg" class="opacity-0 position-absolute start-0 top-0 h-100 w-100 cursor-pointer">
                </label>
            </div>
            @error('newImages.*') <span class="text-danger text-sm d-block mt-2">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="row g-3">
            {{-- Existing Images (Edit Mode) --}}
            @foreach($existingImages as $img)
                <div class="col-6 col-sm-4 col-md-3">
                    <div class="position-relative border rounded overflow-hidden group-preview">
                        <img src="{{ Storage::url($img->image_path) }}" class="img-fluid w-100" style="height: 150px; object-fit: cover;">
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 m-1 shadow-sm"
                            wire:click="removeExistingImage({{ $img->id }})">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>
            @endforeach

            {{-- New Temporary Images --}}
            @foreach($newImages as $index => $img)
                <div class="col-6 col-sm-4 col-md-3">
                    <div class="position-relative border rounded overflow-hidden group-preview">
                        <img src="{{ $img->temporaryUrl() }}" class="img-fluid w-100" style="height: 150px; object-fit: cover;">
                        
                        {{-- Loading Overlay for this image (if re-uploading logic existed, but basic temp URL is instant mostly) --}}
                        
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 m-1 shadow-sm"
                            wire:click="removeNewImage({{ $index }})">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        
        @if(count($existingImages) === 0 && count($newImages) === 0)
            <div class="text-center py-5 text-muted">
                No images added yet.
            </div>
        @endif
    </div>
</div>
