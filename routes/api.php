<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;



Route::middleware('api')->group(function () {
    Route::post('login', [UserController::class, 'login']);
    Route::apiResource('branches', BranchController::class)->only('index');
    Route::apiResource('offers', OfferController::class)->only('index');
    Route::get('offers/all', [OfferController::class, 'all']);
    Route::apiResource('posts', PostController::class)->only('index');
    Route::apiResource('categories', CategoryController::class)->only('index');
    Route::get('employees/branch/{id}', [EmployeeController::class, 'employees_branch']);
    Route::apiResource('positions', PositionController::class)->only('index');

    Route::middleware('auth:api')->group(function () {
        Route::get('authenticated-user', [UserController::class, 'authenticated_user']);
        Route::get('logout', [UserController::class, 'logout']);
        Route::post('edit-profile', [UserController::class, 'edit_profile']);
        Route::post('change-password', [UserController::class, 'change_password']);
        Route::apiResource('branches', BranchController::class)->except('index');
        Route::apiResource('posts', PostController::class)->except('index');
        Route::apiResource('categories', CategoryController::class)->except('index');
        Route::post('offers/{id}', [OfferController::class, 'update']);
        Route::apiResource('offers', OfferController::class)->except('index');
        Route::get('employees/all', [EmployeeController::class, 'all']);
        Route::post('employees/filter', [EmployeeController::class, 'filter']);
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('reviews', ReviewController::class);
        Route::get('stats', [ReviewController::class, 'stats']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('positions', PositionController::class)->except('index');
    });
});
