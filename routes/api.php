<?php

use App\Http\Controllers\FrontEndApi\PropertyController;
use App\Http\Controllers\FrontEndApi\UserChatController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FrontEndApi\AuthApiController;
use App\Http\Controllers\FrontEndApi\FetchRecordApi;
use App\Http\Controllers\FrontEndApi\RenterPreferenceController;
use App\Http\Controllers\FrontEndApi\RentalApplicationController;
use App\Http\Controllers\FrontEndApi\LeaseAgreementController;
use App\Http\Controllers\FrontEndApi\PaymentController;
use App\Http\Controllers\FrontEndApi\ReferralController;
use App\Http\Controllers\FrontEndApi\SupportTicketController;
use App\Http\Controllers\FrontEndApi\UserVerificationController;
use App\Http\Controllers\FrontEndApi\ReviewController;
use App\Http\Controllers\FrontEndApi\RentalInsuranceController;
use App\Http\Controllers\FrontEndApi\NotificationController;
use App\Http\Controllers\FrontEndApi\MaintenanceRequestController;
use App\Http\Controllers\FrontEndApi\DashboardController;
use App\Http\Controllers\FrontEndApi\AdminController;
use App\Http\Controllers\FrontEndApi\AIAssistantController;
use App\Http\Controllers\AdminApplicationController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminReferralController;
use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\AdminVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Broadcast::routes(['middleware' => ['auth:api']]);

Route::post('register', [AuthApiController::class, 'register']);
Route::post('login', [AuthApiController::class, 'login']);
Route::post('logout', [AuthApiController::class, 'logout']);
Route::post('token-save', [AuthApiController::class, 'token_save']);
Route::post('forgot-password', [AuthApiController::class, 'forgotPassword']);
Route::post('reset-password', [AuthApiController::class, 'resetPassword']);
Route::post('/google-login', [AuthApiController::class, 'googleLogin']);
Route::post('/facebook-login', [AuthApiController::class, 'facebookLogin']);
Route::post('/apple-login', [AuthApiController::class, 'appleLogin']);


Route::get('cities', [FetchRecordApi::class, 'cities']);
Route::get('places', [FetchRecordApi::class, 'places']);
Route::get('destination-place/{slug?}', [FetchRecordApi::class, 'destinationPlace']);
Route::get('place-detail/{slug?}', [FetchRecordApi::class, 'place_detail']);
Route::get('amenities', [AuthApiController::class, 'amenities']);
// Route::get(‘auth/google', ‘AuthLoginController@redirectToGoogle');

// Route::get(‘auth/google/callback', ‘AuthLoginController@handleGoogleCallback');”


Route::get('auth/google', [AuthApiController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthApiController::class, 'googleCallback']);


