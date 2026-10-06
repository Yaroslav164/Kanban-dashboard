<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

// Serve the built Vue SPA (frontend is compiled into public/ by Vite).
Route::get('/', function () {
    return Response::file(public_path('index.html'), [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
});
