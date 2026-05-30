<?php

namespace Modules\ShiftGenerator\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Models\EmployeeOverride;
use Modules\ShiftGenerator\Enums\ShiftType;

class EmployeeController extends Controller
{
  public function index() {
    $user = auth()->user();
    $employees = Employee::where('telegram_user_id', $user->id)->orderBy('name')->paginate(10);
    return view('shiftgenerator::web.employees.index', compact('employees'));
  }

  public function create() {
    return view('shiftgenerator::web.employees.form', ['employee' => null]);
  }

  public function store(Request $request) {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'nrp' => 'required|string|max:50|unique:employees',
      'shift_pattern' => 'required|string|max:100|regex:/^[DNO\-]+$/',
      'shift_start_date' => 'required|date',
      'shift_start' => 'required|in:Day,Night',
      'work_days' => 'required|integer|min:1',
      'leave_days' => 'required|integer|min:1',
      'pattern_start_date' => 'required|date',
    ]);
    $validated['telegram_user_id'] = auth()->id();
    Employee::create($validated);
    return redirect()->route('shift.employees.web')->with('success', 'Karyawan berhasil ditambahkan.');
  }

  public function edit(Employee $employee) {
    if ($employee->telegram_user_id !== auth()->id()) abort(403);
    return view('shiftgenerator::web.employees.form', compact('employee'));
  }

  public function update(Request $request, Employee $employee) {
    if ($employee->telegram_user_id !== auth()->id()) abort(403);
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'nrp' => 'required|string|max:50|unique:employees,nrp,' . $employee->id,
      'shift_pattern' => 'required|string|max:100|regex:/^[DNO\-]+$/',
      'shift_start_date' => 'required|date',
      'shift_start' => 'required|in:Day,Night',
      'work_days' => 'required|integer|min:1',
      'leave_days' => 'required|integer|min:1',
      'pattern_start_date' => 'required|date',
    ]);
    $employee->update($validated);
    return redirect()->route('shift.employees.web')->with('success', 'Karyawan berhasil diperbarui.');
  }

  public function destroy(Employee $employee) {
    if ($employee->telegram_user_id !== auth()->id()) abort(403);
    $employee->delete();
    return redirect()->route('shift.employees.web')->with('success', 'Karyawan berhasil dihapus.');
  }

  public function overrides(Employee $employee) {
    if ($employee->telegram_user_id !== auth()->id()) abort(403);
    $overrides = $employee->overrides()->orderBy('start_date')->get();
    return view('shiftgenerator::web.employees.overrides', compact('employee', 'overrides'));
  }

  public function storeOverride(Request $request, Employee $employee) {
    if ($employee->telegram_user_id !== auth()->id()) abort(403);
    $request->validate(['start_date' => 'required|date']);
    $employee->overrides()->create(['start_date' => $request->start_date]);
    return redirect()->route('shift.employees.overrides', $employee)->with('success', 'Override cuti ditambahkan.');
  }

  public function destroyOverride(EmployeeOverride $override) {
    if ($override->employee->telegram_user_id !== auth()->id()) abort(403);
    $override->delete();
    return back()->with('success', 'Override dihapus.');
  }
}