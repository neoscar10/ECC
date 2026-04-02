<?php

namespace App\Models\Archive;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveProductEnquiry extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ArchiveProduct::class, 'archive_product_id')->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class, 'archive_order_id');
    }
}
