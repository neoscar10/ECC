<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MembershipTierController;
use App\Http\Controllers\Api\V1\MembershipStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // Auth Routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        // Password Reset Routes
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('password/request-otp', [App\Http\Controllers\Api\V1\Auth\PasswordResetController::class, 'requestOtp']);
            Route::post('password/reset', [App\Http\Controllers\Api\V1\Auth\PasswordResetController::class, 'reset']);
        });
        
        // Protected Auth Routes
        Route::middleware('auth:api')->group(function () {
             Route::post('refresh', [AuthController::class, 'refresh']);
             Route::post('logout', [AuthController::class, 'logout']);
             Route::get('me', [AuthController::class, 'me']);
             
             // OTP
             Route::post('request-otp', [\App\Http\Controllers\Api\V1\PhoneVerificationController::class, 'requestOtp']);
             Route::post('verify-otp', [\App\Http\Controllers\Api\V1\PhoneVerificationController::class, 'verifyOtp']);

             // Password Change
             Route::post('change-password', [AuthController::class, 'changePassword']);
        });
    });

    // Public Meta Routes
    Route::prefix('meta')->group(function () {
        Route::get('cricket-profile-options', [\App\Http\Controllers\Api\V1\MetaController::class, 'getCricketProfileOptions']);
        Route::get('collector-intent-options', [\App\Http\Controllers\Api\V1\MetaController::class, 'getCollectorIntentOptions']);
    });

    // Protected Application Routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/membership-application/current', [App\Http\Controllers\Api\V1\MembershipApplicationController::class, 'current']);
        
        // Membership Tiers
        Route::get('membership-tiers', [MembershipTierController::class, 'index']);
        Route::get('membership-tiers/{id}', [MembershipTierController::class, 'show']);

        Route::middleware(['verified_phone'])->prefix('membership-applications/{id}')->group(function () {
            Route::patch('/personal-details', [App\Http\Controllers\Api\V1\MembershipApplicationController::class, 'savePersonalDetails']);
            Route::patch('/cricket-profile', [App\Http\Controllers\Api\V1\MembershipApplicationController::class, 'saveCricketProfile']);
            Route::patch('/collector-intent', [App\Http\Controllers\Api\V1\MembershipApplicationController::class, 'saveCollectorIntent']);
            Route::post('/select-tier', [App\Http\Controllers\Api\V1\MembershipApplicationController::class, 'selectTier']);
            Route::post('/payment/confirm', [App\Http\Controllers\Api\V1\MembershipApplicationController::class, 'confirmPayment']);
            Route::post('/submit', [App\Http\Controllers\Api\V1\MembershipApplicationController::class, 'submitApplication']);
        });
    });

    // Membership Status (Flutter Check)
    Route::middleware('auth:api')->get('/membership/status', [MembershipStatusController::class, 'status']);

    // Archive Routes
    Route::middleware('auth:api')->prefix('archive')->group(function () {
        Route::get('categories', [\App\Http\Controllers\Api\V1\Archive\ArchiveCategoryController::class, 'index']);
        Route::get('categories/{id}', [\App\Http\Controllers\Api\V1\Archive\ArchiveCategoryController::class, 'show']);
        Route::get('products', [\App\Http\Controllers\Api\V1\Archive\ArchiveProductController::class, 'index']);
        Route::get('products/{id}', [\App\Http\Controllers\Api\V1\Archive\ArchiveProductController::class, 'show']);
        
        // Enquiry
        Route::post('enquiries', [\App\Http\Controllers\Api\V1\Archive\ArchiveEnquiryController::class, 'store']);
    });

    // Auction Routes
    Route::middleware('auth:api')->prefix('auctions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\AuctionController::class, 'index']);
        // Enquiries List (Must be before {id})
        Route::get('/enquiries', [\App\Http\Controllers\Api\V1\Auctions\AuctionEnquiryController::class, 'index']);
        
        // Dossier (Profile) - Must be before {id}
        Route::get('/dossier', [\App\Http\Controllers\Api\V1\Auctions\AuctionDossierController::class, 'index']);

        Route::get('/{id}', [\App\Http\Controllers\Api\V1\AuctionController::class, 'show']);
        Route::post('/{id}/enquiries', [\App\Http\Controllers\Api\V1\Auctions\AuctionEnquiryController::class, 'store']); // Create Enquiry
        Route::post('/{id}/bid', [\App\Http\Controllers\Api\V1\AuctionController::class, 'bid']);
        Route::post('/{id}/auto-bid', [\App\Http\Controllers\Api\V1\AuctionController::class, 'autoBid']);
        Route::delete('/{id}/auto-bid', [\App\Http\Controllers\Api\V1\AuctionController::class, 'cancelAutoBid']);
    });

    // Shop Routes
    Route::middleware('auth:api')->prefix('shop')->name('shop.')->group(function () {
        Route::get('categories/tree', [\App\Http\Controllers\Api\V1\Shop\ShopCategoryController::class, 'tree'])->name('categories.tree');
        Route::get('categories', [\App\Http\Controllers\Api\V1\Shop\ShopCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{id}', [\App\Http\Controllers\Api\V1\Shop\ShopCategoryController::class, 'show'])->name('categories.show');
        Route::get('categories/{id}/children', [\App\Http\Controllers\Api\V1\Shop\ShopCategoryController::class, 'children'])->name('categories.children');

        // Tags
        Route::get('tags/groups', [\App\Http\Controllers\Api\V1\Shop\ShopTagGroupController::class, 'index'])->name('tags.groups.index');
        Route::get('tags/groups/{id}', [\App\Http\Controllers\Api\V1\Shop\ShopTagGroupController::class, 'show'])->name('tags.groups.show');
        Route::get('tags', [\App\Http\Controllers\Api\V1\Shop\ShopTagController::class, 'index'])->name('tags.index');
        Route::get('tags/{id}', [\App\Http\Controllers\Api\V1\Shop\ShopTagController::class, 'show'])->name('tags.show');
        // Products
        Route::get('products/filters', [\App\Http\Controllers\Api\V1\Shop\ShopProductController::class, 'filters'])->name('products.filters');
        Route::get('products/suggestions', [\App\Http\Controllers\Api\V1\Shop\ShopProductController::class, 'suggestions'])->name('products.suggestions');
        Route::get('products', [\App\Http\Controllers\Api\V1\Shop\ShopProductController::class, 'index'])->name('products.index');
        Route::get('products/{id}', [\App\Http\Controllers\Api\V1\Shop\ShopProductController::class, 'show'])->name('products.show');

        // Shop Addresses
        Route::apiResource('addresses', \App\Http\Controllers\Api\V1\Shop\AddressController::class);

        // Shop Checkout
        Route::prefix('checkout')->group(function () {
            Route::get('summary', [\App\Http\Controllers\Api\V1\Shop\CheckoutController::class, 'summary']);
            Route::post('place-order', [\App\Http\Controllers\Api\V1\Shop\CheckoutController::class, 'placeOrder']);
        });

        // Shop Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Shop\ShopOrderController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\Shop\ShopOrderController::class, 'show']);
            Route::post('/{id}/confirm-payment', [\App\Http\Controllers\Api\V1\Shop\ShopOrderController::class, 'confirmPayment']);
            Route::post('/{id}/cancel', [\App\Http\Controllers\Api\V1\Shop\ShopOrderController::class, 'cancel']);
        });
    });


    // Cart Routes
    Route::middleware('auth:api')->prefix('cart')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Shop\CartController::class, 'index']);
        Route::delete('/', [\App\Http\Controllers\Api\V1\Shop\CartController::class, 'clear']);
        Route::post('/items', [\App\Http\Controllers\Api\V1\Shop\CartController::class, 'addItem']);
        Route::match(['put', 'patch'], '/items/{id}', [\App\Http\Controllers\Api\V1\Shop\CartController::class, 'updateItem']);
        Route::delete('/items/{id}', [\App\Http\Controllers\Api\V1\Shop\CartController::class, 'removeItem']);
    });

    // Mobile Broadcasting Auth (JWT)
    Route::middleware('auth:api')->prefix('broadcasting')->group(function () {
        Route::post('auth', [\App\Http\Controllers\Api\V1\BroadcastController::class, 'authenticate']);
    });

    // Admin Routes
    Route::middleware(['auth:api', 'role:ecc_admin|super_admin'])->prefix('admin')->group(function () {
        Route::patch('memberships/{id}/approve', [MembershipStatusController::class, 'approve']);
        Route::patch('memberships/{id}/reject', [MembershipStatusController::class, 'reject']);
        
        // Realtime Auth
        Route::post('broadcasting/auth', [\App\Http\Controllers\Api\V1\BroadcastController::class, 'authenticate']);

        Route::post('broadcast/test', function () {
            // TODO: Dispatch real event
            return response()->json(['message' => 'Broadcast triggered']);
        });
    });

    // Device Tokens Routes
    Route::middleware('auth:api')->prefix('me/device-tokens')->group(function () {
         Route::post('/', [\App\Http\Controllers\Api\V1\UserDeviceTokenController::class, 'register']);
         Route::get('/', [\App\Http\Controllers\Api\V1\UserDeviceTokenController::class, 'index']);
         Route::post('/unregister', [\App\Http\Controllers\Api\V1\UserDeviceTokenController::class, 'unregister']);
         Route::delete('/{id}', [\App\Http\Controllers\Api\V1\UserDeviceTokenController::class, 'destroy']);
    });
    
    // Auction Subscriptions
    Route::middleware('auth:api')->group(function () {
        Route::put('auctions/{id}/notification-subscription', [\App\Http\Controllers\Api\V1\AuctionSubscriptionController::class, 'toggle']);
        Route::get('me/auction-notification-subscriptions', [\App\Http\Controllers\Api\V1\AuctionSubscriptionController::class, 'index']);
    });
});
