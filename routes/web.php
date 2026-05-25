<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Livewire\Admin\Dashboard;
use App\Http\Middleware\EnsureAdminRole;
use App\Livewire\Splash\SplashScreen;
use App\Livewire\Welcome\WelcomePage;
use App\Livewire\Entry\GatedEntryPage;
use App\Livewire\User\Auth\UserLoginPage;
use App\Livewire\Club\ClubPage;

Route::middleware(['auth', 'ensure_registration_complete'])->group(function () {
    Route::get('/content/blocks/{id}', \App\Livewire\Pavilion\ContentBlockDetailPage::class)->name('content.block.detail');
    Route::get('/archive', \App\Livewire\Archive\ArchiveBrowse::class)->name('archive.index');
    Route::get('/vault', \App\Livewire\Vault\Index::class)->name('vault.index');

    Route::get('/archive/products/{id}', \App\Livewire\Archive\ArchiveProductShow::class)->name('archive.products.show');
    Route::get('/pavilion/{type}/{slugOrId}', \App\Livewire\Pavilion\ContentDetailPage::class)->name('pavilion.detail');
    Route::get('/club', \App\Livewire\Club\ClubPage::class)->name('club');
    Route::get('/settings', \App\Livewire\Settings\SettingsPage::class)->name('settings');
    Route::get('/auctions', \App\Livewire\Auctions\Index::class)->name('auctions.index');
    Route::get('/auctions/{lot}', \App\Livewire\Auctions\Show::class)->name('auctions.show');
    
    // Store
    Route::get('/shop', \App\Livewire\Shop\Index::class)->name('shop.index');
    Route::get('/cart', \App\Livewire\Shop\CartPage::class)->name('shop.cart');
    Route::get('/checkout', \App\Livewire\Shop\CheckoutPage::class)->name('shop.checkout');
    Route::get('/orders', \App\Livewire\Shop\OrderListPage::class)->name('shop.orders');
    Route::get('/order-details/{orderId}', \App\Livewire\Shop\OrderDetailsPage::class)->name('shop.order-details');
    Route::get('/order-success/{orderId}', \App\Livewire\Shop\OrderSuccessPage::class)->name('shop.order-success');
    Route::get('/shop/{slug}', \App\Livewire\Shop\Show::class)->name('shop.show');
    
    // Payments
    Route::get('/payments/{payment}/pay', [\App\Http\Controllers\Web\Payment\GenericPaymentController::class, 'pay'])->name('payments.pay');
    Route::get('/payments/{payment}/retry', [\App\Http\Controllers\Web\Payment\GenericPaymentController::class, 'retry'])->name('payments.retry');
    Route::get('/payments/razorpay/{payment}/pay', [\App\Http\Controllers\Web\Payment\RazorpayPaymentController::class, 'pay'])->name('payments.razorpay.pay');
    Route::post('/payments/razorpay/verify', [\App\Http\Controllers\Web\Payment\RazorpayPaymentController::class, 'verify'])->name('payments.razorpay.verify');
    Route::get('/payments/razorpay/{payment}/retry', [\App\Http\Controllers\Web\Payment\RazorpayPaymentController::class, 'retry'])->name('payments.razorpay.retry');
    // Cashfree Phase 3: developer debug page confirming session creation (no SDK yet)
    Route::get('/payments/cashfree/{payment}/pay', [\App\Http\Controllers\Web\Payment\CashfreePaymentController::class, 'pay'])->name('payments.cashfree.pay');
    Route::post('/payments/cashfree/verify', [\App\Http\Controllers\Web\Payment\CashfreePaymentController::class, 'verify'])->name('payments.cashfree.verify');
    Route::get('/payments/cashfree/return/{payment}', [\App\Http\Controllers\Web\Payment\CashfreePaymentController::class, 'return'])->name('payments.cashfree.return');
    Route::get('/payments/failed', function () {
        return view('shop.payment.failed');
    })->name('payments.failed');
});

Route::get('/home', \App\Livewire\Pavilion\HomePage::class)
    ->middleware(['ensure_registration_complete'])
    ->name('home');

Route::get('/welcome', WelcomePage::class)->name('welcome');
Route::get('/gated-entry', GatedEntryPage::class)->name('gated.entry');

Route::get('/login', UserLoginPage::class)
    ->middleware('guest:web')
    ->name('login');

