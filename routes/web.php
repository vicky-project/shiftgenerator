<?php

use Illuminate\Support\Facades\Route;
use Modules\ShiftGenerator\Http\Controllers\ShiftGeneratorController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('shiftgenerators', ShiftGeneratorController::class)->names('shiftgenerator');
});
