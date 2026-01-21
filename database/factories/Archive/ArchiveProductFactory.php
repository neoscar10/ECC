<?php

namespace Database\Factories\Archive;

use App\Models\Archive\ArchiveCategory;
use App\Models\Archive\ArchiveProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArchiveProductFactory extends Factory
{
    protected $model = ArchiveProduct::class;

    public function definition()
    {
        $title = $this->faker->words(3, true);
        return [
            'archive_category_id' => ArchiveCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraph,
            'is_active' => true,
            'go_live_now' => true,
            'go_live_at' => now(),
            'price_min_amount' => 1000, // 10.00
            'price_max_amount' => 2000,
            'quantity' => 10,
            'restriction_mode' => 'public', // public, restricted
            'restriction_type' => 'none', // none, random
            'blur_enabled' => false,
            'early_access_enabled' => false,
        ];
    }
}
