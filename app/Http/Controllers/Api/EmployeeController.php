<?php

namespace Modules\ShiftGenerator\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Http\Requests\StoreEmployeeRequest;
use Modules\ShiftGenerator\Http\Requests\UpdateEmployeeRequest;

class EmployeeController extends Controller
{
  /**
  * Tampilkan semua karyawan milik user yang sedang login.
  */
  public function index(Request $request) {
    $user = $request->user();
    $perPage = $request->input('per_page', 10);
    $employees = Employee::where('telegram_user_id', $user->id)->orderBy('name')->paginate($perPage);

    return response()->json($employees);
  }

  /**
  * Simpan karyawan baru.
  */
  public function store(StoreEmployeeRequest $request) {
    $data = $request->validated();
    $data['telegram_user_id'] = $request->user()->id;

    $employee = Employee::create($data);

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
  public function update(UpdateEmployeeRequest $request, Employee $employee) {
    $this->authorizeEmployee($request, $employee);

    $employee->update($request->validated());

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