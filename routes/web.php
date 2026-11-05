<<?php

use App\Http\Controllers\ScreeningResultController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('screenings.index'));

Route::resource('screenings', ScreeningResultController::class)
    ->parameters(['screenings' => 'screening']);