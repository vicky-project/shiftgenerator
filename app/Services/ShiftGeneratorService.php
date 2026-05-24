<?php

namespace Modules\ShiftGenerator\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
  public function generate(string $startDate, string $endDate, array $holidays = [], ?int $userId = null): int
  {
    $start = Carbon::parse($startDate)->startOfDay();
    $end = Carbon::parse($endDate)->startOfDay();
    $holidaySet = collect($holidays)->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();

    // Hapus jadwal lama dalam range ini
    ShiftSchedule::whereBetween('date', [$startDate, $endDate])->delete();

    $employees = Employee::when($userId, fn($q) => $q->where('telegram_user_id', $userId))->get();

    $insertData = [];

    foreach ($employees as $employee) {
      foreach (CarbonPeriod::create($start, $end) as $date) {
        $dateStr = $date->format('Y-m-d');
        $shift = $this->determineShift($employee, $date, $holidaySet);
        $insertData[] = [
          'employee_id' => $employee->id,
          'date' => $dateStr,
          'shift' => $shift,
        ];
      }
    }

    // Bulk insert untuk performa
    foreach (array_chunk($insertData, 500) as $chunk) {
      ShiftSchedule::insert($chunk);
    }

    return count($insertData);
  }

  /**
  * Tentukan shift untuk seorang karyawan pada suatu tanggal.
  */
  private function determineShift(Employee $employee, Carbon $date, array $holidays): string
  {
    // 1. Hari libur nasional -> Off
    if (in_array($date->format('Y-m-d'), $holidays)) {
      return 'Off';
    }

    // 2. Jika tanggal termasuk dalam masa cuti siklus -> Off
    if ($this->isInCycleLeave($employee, $date)) {
      return 'Off';
    }

    // 3. Hitung shift dari pola
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
      return 'Off'; // fallback
    }

    $refDate = $employee->shift_start_date->startOfDay();
    $currentDate = $date->copy()->startOfDay();

    $dayDiff = $refDate->diffInDays($currentDate, false);

    $needle = $employee->shift_start === 'Day' ? 'D' : 'N';
    $offset = 0;
    $pos = strpos($pattern, $needle);
    if ($pos !== false) {
      $offset = $pos;
    }

    $position = (($dayDiff + $offset) % $patternLength + $patternLength) % $patternLength;
    $char = $pattern[$position];
    \Log::debug('Shift '. $char, [
      'posisi' => $position,
      'daydiff' => $dayDiff,
      'offset' => $offset,
      'patterlength' => $patternLength,
      'date' => $date->format('d-m-Y')
    ]);

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