<?php

namespace App\Livewire\Admin\Shop\Carts;

use App\Models\Shop\Cart;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'active_or_abandoned'; // Default to interesting ones? Or 'all'? plan said 'All'.
    public $perPage = 10;
    
    // Modal State
    public $selectedCartId = null;

    // Settings State
    public $thresholdValue;
    public $thresholdUnit = 'minutes';

    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function viewCart($cartId)
    {
        $this->selectedCartId = $cartId;
        $this->dispatch('show-cart-modal');
    }

    public function getSelectedCartProperty()
    {
        if (!$this->selectedCartId) return null;
        return Cart::with(['user', 'items.product.images', 'items.selectedVariations', 'items.cartItem.variationValuePivots.variationValue.group'])
            ->find($this->selectedCartId);
    }

    public function openSettingsModal()
    {
        $minutes = \App\Models\Setting::get('cart_abandoned_minutes', config('cart.abandoned_minutes', 60));
        
        if ($minutes >= 1440 && $minutes % 1440 === 0) {
            $this->thresholdValue = $minutes / 1440;
            $this->thresholdUnit = 'days';
        } elseif ($minutes >= 60 && $minutes % 60 === 0) {
            $this->thresholdValue = $minutes / 60;
            $this->thresholdUnit = 'hours';
        } else {
            $this->thresholdValue = $minutes;
            $this->thresholdUnit = 'minutes';
        }

        $this->dispatch('show-settings-modal');
    }

    public function getFormattedThresholdProperty()
    {
        $minutes = \App\Models\Setting::get('cart_abandoned_minutes', config('cart.abandoned_minutes', 60));
        
        $days = floor($minutes / 1440);
        $hours = floor(($minutes % 1440) / 60);
        $mins = $minutes % 60;
        
        $parts = [];
        if ($days > 0) $parts[] = $days . ' ' . \Illuminate\Support\Str::plural('day', $days);
        if ($hours > 0) $parts[] = $hours . ' ' . \Illuminate\Support\Str::plural('hr', $hours);
        if ($mins > 0 || empty($parts)) $parts[] = $mins . ' ' . \Illuminate\Support\Str::plural('min', $mins);
        
        return implode(', ', $parts);
    }

    public function saveSettings()
    {
        $this->validate([
            'thresholdValue' => 'required|integer|min:1',
            'thresholdUnit' => 'required|in:minutes,hours,days',
        ]);

        $minutes = $this->thresholdValue;
        if ($this->thresholdUnit === 'hours') {
            $minutes *= 60;
        } elseif ($this->thresholdUnit === 'days') {
            $minutes *= 1440;
        }

        \App\Models\Setting::set('cart_abandoned_minutes', $minutes);

        $this->dispatch('hide-settings-modal');
        session()->flash('success', 'Abandoned threshold successfully updated to ' . $this->thresholdValue . ' ' . $this->thresholdUnit . '.');
    }
    
    // Note: complex eager loading needed for displaying selected variations text.
    // items -> selectedVariations is HasManyThrough.
    
    public function render()
    {
        $query = Cart::with(['user'])
            ->withCount('items');

        // Search (User Name or Email)
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        // Filter Status
        // Active: items > 0. Abandoned: items > 0 AND accessor check. Empty: items = 0.
        // Doing this via SQL is better than iterating accessors.
        
        $threshold = \App\Models\Setting::get('cart_abandoned_minutes', config('cart.abandoned_minutes', 60));
        $abandonedCutoff = now()->subMinutes($threshold);

        if ($this->filterStatus === 'abandoned') {
            $query->whereHas('items') // Not empty
                  ->where('last_activity_at', '<', $abandonedCutoff)
                  ->whereNull('checked_out_at');
        } elseif ($this->filterStatus === 'active') {
            // "Active" could mean "Not Abandoned" OR "Recently Active".
            // Let's assume Active = Not Abandoned + Not Empty.
            $query->whereHas('items')
                  ->where('last_activity_at', '>=', $abandonedCutoff)
                  ->whereNull('checked_out_at');
        } elseif ($this->filterStatus === 'empty') {
            $query->doesntHave('items');
        }

        $carts = $query->latest('last_activity_at')->paginate($this->perPage);

        return view('livewire.admin.shop.carts.index', [
            'carts' => $carts,
            'selectedCart' => $this->selectedCartId ? Cart::with(['user', 'items.product', 'items.selectedVariations.group'])->find($this->selectedCartId) : null
        ])->layout('layouts.admin');
    }
}
