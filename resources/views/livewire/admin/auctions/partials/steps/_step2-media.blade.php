<div class="row g-3">
    <!-- Product Images -->
    <div class="col-12">
        <label class="form-label fw-bold">1. Main Lot Images <span class="text-danger">*</span></label>
        
        <!-- Existing Images -->
        @if(count($existingImages) > 0)
            <div class="d-flex gap-2 flex-wrap mb-2">
                @foreach($existingImages as $img)
                    <div class="position-relative" style="width: 80px; height: 80px;">
                        <img src="{{ Storage::url($img['path']) }}" class="img-fluid rounded w-100 h-100 object-cover border">
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle" 
                                style="width: 20px; height: 20px; min-width: 20px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="deleteImage({{ $img['id'] }}, 'main')">
                            <i class="ri-close-line" style="font-size: 12px;"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Upload Input -->
        <div class="input-group">
            <input type="file" class="form-control" multiple wire:model="newImages" id="mainImageInput" accept="image/*">
            <label class="input-group-text" for="mainImageInput">Upload</label>
        </div>
        <div class="d-flex align-items-center mt-1">
             <i class="ri-upload-cloud-2-line text-muted me-1"></i>
             <span class="text-muted fs-11">Max 10MB per file.</span>
        </div>
        @error('newImages') <span class="text-danger text-sm d-block mt-1">{{ $message }}</span> @enderror

        <!-- Previews -->
        @if ($newImages)
            <div class="d-flex gap-2 mt-2 overflow-auto custom-scrollbar">
                @foreach ($newImages as $i => $tempImg)
                     <div class="avatar-md bg-white border rounded p-1 flex-shrink-0 position-relative" wire:key="new-main-{{ $i }}">
                         <img src="{{ $tempImg->temporaryUrl() }}" class="img-fluid rounded h-100 object-cover">
                         <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle" 
                                style="width: 20px; height: 20px; min-width: 20px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="removeNewImage({{ $i }})">
                            <i class="ri-close-line" style="font-size: 12px;"></i>
                        </button>
                     </div>
                @endforeach
            </div>
        @endif
    </div>
    
    <!-- 360 Images -->
    <div class="col-12 border-top pt-3">
        <label class="form-label fw-bold">2. 360° View Images (Optional)</label>

        @if(count($existing360Images) > 0)
            <div class="d-flex gap-2 flex-wrap mb-2">
                @foreach($existing360Images as $img)
                     <div class="position-relative" style="width: 80px; height: 80px;">
                        <img src="{{ Storage::url($img['path']) }}" class="img-fluid rounded w-100 h-100 object-cover border">
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle" 
                                style="width: 20px; height: 20px; min-width: 20px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="deleteImage({{ $img['id'] }}, '360')">
                            <i class="ri-close-line" style="font-size: 12px;"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="input-group">
            <input type="file" class="form-control" multiple wire:model="new360Images" id="360ImageInput" accept="image/*">
             <label class="input-group-text" for="360ImageInput">Upload 360°</label>
        </div>
        <div class="d-flex align-items-center mt-1">
             <i class="ri-360-line text-muted me-1"></i>
             <span class="text-muted fs-11">Upload sequential images.</span>
        </div>
        @error('new360Images') <span class="text-danger text-sm d-block mt-1">{{ $message }}</span> @enderror

         @if ($new360Images)
            <div class="d-flex gap-2 mt-2 overflow-auto custom-scrollbar">
                @foreach ($new360Images as $i => $tempImg)
                     <div class="avatar-md bg-white border rounded p-1 flex-shrink-0 position-relative" wire:key="new-360-{{ $i }}">
                         <img src="{{ $tempImg->temporaryUrl() }}" class="img-fluid rounded h-100 object-cover">
                         <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle" 
                                style="width: 20px; height: 20px; min-width: 20px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="removeNew360Image({{ $i }})">
                            <i class="ri-close-line" style="font-size: 12px;"></i>
                        </button>
                     </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
