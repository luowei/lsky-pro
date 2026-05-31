<?php

use App\Http\Controllers\AssetRouter\ApiDocController;
use App\Http\Controllers\AssetRouter\AssetController;
use App\Http\Controllers\AssetRouter\DashboardController;
use App\Http\Controllers\AssetRouter\UploadController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ar', 'middleware' => ['auth']], function () {
    Route::get('dashboard', DashboardController::class)->name('asset-router.dashboard');
    Route::get('upload', [UploadController::class, 'create'])->name('asset-router.upload');
    Route::post('upload', [UploadController::class, 'store'])->name('asset-router.upload.store');
    Route::get('images', [AssetController::class, 'index'])->name('asset-router.images');
    Route::get('images/{asset}', [AssetController::class, 'show'])->name('asset-router.images.show');
    Route::put('images/{asset}', [AssetController::class, 'update'])->name('asset-router.images.update');
    Route::put('images/{asset}/visibility', [AssetController::class, 'visibility'])->name('asset-router.images.visibility');
    Route::delete('images/{asset}', [AssetController::class, 'destroy'])->name('asset-router.images.destroy');
    Route::get('api', ApiDocController::class)->name('asset-router.api');
});
