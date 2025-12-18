<?php

use App\Http\Controllers\SupplyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
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
Route::get('/customers/{id}', [CustomerController::class, 'show'])->middleware('auth:sanctum');
Route::put('/customers/{id}', [CustomerController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->middleware('auth:sanctum');


Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::get('/{id}', [EmployeeController::class, 'show']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::put('/{id}', [EmployeeController::class, 'update']);
    Route::delete('/{id}', [EmployeeController::class, 'destroy']);
});

Route::prefix('supplies')->group(function () {
    Route::get('/', [SupplyController::class, 'index']);
    Route::get('/{id}', [SupplyController::class, 'show']);
    Route::post('/', [SupplyController::class, 'store']);
    Route::put('/{id}', [SupplyController::class, 'update']);
    Route::delete('/{id}', [SupplyController::class, 'destroy']);
});


Route::apiResource('supplies', SupplyController::class);

// 👤 Get current authenticated user
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
