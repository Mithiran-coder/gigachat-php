<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| GigaChat API Routes
|--------------------------------------------------------------------------
|
| Example routes for GigaChat integration. Add these to your routes/api.php
| or routes/web.php file.
|
*/

Route::prefix('api/gigachat')->middleware(['api', 'gigachat.rate_limit:30,1'])->group(function () {
    
    // Simple chat endpoint
    Route::post('/chat', [ChatController::class, 'chat']);
    
    // Conversation with history
    Route::post('/conversation', [ChatController::class, 'conversation']);
    
    // Streaming chat
    Route::post('/stream', [ChatController::class, 'stream']);
    
    // Content generation
    Route::post('/generate', [ChatController::class, 'generate']);
    
});

// Web routes for testing (optional)
Route::prefix('gigachat')->middleware(['web'])->group(function () {
    
    Route::get('/test', function () {
        return view('gigachat.test');
    });
    
    Route::get('/playground', function () {
        return view('gigachat.playground');
    });
    
});
