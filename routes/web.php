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
    Route::get('/users', \App\Livewire\Admin\Users\Index::class)->name('users.index');
    
    // Membership
    Route::prefix('membership')->name('membership.')->group(function () {
        Route::get('/applications', \App\Livewire\Admin\Membership\Applications\Index::class)->name('applications');
        Route::get('/tiers', \App\Livewire\Admin\Membership\Tiers\Index::class)->name('tiers');
        Route::get('/members', \App\Livewire\Admin\Members\Index::class)->name('members');
    });
});
