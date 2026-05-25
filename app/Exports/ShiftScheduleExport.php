<?php

namespace Modules\ShiftGenerator\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\ShiftGenerator\Models\ShiftSchedule;

class ShiftScheduleExport implements FromCollection, WithHeadings, WithMapping
{
  protected string $start;
  protected string $end;
  protected ?int $userId;

  public function __construct(string $start, string $end, ?int $userId = null) {
    $this->start = $start;
    $this->end = $end;
    $this->userId = $userId;
  }

  public function collection() {
    $query = ShiftSchedule::with('employee')
    ->whereBetween('date', [$this->start, $this->end]);

    if ($this->userId) {
      $query->whereHas('employee', fn($q) => $q->where('telegram_user_id', $this->userId));
    }

    return $query->orderBy('date')->orderBy('employee_id')->get();
  }

  public function headings(): array
  {
    return ['Tanggal',
      'NRP',
      'Nama',
      'Shift'];
  }

  public function map($schedule): array
  {
    return [
      $schedule->date->format('d/m/Y'),
      $schedule->employee->nrp,
      $schedule->employee->name,
      $schedule->shift,
    ];
  }
}