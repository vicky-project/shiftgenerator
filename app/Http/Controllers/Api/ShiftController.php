<?php

namespace Modules\ShiftGenerator\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\ShiftGenerator\Services\ShiftGeneratorService;
use Modules\ShiftGenerator\Models\ShiftSchedule;
use Maatwebsite\Excel\Facades\Excel;

class ShiftController extends Controller
{
  /**
  * Generate roster shift untuk karyawan milik user login.
  */
  public function generate(Request $request, ShiftGeneratorService $service) {
    $validated = $request->validate([
      'start_date' => 'required|date|date_format:Y-m-d',
      'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
      'holidays' => 'sometimes|array',
      'holidays.*' => 'date|date_format:Y-m-d',
    ]);

    $count = $service->generate(
      $validated['start_date'],
      $validated['end_date'],
      $validated['holidays'] ?? [],
      $request->user()->id
    );

    return response()->json([
      'message' => "Roster berhasil dibuat: {$count} entri.",
      'count' => $count
    ]);
  }

  /**
  * Ambil data schedule yang sudah di-generate.
  */
  public function schedules(Request $request) {
    $request->validate([
      'start_date' => 'required|date|date_format:Y-m-d',
      'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
    ]);

    $user = $request->user();
    $schedules = ShiftSchedule::with('employee')
    ->whereHas('employee', fn($q) => $q->where('telegram_user_id', $user->id))
    ->whereBetween('date', [$request->start_date, $request->end_date])
    ->orderBy('date')
    ->orderBy('employee_id')
    ->get();

    return $schedules;
  }
}