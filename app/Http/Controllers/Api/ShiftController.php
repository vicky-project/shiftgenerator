<?php

namespace Modules\ShiftGenerator\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\ShiftGenerator\Services\HolidayService;
use Modules\ShiftGenerator\Services\ShiftGeneratorService;
use Modules\ShiftGenerator\Models\ShiftSchedule;
use Modules\ShiftGenerator\Exports\ShiftScheduleExport;
use Modules\Telegram\Services\Support\TelegramApi;
use Maatwebsite\Excel\Facades\Excel;

class ShiftController extends Controller
{
  /**
  * Generate roster shift untuk karyawan milik user login.
  */
  public function generate(Request $request, ShiftGeneratorService $service, HolidayService $holidayService) {
    $validated = $request->validate([
      'start_date' => 'required|date|date_format:Y-m-d',
      'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
    ]);

    $count = $service->generate(
      $validated['start_date'],
      $validated['end_date'],
      $request->user()->id
    );

    // Ambil data lengkap untuk frontend (date + name)
    $holidays = $holidayService->getHolidays();

    return response()->json([
      'message' => "Roster berhasil dibuat: {$count} entri.",
      'count' => $count,
      'holidays' => $holidays,
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

  /**
  * Export roster ke Telegram.
  */
  public function exportToTelegram(Request $request) {
    $validated = $request->validate([
      'start_date' => 'required|date|date_format:Y-m-d',
      'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
    ]);

    $user = $request->user();

    // Pastikan model memiliki chat_id (kolom chat_id di tabel telegram_users)
    if (!$user->telegram_id) {
      return response()->json(['message' => 'Akun Telegram tidak terhubung.'], 400);
    }

    // Buat file Excel
    $export = new ShiftScheduleExport($validated['start_date'], $validated['end_date'], $user->id);
    $fileName = 'shift_roster_' . uniqid() . '.xlsx';
    $tempFile = "temp/exports/{$fileName}";

    // Simpan Excel ke storage lokal
    Excel::store($export, $tempFile, 'local');
    $tempPath = Storage::disk('local')->path($tempFile);

    // Kirim via Telegram
    $telegramApi = app(TelegramApi::class);
    $result = $telegramApi->sendDocument(
      chatId: $user->telegram_id,
      filePath: $tempPath,
      caption: "📅 Roster Shift Karyawan ({$validated['start_date']} – {$validated['end_date']})",
    );

    // Hapus file setelah dikirim
    if (Storage::disk('local')->exists($tempFile)) {
      Storage::disk('local')->delete($tempFile);
    }

    if ($result) {
      return response()->json(['message' => 'File roster telah dikirim ke Telegram Anda.']);
    } else {
      return response()->json(['message' => 'Gagal mengirim file ke Telegram.'], 500);
    }
  }
}