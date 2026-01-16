<?php

namespace App\Models\Archive;

use Illuminate\Database\Eloquent\Model;

class ArchiveProduct360Image extends Model
{
    protected $table = 'archive_product_360_images';

    protected $fillable = [
        'archive_product_id',
        'image_path',
        'sort_order',
    ];
}
