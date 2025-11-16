<?php

use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\SocialAuthController;
use App\Http\Controllers\API\TopupController;
use App\Http\Controllers\API\UserAuthController;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\API\WebHookController;
use App\Http\Controllers\CustomizationController;
use Illuminate\Support\Facades\Route;

Route::post('/web-hook/order-update', [WebHookController::class, 'orderUpdate']);
Route::get('/server-status', [CustomizationController::class, 'serverStatus']);

// Authentication
Route::post('/create-account', [UserAuthController::class, 'register']);
Route::post('/sign-in', [UserAuthController::class, 'login']);
Route::post('/google-sign-in', [SocialAuthController::class, 'loginWithGoogle']);

Route::get('/popular-products', [GameController::class, 'getPopularGames']);
Route::get('/products', [GameController::class, 'getGames']);
Route::get('/products/{slug}', [GameController::class, 'gameDetail']);

Route::middleware('auth:sanctum')->group(function () {
    // Account Management
    Route::post('/sign-out', [UserAuthController::class, 'logout']);
    Route::get('/account-info', [UserProfileController::class, 'profile']);
    Route::post('/update-account-info', [UserProfileController::class, 'updateProfile']);
    Route::post('/change-password', [UserProfileController::class, 'updatePassword']);
    Route::delete('/delete-account', [UserProfileController::class, 'deleteAccount']);

    // Customization
    Route::get('/slider', [CustomizationController::class, 'getBanners']);

    Route::get('/banking-options', [TopupController::class, 'getPaymentMethods']);
    Route::post('/deposit', [TopupController::class, 'topup']);
    Route::get('/deposit-history', [TopupController::class, 'getTopupHistory']);

    Route::post('/products/{slug}/check-account', [GameController::class, 'checkGameAccount']);
    Route::post('/games/{slug}/purchase', [GameController::class, 'placeOrder']);

    Route::get('/purchase-history', [GameController::class, 'getOrderHistory']);
    Route::get('/purchase-history/{order_id}', [GameController::class, 'getOrderDetail']);
});
