<?php

use App\Http\Controllers\Api\AssetRouter\AssetController;
use App\Http\Controllers\Api\AssetRouter\JobController;
use App\Http\Controllers\Api\AssetRouter\ProviderController;
use App\Http\Controllers\Api\AssetRouter\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('status', StatusController::class);
Route::get('providers', [ProviderController::class, 'index']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('assets', [AssetController::class, 'index']);
    Route::post('assets', [AssetController::class, 'store']);
    Route::get('assets/{asset}', [AssetController::class, 'show']);
    Route::put('assets/{asset}', [AssetController::class, 'update']);
    Route::patch('assets/{asset}', [AssetController::class, 'update']);
    Route::delete('assets/{asset}', [AssetController::class, 'destroy']);
    Route::get('assets/{asset}/links', [AssetController::class, 'links']);
    Route::post('assets/{asset}/mirror', [AssetController::class, 'mirror']);
    Route::post('assets/{asset}/probe', [ProviderController::class, 'probe']);
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('jobs/{job}', [JobController::class, 'show']);
    Route::post('picgo/upload', [AssetController::class, 'picgoStore']);
});
