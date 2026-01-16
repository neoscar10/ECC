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
    });
});
