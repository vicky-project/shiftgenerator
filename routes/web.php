<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Route;

// Route untuk melayani asset JS dari modul
Route::get('shift/js/{file}', function ($file) {
  $path = module_path('ShiftGenerator', 'resources/assets/js/' . $file);
  if (file_exists($path)) {
    return response()->file($path, ['Content-Type' => 'application/javascript']);
  }
  abort(404);
})->where('file', '.*');

Route::middleware(['auth:sanctum'])->prefix('apps')->group(function () {
  Route::prefix('shift')->group(function() {
    Route::get('/{any?}', function () {
      return view('shiftgenerator::index');
    })->where('any', '.*');
  });
});