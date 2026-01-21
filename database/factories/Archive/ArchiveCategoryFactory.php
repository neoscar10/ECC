<?php

namespace Database\Factories\Archive;

use App\Models\Archive\ArchiveCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArchiveCategoryFactory extends Factory
{
    protected $model = ArchiveCategory::class;

    public function definition()
    {
        $title = $this->faker->words(2, true);
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->sentence,
            'is_active' => true,
            'sort_order' => 0,
            'visibility' => 'public',
        ];
    }
}
