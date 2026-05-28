<?php

namespace App\Models\Shop;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'last_activity_at',
        'checked_out_at',
        'admin_note',
    ];

    protected static function booted()
    {
        static::creating(function ($cart) {
            if (!$cart->last_activity_at) {
                $cart->last_activity_at = now();
            }
        });
    }

    protected $casts = [
        'last_activity_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- Relationships ---

    /**
     * Get the user who owns the cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items in the cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // --- Accessors & Attributes ---

    /**
     * Check if the cart is abandoned based on config threshold.
     *
     * @return bool
     */
    public function getIsAbandonedAttribute(): bool
    {
        if ($this->checked_out_at) {
            return false;
        }

        if ($this->items()->count() === 0) {
            return false;
        }

        $thresholdMinutes = \App\Models\Setting::get('cart_abandoned_minutes', config('cart.abandoned_minutes', 60));

        return $this->last_activity_at && $this->last_activity_at->lt(now()->subMinutes($thresholdMinutes));
    }
}