Route::middleware('auth:api')->group(function () {
    Route::prefix('property')->controller(PropertyController::class)->group(function () {
        Route::post('create', 'store');
        Route::get('/countries','getCountries');
        Route::get('wish-lists', 'wish_lists');
        Route::post('wish-list-create', 'wish_list_store');
        Route::post('wish-list-delete', 'wish_list_delete');
    });
    Route::get('users', [UserChatController::class, 'users']);
    Route::post('user-chat', [UserChatController::class, 'user_chat']);
    Route::post('reserve', [UserChatController::class, 'reserve']);
    Route::get('chats', [UserChatController::class, 'getChats']);
    Route::post('send-message', [UserChatController::class, 'send_message']);
    Route::get('chats/unread-count', [UserChatController::class, 'unreadCount']);
    Route::post('chats/mark-read', [UserChatController::class, 'markRead']);
    Route::get('chats/conversations', [UserChatController::class, 'conversations']);
    Route::get('user-detail/{id}', [UserChatController::class, 'user_detail']);
    Route::post('profile-update', [AuthApiController::class, 'profile_update']);

    // Renter Preferences
    Route::prefix('preferences')->controller(RenterPreferenceController::class)->group(function () {
        Route::get('/', 'show');
        Route::post('/', 'store');
    });

    // Advanced Property Search
    Route::get('property/search', [RenterPreferenceController::class, 'searchProperties']);

    // Rental Applications
    Route::prefix('applications')->controller(RentalApplicationController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('/upload-document', 'uploadDocument');
        Route::get('/{id}', 'show');
        Route::post('/{id}/status', 'updateStatus');
        Route::post('/{id}/withdraw', 'withdraw');
    });

    // Lease Agreements
    Route::prefix('leases')->controller(LeaseAgreementController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/create-from-application', 'createFromApplication');
        Route::post('/{id}/sign', 'sign');
        Route::post('/{id}/terminate', 'terminate');
    });

    // Payments
    Route::prefix('payments')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/initiate', 'initiatePayment');
        Route::get('/breakdown/{leaseId}', 'breakdown');
        Route::get('/{id}', 'show');
        Route::post('/{id}/confirm', 'confirmPayment');
    });

    // Referrals
    Route::prefix('referrals')->controller(ReferralController::class)->group(function () {
        Route::post('/generate-code', 'generateCode');
        Route::post('/apply-code', 'applyCode');
        Route::get('/my-referrals', 'myReferrals');
    });

    // Support Tickets
    Route::prefix('support')->controller(SupportTicketController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
    });

    // User Verification
    Route::prefix('verification')->controller(UserVerificationController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/submit', 'submit');
        Route::get('/status', 'status');
    });

    // Reviews
    Route::prefix('reviews')->controller(ReviewController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
    });

    // Rental Insurance
    Route::prefix('insurance')->controller(RentalInsuranceController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/summary', 'summary');
        Route::get('/{id}', 'show');
    });

    // Notifications
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/unread-count', 'unreadCount');
        Route::post('/mark-all-read', 'markAllAsRead');
        Route::post('/{id}/read', 'markAsRead');
        Route::delete('/{id}', 'destroy');
    });

    // Maintenance Requests
    Route::prefix('maintenance')->controller(MaintenanceRequestController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}/status', 'updateStatus');
    });

    // Dashboard
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // AI Assistant
    Route::prefix('ai-assistant')->controller(AIAssistantController::class)->group(function () {
        Route::post('/chat', 'chat');
        Route::get('/suggestions', 'suggestions');
    });

    // My Properties (Landlord)
    Route::get('property/my-properties', [PropertyController::class, 'myProperties']);
    Route::post('property/{id}/update', [PropertyController::class, 'update']);
    Route::delete('property/{id}', [PropertyController::class, 'destroy']);
    Route::delete('property/image/{imageId}', [PropertyController::class, 'deleteImage']);
});
Route::prefix('property')->controller(PropertyController::class)->group(function () {
    Route::get('lists', 'lists');
});
Route::get('property-detail/{slug}/{id}', [PropertyController::class, 'property_detail']);
Route::get('reviews/property/{propertyId}', [ReviewController::class, 'propertyReviews']);
Route::get('reviews/user/{userId}', [ReviewController::class, 'userReviews']);

// Admin routes
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    // Core admin (stats, users, properties)
    Route::controller(AdminController::class)->group(function () {
        Route::get('stats', 'stats');
        Route::get('users', 'users');
        Route::get('users/{id}', 'userDetail');
        Route::post('users/{id}/role', 'updateUserRole');
        Route::delete('users/{id}', 'deleteUser');
        Route::get('properties', 'properties');
        Route::delete('properties/{id}', 'deleteProperty');
    });

    // Applications management
    Route::prefix('applications')->controller(AdminApplicationController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/documents/{id}/verify', 'verifyDocument');
    });

    // Payments management
    Route::prefix('payments')->controller(AdminPaymentController::class)->group(function () {
        Route::get('/dashboard', 'dashboard');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/{id}/landlord-payout', 'processLandlordPayout');
        Route::post('/{id}/insurance-payout', 'processInsurancePayout');
    });

    // Referrals management
    Route::prefix('referrals')->controller(AdminReferralController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/{id}/complete', 'markCompleted');
        Route::post('/{id}/pay', 'processPayment');
    });

    // Support tickets management
    Route::prefix('support')->controller(AdminSupportController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/{id}/respond', 'respond');
    });

    // Verifications management
    Route::prefix('verifications')->controller(AdminVerificationController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/{id}/status', 'updateStatus');
    });
});
