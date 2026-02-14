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
}
