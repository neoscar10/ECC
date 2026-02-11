<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label">Content Type <span class="text-danger">*</span></label>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check card-radio">
                    <input id="type-banner" name="type" type="radio" value="banner" class="form-check-input" wire:model.live="type">
                    <label class="form-check-label p-3 w-100 text-center" for="type-banner">
                        <i class="ri-image-2-line fs-1 display-5 text-muted mb-2 d-block"></i>
                        <span class="fs-16 fw-semibold">Banner</span>
                        <span class="d-block text-muted fs-12 mt-1">Single image with overlay text</span>
                    </label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check card-radio">
                    <input id="type-slider" name="type" type="radio" value="slider" class="form-check-input" wire:model.live="type">
                    <label class="form-check-label p-3 w-100 text-center" for="type-slider">
                        <i class="ri-gallery-upload-line fs-1 display-5 text-muted mb-2 d-block"></i>
                        <span class="fs-16 fw-semibold">Slider</span>
                        <span class="d-block text-muted fs-12 mt-1">Carousel of items or images</span>
                    </label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check card-radio">
                    <input id="type-card" name="type" type="radio" value="card" class="form-check-input" wire:model.live="type">
                    <label class="form-check-label p-3 w-100 text-center" for="type-card">
                        <i class="ri-file-text-line fs-1 display-5 text-muted mb-2 d-block"></i>
                        <span class="fs-16 fw-semibold">Text Card</span>
                        <span class="d-block text-muted fs-12 mt-1">Simple text content card</span>
                    </label>
                </div>
            </div>
        </div>
        @error('type') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>

    @if($type === 'slider')
        <div class="col-md-12 mt-4">
            <label class="form-label">Slider Mode <span class="text-danger">*</span></label>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check card-radio">
                        <input id="mode-category" name="sliderMode" type="radio" value="category" class="form-check-input" wire:model.live="sliderMode">
                        <label class="form-check-label p-3 w-100" for="mode-category">
                            <span class="fs-14 fw-semibold d-block">Whole Category</span>
                            <span class="text-muted fs-12">Auto-populate from a category</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check card-radio">
                        <input id="mode-manual" name="sliderMode" type="radio" value="manual" class="form-check-input" wire:model.live="sliderMode">
                        <label class="form-check-label p-3 w-100" for="mode-manual">
                            <span class="fs-14 fw-semibold d-block">Manual Items</span>
                            <span class="text-muted fs-12">Select specific items</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check card-radio">
                        <input id="mode-images" name="sliderMode" type="radio" value="images" class="form-check-input" wire:model.live="sliderMode">
                        <label class="form-check-label p-3 w-100" for="mode-images">
                            <span class="fs-14 fw-semibold d-block">Image Slider</span>
                            <span class="text-muted fs-12">Upload custom slides</span>
                        </label>
                    </div>
                </div>
            </div>
            @error('sliderMode') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>
    @endif
</div>
