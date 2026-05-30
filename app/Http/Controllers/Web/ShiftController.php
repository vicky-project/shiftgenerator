<?php

namespace Modules\ShiftGenerator\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\ShiftGenerator\Services\ShiftGeneratorService;
use Modules\ShiftGenerator\Services\HolidayService;
use Modules\ShiftGenerator\Models\ShiftSchedule;
use Modules\ShiftGenerator\Exports\ShiftScheduleExport;
use Maatwebsite\Excel\Facades\Excel;

class ShiftController extends Controller
{
  public function index() {
    return view('shiftgenerator::web.generate');
  }

  public function generate(Request $request, ShiftGeneratorService $service) {
    $validated = $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $service->generate(
      $validated['start_date'],
      $validated['end_date'],
      auth()->id()
    );

    return back()->with('success', 'Roster berhasil dibuat.');
  }

  public function export(Request $request) {
    $start = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
    $end = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

    return Excel::download(
      new ShiftScheduleExport($start, $end, auth()->id()),
      'shift_roster.xlsx'
    );
  }

  public function apiGenerate(Request $request, ShiftGeneratorService $service) {
    try {
      $validated = $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
      ]);

      $service->generate($validated['start_date'], $validated['end_date'], auth()->id());

      return response()->json(['message' => 'Roster berhasil dibuat']);
    } catch(\Exception $e) {
      \Log::error('Failed to generate roster.', [
        'message' => $e->getMessage(),
        'trace' => $e->getTrace()
      ]);

      return response()->json(['message' => $e->getMessage()], 500);
    }
  }

  public function apiSchedules(Request $request, HolidayService $holidayService) {
    $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $user = auth()->user();
    $schedules = ShiftSchedule::with('employee')
    ->whereHas('employee', fn($q) => $q->where('telegram_user_id', $user->id))
    ->whereBetween('date', [$request->start_date, $request->end_date])
    ->orderBy('date')
    ->orderBy('employee_id')
    ->get();

    return response()->json([
      'schedules' => $schedules,
      'holidays' => $holidayService->getHolidays(),
    ]);
  }
}