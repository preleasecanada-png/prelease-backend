<?php

use App\Http\Controllers\AllHostController;
use App\Http\Controllers\AmentieController;
use App\Http\Controllers\CityController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\FrontEndApi\AuthApiController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminVerificationController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\AdminReferralController;
use App\Http\Controllers\AdminApplicationController;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('sign_in');
});


Broadcast::routes(['middleware' => ['auth:api']]);


Route::get('/verify-email/{token}', [AuthApiController::class, 'verify'])->name('user.verify_email');

Route::controller(UserController::class)->group(function () {
    Route::get('admin/login', 'sign_in')->name('sign_in');
    Route::post('admin/login', 'sign_in_do')->name('sign.in.do');
    // Route::get('sign-up', 'sign_up')->name('sign_up');
    // Route::post('sign-up', 'sign_up_do')->name('sign_up_do');
    Route::get('logout', 'logout')->name('logout');
});

Route::middleware('auth')->prefix('acccount')->group(function () {
    Route::get('dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::prefix('setting')->controller(SettingController::class)->as('setting.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'profile_update')->name('profile_update');
        Route::post('/password-update', 'password_update')->name('password_update');
    });

    Route::prefix('properties')->controller(PropertyController::class)->as('properties.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('create', 'create_do')->name('create_do');
        Route::get('edit/{id?}', 'edit')->name('edit');
        Route::post('edit', 'update')->name('update');
        Route::delete('delete', 'delete')->name('delete');
        Route::get('delete-property-images', 'delete_property_images')->name('delete_property_images');
        Route::get('export', 'export')->name('export');
    });
    Route::prefix('amenities')->controller(AmentieController::class)->as('amenities.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('create', 'create_do')->name('create_do');
        Route::get('edit/{id?}', 'edit')->name('edit');
        Route::post('edit', 'update')->name('update');
        Route::delete('delete', 'delete')->name('delete');
    });
    Route::prefix('cities')->controller(CityController::class)->as('cities.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('create', 'create_do')->name('create_do');
        Route::get('edit/{id?}', 'edit')->name('edit');
        Route::post('edit', 'update')->name('update');
        Route::delete('delete', 'delete')->name('delete');
    });

    Route::prefix('user-chats')->controller(UserChatController::class)->as('user.chats.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('view/{id}', 'view')->name('view');
    });

    Route::prefix('all-hosts')->controller(AllHostController::class)->as('all.hosts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('view/{id}', 'view')->name('view');
    });

    // Admin Verification Management
    Route::prefix('verifications')->controller(AdminVerificationController::class)->as('verifications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}/update-status', 'updateStatus')->name('update_status');
    });

    // Admin Payment Management
    Route::prefix('payments')->controller(AdminPaymentController::class)->as('payments.')->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}/landlord-payout', 'processLandlordPayout')->name('landlord_payout');
        Route::post('/{id}/insurance-payout', 'processInsurancePayout')->name('insurance_payout');
    });

    // Admin Support Ticket Management
    Route::prefix('support-tickets')->controller(AdminSupportController::class)->as('support.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}/respond', 'respond')->name('respond');
    });

    // Admin Referral Management
    Route::prefix('referrals')->controller(AdminReferralController::class)->as('referrals.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{id}/complete', 'markCompleted')->name('complete');
        Route::post('/{id}/pay', 'processPayment')->name('pay');
    });

    // Admin Application Management
    Route::prefix('rental-applications')->controller(AdminApplicationController::class)->as('rental.applications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/document/{id}/verify', 'verifyDocument')->name('verify_document');
    });
});
