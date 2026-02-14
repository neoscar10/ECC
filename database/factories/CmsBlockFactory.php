<?php

namespace Database\Factories;

use App\Models\Cms\CmsBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

class CmsBlockFactory extends Factory
{
    protected $model = CmsBlock::class;

    public function definition()
    {
        return [
            'placement' => 'home-hero',
            'title' => $this->faker->sentence,
            'type' => 'card',
            'content' => [
                'title' => $this->faker->sentence,
                'subtitle' => $this->faker->sentence,
                'body' => $this->faker->paragraph,
                'cta_text' => 'Read More',
                'cta_link' => '#',
                'image_url' => $this->faker->imageUrl,
            ],
            'is_active' => true,
            'sort_order' => 1,
            'restriction_mode' => 'public',
            'restriction_type' => 'hierarchical',
            'blur_enabled' => false,
        ];
    }
}
