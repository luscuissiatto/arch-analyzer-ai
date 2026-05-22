<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiagramUploadController;

Route::post('/upload', [DiagramUploadController::class, 'upload']);
Route::get('/status/{id}', [DiagramUploadController::class, 'status']);
Route::post('/webhook/status', [DiagramUploadController::class, 'webhook']);
