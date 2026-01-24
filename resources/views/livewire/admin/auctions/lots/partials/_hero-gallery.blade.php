<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title mb-1">{{ $lot->title }}</h5>
            </div>
            <div>
                 @if($lot->status == 'live')
                     <span class="badge bg-success fs-12">LIVE</span>
                @elseif($lot->status == 'upcoming')
                     <span class="badge bg-info fs-12">UPCOMING</span>
                @elseif($lot->status == 'ended')
                     <span class="badge bg-secondary fs-12">ENDED</span>
                @elseif($lot->status == 'unsold')
                     <span class="badge bg-warning fs-12">UNSOLD</span>
                @elseif($lot->status == 'cancelled')
                     <span class="badge bg-danger fs-12">CANCELLED</span>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Main Image Column --}}
            <div class="col-lg-8 mb-3 mb-lg-0">
                @php
                    $activeImg = $this->activeImage;
                    $mainPath = null;
                    if ($activeImg) {
                        $mainPath = preg_replace('#^public/#', '', str_replace('\\','/', $activeImg->path));
                    }
                @endphp
                
                <div class="bg-light rounded d-flex align-items-center justify-content-center border p-2" 
                     style="height: 420px; overflow: hidden; transition: all 0.3s ease;">
                    @if($mainPath)
                        <img src="{{ Storage::url($mainPath) }}" 
                             class="w-100 h-100 object-fit-contain" 
                             alt="Main Auction Image">
                    @else
                        <div class="text-center text-muted">
                            <i class="ri-image-line fs-48"></i>
                            <p class="mt-2">No Image Selected</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Thumbnails Column --}}
            <div class="col-lg-4">
                <div class="d-flex flex-row flex-lg-column gap-2 overflow-auto" 
                     style="max-height: 420px; padding-right: 5px;">
                    @forelse($lot->images->sortBy('sort_order') as $img)
                        @php 
                            $thumbPath = preg_replace('#^public/#', '', str_replace('\\','/', $img->path));
                            $isActive = $activeImageId === $img->id;
                        @endphp
                        
                        <div wire:key="thumb-{{ $img->id }}" 
                             wire:click="selectImage({{ $img->id }})"
                             class="d-flex align-items-center justify-content-center flex-shrink-0 cursor-pointer border rounded bg-white p-1 {{ $isActive ? 'border-primary border-2 shadow-sm' : 'border-light' }}"
                             style="width: 80px; height: 80px; cursor: pointer; transition: all 0.2s;"
                             role="button">
                            <img src="{{ Storage::url($thumbPath) }}" 
                                 class="img-fluid" 
                                 style="max-height: 100%; object-fit: contain;">
                        </div>
                    @empty
                        <div class="text-muted fs-12">No images available.</div>
                    @endforelse
                    
                    @if($lot->images->count() > 5)
                        <div class="d-none d-lg-block text-center mt-2 text-muted fs-11">
                            Scroll for more
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
