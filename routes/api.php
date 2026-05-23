<?php

use Illuminate\Support\Facades\Route;
use Modules\ShiftGenerator\Http\Controllers\Api\EmployeeController;
use Modules\ShiftGenerator\Http\Controllers\Api\OverrideController;
use Modules\ShiftGenerator\Http\Controllers\Api\ShiftController;

Route::middleware('auth:sanctum')->group(function () {

  // Override custom routes
  Route::get('employees/{employee}/overrides', [OverrideController::class, 'index']);
  Route::post('employees/{employee}/overrides', [OverrideController::class, 'store']);
  Route::delete('overrides/{override}', [OverrideController::class, 'destroy']);

  // Employees
  Route::apiResource('employees', EmployeeController::class);
  // Roster
  Route::post('generate', [ShiftController::class, 'generate']);
  Route::get('schedules', [ShiftController::class, 'schedules']);
  Route::get('export', [ShiftController::class, 'export']);
});