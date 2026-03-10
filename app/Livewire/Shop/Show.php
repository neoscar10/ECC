<?php

namespace App\Livewire\Shop;

use Livewire\Component;

class Show extends Component
{
    public $slug;

    public function mount($slug)
    {
        $this->slug = $slug;
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <h1>Shop Product Detail Placeholder</h1>
            <p>Product Slug: {{ $slug }}</p>
        </div>
        HTML;
    }
}
