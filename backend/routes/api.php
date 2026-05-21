<?php

use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/channels', [ChannelController::class, 'index']);
Route::get('/channels/{channel}', [ChannelController::class, 'show']);
Route::get('/channels/country/{code}', [ChannelController::class, 'byCountry']);
Route::get('/channels/uganda/all', [ChannelController::class, 'uganda']);
Route::get('/channels/uganda/working', [ChannelController::class, 'ugandaWorking']);
Route::get('/channels/random/one', [ChannelController::class, 'random']);

Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/{code}', [CountryController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/languages', [LanguageController::class, 'index']);

Route::get('/search', [SearchController::class, 'search']);
Route::get('/search/suggest', [SearchController::class, 'suggest']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);
    Route::post('/favorites/check', [FavoriteController::class, 'check']);
});
