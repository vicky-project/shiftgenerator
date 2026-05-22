<?php

use Illuminate\Support\Facades\Route;

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