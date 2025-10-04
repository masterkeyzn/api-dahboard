<?php

use App\Http\Controllers\API\AgentController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BankController;
use App\Http\Controllers\API\DepositController;
use App\Http\Controllers\API\PromotionController;
use App\Http\Controllers\API\ReferralController;
use App\Http\Controllers\API\ReportsController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WebController;
use App\Http\Controllers\API\WithdrawalController;
use App\Http\Middleware\OwnerApi;
use App\Http\Middleware\SetDatabaseConnection;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', SetDatabaseConnection::class])->group(function () {

    //admin
    Route::get('/auth/admin', [AuthController::class, 'authAdmin']);
    Route::post('/admin/reset-password', [AuthController::class, 'resetPasswordAdmin']);
    Route::get('/admin/informations', [AuthController::class, 'agentStatistic']);
    Route::get('/admin', [AuthController::class, 'adminIndex']);
    Route::post('/admin', [AuthController::class, 'createAdmin']);
    Route::put('/admin', [AuthController::class, 'updateAdmin']);
    Route::delete('/admin/{id}', [AuthController::class, 'deleteAdmin']);
    Route::get('web-informations', [AuthController::class, 'webInformations']);
    Route::get('/roles', [AgentController::class, 'rolesIndex']);

    //users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user/{userId}', [UserController::class, 'show']);
    Route::post('/user/{userId}/deposit', [UserController::class, 'deposit']);
    Route::post('/user/{userId}/withdrawal', [UserController::class, 'withdrawal']);
    Route::post('/user/{userId}/reset-password', [UserController::class, 'password']);
    Route::post('/user/{userId}/status/{field}', [UserController::class, 'status']);
    Route::post('/user/{userId}/update-bank-account', [UserController::class, 'updateBankAccount']);

    //deposit
    Route::get('/deposit', [DepositController::class, 'index']);
    Route::post('/deposit', [DepositController::class, 'update']);

    //withdrawal
    Route::get('/withdrawal', [WithdrawalController::class, 'index']);
    Route::post('/withdrawal', [WithdrawalController::class, 'update']);

    //referral
    Route::get('/referral', [ReferralController::class, 'index']);
    Route::get('/referral/{id}/user-list', [ReferralController::class, 'userList']);
    Route::put('/referral/{id}', [ReferralController::class, 'update']);
    Route::get('/referral/{id}/approve', [ReferralController::class, 'approveReferral']);
    Route::delete('/referral/{id}', [ReferralController::class, 'destroy']);
    Route::delete('/referrals/bulk-delete', [ReferralController::class, 'bulkDelete']);
    Route::delete('/referrals/{id}', [ReferralController::class, 'deleteUserReferral']);

    //bankdeposit
    Route::get('/bank-deposit', [BankController::class, 'index']);
    Route::post('/bank-deposit', [BankController::class, 'createBank']);
    Route::put('/bank-deposit/{id}', [BankController::class, 'update']);
    Route::patch('/bank-deposit/{id}/status', [BankController::class, 'updateStatus']);
    Route::delete('/bank-deposit/{id}', [BankController::class, 'destroy']);

    //reports
    Route::get('/reports/daily-reports', [ReportsController::class, 'dailyReports']);
    Route::get('/reports/transactions', [ReportsController::class, 'index']);
    Route::get('/reports/promotions', [ReportsController::class, 'promotions']);
    Route::get('/reports/win-lose', [ReportsController::class, 'winLose']);
    Route::get('/reports/win-lose/{playerToken}/history', [ReportsController::class, 'winLoseByPlayerToken']);

    //promotionsDeposit
    Route::get('/promotions/deposit', [PromotionController::class, 'index']);
    Route::delete('/promotions/deposit/{id}', [PromotionController::class, 'destroy']);
    Route::put('/promotions/deposit/{id}', [PromotionController::class, 'update']);
    Route::post('/promotions/deposit', [PromotionController::class, 'store']);
    Route::post('/promotions/share-bonus', [PromotionController::class, 'bulkShareBonus']);

    //webManagement
    Route::get('/web-management/general', [WebController::class, 'general']);
    Route::post('/web-management/general/website', [WebController::class, 'updateWebsite']);
    Route::post('/web-management/general/transaction', [WebController::class, 'updateTransaction']);
    Route::get('/web-management/social-media', [WebController::class, 'socialMedia']);
    Route::post('/web-management/social-media/livechat', [WebController::class, 'updateSocialMediaLivechat']);
    Route::post('/web-management/social-media/{id}', [WebController::class, 'updateSocialMedia']);
    Route::get('/web-management/popup-slider', [WebController::class, 'popupSlider']);
    Route::post('/web-management/popup-slider/popup', [WebController::class, 'updatePopup']);
    Route::post('/web-management/popup-slider/slider', [WebController::class, 'addSlider']);
    Route::delete('/web-management/popup-slider/slider/{id}', [WebController::class, 'deleteSlider']);
    Route::get('/web-management/theme', [WebController::class, 'theme']);
    Route::post('/web-management/theme', [WebController::class, 'updateTheme']);
    Route::get('/web-management/promotion', [WebController::class, 'promotionIndex']);
    Route::post('/web-management/promotion', [WebController::class, 'promotionStore']);
    Route::put('/web-management/promotion/{id}', [WebController::class, 'promotionUpdate']);
    Route::delete('/web-management/promotion/{id}', [WebController::class, 'promotionDestroy']);
    Route::get('/seo-management', [WebController::class, 'seoIndex']);
    Route::post('/seo-management', [WebController::class, 'seoUpdate']);
    Route::get('/web-management/api', [AuthController::class, 'apiCredential']);
    Route::put('/web-management/api', [AuthController::class, 'updateApi']);
    Route::get('/web-management/reset-user-balance', [AuthController::class, 'resetUserBalance']);
    Route::post('/web-management/refreshMigration', [AuthController::class, 'refreshMigration']);
    Route::get('/web-management/domain', [WebController::class, 'indexDomain']);
    Route::post('/web-management/domain', [WebController::class, 'addDomain']);
    Route::put('/web-management/domain/{id}', [WebController::class, 'updateDomain']);
    Route::delete('/web-management/domain/{id}', [WebController::class, 'deleteDomain']);

    //getMenu
    Route::get('/getMenu', [WebController::class, 'getMenu']);
});

Route::middleware(OwnerApi::class)->group(function () {
    Route::prefix('agent')->group(function () {
        Route::get('/', [AgentController::class, 'index']);
        Route::post('/', [AgentController::class, 'agentStore']);
        Route::put('/{id}', [AgentController::class, 'update']);
        Route::delete('/{id}', [AgentController::class, 'destroy']);
    });

    Route::prefix('credential')->group(function () {
        Route::post('/expired-website', [AgentController::class, 'addDays']);
        Route::get('/', [AgentController::class, 'indexCredential']);
        Route::post('/', [AgentController::class, 'storeCredential']);
        Route::put('/{id}', [AgentController::class, 'updateCredential']);
        Route::delete('/{id}', [AgentController::class, 'deleteCredential']);
    });
});
