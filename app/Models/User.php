<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected static function booted()
    {
        static::deleted(function (User $user) {
            if ($user->isForceDeleting()) {
                return;
            }

            // Anonymize email and phone to free up constraints
            $prefix = 'del_' . time() . '_' . $user->id . '_';
            $updates = [];
            
            if ($user->email && !str_starts_with($user->email, 'del_')) {
                $maxEmailLength = 255 - strlen($prefix);
                $updates['email'] = $prefix . substr($user->email, 0, $maxEmailLength);
                $user->email = $updates['email']; // keep memory model in sync
            }
            
            if ($user->phone && !str_starts_with($user->phone, 'del_')) {
                $maxPhoneLength = 255 - strlen($prefix);
                $updates['phone'] = $prefix . substr($user->phone, 0, $maxPhoneLength);
                $user->phone = $updates['phone']; // keep memory model in sync
            }

            if (!empty($updates)) {
                // Raw update to save directly without triggering loops
                User::withTrashed()->where('id', $user->id)->update($updates);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_verified_at',
        'full_name',
        'date_of_birth',
        'country',
        'city',
        'avatar_path',
        'gender', // If used
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function currentMembership()
    {
        return $this->hasOne(Membership::class)->where('status', 'active')->latest('started_at');
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function hasActiveMembership(): bool
    {
        return $this->currentMembership()->exists();
    }

    public function hasPrivilege($privilegeKey): bool
    {
        $membership = $this->currentMembership()->with('membershipTier.privileges')->first();
        
        if (!$membership || !$membership->membershipTier) {
            return false;
        }

        return $membership->membershipTier->privileges->contains('key', $privilegeKey);
    }

    public function bids()
    {
        return $this->hasMany(\App\Models\Auctions\AuctionBid::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(\App\Models\UserDeviceToken::class);
    }

    public function auctionNotificationSubscriptions()
    {
        return $this->hasMany(\App\Models\Auctions\AuctionNotificationSubscription::class);
    }

    public function addresses()
    {
        return $this->hasMany(\App\Models\Shop\UserAddress::class);
    }

    public function vaultItems()
    {
        return $this->hasMany(UserVaultItem::class);
    }

    public function getMemberCodeAttribute(): string
    {
        return 'EXEC-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getDisplayLocationAttribute(): string
    {
        $parts = array_filter([$this->city, $this->country]);
        return empty($parts) ? '—' : implode(', ', $parts);
    }

    public function getHasVaultAccessAttribute(): bool
    {
        return (bool) $this->currentMembership?->membershipTier?->has_vault_access;
    }
}
