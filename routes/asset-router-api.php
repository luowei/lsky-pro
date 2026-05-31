<?php

use App\Http\Controllers\Api\AssetRouter\AssetController;
use App\Http\Controllers\Api\AssetRouter\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('status', StatusController::class);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('assets', [AssetController::class, 'index']);
    Route::post('assets', [AssetController::class, 'store']);
    Route::get('assets/{asset}', [AssetController::class, 'show']);
    Route::put('assets/{asset}', [AssetController::class, 'update']);
    Route::delete('assets/{asset}', [AssetController::class, 'destroy']);
    Route::post('picgo/upload', [AssetController::class, 'picgoStore']);
});
