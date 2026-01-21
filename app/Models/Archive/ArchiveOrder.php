<?php

namespace App\Models\Archive;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sold_at' => 'datetime',
        'qty' => 'integer',
        'unit_price_inr' => 'decimal:2',
        'subtotal_inr' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ArchiveProduct::class, 'archive_product_id');
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
}
