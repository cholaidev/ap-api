<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// 🧑 Register
Route::post('/register', [AuthController::class, 'register']);

// 🔐 Login
Route::post('/login', [AuthController::class, 'login']);

// 🚪 Logout (requires auth)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/customers', [CustomerController::class, 'index'])->middleware('auth:sanctum');
Route::post('/customers', [CustomerController::class, 'store'])->middleware('auth:sanctum');

// 👤 Get current authenticated user
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
