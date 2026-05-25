<?php

use Illuminate\Support\Facades\Route;
use Modules\ShiftGenerator\Http\Controllers\Api\EmployeeController;
use Modules\ShiftGenerator\Http\Controllers\Api\OverrideController;
use Modules\ShiftGenerator\Http\Controllers\Api\ShiftController;

Route::middleware('auth:sanctum')->group(function () {

  // Override custom routes
  Route::get('employees/{employee}/overrides', [OverrideController::class, 'index']);
  Route::post('employees/{employee}/overrides', [OverrideController::class, 'store']);
  // Roster
  Route::delete('employees/overrides/{override}', [OverrideController::class, 'destroy']);
  Route::post('employees/generate', [ShiftController::class, 'generate']);
  Route::get('employees/schedules', [ShiftController::class, 'schedules']);
  Route::post('employees/export-telegram', [ShiftController::class, 'exportToTelegram']);

  // Employees
  Route::apiResource('employees', EmployeeController::class);
});