<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubject extends Model
{
    protected $fillable = ['key', 'label', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->key)) {
                $model->key = \Illuminate\Support\Str::slug($model->label);
            }
        });
    }
}
