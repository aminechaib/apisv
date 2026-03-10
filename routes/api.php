<?php

// In routes/api.php

use App\Http\Controllers\Api\V1\ContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; // <-- Add this line

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Change this route
Route::post('/process-card', [ContactController::class, 'processCard']);
Route::get('/cards/{contact}/image', [ContactController::class, 'image']);
Route::get('/contacts', [ContactController::class, 'listContacts']);
Route::get('/export-contacts', [ContactController::class, 'exportToExcel']);
Route::post('/process-text', [ContactController::class, 'processExtractedText']);
Route::put('/contacts/{contact}', [ContactController::class, 'update']);
Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);
