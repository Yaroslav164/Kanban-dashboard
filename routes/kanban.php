<?php

declare(strict_types=1);

use App\Http\Controllers\KanbanBoardController;
use App\Http\Controllers\KanbanBroadcastController;
use App\Http\Controllers\KanbanCardController;
use App\Http\Controllers\KanbanColumnController;
use Illuminate\Support\Facades\Route;

Route::apiResource('boards', KanbanBoardController::class)->except('update');
Route::apiResource('boards.columns', KanbanColumnController::class)->except('update');
Route::apiResource('columns.cards', KanbanCardController::class)->except('update');
Route::patch('boards/{board}', [KanbanBoardController::class, 'update']);
Route::patch('boards/{board}/columns/{column}', [KanbanColumnController::class, 'update']);
Route::patch('columns/{column}/cards/{card}', [KanbanCardController::class, 'update']);
Route::post('cards/{card}/move', [KanbanCardController::class, 'move']);
Route::post('broadcasting/auth', KanbanBroadcastController::class);
