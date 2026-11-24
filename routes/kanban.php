<?php

declare(strict_types=1);

use App\Http\Controllers\KanbanArchiveController;
use App\Http\Controllers\KanbanBoardController;
use App\Http\Controllers\KanbanBoardMemberController;
use App\Http\Controllers\KanbanBoardRoleController;
use App\Http\Controllers\KanbanBroadcastController;
use App\Http\Controllers\KanbanCardActivityController;
use App\Http\Controllers\KanbanCardController;
use App\Http\Controllers\KanbanCardHistoryController;
use App\Http\Controllers\KanbanCardTemplateController;
use App\Http\Controllers\KanbanColumnController;
use App\Http\Controllers\KanbanInboxController;
use Illuminate\Support\Facades\Route;

Route::apiResource('boards', KanbanBoardController::class)->except('update');
Route::get('me/tasks', [KanbanBoardController::class, 'assignedTasks']);
Route::get('me/notifications', [KanbanInboxController::class, 'index']);
Route::post('me/notifications/{notification}/read', [KanbanInboxController::class, 'read']);
Route::get('cards/{card}/history', [KanbanCardHistoryController::class, 'index']);
Route::get('boards/{board}/archive', [KanbanArchiveController::class, 'index']);
Route::post('cards/{card}/archive', [KanbanArchiveController::class, 'archive']);
Route::post('cards/{card}/restore', [KanbanArchiveController::class, 'restore']);
Route::apiResource('boards.columns', KanbanColumnController::class)->except('update');
Route::apiResource('columns.cards', KanbanCardController::class)->except('update');
Route::patch('boards/{board}', [KanbanBoardController::class, 'update']);
Route::apiResource('boards.members', KanbanBoardMemberController::class)->only('index', 'store', 'destroy');
Route::apiResource('boards.roles', KanbanBoardRoleController::class)->only('index', 'store', 'destroy');
Route::get('boards/{board}/card-templates', [KanbanCardTemplateController::class, 'index']);
Route::post('boards/{board}/card-templates', [KanbanCardTemplateController::class, 'store']);
Route::post('columns/{column}/cards/from-template', [KanbanCardTemplateController::class, 'apply']);
Route::patch('boards/{board}/roles/{role}', [KanbanBoardRoleController::class, 'update']);
Route::patch('boards/{board}/members/{member}', [KanbanBoardMemberController::class, 'update']);
Route::patch('boards/{board}/columns/{column}', [KanbanColumnController::class, 'update']);
Route::patch('columns/{column}/cards/{card}', [KanbanCardController::class, 'update']);
Route::post('cards/{card}/move', [KanbanCardController::class, 'move']);
Route::get('cards/{card}/activity', [KanbanCardActivityController::class, 'show']);
Route::post('cards/{card}/comments', [KanbanCardActivityController::class, 'comment']);
Route::delete('cards/{card}/comments/{comment}', [KanbanCardActivityController::class, 'deleteComment']);
Route::post('cards/{card}/attachments', [KanbanCardActivityController::class, 'upload']);
Route::delete('cards/{card}/attachments/{attachment}', [KanbanCardActivityController::class, 'deleteAttachment']);
Route::post('broadcasting/auth', KanbanBroadcastController::class);
