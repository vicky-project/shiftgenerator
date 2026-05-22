<?php

namespace Modules\ShiftGenerator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShiftGeneratorController extends Controller
{
  /**
  * Display a listing of the resource.
  */
  public function index() {
    return view('shiftgenerator::index');
  }
}