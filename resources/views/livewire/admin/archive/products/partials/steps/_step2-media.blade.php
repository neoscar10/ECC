<div class="row g-3">
    <!-- Product Images -->
    <div class="col-12">
        <label class="form-label fw-bold">1. Main Product Images <span class="text-danger">*</span></label>
        
        <!-- Existing Images -->
        @if(count($existingImages) > 0)
            <div id="existing-main-images" class="d-flex gap-2 flex-wrap mb-3">
                @foreach($existingImages as $img)
                    <div class="position-relative" style="width: 70px; height: 70px;" wire:key="ex-img-{{ $img->id }}" data-id="{{ $img->id }}">
                        <img src="{{ Storage::url(str_replace('\\', '/', $img->image_path)) }}" class="img-fluid rounded w-100 h-100 object-cover border cursor-move">
                        
                        <div class="position-absolute top-0 end-0 d-flex flex-column gap-1" style="transform: translate(30%, -30%);">
                            <button type="button" class="btn btn-danger btn-icon rounded-circle shadow-sm" 
                                    style="width: 18px; height: 18px; padding: 0;"
                                    wire:click.prevent.stop="deleteImage({{ $img->id }}, 'main')">
                                <i class="ri-close-line" style="font-size: 11px;"></i>
                            </button>
                        </div>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-1 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.4));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveImage({{ $img->id }}, 'up', 'main')">
                                    <i class="ri-arrow-left-s-line" style="font-size: 14px;"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveImage({{ $img->id }}, 'down', 'main')">
                                    <i class="ri-arrow-right-s-line" style="font-size: 14px;"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Compact Upload -->
        <div class="input-group">
            <input type="file" class="form-control" multiple wire:model="newImages" id="mainImageInput" accept="image/png,image/jpeg">
            <label class="input-group-text" for="mainImageInput">Upload</label>
        </div>
        <div class="d-flex align-items-center mt-1">
             <i class="ri-upload-cloud-2-line text-muted me-1"></i>
             <span class="text-muted fs-11">Max 10MB per file. <span class="text-info fw-semibold ms-2"><i class="mdi mdi-information-outline me-1"></i>Recommended: 1080 &times; 1080</span></span>
        </div>
        @error('newImages') <span class="text-danger text-sm d-block mt-1">{{ $message }}</span> @enderror

        <!-- Loading Indicator -->
        <div wire:loading wire:target="newImages" class="mt-2">
            <div class="d-flex align-items-center gap-2 text-primary fs-12">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <span>Uploading images...</span>
            </div>
        </div>

        @if ($newImages)
            <div id="new-main-images" class="d-flex gap-2 mt-2 pb-2 overflow-auto custom-scrollbar">
                @foreach ($newImages as $i => $tempImg)
                     <div class="avatar-lg bg-white border rounded p-1 flex-shrink-0 position-relative" style="width: 70px; height: 70px;" wire:key="new-main-{{ $i }}" data-index="{{ $i }}">
                         <img src="{{ $tempImg->temporaryUrl() }}" class="img-fluid rounded h-100 w-100 object-cover cursor-move">
                         <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm" 
                                style="width: 18px; height: 18px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="removeNewImage({{ $i }})">
                            <i class="ri-close-line" style="font-size: 11px;"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-1 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.4));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveNewImage({{ $i }}, 'up', 'main')">
                                    <i class="ri-arrow-left-s-line" style="font-size: 14px;"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveNewImage({{ $i }}, 'down', 'main')">
                                    <i class="ri-arrow-right-s-line" style="font-size: 14px;"></i>
                                </button>
                            @endif
                        </div>
                     </div>
                @endforeach
            </div>
        @endif
    </div>
    
    <!-- 360 Images -->
    <div class="col-12 border-top pt-3">
        <label class="form-label fw-bold">2. 360° View Images (Optional)</label>

        <!-- Existing 360 Images -->
        @if(count($existing360Images) > 0)
            <div id="existing-360-images" class="d-flex gap-2 flex-wrap mb-3">
                @foreach($existing360Images as $img)
                     <div class="position-relative" style="width: 70px; height: 70px;" wire:key="ex-360-{{ $img->id }}" data-id="{{ $img->id }}">
                        <img src="{{ Storage::url(str_replace('\\', '/', $img->image_path)) }}" class="img-fluid rounded w-100 h-100 object-cover border cursor-move">
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm" 
                                style="width: 18px; height: 18px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="deleteImage({{ $img->id }}, '360')">
                            <i class="ri-close-line" style="font-size: 11px;"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-1 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.4));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveImage({{ $img->id }}, 'up', '360')">
                                    <i class="ri-arrow-left-s-line" style="font-size: 14px;"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveImage({{ $img->id }}, 'down', '360')">
                                    <i class="ri-arrow-right-s-line" style="font-size: 14px;"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Compact Upload -->
        <div class="input-group">
            <input type="file" class="form-control" multiple wire:model="new360Images" id="360ImageInput" accept="image/png,image/jpeg">
             <label class="input-group-text" for="360ImageInput">Upload 360°</label>
        </div>
        <div class="d-flex align-items-center mt-1">
             <i class="ri-360-line text-muted me-1"></i>
             <span class="text-muted fs-11">Upload sequential images. Recommended: 1080 &times; 1080</span>
        </div>
        @error('new360Images') <span class="text-danger text-sm d-block mt-1">{{ $message }}</span> @enderror

        <!-- Loading Indicator -->
        <div wire:loading wire:target="new360Images" class="mt-2">
            <div class="d-flex align-items-center gap-2 text-info fs-12">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <span>Uploading 360° images...</span>
            </div>
        </div>

         @if ($new360Images)
            <div id="new-360-images" class="d-flex gap-2 mt-2 pb-2 overflow-auto custom-scrollbar">
                @foreach ($new360Images as $i => $tempImg)
                     <div class="avatar-lg bg-white border rounded p-1 flex-shrink-0 position-relative" style="width: 70px; height: 70px;" wire:key="new-360-{{ $i }}" data-index="{{ $i }}">
                         <img src="{{ $tempImg->temporaryUrl() }}" class="img-fluid rounded h-100 w-100 object-cover cursor-move">
                         <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm" 
                                style="width: 18px; height: 18px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="removeNew360Image({{ $i }})">
                            <i class="ri-close-line" style="font-size: 11px;"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-1 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.4));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveNewImage({{ $i }}, 'up', '360')">
                                    <i class="ri-arrow-left-s-line" style="font-size: 14px;"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveNewImage({{ $i }}, 'down', '360')">
                                    <i class="ri-arrow-right-s-line" style="font-size: 14px;"></i>
                                </button>
                            @endif
                        </div>
                     </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

