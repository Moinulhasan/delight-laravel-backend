<?php

use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/content', ContentController::class);
Route::post('/v1/leads', LeadController::class)->middleware('throttle:leads');
