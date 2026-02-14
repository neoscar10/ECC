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
        'price' => 'decimal:2',
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
}
