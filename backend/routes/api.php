<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactDriverController;

Route::get('/contact-driver', [ContactDriverController::class, 'index']);
Route::get('/contact-driver/lookups', [ContactDriverController::class, 'lookups']);
Route::get('/contact-driver/{contactId}', [ContactDriverController::class, 'show']);
Route::post('/contact-driver', [ContactDriverController::class, 'store']);
Route::put('/contact-driver/{contactId}', [ContactDriverController::class, 'update']);
Route::delete('/contact-driver/{contactId}', [ContactDriverController::class, 'destroy']);
