<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEnquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'subject',
        'message',
        'status',
        'admin_note',
        'handled_by_admin_id',
        'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    /**
     * Get the user that owns the enquiry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include enquiries with a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
