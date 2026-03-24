<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

// Keep mutating API actions protected; allow read endpoints to fail-soft for session-based frontends.
Route::get('/notifications/count', [NotificationController::class, 'count']);
Route::get('/notifications/dropdown', [NotificationController::class, 'dropdown']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});
