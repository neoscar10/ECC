<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Livewire\Admin\Dashboard;
use App\Http\Middleware\EnsureAdminRole;

Route::get('/', function () {
    return view('landing');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', EnsureAdminRole::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/users', \App\Livewire\Admin\Users\UsersIndex::class)->name('users.index');
    Route::get('/admin-users', \App\Livewire\Admin\Users\AdminIndex::class)->name('users.admin');
    
    // Membership
    Route::prefix('membership')->name('membership.')->group(function () {
        Route::get('/applications', \App\Livewire\Admin\Membership\Applications\Index::class)->name('applications');
        Route::get('/tiers', \App\Livewire\Admin\Membership\Tiers\Index::class)->name('tiers');
        Route::get('/members', \App\Livewire\Admin\Members\Index::class)->name('members');
    });

    // The Archive
    Route::prefix('archive')->name('archive.')->group(function () {
        Route::get('/categories', \App\Livewire\Admin\Archive\Categories\Index::class)->name('categories');
        Route::get('/products', \App\Livewire\Admin\Archive\Products\Index::class)->name('products');
        Route::get('/enquiries', \App\Livewire\Admin\Archive\Enquiries\Index::class)->name('enquiries');
        Route::get('/orders', \App\Livewire\Admin\Archive\Orders\Index::class)->name('orders.index');
    });

    // Auctions
    Route::prefix('auctions')->name('auctions.')->group(function () {
        // Redirect root 'auctions' to lots index if needed, or just use lots index as main
        Route::get('/', \App\Livewire\Admin\Auctions\Lots\Index::class)->name('index'); // Keeping 'index' as name for backward compat if needed? 
        // Request said: "Fix it to lots index route." and used 'admin.auctions.lots.index' in breadcrumb.
        // So I should name it 'lots.index' ?
        // But if I change 'admin.auctions.index' to 'admin.auctions.lots.index', I break existing links unless I update them.
        // The Prompt Breadcrumb: <li class="breadcrumb-item"><a href="{{ route('admin.auctions.lots.index') }}">Auction Lots</a></li>
        // So I should define 'lots.index'.
        
        // Enquiries (New)
        Route::get('/enquiries', \App\Livewire\Admin\Auctions\Enquiries\Index::class)->name('enquiries');

        Route::prefix('lots')->name('lots.')->group(function() {
             Route::get('/', \App\Livewire\Admin\Auctions\Lots\Index::class)->name('index');
             Route::get('/{id}', \App\Livewire\Admin\Auctions\Lots\Show::class)->name('show');
        });
        
        // Orders
        Route::get('/orders', \App\Livewire\Admin\Auctions\Orders\Index::class)->name('orders.index');
    });

    // CMS
    Route::prefix('cms')->name('cms.')->group(function () {
        Route::get('/blocks', \App\Livewire\Admin\Cms\Blocks\Index::class)->name('blocks.index');
    });

    // Shop
    Route::prefix('shop')->name('shop.')->group(function () {
        Route::get('/categories', \App\Livewire\Admin\Shop\Categories\CategoriesExplorer::class)->name('categories');
        Route::get('/tags', \App\Livewire\Admin\Shop\Tags\TagsExplorer::class)->name('tags');
        Route::get('/products', \App\Livewire\Admin\Shop\Products\Index::class)->name('products');
        Route::get('/carts', \App\Livewire\Admin\Shop\Carts\Index::class)->name('carts');
        Route::get('/inventory', \App\Livewire\Admin\Shop\Inventory\Index::class)->name('inventory');
        Route::get('/orders', \App\Livewire\Admin\Shop\Orders\Index::class)->name('orders');
        Route::get('/orders/{id}', \App\Livewire\Admin\Shop\Orders\Show::class)->name('orders.show');
    });
});
