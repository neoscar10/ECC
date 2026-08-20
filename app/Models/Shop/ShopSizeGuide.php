<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopSizeGuide extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'how_to_measure_text',
        'how_to_measure_image_path',
        'table_data',
        'cm_to_inch_multiplier',
    ];

    protected $casts = [
        'table_data' => 'array',
        'cm_to_inch_multiplier' => 'float',
    ];

    public function categories()
    {
        return $this->hasMany(ShopCategory::class, 'size_guide_id');
    }

    public function products()
    {
        return $this->hasMany(ShopProduct::class, 'size_guide_id');
    }
}
