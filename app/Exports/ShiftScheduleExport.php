<?php

namespace Modules\ShiftGenerator\Exports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Modules\ShiftGenerator\Enums\ShiftType;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Models\ShiftSchedule;

class ShiftScheduleExport implements FromCollection, WithEvents, ShouldAutoSize
{
  protected string $start;
  protected string $end;
  protected ?int $userId;

  public function __construct(string $start, string $end, ?int $userId = null) {
    $this->start = $start;
    $this->end = $end;
    $this->userId = $userId;
  }

  public function collection(): Collection
  {
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

      $empSchedules = $schedules->get($employee->id, collect())->keyBy(function ($item) {
        return Carbon::parse($item->date)->format('Y-m-d');
      });

      foreach ($dates as $date) {
        $schedule = $empSchedules->get($date);
        if ($schedule) {
          $row[] = match ($schedule->shift) {
            ShiftType::Day->value => 'D',
            ShiftType::Night->value => 'N',
            ShiftType::Off->value => 'O',
            ShiftType::Leave->value => 'CT',
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

    public function registerEvents(): array
    {
      return [
        AfterSheet::class => function (AfterSheet $event) {
          $sheet = $event->sheet->getDelegate();

          $period = CarbonPeriod::create($this->start, $this->end);
          $dates = [];
          foreach ($period as $date) {
            $dates[] = $date;
          }
          $totalDates = count($dates);
          $lastCol = Coordinate::stringFromColumnIndex(2 + $totalDates);

          // Insert 3 baris di atas agar header memiliki 3 baris (title, month, date)
          $sheet->insertNewRowBefore(1, 3);

          // Baris 1: Judul
          $sheet->setCellValue('A1', 'Roster Shift');
          $sheet->mergeCells('A1:' . $lastCol . '1');
          $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
          $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

          // Merge A2:A3 untuk NRP dan B2:B3 untuk Nama
          $sheet->mergeCells('A2:A3');
          $sheet->setCellValue('A2', 'NRP');
          $sheet->mergeCells('B2:B3');
          $sheet->setCellValue('B2', 'Nama');
          $sheet->getStyle('A2:B3')->getFont()->setBold(true);
          $sheet->getStyle('A2:B3')->getAlignment()->setVertical('center');

          // Baris 2: Nama bulan (merge per bulan)
          $currentMonth = null;
          $startCol = 3; // kolom pertama untuk tanggal adalah C (indeks 3)
          for ($i = 0; $i < $totalDates; $i++) {
            $month = $dates[$i]->format('F');
            if ($month !== $currentMonth) {
              if ($currentMonth !== null) {
                $endColIndex = $startCol + $i - 1;
                $endCol = Coordinate::stringFromColumnIndex($endColIndex);
                $sheet->mergeCells(Coordinate::stringFromColumnIndex($startCol) . '2:' . $endCol . '2');
              }
              $currentMonth = $month;
              $startCol = 3 + $i;
            }
            // Untuk bulan pertama atau setelah ganti bulan, isi nama bulan di sel awal
            if ($i === 0 || $month !== $dates[$i - 1]->format('F')) {
              $sheet->setCellValue(Coordinate::stringFromColumnIndex(3 + $i) . '2', $month);
            }
          }
          // Merge sisa terakhir
          if ($startCol <= 2 + $totalDates) {
            $endColIndex = 2 + $totalDates;
            $sheet->mergeCells(Coordinate::stringFromColumnIndex($startCol) . '2:' . Coordinate::stringFromColumnIndex($endColIndex) . '2');
          }
          $sheet->getStyle('C2:' . $lastCol . '2')->getFont()->setBold(true);
          $sheet->getStyle('C2:' . $lastCol . '2')->getFill()
          ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDDDDDD');

          // Baris 3: Tanggal (hari)
          for ($i = 0; $i < $totalDates; $i++) {
            $col = Coordinate::stringFromColumnIndex(3 + $i);
            $sheet->setCellValue($col . '3', $dates[$i]->format('d'));
          }
          $sheet->getStyle('C3:' . $lastCol . '3')->getFont()->setBold(true);
          $sheet->getStyle('C3:' . $lastCol . '3')->getFill()
          ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4A90E2');
          $sheet->getStyle('C3:' . $lastCol . '3')->getFont()->getColor()->setARGB('FFFFFFFF');

          // Data mulai dari baris ke-4
          $colorMap = [
            'D' => 'FF2ecc71',
            'N' => 'FF000000',
            'O' => 'FFe74c3c',
            'CT' => 'FFf1c40f',
          ];

          $highestRow = $sheet->getHighestRow();
          for ($row = 4; $row <= $highestRow; $row++) {
            for ($col = 3; $col <= Coordinate::columnIndexFromString($lastCol); $col++) {
              $cell = $sheet->getCellByColumnAndRow($col, $row);
              $value = $cell->getValue();
              if (isset($colorMap[$value])) {
                $cell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($colorMap[$value]);
                if ($value === 'N') {
                  $cell->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
                }
              }
            }
          }

          // Tambahkan border tipis untuk semua sel
          $styleArray = [
            'borders' => [
              'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
              ],
            ],
          ];
          $sheet->getStyle('A1:' . $lastCol . $highestRow)->applyFromArray($styleArray);
        },
      ];
    }
  }