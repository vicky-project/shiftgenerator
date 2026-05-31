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
    $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $socialAccountService = \Modules\SocialAccount\Services\SocialAccountService::class;

    if (!class_exists($socialAccountService)) {
      return back()->with('error', 'Fitur Social Account belum ada.');
    }

    $userId = $request->user()->id;
    $service = app($socialAccountService);
    $socialAccounts = $service->getByUserId($userId);
    // Social Account not exists
    if (!$socialAccounts || $socialAccounts->isEmpty()) {
      return back()->with('error', 'Tidak ada Akun Sosial yang terhubung.');
    }

    $telegram = $socialAccounts->where("provider", \Modules\SocialAccount\Enums\Provider::TELEGRAM)->first();

    // Social Account not have provider
    if (!$telegram || !$telegram->providerable) {
      return back()->with('error', 'Telegram tidak terhubung. Hubungkan akun telegram di menu profile.');
    }

    // Cek apakah user memiliki karyawan
    $employeeCount = Employee::where('telegram_user_id', $telegram->telegram_id)->count();
    if ($employeeCount === 0) {
      return back()->with('error', 'Tidak ada karyawan yang tersedia. Silakan tambahkan karyawan terlebih dahulu.');
    }

    return Excel::download(
      new ShiftScheduleExport($request->start_date, $request->end_date, $userId),
      'shift_roster.xlsx'
    );
  }

  public function apiGenerate(Request $request, ShiftGeneratorService $service) {
    $validated = $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    try {
      $service->generate(
        $validated['start_date'],
        $validated['end_date'],
        auth()->id()
      );
      return response()->json(['message' => 'Roster berhasil dibuat'], 200);
    } catch (\Exception $e) {
      return response()->json(['message' => 'Gagal generate: ' . $e->getMessage()], 500);
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