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
  * @return int jumlah record yang dibuat
  */
  public function generate(
    string $startDate,
    string $endDate,
    array $holidays = [],
    ?int $userId = null
  ): int
  {
    $start = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);
    $holidaySet = collect($holidays)->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();

    // Hapus jadwal lama dalam range ini (opsional, bisa juga replace)
    ShiftSchedule::whereBetween('date', [$startDate, $endDate])->delete();

    $query = Employee::with('overrides')->get();
    if ($userId) {
      $query->where('telegram_user_id', $userId);
    }
    $employees = $query->get();
    $insertData = [];

    foreach ($employees as $employee) {
      // Dapatkan semua periode cuti (normal + override) yang tumpang tindih dengan range
      $leavePeriods = $this->getLeavePeriods($employee, $start, $end);

      foreach (CarbonPeriod::create($start, $end) as $date) {
        $dateStr = $date->format('Y-m-d');
        $shift = $this->determineShift($employee, $date, $leavePeriods, $holidaySet);
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
  private function determineShift(Employee $employee, Carbon $date, array $leavePeriods, array $holidays): string
  {
    // 1. Hari libur nasional -> Off
    if (in_array($date->format('Y-m-d'), $holidays)) {
      return 'Off';
    }

    // 2. Jika tanggal termasuk dalam salah satu periode cuti -> Off
    foreach ($leavePeriods as $period) {
      if ($date->between($period['start'], $period['end'])) {
        return 'Off';
      }
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

    $refDate = $employee->shift_start_date;
    $dayDiff = $refDate->diffInDays($date, false); // negatif jika sebelum refDate

    // offset berdasarkan shift_start
    $needle = $employee->shift_start === 'Day' ? 'D' : 'N';
    $offset = 0;
    $pos = strpos($pattern, $needle);
    if ($pos !== false) {
      $offset = $pos;
    }

    // modulo yang aman untuk nilai negatif
    $position = (($dayDiff + $offset) % $patternLength + $patternLength) % $patternLength;
    $char = $pattern[$position];

    return match ($char) {
      'D' => 'Day',
      'N' => 'Night',
      default => 'Off',
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
      $current = $employee->pattern_start_date;
      $overrides = $employee->overrides->toArray(); // array of ['start_date' => 'Y-m-d']
      // Ubah ke Carbon dan urutkan
      $overrideDates = collect($overrides)->map(fn($ov) => Carbon::parse($ov['start_date']))->sort()->values()->toArray();

      $periods = [];

      // Kita perlu mencari semua periode cuti yang mungkin overlap dengan range
      // Caranya: lakukan simulasi dari current hingga melewati rangeEnd.
      while ($current->lte($rangeEnd)) {
        $normalStart = $current->copy()->addDays($workLength);
        $normalEnd = $normalStart->copy()->addDays($leaveLength - 1);

        // Cek apakah ada override yang start_date-nya dalam rentang normalStart ±14
        $chosenOverride = null;
        $chosenIndex = null;
        foreach ($overrideDates as $index => $ovDate) {
          $diff = $normalStart->diffInDays($ovDate, false);
          if (abs($diff) <= 14) {
            $chosenOverride = $ovDate;
            $chosenIndex = $index;
            break;
          }
        }

        if ($chosenOverride) {
          $leaveStart = $chosenOverride->copy();
          // hapus override yang sudah digunakan
          array_splice($overrideDates, $chosenIndex, 1);
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
        $current = $leaveEnd->copy()->addDay();
      }

      return $periods;
    }
  }