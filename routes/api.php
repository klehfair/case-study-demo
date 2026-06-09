<?php

use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::post('/conversation',               [ConversationController::class, 'send']);
Route::get('/conversation/{sessionId}',    [ConversationController::class, 'history'])
    ->where('sessionId', '[a-zA-Z0-9_\-]+');
Route::delete('/conversation/{sessionId}', [ConversationController::class, 'clear'])
    ->where('sessionId', '[a-zA-Z0-9_\-]+');
