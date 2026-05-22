<?php

use Illuminate\Support\Facades\Route;
use Modules\ShiftGenerator\Http\Controllers\ShiftGeneratorController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('shiftgenerators', ShiftGeneratorController::class)->names('shiftgenerator');
});
