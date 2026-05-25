<?php

namespace Modules\ShiftGenerator\Exports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Models\ShiftSchedule;

class ShiftScheduleExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
  protected string $start;
  protected string $end;
  protected ?int $userId;

  public function __construct(string $start, string $end, ?int $userId = null) {
    $this->start = $start;
    $this->end = $end;
    $this->userId = $userId;
  }

  /**
  * Kumpulkan data yang akan diekspor.
  */
  public function collection() {
    $employees = Employee::when($this->userId, fn($q) => $q->where('telegram_user_id', $this->userId))
    ->orderBy('nrp')
    ->get();

    $schedules = ShiftSchedule::whereBetween('date', [$this->start, $this->end])
    ->when($this->userId, fn($q) => $q->whereHas('employee', fn($q) => $q->where('telegram_user_id', $this->userId)))
    ->get()
    ->groupBy('employee_id');

    $period = CarbonPeriod::create($this->start, $this->end);
    $dates = [];
    foreach ($period as $date) {
      $dates[] = $date->format('Y-m-d');
    }

    $rows = collect();

    foreach ($employees as $employee) {
      $row = [
        $employee->nrp,
        $employee->name,
      ];

      $empSchedules = $schedules->get($employee->id, collect())->keyBy('date');

      foreach ($dates as $date) {
        $schedule = $empSchedules->get($date);
        if ($schedule) {
          // Konversi shift ke kode
          $row[] = match ($schedule->shift) {
            'Day' => 'D',
            'Night' => 'N',
            'Off' => 'O',
            'Leave' => 'CT',
            default => '',
            };
          } else {
            $row[] = '';
          }
        }

        $rows->push($row);
      }

      return $rows;
    }

    /**
    * Header kolom.
    */
    public function headings(): array
    {
      $period = CarbonPeriod::create($this->start, $this->end);
      $dates = [];
      foreach ($period as $date) {
        $dates[] = $date->format('d/m');
      }

      return array_merge(['NRP', 'Nama'], $dates);
    }

    /**
    * Daftarkan event untuk styling setelah sheet dibuat.
    */
    public function registerEvents(): array
    {
      return [
        AfterSheet::class => function (AfterSheet $event) {
          $sheet = $event->sheet->getDelegate();
          $highestRow = $sheet->getHighestRow();
          $highestColumn = $sheet->getHighestColumn();

          // Style untuk header
          $headerRange = 'A1:' . $highestColumn . '1';
          $sheet->getStyle($headerRange)->getFont()->setBold(true);
          $sheet->getStyle($headerRange)->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FF4A90E2');
          $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

          // Warna background untuk kode shift
          $colorMap = [
            'D' => 'FF2ecc71',
            // hijau
            'N' => 'FF000000',
            // hitam (font putih)
            'O' => 'FFe74c3c',
            // merah
            'CT' => 'FFf1c40f',
            // kuning
          ];

          // Mulai dari baris ke-2 (data) dan kolom ke-3 (tanggal pertama)
          for ($row = 2; $row <= $highestRow; $row++) {
            for ($col = 3; $col <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); $col++) {
              $cell = $sheet->getCellByColumnAndRow($col, $row);
              $value = $cell->getValue();
              if (isset($colorMap[$value])) {
                $cell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($colorMap[$value]);
                // Untuk background hitam, font putih
                if ($value === 'N') {
                  $cell->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
                }
              }
            }
          }
        },
      ];
    }
  }