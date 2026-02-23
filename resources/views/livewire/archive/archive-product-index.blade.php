<div>
    <div class="filters mb-4">
        <select wire:model.live="categoryId" class="form-select">
            <option value="">All Categories</option>
            <!-- Categories would be passed from component or fetched here -->
        </select>
    </div>

    <div class="row">
        @foreach($products as $product)
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="{{ $product->primary_image_url }}" class="card-img-top" alt="{{ $product->title }}">
                    <div class="card-body">
                        <h5 class="card-title">{{ $product->title }}</h5>
                        <p class="card-text">{{ $product->category?->name }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
