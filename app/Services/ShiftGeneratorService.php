<?php

namespace Modules\ShiftGenerator\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Models\ShiftSchedule;
use Modules\ShiftGenerator\Enums\ShiftType;

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

    // Hapus jadwal lama dalam range ini untuk user ini
    ShiftSchedule::whereBetween('date', [$startDate, $endDate])
    ->whereHas('employee', fn($q) => $q->where('telegram_user_id', $userId))
    ->delete();

    $employees = Employee::with('overrides')
    ->when($userId, fn($q) => $q->where('telegram_user_id', $userId))
    ->get();

    $insertData = [];

    foreach ($employees as $employee) {
      $leavePeriods = $this->getLeavePeriods($employee, $start, $end);
      foreach (CarbonPeriod::create($start, $end) as $date) {
        $shift = $this->determineShift($employee, $date, $leavePeriods);
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
  private function determineShift(Employee $employee, Carbon $date, array $leavePeriods): string
  {
    // 1. Cuti siklus -> Leave
    foreach ($leavePeriods as $period) {
      if ($date->between($period['start'], $period['end'])) {
        return ShiftType::Leave->value;
      }
    }

    // 2. Pola shift normal
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
      return ShiftType::Off->value; // fallback
    }

    $refDate = $employee->shift_start_date->startOfDay();
    $currentDate = $date->copy()->startOfDay();

    // Selisih hari (bisa negatif)
    $dayDiff = $refDate->diffInDays($currentDate, false);

    // Tentukan karakter target berdasarkan shift_start (enum)
    $targetChar = ($employee->shift_start === ShiftType::Day) ? 'D' : 'N';
    $offset = strpos($pattern, $targetChar);
    if ($offset === false) {
      // Pola tidak mengandung karakter yang sesuai, fallback ke Off
      return ShiftType::Off->value;
    }

    // modulo aman untuk nilai negatif
    $position = (($dayDiff + $offset) % $patternLength + $patternLength) % $patternLength;
    $char = $pattern[$position];

    return match ($char) {
      'D' => ShiftType::Day->value,
      'N' => ShiftType::Night->value,
      default => ShiftType::Off->value,
      };
    }

    /**
    * Hitung semua periode cuti (normal & override) yang berpotongan dengan rentang generate.
    *
    * @param Employee $employee
    * @param Carbon $rangeStart
    * @param Carbon $rangeEnd
    * @return array of ['start' => Carbon, 'end' => Carbon]
    */
    private function getLeavePeriods(Employee $employee, Carbon $rangeStart, Carbon $rangeEnd): array
    {
      $leaveLength = $employee->leave_days;
      $workLength = $employee->work_days;
      $current = $employee->pattern_start_date->startOfDay();

      // Ambil override yang sudah diurutkan berdasarkan start_date
      $overrides = $employee->overrides->sortBy('start_date')->values()->toArray();
      $overrideIndex = 0;
      $overrideCount = count($overrides);

      $periods = [];

      // Simulasi siklus kerja-cuti sampai melewati rangeEnd
      while ($current->lte($rangeEnd)) {
        $normalStart = $current->copy()->addDays($workLength);
        $normalEnd = $normalStart->copy()->addDays($leaveLength - 1);

        // Cek apakah ada override yang start_date-nya dalam rentang normalStart ±14
        $chosenOverride = null;
        while ($overrideIndex < $overrideCount) {
          $ovStart = Carbon::parse($overrides[$overrideIndex]['start_date'])->startOfDay();
          $diff = $normalStart->diffInDays($ovStart, false);
          if (abs($diff) <= 14) {
            $chosenOverride = $ovStart;
            $overrideIndex++;
            break;
          } elseif ($ovStart->lt($normalStart)) {
            // override sudah lewat, lanjutkan
            $overrideIndex++;
          } else {
            break;
          }
        }

        if ($chosenOverride) {
          $leaveStart = $chosenOverride->copy();
        } else {
          $leaveStart = $normalStart->copy();
        }

        $leaveEnd = $leaveStart->copy()->addDays($leaveLength - 1);

        // Apakah periode ini berpotongan dengan range generate?
        if ($leaveEnd->gte($rangeStart) && $leaveStart->lte($rangeEnd)) {
          $periods[] = [
            'start' => $leaveStart->copy(),
            'end' => $leaveEnd->copy(),
          ];
        }

        // Reset current ke sehari setelah akhir cuti ini
        $current = $leaveEnd->copy()->addDay()->startOfDay();
      }

      return $periods;
    }
  }