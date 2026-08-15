<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use App\Models\Setting;
use Livewire\Attributes\Title;

#[Title('Navigation Links Settings')]
class NavigationLinks extends Component
{
    public array $items = [];

    public function mount()
    {
        $defaultSequence = ['explore', 'archive', 'auctions', 'club', 'shop', 'profile'];
        
        $sequenceJson = Setting::get('nav_sequence');
        $sequence = $sequenceJson ? json_decode($sequenceJson, true) : $defaultSequence;
        
        foreach ($defaultSequence as $key) {
            if (!in_array($key, $sequence)) {
                $sequence[] = $key;
            }
        }

        $labels = [
            'explore' => Setting::get('nav_label_explore', 'Explore'),
            'archive' => Setting::get('nav_label_archive', 'Archive'),
            'auctions' => Setting::get('nav_label_auctions', 'Auctions'),
            'club' => Setting::get('nav_label_club', 'Club'),
            'shop' => Setting::get('nav_label_shop', 'Shop'),
            'profile' => Setting::get('nav_label_profile', 'Profile'),
        ];

        foreach ($sequence as $key) {
            if (isset($labels[$key])) {
                $this->items[] = [
                    'key' => $key,
                    'label' => $labels[$key],
                ];
            }
        }
    }

    public function updateOrder($list)
    {
        $newOrderKeys = collect($list)->sortBy('order')->pluck('value')->toArray();
        
        $sortedItems = [];
        foreach ($newOrderKeys as $key) {
            foreach ($this->items as $item) {
                if ($item['key'] === $key) {
                    $sortedItems[] = $item;
                    break;
                }
            }
        }
        $this->items = $sortedItems;
    }

    public function save()
    {
        $this->validate([
            'items.*.label' => 'required|string|max:15',
        ], [
            'items.*.label.required' => 'The label is required.',
            'items.*.label.max' => 'The label must not be greater than 15 characters.',
        ]);

        $sequence = [];
        foreach ($this->items as $item) {
            $key = $item['key'];
            $sequence[] = $key;
            Setting::set("nav_label_{$key}", $item['label']);
        }

        Setting::set('nav_sequence', json_encode($sequence));

        session()->flash('success', 'Navigation links and sequence updated successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings.navigation-links')->layout('layouts.admin');
    }
}
