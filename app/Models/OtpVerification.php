<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'purpose',
        'otp_hash',
        'meta_message_id',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
        'last_sent_at',
        'resend_count',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'resend_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereNull('verified_at')->where('expires_at', '>', now());
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('verified_at')->where('expires_at', '<=', now());
    }

    // Helper Methods
    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function hasAttemptsRemaining(): bool
    {
        return $this->attempts < $this->max_attempts;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    public function markVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function canResend(): bool
    {
        // Simple default cooldown: wait 60 seconds before resending
        return $this->last_sent_at->addSeconds(60)->isPast();
    }
}
