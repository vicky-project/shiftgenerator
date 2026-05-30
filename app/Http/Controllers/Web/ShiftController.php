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

  public function generate(Request $request, ShiftGeneratorService $service, HolidayService $holidayService) {
    $validated = $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $holidayDates = $holidayService->getHolidayDates();
    $service->generate($validated['start_date'], $validated['end_date'], $holidayDates, auth()->id());

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
}