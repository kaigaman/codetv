<?php

use App\Http\Controllers\Web\HomeController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/watch/{slug}', [HomeController::class, 'watch'])->name('watch');
Route::get('/browse', [HomeController::class, 'browse'])->name('browse');
Route::get('/uganda', [HomeController::class, 'uganda'])->name('uganda');
Route::get('/uganda/working', [HomeController::class, 'ugandaWorking'])->name('uganda.working');
Route::get('/channels', [HomeController::class, 'browse'])->name('channels');
Route::get('/tv-guide', [HomeController::class, 'guide'])->name('guide');
Route::get('/favorites', [HomeController::class, 'favorites'])->name('favorites');
Route::get('/sports', [HomeController::class, 'sports'])->name('sports');
Route::get('/international', [HomeController::class, 'international'])->name('international');
Route::get('/worldcup', [HomeController::class, 'worldcup'])->name('worldcup');

Route::get('/m3u/uganda-verified.m3u8', function () {
    $pythonApi = config('services.python_api.url');
    $resp = Http::timeout(15)->get("{$pythonApi}/api/v1/m3u/uganda");
    if ($resp->successful()) {
        return response($resp->body(), 200)
            ->header('Content-Type', 'audio/x-mpegurl')
            ->header('Content-Disposition', 'inline; filename="uganda-verified.m3u8"');
    }
    abort(502, 'Failed to generate playlist');
});
