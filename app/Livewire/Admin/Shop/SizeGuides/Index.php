<?php

namespace App\Livewire\Admin\Shop\SizeGuides;

use Livewire\Component;
use App\Models\Shop\ShopSizeGuide;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    // Size Guide Form Modal State
    public $isEditMode = false;
    public $guideId = null;
    public $name = '';
    public $description = '';

    protected $paginationTheme = 'bootstrap';

    // --- Form Logic ---
    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->dispatch('show-guide-modal');
    }

    public function editGuide($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        
        $guide = ShopSizeGuide::findOrFail($id);
        $this->guideId = $guide->id;
        $this->name = $guide->name;
        $this->description = $guide->description;

        $this->dispatch('show-guide-modal');
    }

    public function resetForm()
    {
        $this->reset([
            'guideId', 'name', 'description'
        ]);
    }

    public function saveGuide()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $defaultTable = [
            [
                'title' => '',
                'columns' => ['Label', 'Value'],
                'rows' => [
                    ['', '']
                ]
            ]
        ];

        $data = [
            'name' => $this->name,
            'description' => $this->description,
        ];

        if ($this->isEditMode && $this->guideId) {
            ShopSizeGuide::findOrFail($this->guideId)->update($data);
            $msg = 'Size guide updated successfully.';
        } else {
            // Pre-populate table_data with empty structure on create
            $data['table_data'] = [
                'cm' => $defaultTable,
                'inch' => $defaultTable,
            ];
            ShopSizeGuide::create($data);
            $msg = 'Size guide created successfully.';
        }

        $this->dispatch('hide-guide-modal');
        $this->dispatch('notify', message: $msg, type: 'success');
    }

    public function deleteGuide($id)
    {
        ShopSizeGuide::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Size guide deleted.', type: 'success');
    }

    public function render()
    {
        $guides = ShopSizeGuide::where('name', 'like', "%{$this->search}%")
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.shop.size-guides.index', compact('guides'))
            ->layout('layouts.admin');
    }
}
