<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\EmergencyContactController;
use App\Http\Controllers\Api\FallController;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
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

Route::middleware('check.accept')->group(function () {
    //======================= Authentication =======================
    Route::group([], function () {
        // Caregivers
        Route::prefix('caregivers')->group(function () {
            Route::group(["middleware" => ["check.token:Caregiver", "guest:sanctum"]], function () {
                // Register a new user
                Route::post('register', [\App\Http\Controllers\Api\CaregiverController::class, 'register']);
                // Login a user
                Route::post('login', [\App\Http\Controllers\Api\CaregiverController::class, 'login']);
                // Verify Email
                Route::post('verify-email', [\App\Http\Controllers\Api\CaregiverController::class, 'verifyEmail']);
                Route::post('resend-code', [\App\Http\Controllers\Api\CaregiverController::class, 'resendOtp']);

                // Reset Password
                Route::post('forgot-password', [\App\Http\Controllers\Api\CaregiverController::class, 'forgotPassword']);
                Route::post('reset-password', [\App\Http\Controllers\Api\CaregiverController::class, 'resetPassword']);
            });

            Route::group(["middleware" => ["check.role", "auth:sanctum"]], function () {
                Route::get('/', [App\Http\Controllers\Api\CaregiverController::class, 'index']);

                Route::prefix('me')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\CaregiverController::class, 'me']);
                    Route::post('logout', [App\Http\Controllers\Api\CaregiverController::class, 'logout']);

                    Route::get('patients', [App\Http\Controllers\Api\CaregiverController::class, 'patients']);
                    Route::get('patients/{id}', [App\Http\Controllers\Api\CaregiverController::class, 'patient']);

                    Route::get('patients/{id}/contacts', [App\Http\Controllers\Api\CaregiverController::class, 'contacts']);
                    Route::get('patients/{id}/contacts/{contact_id}', [App\Http\Controllers\Api\CaregiverController::class, 'contact']);

                    Route::get('patients/{id}/falls', [App\Http\Controllers\Api\CaregiverController::class, 'falls']);
                });
            });

            // Logout a user
            Route::middleware("auth:sanctum")->post('logout', [\App\Http\Controllers\Api\CaregiverController::class, 'logout']);
            
        });

            // Patients
            Route::prefix('patients')->group(function () {
                Route::group(["middleware" => ["check.token:Patient", "guest:sanctum"]], function () {
                    // Register a new user
                    Route::post('register', [\App\Http\Controllers\Api\UserController::class, 'register']);
                    // Login a user
                    Route::post('login', [\App\Http\Controllers\Api\UserController::class, 'login']);
                    // Verify Email
                    Route::post('verify-email', [\App\Http\Controllers\Api\UserController::class, 'verifyEmail']);
                    Route::post('resend-code', [\App\Http\Controllers\Api\UserController::class, 'resendOtp']);

                    // Reset Password
                    Route::post('forgot-password', [\App\Http\Controllers\Api\UserController::class, 'forgotPassword']);
                    Route::post('reset-password', [\App\Http\Controllers\Api\UserController::class, 'resetPassword']);

                });

                // Logout a user
                Route::middleware('auth:sanctum')->post('logout', [\App\Http\Controllers\Api\UserController::class, 'logout']);
            });

            // Social Auth
            Route::prefix('auth')->group(function () {
                Route::get('google', [\App\Http\Controllers\Api\AuthController::class, 'redirectToGoogle']);
                Route::get('google/callback', [\App\Http\Controllers\Api\AuthController::class, 'handleGoogleCallback']);
            });

            // Search
            Route::get('search', [App\Http\Controllers\Api\SearchController::class, 'search']);
        });

        Route::group(["middleware" => ['auth:sanctum']], function () {
            // ======================= Shared Routes ===================
            Route::middleware(['check.role'])->group(function () {
                Route::prefix('me')->group(function () { // me refer to Caregiver not patient in this case
                    Route::get('/', function (Request $request) {
                        if ($request->query('deep') == 'true') {
                            if ($request->user()->role == 'caregiver') {
                                $request->user()->load('patients', 'patients.contacts', 'patients.falls');
                            }
                        }

                        return response()->json([
                            'data' => new UserResource($request->user()),
                        ]);
                    });

                    // Chats
                    Route::get('/chats', [ChatController::class, 'latestChats']);

                    // Follow-ups
                    // Caregiver can follow many patients, but patients cannot follow anyone
                    Route::post('follow/{id}', [App\Http\Controllers\Api\CaregiverController::class, 'follow']);
                    Route::post('unfollow/{id}', [App\Http\Controllers\Api\CaregiverController::class, 'unfollow']);
                });

                // Logout
                Route::post('auth/logout', [\App\Services\AuthService::class, 'logout']);

                // Emergency Contacts
                Route::apiResource('emergency-contacts', EmergencyContactController::class);

                // Falls
                Route::apiResource('falls', FallController::class);
                Route::get('falls/{id}/user', [FallController::class, 'user']);

                // Chat
                Route::group([
                    'prefix' => 'chat',
                    'middleware' => 'auth:sanctum',
                ], function () {
                    Route::get('/{other_id}', [ChatController::class, 'getMessagesOfOtherUser']);
                    Route::post('/{receiver_id}', [ChatController::class, 'sendMessage']);

                    // Route::get('/{receiver_id}/unread', [ChatController::class, 'getUnreadMessages']);
                    // Route::post('/{receiver_id}/read', [ChatController::class, 'markAsRead']);
                });
            });

            //======================= Caregivers =======================
            Route::prefix('caregivers')->middleware(['check.role'])->group(function () {

            });
        });

        //======================= Patients =======================
        Route::prefix('patients')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\UserController::class, 'index']); // Get all users

            Route::prefix('{id}')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\UserController::class, 'show'])->where([
                    'id' => '[0-9]+',
                ]); // Get a specific user

                Route::get('/contacts', [App\Http\Controllers\Api\UserController::class, 'contacts'])->where([
                    'id' => '[0-9]+',
                ]); // Get all contacts for a user

                Route::get('/contacts/{contact_id}', [App\Http\Controllers\Api\UserController::class, 'contact'])->where([
                    'id' => '[0-9]+',
                ]); // Get specific contacts for a user

                Route::get('/falls', [App\Http\Controllers\Api\UserController::class, 'falls'])->where([
                    'id' => '[0-9]+',
                ]); // Get all falls for a user
            });

            Route::prefix('me')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\UserController::class, 'me']); // Get the current user
                Route::post('logout', [App\Http\Controllers\Api\UserController::class, 'logout']); // Logout a user
            });

            Route::post('follow/{id}', [App\Http\Controllers\Api\UserController::class, 'follow']);
            Route::post('unfollow/{id}', [App\Http\Controllers\Api\UserController::class, 'unfollow']);
        });
    });
});
