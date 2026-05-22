<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::get('/reports/{analysis_id}', [ReportController::class, 'show']);
