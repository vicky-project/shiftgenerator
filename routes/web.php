<?php

use Illuminate\Support\Facades\Route;
use Modules\ShiftGenerator\Http\Controllers\Web\WebController;
use Modules\ShiftGenerator\Http\Controllers\Web\EmployeeController;
use Modules\ShiftGenerator\Http\Controllers\Web\ShiftController;

Route::prefix('apps/shift')->group(function () {

  // Melayani file JavaScript
  Route::get('/js/{file}', function ($file) {
    $path = module_path('ShiftGenerator', 'Resources/assets/js/' . $file);
    if (file_exists($path)) {
      return response()->file($path, ['Content-Type' => 'application/javascript']);
    }
    abort(404);
  })->where('file',
    '.*');

  // Semua halaman SPA (fallback)
  Route::get('/{any?}',
    function () {
      return view('shiftgenerator::index');
    })->where('any',
    '.*');
});

Route::middleware(['auth'])->prefix('shift')->name('shift.')->group(function () {
  Route::get('/web',
    [WebController::class,
      'dashboard'])->name('web');

  // Karyawan
  Route::get('/employees',
    [EmployeeController::class,
      'index'])->name('employees.web');
  Route::get('/employees/create',
    [EmployeeController::class,
      'create'])->name('employees.create');
  Route::post('/employees',
    [EmployeeController::class,
      'store'])->name('employees.store');
  Route::get('/employees/{employee}/edit',
    [EmployeeController::class,
      'edit'])->name('employees.edit');
  Route::put('/employees/{employee}',
    [EmployeeController::class,
      'update'])->name('employees.update');
  Route::delete('/employees/{employee}',
    [EmployeeController::class,
      'destroy'])->name('employees.destroy');
  Route::get('/employees/{employee}/overrides',
    [EmployeeController::class,
      'overrides'])->name('employees.overrides');
  Route::post('/employees/{employee}/overrides',
    [EmployeeController::class,
      'storeOverride'])->name('employees.overrides.store');
  Route::delete('/overrides/{override}',
    [EmployeeController::class,
      'destroyOverride'])->name('employees.overrides.destroy');

  // Generate
  Route::get('/generate',
    [ShiftController::class,
      'index'])->name('generate.web');
  Route::post('/generate',
    [ShiftController::class,
      'generate'])->name('generate.run');
  Route::get('/export',
    [ShiftController::class,
      'export'])->name('generate.export');
  Route::post('/generate-api',
    [ShiftController::class,
      'apiGenerate'])->name('generate.api');
  Route::get('/schedules-api',
    [ShiftController::class,
      'apiSchedules'])->name('generate.schedules-api');
  Route::get('/validate-export',
    [ShiftController::class,
      'validateExport'])->name('generate.validate-export');
});