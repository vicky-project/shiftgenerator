<?php

namespace Modules\ShiftGenerator\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\ShiftGenerator\Models\Employee;

class EmployeeController extends Controller
{
  /**
  * Tampilkan semua karyawan milik user yang sedang login.
  */
  public function index(Request $request) {
    $user = $request->user();
    try {
      return Employee::where('telegram_user_id', $user->id)->get();
    } catch(\Exception $e) {
      \Log::error("Error getting employees", [
        'message' => $e->getMessage(),
        'trace' => $e->getTrace()
      ]);
      return [];
    }
  }

  /**
  * Simpan karyawan baru.
  */
  public function store(Request $request) {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'nrp' => 'required|string|max:50|unique:employees,nrp',
      'shift_pattern' => 'required|string|max:100',
      'shift_start_date' => 'required|date',
      'shift_start' => 'required|in:Day,Night',
      'work_days' => 'required|integer|min:1',
      'leave_days' => 'required|integer|min:1',
      'pattern_start_date' => 'required|date',
    ]);

    $user = $request->user();
    $validated['telegram_user_id'] = $user->id;
    $employee = Employee::create($validated);

    return response()->json($employee, 201);
  }

  /**
  * Tampilkan satu karyawan (hanya jika milik user login).
  */
  public function show(Request $request, Employee $employee) {
    $this->authorizeEmployee($request, $employee);

    return $employee;
  }

  /**
  * Perbarui data karyawan.
  */
  public function update(Request $request, Employee $employee) {
    $this->authorizeEmployee($request, $employee);

    $validated = $request->validate([
      'name' => 'sometimes|string|max:255',
      'nrp' => 'sometimes|string|max:50|unique:employees,nrp,' . $employee->id,
      'shift_pattern' => 'sometimes|string|max:100',
      'shift_start_date' => 'sometimes|date',
      'shift_start' => 'sometimes|in:Day,Night',
      'work_days' => 'sometimes|integer|min:1',
      'leave_days' => 'sometimes|integer|min:1',
      'pattern_start_date' => 'sometimes|date',
    ]);

    $employee->update($validated);

    return response()->json($employee);
  }

  /**
  * Hapus karyawan beserta jadwal dan override terkait.
  */
  public function destroy(Request $request, Employee $employee) {
    $this->authorizeEmployee($request, $employee);

    $employee->delete();

    return response()->json(null, 204);
  }

  /**
  * Pastikan karyawan milik user yang login.
  */
  protected function authorizeEmployee(Request $request, Employee $employee): void
  {
    if ($employee->telegram_user_id !== $request->user()->id) {
      abort(403, 'Forbidden');
    }
  }
}