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
                    <div class="form-text text-muted mt-2">Recommended: 1080 &times; 1080</div>
                </label>
            </div>
            @error('newImages.*') <span class="text-danger text-sm d-block mt-2">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="row g-3" id="shop-existing-images">
            {{-- Existing Images (Edit Mode) --}}
            @foreach($existingImages as $img)
                <div class="col-6 col-sm-4 col-md-3" data-id="{{ $img->id }}" wire:key="ex-img-{{ $img->id }}">
                    <div class="position-relative border rounded overflow-hidden group-preview">
                        <img src="{{ Storage::url($img->image_path) }}" class="img-fluid w-100 cursor-move" style="height: 150px; object-fit: cover;">
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 m-1 shadow-sm"
                            wire:click="removeExistingImage({{ $img->id }})">
                            <i class="ri-close-line"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-2 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.5));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0" wire:click.prevent="moveImage({{ $img->id }}, 'up')">
                                    <i class="ri-arrow-left-s-line fs-18"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0" wire:click.prevent="moveImage({{ $img->id }}, 'down')">
                                    <i class="ri-arrow-right-s-line fs-18"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mt-1" id="shop-new-images">
            {{-- New Temporary Images --}}
            @foreach($newImages as $index => $img)
                <div class="col-6 col-sm-4 col-md-3" data-index="{{ $index }}" wire:key="new-img-{{ $index }}">
                    <div class="position-relative border rounded overflow-hidden group-preview">
                        @try
                            <img src="{{ $img->temporaryUrl() }}" class="img-fluid w-100 cursor-move" style="height: 150px; object-fit: cover;">
                        @catch(\Exception $e)
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                <i class="ri-image-line fs-24 text-muted"></i>
                            </div>
                        @endtry
                        
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 m-1 shadow-sm"
                            wire:click="removeNewImage({{ $index }})">
                            <i class="ri-close-line"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-2 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.5));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0" wire:click.prevent="moveNewImage({{ $index }}, 'up')">
                                    <i class="ri-arrow-left-s-line fs-18"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0" wire:click.prevent="moveNewImage({{ $index }}, 'down')">
                                    <i class="ri-arrow-right-s-line fs-18"></i>
                                </button>
                            @endif
                        </div>
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
