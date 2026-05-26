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
  * Generate shift roster untuk rentang tanggal (actual).
  */
  public function generate(string $startDate, string $endDate, ?int $userId = null, array $holidays = []): int
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
        $shift = $this->determineShift($employee, $date, $leavePeriods, $holidays);
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
  * Tentukan shift actual (dengan override) untuk suatu tanggal.
  */
  private function determineShift(Employee $employee, Carbon $date, array $leavePeriods, array $holidays): string
  {
    // 1. Hari libur nasional -> Off (tetap)
    if (in_array($date->format('Y-m-d'), $holidays)) {
      return ShiftType::Off->value;
    }

    // 2. Jika tanggal termasuk dalam salah satu periode cuti -> Leave
    foreach ($leavePeriods as $period) {
      if ($date->between($period['start'], $period['end'])) {
        return ShiftType::Leave->value;
      }
    }

    // 3. Pola shift normal
    return $this->calculatePatternShift($employee, $date);
  }

  /**
  * Hitung karakter pola shift untuk suatu tanggal (internal).
  */
  private function calculatePatternShiftChar(Employee $employee, Carbon $date): string
  {
    $pattern = $employee->shift_pattern;
    $patternLength = strlen($pattern);
    if ($patternLength === 0) {
      return 'O';
    }

    $refDate = $employee->shift_start_date->startOfDay();
    $currentDate = $date->copy()->startOfDay();
    $dayDiff = $refDate->diffInDays($currentDate, false);

    $targetChar = ($employee->shift_start === ShiftType::Day) ? 'D' : 'N';
    $offset = strpos($pattern, $targetChar);
    if ($offset === false) {
      return 'O';
    }

    $position = (($dayDiff + $offset) % $patternLength + $patternLength) % $patternLength;
    return $pattern[$position];
  }

  /**
  * Hitung shift actual (dengan override) untuk generate.
  */
  private function calculatePatternShift(Employee $employee, Carbon $date): string
  {
    $char = $this->calculatePatternShiftChar($employee, $date);
    return match ($char) {
      'D' => ShiftType::Day->value,
      'N' => ShiftType::Night->value,
      default => ShiftType::Off->value,
      };
    }

    /**
    * Hitung shift plan (normal, tanpa pengaruh override).
    * Digunakan oleh export Excel.
    */
    public function calculatePlanShift(Employee $employee, Carbon $date): ShiftType
    {
      // Cek apakah tanggal masuk dalam cuti siklus normal (tanpa override)
      if ($this->isInCycleLeave($employee, $date)) {
        return ShiftType::Leave;
      }

      // Jika tidak, gunakan pola shift normal
      $char = $this->calculatePatternShiftChar($employee, $date);
      return match ($char) {
        'D' => ShiftType::Day,
        'N' => ShiftType::Night,
      default => ShiftType::Off,
      };
    }

    /**
    * Periksa apakah tanggal termasuk dalam masa cuti siklus normal (tanpa override).
    */
    public function isInCycleLeave(Employee $employee, Carbon $date): bool
    {
      if (!$employee->pattern_start_date || !$employee->work_days || !$employee->leave_days) {
        return false;
      }

      $start = $employee->pattern_start_date->startOfDay();
      $current = $date->copy()->startOfDay();
      $totalCycle = $employee->work_days + $employee->leave_days;

      $dayDiff = $start->diffInDays($current, false);
      $position = $dayDiff % $totalCycle;

      return $position >= $employee->work_days;
    }

    /**
    * Hitung semua periode cuti (normal & override) yang berpotongan dengan rentang generate.
    */
    private function getLeavePeriods(Employee $employee, Carbon $rangeStart, Carbon $rangeEnd): array
    {
      $leaveLength = $employee->leave_days;
      $workLength = $employee->work_days;
      $current = $employee->pattern_start_date->startOfDay();

      $overrides = $employee->overrides->sortBy('start_date')->values()->toArray();
      $overrideIndex = 0;
      $overrideCount = count($overrides);

      $periods = [];

      while ($current->lte($rangeEnd)) {
        $normalStart = $current->copy()->addDays($workLength);
        $normalEnd = $normalStart->copy()->addDays($leaveLength - 1);

        $chosenOverride = null;
        while ($overrideIndex < $overrideCount) {
          $ovStart = Carbon::parse($overrides[$overrideIndex]['start_date'])->startOfDay();
          $diff = $normalStart->diffInDays($ovStart, false);
          if (abs($diff) <= 14) {
            $chosenOverride = $ovStart;
            $overrideIndex++;
            break;
          } elseif ($ovStart->lt($normalStart)) {
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

        if ($leaveEnd->gte($rangeStart) && $leaveStart->lte($rangeEnd)) {
          $periods[] = [
            'start' => $leaveStart->copy(),
            'end' => $leaveEnd->copy(),
          ];
        }

        $current = $leaveEnd->copy()->addDay()->startOfDay();
      }

      return $periods;
    }
  }