<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use App\Models\Setting;
use Livewire\Attributes\Title;

#[Title('Navigation Links Settings')]
class NavigationLinks extends Component
{
    public $explore;
    public $archive;
    public $auctions;
    public $club;
    public $shop;
    public $profile;

    public function mount()
    {
        $this->explore = Setting::get('nav_label_explore', 'Explore');
        $this->archive = Setting::get('nav_label_archive', 'Archive');
        $this->auctions = Setting::get('nav_label_auctions', 'Auctions');
        $this->club = Setting::get('nav_label_club', 'Club');
        $this->shop = Setting::get('nav_label_shop', 'Shop');
        $this->profile = Setting::get('nav_label_profile', 'Profile');
    }

    public function save()
    {
        $this->validate([
            'explore' => 'nullable|string|max:50',
            'archive' => 'nullable|string|max:50',
            'auctions' => 'nullable|string|max:50',
            'club' => 'nullable|string|max:50',
            'shop' => 'nullable|string|max:50',
            'profile' => 'nullable|string|max:50',
        ]);

        Setting::set('nav_label_explore', $this->explore ?: 'Explore');
        Setting::set('nav_label_archive', $this->archive ?: 'Archive');
        Setting::set('nav_label_auctions', $this->auctions ?: 'Auctions');
        Setting::set('nav_label_club', $this->club ?: 'Club');
        Setting::set('nav_label_shop', $this->shop ?: 'Shop');
        Setting::set('nav_label_profile', $this->profile ?: 'Profile');

        session()->flash('success', 'Navigation links updated successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings.navigation-links')->layout('layouts.admin');
    }
}
