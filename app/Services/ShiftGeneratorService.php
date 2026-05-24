<?php

namespace Modules\ShiftGenerator\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\ShiftGenerator\Enums\ShiftType;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Models\ShiftSchedule;

class ShiftGeneratorService
{
  /**
  * Generate shift roster untuk rentang tanggal.
  *
  * @param string $startDate  Y-m-d
  * @param string $endDate    Y-m-d
  * @param array  $holidays   array of Y-m-d (tanggal merah)
  * @param int|null $userId   ID user Telegram (untuk filter karyawan)
  * @return int jumlah record yang dibuat
  */
  public function generate(string $startDate, string $endDate, ?int $userId = null): int
  {
    $start = Carbon::parse($startDate)->startOfDay();
    $end = Carbon::parse($endDate)->startOfDay();

    // Hapus hanya data milik user yang sedang login
    ShiftSchedule::whereBetween('date', [$startDate, $endDate])
    ->whereHas('employee', fn($q) => $q->where('telegram_user_id', $userId))
    ->delete();

    $employees = Employee::when($userId, fn($q) => $q->where('telegram_user_id', $userId))->get();
    $insertData = [];

    foreach ($employees as $employee) {
      foreach (CarbonPeriod::create($start, $end) as $date) {
        $shift = $this->determineShift($employee, $date); // tanpa holiday
        $insertData[] = [
          'employee_id' => $employee->id,
          'date' => $date->format('Y-m-d'),
          'shift' => $shift,
        ];
      }
    }

    foreach (array_chunk($insertData, 500) as $chunk) {
      ShiftSchedule::insert($chunk);
    }

    return count($insertData);
  }


  /**
  * Tentukan shift untuk seorang karyawan pada suatu tanggal.
  */
  private function determineShift(Employee $employee, Carbon $date): string
  {
    if ($this->isInCycleLeave($employee, $date)) {
      return 'Off';
    }
    return $this->calculatePatternShift($employee, $date);
  }

  /**
  * Hitung shift berdasarkan pola shift harian.
  */
  private function calculatePatternShift(Employee $employee, Carbon $date): string
  {
    $pattern = $employee->shift_pattern;
    $patternLength = strlen($pattern);

    if ($patternLength === 0) {
      \Log::warning('Pola shift kosong untuk karyawan ' . $employee->id);
      return 'Off';
    }

    $refDate = $employee->shift_start_date->startOfDay();
    $currentDate = $date->copy()->startOfDay();
    $dayDiff = $refDate->diffInDays($currentDate, false);

    // Tentukan karakter target berdasarkan shift_start
    $targetChar = ($employee->shift_start === ShiftType::Day) ? 'D' : 'N';

    // Cari indeks pertama karakter target dalam pola
    $offset = strpos($pattern, $targetChar);
    if ($offset === false) {
      \Log::error("Pola shift tidak mengandung karakter '$targetChar' untuk karyawan {$employee->id}");
      return 'Off';
    }

    // Hitung posisi dalam siklus dengan offset, amankan dari negatif
    $position = (($dayDiff + $offset) % $patternLength + $patternLength) % $patternLength;
    $char = $pattern[$position];

    return match ($char) {
      'D' => 'Day',
      'N' => 'Night',
      default => 'Off',
      };
    }

    /**
    * Periksa apakah tanggal termasuk dalam masa cuti siklus kerja-cuti.
    */
    private function isInCycleLeave(Employee $employee, Carbon $date): bool
    {
      if (!$employee->pattern_start_date || !$employee->work_days || !$employee->leave_days) {
        return false;
      }

      $start = $employee->pattern_start_date->startOfDay();
      $current = $date->copy()->startOfDay();
      $totalCycle = $employee->work_days + $employee->leave_days;

      $dayDiff = $start->diffInDays($current, false);
      $position = $dayDiff % $totalCycle;

      // Jika posisi >= work_days, berarti masa cuti
      return $position >= $employee->work_days;
    }
  }