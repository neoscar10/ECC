<?php

namespace App\Models;

use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\Auctions\AuctionLot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sold_at' => 'datetime',
        'paid_at' => 'datetime',
        'qty' => 'integer',
        'unit_price_inr' => 'decimal:2',
        'subtotal_inr' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ArchiveProduct::class, 'archive_product_id');
    }

    public function auctionLot(): BelongsTo
    {
        return $this->belongsTo(AuctionLot::class, 'auction_lot_id');
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(ArchiveProductEnquiry::class, 'archive_product_enquiry_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Payment::class, 'payable');
    }

    public function vaultItem(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(\App\Models\UserVaultItem::class, 'sale_context');
    }
}
