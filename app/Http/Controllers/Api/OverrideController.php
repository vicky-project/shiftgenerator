<?php

namespace Modules\ShiftGenerator\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Models\EmployeeOverride;
use Carbon\Carbon;

class OverrideController extends Controller
{
  /**
  * Tampilkan semua override milik seorang karyawan.
  */
  public function index(Request $request, $employeeId) {
    $employee = $this->findEmployeeOwnedByUser($request, $employeeId);

    return $employee->overrides;
  }

  /**
  * Tambah override baru.
  */
  public function store(Request $request, $employeeId) {
    $employee = $this->findEmployeeOwnedByUser($request, $employeeId);

    $validated = $request->validate([
      'start_date' => 'required|date|date_format:Y-m-d',
    ]);

    $startDate = Carbon::parse($validated['start_date']);
    $leaveDays = $employee->leave_days;

    // Validasi: tidak boleh tumpang tindih dengan override yang sudah ada
    $conflict = $employee->overrides()
    ->where(function ($q) use ($startDate, $leaveDays) {
      $endDate = $startDate->copy()->addDays($leaveDays - 1);
      $q->whereBetween('start_date', [$startDate, $endDate])
      ->orWhere(function ($sub) use ($startDate, $leaveDays) {
        $sub->where('start_date', '<=', $startDate)
        ->whereRaw('DATE_ADD(start_date, INTERVAL ? DAY) >= ?', [$leaveDays - 1, $startDate]);
      });
    })
    ->exists();

    if ($conflict) {
      return response()->json([
        'message' => 'Override bertabrakan dengan override lain yang sudah ada.'
      ], 422);
    }

    $override = $employee->overrides()->create([
      'start_date' => $startDate,
    ]);

    return response()->json($override, 201);
  }

  /**
  * Hapus override.
  */
  public function destroy(Request $request, $id) {
    $override = EmployeeOverride::with('employee')->findOrFail($id);

    // Pastikan override milik karyawan yang dimiliki user ini
    if ($override->employee->telegram_user_id !== $request->user()->id) {
      abort(403, 'Forbidden');
    }

    $override->delete();

    return response()->json(null, 204);
  }

  /**
  * Cari karyawan berdasarkan ID dan pastikan milik user login.
  */
  protected function findEmployeeOwnedByUser(Request $request, $employeeId): Employee
  {
    return Employee::where('telegram_user_id', $request->user()->id)
    ->findOrFail($employeeId);
  }
}