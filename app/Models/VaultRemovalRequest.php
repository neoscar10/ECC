<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class VaultRemovalRequest extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $guarded = [];
 
    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
 
    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_COMPLETED = 'completed';
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function vaultItem()
    {
        return $this->belongsTo(UserVaultItem::class, 'vault_item_id');
    }
 
    public function reviewedByAdmin()
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }

    public function address()
    {
        return $this->belongsTo(\App\Models\Shop\UserAddress::class, 'address_id');
    }
}
