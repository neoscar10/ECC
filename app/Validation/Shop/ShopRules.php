<?php

namespace App\Validation\Shop;

class ShopRules
{
    public static function listing(): array
    {
        return [
            'q' => 'nullable|string|max:100',
            'category_ids' => 'nullable|string', // comma separated or array
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'sort' => 'nullable|string|in:newest,oldest,price_asc,price_desc,price_low,price_high,title_asc,title_desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'in_stock' => 'nullable|boolean'
        ];
    }

    public static function suggestions(): array
    {
        return [
            'q' => 'required|string|min:2',
            'limit' => 'integer|min:1|max:20'
        ];
    }
}
