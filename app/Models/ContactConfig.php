<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactConfig extends Model
{
    protected $fillable = ['concierge_phone', 'support_email'];
}
