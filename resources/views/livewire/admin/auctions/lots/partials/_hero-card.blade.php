<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title mb-1">{{ $lot->title }}</h5>
                 <p class="text-muted mb-0">
                    @if($lot->status == 'upcoming')
                        Starts: {{ $lot->starts_at?->format('d M Y, h:i A') }}
                    @elseif($lot->status == 'live')
                        Ends: {{ $lot->ends_at?->format('d M Y, h:i A') }}
                    @else
                        Ended: {{ $lot->ends_at?->format('d M Y, h:i A') }}
                    @endif
                 </p>
            </div>
            <div>
                 @if($lot->status == 'live')
                     <span class="badge bg-success fs-12">LIVE</span>
                @elseif($lot->status == 'upcoming')
                     <span class="badge bg-info fs-12">UPCOMING</span>
                @elseif($lot->status == 'ended')
                     <span class="badge bg-secondary fs-12">ENDED</span>
                @elseif($lot->status == 'cancelled')
                     <span class="badge bg-danger fs-12">CANCELLED</span>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 text-center">
                 @php
                    $mainImg = $lot->images->sortBy('sort_order')->first();
                    $path = $mainImg ? preg_replace('#^public/#', '', str_replace('\\','/',$mainImg->path)) : null;
                @endphp
                <div class="bg-light rounded p-4 mb-3" style="height: 350px;">
                    @if($path)
                        <img src="{{ Storage::url($path) }}" class="img-fluid h-100 object-fit-contain" alt="Main Image">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">No Main Image</div>
                    @endif
                </div>
                
                @if($lot->images->count() > 1)
                    <div class="d-flex gap-2 justify-content-center overflow-auto py-2">
                        @foreach($lot->images as $img)
                            @php $subPath = preg_replace('#^public/#', '', str_replace('\\','/',$img->path)); @endphp
                            <div class="border rounded p-1" style="width: 60px; height: 60px; cursor: pointer;">
                                 <img src="{{ Storage::url($subPath) }}" class="img-fluid h-100 object-fit-contain">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
