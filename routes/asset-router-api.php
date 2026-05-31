<?php

use App\Http\Controllers\Api\AssetRouter\AssetController;
use App\Http\Controllers\Api\AssetRouter\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('status', StatusController::class);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('assets', [AssetController::class, 'index']);
    Route::post('assets', [AssetController::class, 'store']);
});
