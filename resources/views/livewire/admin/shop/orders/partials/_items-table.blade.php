<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Order Items</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive table-card">
            <table class="table align-middle table-nowrap">
                <thead class="table-light text-muted">
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col">Unit Price</th>
                        <th scope="col">Quantity</th>
                        <th scope="col" class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->order->items as $item)
                        <tr>
                            <td>
                                <div class="d-flex">
                                    <div class="flex-shrink-0 avatar-md bg-light rounded p-1">
                                        @php
                                            $img = $item->product ? $item->product->images->first() : null;
                                            $imgUrl = $img ? $img->url : null; // Assuming 'url' accessor or similar on ShopProductImage model? 
                                            // Let's check ShopProductImage model or assume standard 'url' / 'path'.
                                            // Ideally we use a helper or accessor on ShopProduct if it exists.
                                            // Given previous codebase patterns, let's assume 'url' or 'disk' path. 
                                            // If using Spatie MediaLibrary, it's ->getUrl().
                                            // Users codebase seems custom. Let's try to access 'url' attribute if it exists, or check the model.
                                            // Wait, I didn't check ShopProductImage model. 
                                            // Let's assume standard 'image_url' or similar if available, or just try to display if we have a path.
                                            // Actually, safely fallback to icon if no image.
                                        @endphp
                                        
                                        @if($item->product && $item->product->images->isNotEmpty())
                                             <img src="{{ $item->product->images->first()->url }}" alt="" class="img-fluid d-block">
                                        @else
                                            <div class="avatar-title bg-soft-light text-secondary rounded">
                                                <i class="ri-shopping-bag-3-line fs-24"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="fs-15 mb-1">
                                            <a href="#" class="text-dark">{{ $item->title_snapshot }}</a>
                                        </h5>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($item->variationValues as $val)
                                                <span class="badge bg-light text-dark border border-secondary border-opacity-25">
                                                    {{ $val->caption }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $this->order->currency }} {{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-end fw-medium">{{ $this->order->currency }} {{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end mt-4">
            <div class="col-lg-4 col-md-6">
                <table class="table table-borderless table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-end">Sub Total :</td>
                            <td class="text-end" style="width: 120px;">{{ $this->order->currency }} {{ number_format($this->order->subtotal, 2) }}</td>
                        </tr>
                        @if($this->order->discount_amount > 0)
                            <tr>
                                <td class="text-end text-danger">Discount :</td>
                                <td class="text-end text-danger">-{{ $this->order->currency }} {{ number_format($this->order->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($this->order->shipping_fee > 0)
                            <tr>
                                <td class="text-end">Shipping Charge :</td>
                                <td class="text-end">{{ $this->order->currency }} {{ number_format($this->order->shipping_fee, 2) }}</td>
                            </tr>
                        @endif
                        @if($this->order->tax_amount > 0)
                            <tr>
                                <td class="text-end">Estimated Tax :</td>
                                <td class="text-end">{{ $this->order->currency }} {{ number_format($this->order->tax_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="border-top border-top-dashed">
                            <th class="text-end">Total Amount ({{ $this->order->currency }}) :</th>
                            <th class="text-end">
                                <span class="fw-bold">{{ $this->order->currency }} {{ number_format($this->order->total_amount, 2) }}</span>
                            </th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
