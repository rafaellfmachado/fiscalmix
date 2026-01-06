<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Test route
Route::get('/test-api', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API routes are working!',
    ]);
});

// Auth routes
Route::post('/auth/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
Route::post('/auth/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout']);
    Route::get('/auth/me', [App\Http\Controllers\Auth\AuthController::class, 'me']);
});