<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserVaultItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'locked_at' => 'datetime',
        'removed_at' => 'datetime',
        'unit_price' => 'decimal:2',
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function removedByAdmin()
    {
        return $this->belongsTo(User::class, 'removed_by_admin_id');
    }

    public function saleContext()
    {
        return $this->morphTo();
    }

    public function removalRequests()
    {
        return $this->belongsToMany(VaultRemovalRequest::class, 'user_vault_item_vault_removal_request');
    }

    public function getPendingRemovalRequestAttribute()
    {
        if ($this->relationLoaded('removalRequests')) {
            return $this->removalRequests->where('status', 'pending')->first();
        }
        return $this->removalRequests()->where('status', 'pending')->first();
    }

    public function getLatestDeliveryRequestAttribute()
    {
        if ($this->relationLoaded('removalRequests')) {
            return $this->removalRequests->sortByDesc('created_at')->first();
        }
        return $this->removalRequests()->latest('created_at')->first();
    }

    public function getTotalValueAttribute()
    {
        return ($this->unit_price ?? $this->price ?? 0) * ($this->quantity ?? 1);
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    public function scopeRemoved($query)
    {
        return $query->where('status', 'removed');
    }

    public function getDisplayImageUrlAttribute()
    {
        // 1. Try stored snapshot
        $url = $this->item_image_url;
        
        // 2. If missing, try to fetch from source (fallback)
        if (empty($url)) {
            if ($this->source_type === 'archive_product') {
                $product = \App\Models\Archive\ArchiveProduct::find($this->source_id);
                $url = $product?->image_url; // Uses the accessor we added to ArchiveProduct
            } elseif ($this->source_type === 'auction_lot' || $this->source_type === 'auction') {
                $lot = \App\Models\Auctions\AuctionLot::find($this->source_id);
                $url = $lot?->image_url; // Uses the accessor we added to AuctionLot
            }
        }

        if (empty($url)) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return \Illuminate\Support\Facades\Storage::url($url);
    }

    // ── Source Item & Shipping Dimensions Delegates ──

    public function getSourceItemAttribute()
    {
        if ($this->source_type === 'archive_product') {
            return \App\Models\Archive\ArchiveProduct::find($this->source_id);
        }
        if ($this->source_type === 'auction_lot' || $this->source_type === 'auction') {
            return \App\Models\Auctions\AuctionLot::find($this->source_id);
        }
        return null;
    }

    public function getWeightKgAttribute(): ?float
    {
        $item = $this->source_item;
        return $item ? (float) $item->weight_kg : null;
    }

    public function getLengthCmAttribute(): ?float
    {
        $item = $this->source_item;
        return $item ? (float) $item->length_cm : null;
    }

    public function getBreadthCmAttribute(): ?float
    {
        $item = $this->source_item;
        return $item ? (float) $item->breadth_cm : null;
    }

    public function getHeightCmAttribute(): ?float
    {
        $item = $this->source_item;
        return $item ? (float) $item->height_cm : null;
    }

    public function getVolumetricWeightKgAttribute(): ?float
    {
        return $this->source_item?->volumetric_weight_kg;
    }

    public function getChargeableWeightKgAttribute(): ?float
    {
        return $this->source_item?->chargeable_weight_kg;
    }
}
