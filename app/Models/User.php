<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

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
}
