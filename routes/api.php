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
        
        // Protected Auth Routes
        Route::middleware('auth:api')->group(function () {
             Route::post('refresh', [AuthController::class, 'refresh']);
             Route::post('logout', [AuthController::class, 'logout']);
             Route::get('me', [AuthController::class, 'me']);
             
             // OTP
             Route::post('request-otp', [\App\Http\Controllers\Api\V1\PhoneVerificationController::class, 'requestOtp']);
             Route::post('verify-otp', [\App\Http\Controllers\Api\V1\PhoneVerificationController::class, 'verifyOtp']);
        });
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

    // Admin Routes
    Route::middleware(['auth:api', 'role:ecc_admin|super_admin'])->prefix('admin')->group(function () {
        Route::patch('memberships/{id}/approve', [MembershipStatusController::class, 'approve']);
        Route::patch('memberships/{id}/reject', [MembershipStatusController::class, 'reject']);
        
        Route::post('broadcast/test', function () {
            // TODO: Dispatch real event
            return response()->json(['message' => 'Broadcast triggered']);
        });
    });
});
