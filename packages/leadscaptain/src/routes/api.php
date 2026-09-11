<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Leadscaptain\Http\Controllers\LeadSyncController;

Route::prefix('api/leadscaptain')
    ->middleware(['api', 'throttle:60,1'])
    ->group(function () {
        
        Route::post('/sync', [LeadSyncController::class, 'triggerSync'])->name('leadscaptain.sync');
        
        Route::post('/webhook', [LeadSyncController::class, 'handleWebhook'])->name('leadscaptain.webhook');
    });