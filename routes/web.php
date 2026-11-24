<?php

use App\Http\Controllers\GithubController;
use App\Http\Controllers\KanbanCardActivityController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/auth')->controller(SessionController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout')->middleware('auth:sanctum');
    Route::get('me', 'me')->middleware('auth:sanctum');
});
Route::get('/auth/github/redirect', [GithubController::class, 'start']);
Route::get('/auth/github/callback', [GithubController::class, 'finish']);
// A direct file download can carry the browser session without Origin or Referer.
Route::get('/api/cards/{card}/attachments/{attachment}', [KanbanCardActivityController::class, 'download'])
    ->middleware('auth:sanctum');
Route::view('/{path?}', 'app')->where('path', '(?!(?:api|auth|sanctum|docs|storage)(?:/|$)).*');
