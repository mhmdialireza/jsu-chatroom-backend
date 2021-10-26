<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

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
        Route::get('', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::delete('delete/{id}', [UserController::class, 'destroy']);
        // Route::delete('delete-by-email/{email}', [UserController::class,'destroyByEmail', ]);
    });

//rooms
Route::prefix('rooms')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('', [RoomController::class, 'index']);
        Route::get('/user', [RoomController::class, 'userRooms']);
        Route::get('/{name}', [RoomController::class, 'show']);
        Route::post('/', [RoomController::class, 'store']);
        Route::put('/join', [RoomController::class, 'join']);
        Route::put('/left', [RoomController::class, 'left']);
        Route::put('/reset-key', [RoomController::class, 'resetKey']);
        Route::put('/update/{id}', [RoomController::class, 'update']);
        Route::delete('/delete/{id}', [RoomController::class, 'delete']);
    });

//messages
Route::middleware('auth:sanctum')
    ->prefix('messages')
    ->group(function () {
        Route::get('/room/{id}', [MessageController::class, 'index']);
        Route::post('/room', [MessageController::class, 'store']);
    });