Route::get('/', SplashScreen::class)->name('root');
Route::get('/splash', SplashScreen::class)->name('splash');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'index'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth:web', EnsureAdminRole::class])->prefix('admin')->name('admin.')->group(function () {
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
        Route::get('/products/{id}', \App\Livewire\Admin\Archive\Products\Show::class)->name('products.show');
        Route::get('/enquiries', \App\Livewire\Admin\Archive\Enquiries\Index::class)->name('enquiries');
        Route::get('/orders', \App\Livewire\Admin\Archive\Orders\Index::class)->name('orders.index');
    });

    // Auctions
    Route::prefix('auctions')->name('auctions.')->group(function () {
        // Redirect root 'auctions' to lots index if needed, or just use lots index as main
        Route::get('/', \App\Livewire\Admin\Auctions\Lots\Index::class)->name('index'); // Keeping 'index' as name for backward compat if needed? 
        // Request said: "Fix it to lots index route." and used 'admin.auctions.lots.index' in breadcrumb.
        // So I should name it 'lots.index' ?
        
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
        Route::get('/products/{id}', \App\Livewire\Admin\Shop\Products\Show::class)->name('products.show');
        Route::get('/carts', \App\Livewire\Admin\Shop\Carts\Index::class)->name('carts');
        Route::get('/inventory', \App\Livewire\Admin\Shop\Inventory\Index::class)->name('inventory');
        Route::get('/orders', \App\Livewire\Admin\Shop\Orders\Index::class)->name('orders');
        Route::get('/orders/{id}', \App\Livewire\Admin\Shop\Orders\Show::class)->name('orders.show');
    });

    // Enquiries
    Route::get('/enquiries', \App\Livewire\Admin\Enquiries\Index::class)->name('enquiries.index');

    // Vault Access
    Route::get('/vault-access', \App\Livewire\Admin\Vault\Index::class)->name('vault-access.index');
    Route::get('/vault-access/{user}', \App\Livewire\Admin\Vault\Show::class)->name('vault-access.show');
    Route::get('/vault/removal-requests', \App\Livewire\Admin\Vault\RemovalRequests::class)->name('vault.removal-requests');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', \App\Livewire\Admin\Reports\Index::class)->name('index');
        Route::get('/sales', \App\Livewire\Admin\Reports\SalesReport::class)->name('sales');
        Route::get('/membership', \App\Livewire\Admin\Reports\MembershipReport::class)->name('membership');
        Route::get('/auctions', \App\Livewire\Admin\Reports\AuctionReport::class)->name('auctions');
        Route::get('/vault', \App\Livewire\Admin\Reports\VaultLedgerReport::class)->name('vault');
    });
});

// Membership Application Flow (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/membership/apply-intro', \App\Livewire\Membership\ApplyIntroPage::class)->name('membership.apply-intro');
    Route::get('/membership/apply', \App\Livewire\Membership\ApplyPage::class)->name('membership.apply');
    
    // Application Wizard (Guest Steps)
    Route::get('/membership/application/step-1', \App\Livewire\Membership\Application\Step1RegisterAccount::class)->name('membership.application.step1');
    Route::get('/membership/application/step-2', \App\Livewire\Membership\Application\Step2VerifyOtp::class)->name('membership.application.step2');
});

// Application Wizard (Member Steps - Requires Auth)
Route::middleware(['auth'])->group(function() {
    Route::get('/membership/application/step-3', \App\Livewire\Membership\Application\Step3PersonalDetails::class)->name('membership.application.step3');
    Route::get('/membership/application/step-4', \App\Livewire\Membership\Application\Step4CricketProfile::class)->name('membership.application.step4');
    Route::get('/membership/application/step-5', \App\Livewire\Membership\Application\Step5CollectorIntent::class)->name('membership.application.step5');
    
    Route::get('/membership/application/step-6', \App\Livewire\Membership\Application\Step6SelectTier::class)->name('membership.application.step6');
    Route::get('/membership/application/step-7', \App\Livewire\Membership\Application\Step7Payment::class)->name('membership.application.step7');
    Route::get('/membership/application/step-8', \App\Livewire\Membership\Application\Step8Success::class)->name('membership.application.step8');

    // Upgrade Flow (Member Steps - Requires Auth)
    Route::get('/membership/upgrade/payment', \App\Livewire\Membership\Upgrade\Payment::class)->name('membership.upgrade.payment');
    Route::get('/membership/upgrade/success', \App\Livewire\Membership\Upgrade\Success::class)->name('membership.upgrade.success');
});

Route::post('/webhooks/razorpay', [\App\Http\Controllers\Webhooks\RazorpayWebhookController::class, 'handle'])->name('webhooks.razorpay');
Route::post('/webhooks/cashfree', [\App\Http\Controllers\Webhooks\CashfreeWebhookController::class, 'handle'])->name('webhooks.cashfree');
