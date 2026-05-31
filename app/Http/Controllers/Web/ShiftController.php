<?php

namespace Modules\ShiftGenerator\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ShiftGenerator\Services\ShiftGeneratorService;
use Modules\ShiftGenerator\Services\HolidayService;
use Modules\ShiftGenerator\Models\Employee;
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

    $service->generate($validated['start_date'], $validated['end_date'], auth()->id());
    return back()->with('success', 'Roster berhasil dibuat.');
  }

  public function apiGenerate(Request $request, ShiftGeneratorService $service) {
    $validated = $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    try {
      $service->generate($validated['start_date'], $validated['end_date'], auth()->id());
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

  public function validateExport(Request $request) {
    $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $result = $this->checkExportEligibility();
    return response()->json($result, $result['valid'] ? 200 : 422);
  }

  public function export(Request $request) {
    $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $result = $this->checkExportEligibility();
    if (!$result['valid']) {
      return back()->with('error', $result['message']);
    }

    return Excel::download(
      new ShiftScheduleExport(
        $request->start_date,
        $request->end_date,
        $result['telegram_user_id']
      ),
      'shift_roster.xlsx'
    );
  }

  /**
  * Pengecekan kelayakan export (digunakan bersama).
  */
  private function checkExportEligibility(): array
  {
    $socialAccountService = \Modules\SocialAccount\Services\SocialAccountService::class;

    if (!class_exists($socialAccountService)) {
      return ['valid' => false,
        'message' => 'Fitur Social Account belum tersedia.'];
    }

    $service = app($socialAccountService);
    $socialAccounts = $service->getByUserId(auth()->id());

    if (!$socialAccounts || $socialAccounts->isEmpty()) {
      return ['valid' => false,
        'message' => 'Tidak ada Akun Sosial yang terhubung. Hubungkan akun Telegram di menu Profile.'];
    }

    $telegram = $socialAccounts->where('provider', \Modules\SocialAccount\Enums\Provider::TELEGRAM)->first();
    if (!$telegram || !$telegram->providerable) {
      return ['valid' => false,
        'message' => 'Akun Telegram belum terhubung. Hubungkan di menu Profile.'];
    }

    $telegramUserId = $telegram->telegram_id;
    $employeeCount = Employee::where('telegram_user_id', $telegramUserId)->count();
    if ($employeeCount === 0) {
      return ['valid' => false,
        'message' => 'Tidak ada karyawan yang tersedia. Silakan tambahkan karyawan terlebih dahulu.'];
    }

    return ['valid' => true,
      'telegram_user_id' => $telegramUserId];
  }
}