<?php

namespace Modules\ShiftGenerator\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Models\ShiftSchedule;
use Modules\ShiftGenerator\Models\EmployeeOverride;

class WebController extends Controller
{
  public function dashboard() {
    $user = auth()->user();
    return view('shiftgenerator::web.dashboard', [
      'employeeCount' => Employee::where('telegram_user_id', $user->id)->count(),
      'rosterCount' => ShiftSchedule::whereHas('employee', fn($q) => $q->where('telegram_user_id', $user->id))->count(),
      'overrideCount' => EmployeeOverride::whereHas('employee', fn($q) => $q->where('telegram_user_id', $user->id))->count(),
    ]);
  }
}