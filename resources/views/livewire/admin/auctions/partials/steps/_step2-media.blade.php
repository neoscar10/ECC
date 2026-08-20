<div class="row g-3">
    <!-- Product Images -->
    <div class="col-12">
        <label class="form-label fw-bold">1. Main Lot Images <span class="text-danger">*</span></label>
        
        <!-- Existing Images -->
        @if(count($existingImages) > 0)
            <div id="auction-existing-main" class="d-flex gap-2 flex-wrap mb-3">
                @foreach($existingImages as $img)
                    <div class="position-relative" style="width: 80px; height: 80px;" data-id="{{ $img['id'] }}" wire:key="ex-img-{{ $img['id'] }}">
                        <img src="{{ Storage::url($img['path']) }}" class="img-fluid rounded w-100 h-100 object-cover border cursor-move">
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm" 
                                style="width: 20px; height: 20px; min-width: 20px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="deleteImage({{ $img['id'] }}, 'main')">
                            <i class="ri-close-line" style="font-size: 12px;"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-1 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.5));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveImage({{ $img['id'] }}, 'up', 'main')">
                                    <i class="ri-arrow-left-s-line" style="font-size: 16px;"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveImage({{ $img['id'] }}, 'down', 'main')">
                                    <i class="ri-arrow-right-s-line" style="font-size: 16px;"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Upload Input -->
        <div class="input-group">
            <input type="file" class="form-control" multiple wire:model="newImages" id="mainImageInput" accept="image/png,image/jpeg">
            
            <label class="input-group-text" for="mainImageInput">Upload</label>
        </div>
        <div class="d-flex align-items-center mt-1">
             <i class="ri-upload-cloud-2-line text-muted me-1"></i>
             <span class="text-muted fs-11">Max 10MB per file. <span class="text-info fw-semibold ms-2"><i class="mdi mdi-information-outline me-1"></i>Recommended: 1080 &times; 1080</span></span>
        </div>
        @error('newImages') <span class="text-danger text-sm d-block mt-1">{{ $message }}</span> @enderror

        <!-- Previews -->
        @if ($newImages)
            <div id="auction-new-main" class="d-flex gap-2 mt-2 pb-2 overflow-auto custom-scrollbar">
                @foreach ($newImages as $i => $tempImg)
                     <div class="avatar-md bg-white border rounded p-1 flex-shrink-0 position-relative" style="width: 80px; height: 80px;" wire:key="new-main-{{ $i }}" data-index="{{ $i }}">
                         @php
                             $imgUrl = null;
                             try {
                                 $imgUrl = $tempImg->temporaryUrl();
                             } catch (\Exception $e) {}
                         @endphp
                         @if($imgUrl)
                             <img src="{{ $imgUrl }}" class="img-fluid rounded h-100 object-cover cursor-move">
                         @else
                             <div class="bg-light d-flex align-items-center justify-content-center" style="height: 100%;">
                                 <i class="ri-image-line fs-20 text-muted"></i>
                             </div>
                         @endif
                         <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm" 
                                style="width: 20px; height: 20px; min-width: 20px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="removeNewImage({{ $i }})">
                            <i class="ri-close-line" style="font-size: 12px;"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-1 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.5));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveNewImage({{ $i }}, 'up', 'main')">
                                    <i class="ri-arrow-left-s-line" style="font-size: 16px;"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveNewImage({{ $i }}, 'down', 'main')">
                                    <i class="ri-arrow-right-s-line" style="font-size: 16px;"></i>
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

        @if(count($existing360Images) > 0)
            <div id="auction-existing-360" class="d-flex gap-2 flex-wrap mb-3">
                @foreach($existing360Images as $img)
                     <div class="position-relative" style="width: 80px; height: 80px;" data-id="{{ $img['id'] }}" wire:key="ex-360-{{ $img['id'] }}">
                        <img src="{{ Storage::url($img['path']) }}" class="img-fluid rounded w-100 h-100 object-cover border cursor-move">
                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm" 
                                style="width: 20px; height: 20px; min-width: 20px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="deleteImage({{ $img['id'] }}, '360')">
                            <i class="ri-close-line" style="font-size: 12px;"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-1 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.5));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveImage({{ $img['id'] }}, 'up', '360')">
                                    <i class="ri-arrow-left-s-line" style="font-size: 16px;"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveImage({{ $img['id'] }}, 'down', '360')">
                                    <i class="ri-arrow-right-s-line" style="font-size: 16px;"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="input-group">
            <input type="file" class="form-control" multiple wire:model="new360Images" id="360ImageInput" accept="image/png,image/jpeg">
             <label class="input-group-text" for="360ImageInput">Upload 360°</label>
        </div>
        <div class="d-flex align-items-center mt-1">
             <i class="ri-360-line text-muted me-1"></i>
             <span class="text-muted fs-11">Upload sequential images. Recommended: 1080 &times; 1080</span>
        </div>
        @error('new360Images') <span class="text-danger text-sm d-block mt-1">{{ $message }}</span> @enderror

         @if ($new360Images)
            <div id="auction-new-360" class="d-flex gap-2 mt-2 pb-2 overflow-auto custom-scrollbar">
                @foreach ($new360Images as $i => $tempImg)
                     <div class="avatar-md bg-white border rounded p-1 flex-shrink-0 position-relative" style="width: 80px; height: 80px;" wire:key="new-360-{{ $i }}" data-index="{{ $i }}">
                         @php
                             $imgUrl = null;
                             try {
                                 $imgUrl = $tempImg->temporaryUrl();
                             } catch (\Exception $e) {}
                         @endphp
                         @if($imgUrl)
                             <img src="{{ $imgUrl }}" class="img-fluid rounded h-100 object-cover cursor-move">
                         @else
                             <div class="bg-light d-flex align-items-center justify-content-center" style="height: 100%;">
                                 <i class="ri-image-line fs-20 text-muted"></i>
                             </div>
                         @endif
                         <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm" 
                                style="width: 20px; height: 20px; min-width: 20px; transform: translate(30%, -30%); padding: 0;"
                                wire:click.prevent.stop="removeNew360Image({{ $i }})">
                            <i class="ri-close-line" style="font-size: 12px;"></i>
                        </button>

                        <div class="position-absolute bottom-0 start-0 w-100 d-flex justify-content-between px-1 pb-1" style="background: linear-gradient(transparent, rgba(0,0,0,0.5));">
                            @if(!$loop->first)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveNewImage({{ $i }}, 'up', '360')">
                                    <i class="ri-arrow-left-s-line" style="font-size: 16px;"></i>
                                </button>
                            @else
                                <span></span>
                            @endif
                            @if(!$loop->last)
                                <button type="button" class="btn btn-link text-white p-0 m-0" wire:click.prevent="moveNewImage({{ $i }}, 'down', '360')">
                                    <i class="ri-arrow-right-s-line" style="font-size: 16px;"></i>
                                </button>
                            @endif
                        </div>
                     </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
