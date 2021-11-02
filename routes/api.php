<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessageController;
use GuzzleHttp\Psr7\Request;

// Auth
Route::post('register', [AuthController::class, 'register'])
    ->name('register')
    ->middleware('guest');

Route::post('login', [AuthController::class, 'login'])
    ->name('login')
    ->middleware('guest');

Route::post('logout', [AuthController::class, 'logout'])
    ->name('login')
    ->middleware('auth:sanctum');

// users
Route::prefix('users')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::prefix('profile')->group(function () {
            Route::get('/', [UserController::class, 'profileIndex']);
            Route::put('/', [UserController::class, 'profileUpdate']);
            Route::put('/change-password', [
                UserController::class,
                'profileChangePassword',
            ]);
        });
        Route::get('', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::delete('delete/{id}', [UserController::class, 'destroy']);
        Route::get('chart/{start}/{end}', [MessageController::class, 'getAllMessageInPeriodOfTime',]);
        Route::get('cake/{start}/{end}', [MessageController::class, 'cakeChart']);
    });

//rooms
Route::prefix('rooms')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('', [RoomController::class, 'index']);
        Route::get('/user', [RoomController::class, 'userRooms']);
        Route::get('/{name}', [RoomController::class, 'show']);
        Route::get('search/{name}', [RoomController::class, 'search']);
        Route::post('/', [RoomController::class, 'store']);
        Route::put('/join', [RoomController::class, 'join']);
        Route::put('/left', [RoomController::class, 'left']);
        Route::put('/reset-key', [RoomController::class, 'resetKey']);
        Route::put('/update/{id}', [RoomController::class, 'update']);
        Route::delete('/{id}', [RoomController::class, 'destroy']);
        Route::delete('/{room_id}/user/{user_id}', [
            RoomController::class,
            'deleteMember',
        ]);
    });

//messages
Route::middleware('auth:sanctum')
    ->prefix('messages')
    ->group(function () {
        Route::get('/room/{id}', [MessageController::class, 'index']);
        Route::post('/room', [MessageController::class, 'store']);
    });

Route::post('/token', function (Request $r) {
    if (auth()->user()) {
        return response()->json(auth()->user());
    }
    return response()->json('error', 404);
});
