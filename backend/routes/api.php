<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExpenseController;

Route::get('expenses/summary', [ExpenseController::class, 'summary']);
Route::apiResource('expenses', ExpenseController::class);