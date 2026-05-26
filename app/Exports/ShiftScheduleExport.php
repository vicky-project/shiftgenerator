<?php
namespace Modules\ShiftGenerator\Exports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Modules\ShiftGenerator\Enums\ShiftType;
use Modules\ShiftGenerator\Models\Employee;
use Modules\ShiftGenerator\Models\ShiftSchedule;
use Modules\ShiftGenerator\Services\HolidayService;

class ShiftScheduleExport implements WithEvents
{
  use RegistersEventListeners;

  protected string $start;
  protected string $end;
  protected ?int $userId;

  public function __construct(string $start, string $end, ?int $userId = null) {
    $this->start = $start;
    $this->end = $end;
    $this->userId = $userId;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // --- Data dari database ---
        $employees = Employee::when($this->userId, fn($q) => $q->where('telegram_user_id', $this->userId))
        ->orderBy('nrp')
        ->get();

        $schedules = ShiftSchedule::whereBetween('date', [$this->start, $this->end])
        ->when($this->userId, fn($q) => $q->whereHas('employee', fn($q) => $q->where('telegram_user_id', $this->userId)))
        ->get()
        ->groupBy('employee_id');

        // --- Rentang tanggal ---
        $period = CarbonPeriod::create($this->start, $this->end);
        $dates = [];
        foreach ($period as $date) {
          $dates[] = $date;
        }
        $totalDates = count($dates);
        $lastColIndex = 2 + $totalDates;
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

        // --- Judul ---
        $sheet->setCellValue('A1', 'Roster Shift');
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Header NRP & Nama (merge vertikal 2 baris) ---
        $sheet->mergeCells('A2:A3');
        $sheet->setCellValue('A2', 'NRP');
        $sheet->mergeCells('B2:B3');
        $sheet->setCellValue('B2', 'Nama');
        $sheet->getStyle('A2:B3')->getFont()->setBold(true);
        $sheet->getStyle('A2:B3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A2:B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:B3')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFDDDDDD');

        // --- Header bulan (baris 2) ---
        $currentMonth = null;
        $startMonthCol = 3;
        for ($i = 0; $i < $totalDates; $i++) {
          $month = $dates[$i]->format('F');
          $colIndex = 3 + $i;
          $colLetter = Coordinate::stringFromColumnIndex($colIndex);

          if ($month !== $currentMonth) {
            if ($currentMonth !== null) {
              $endMergeColLetter = Coordinate::stringFromColumnIndex($colIndex - 1);
              $sheet->mergeCells(
                Coordinate::stringFromColumnIndex($startMonthCol) . '2:' . $endMergeColLetter . '2'
              );
            }
            $currentMonth = $month;
            $startMonthCol = $colIndex;
            $sheet->setCellValue($colLetter . '2', $month);
          }
        }
        if ($startMonthCol <= $lastColIndex) {
          $endMergeColLetter = Coordinate::stringFromColumnIndex($lastColIndex);
          $sheet->mergeCells(
            Coordinate::stringFromColumnIndex($startMonthCol) . '2:' . $endMergeColLetter . '2'
          );
        }
        $sheet->getStyle('C2:' . $lastCol . '2')->getFont()->setBold(true);
        $sheet->getStyle('C2:' . $lastCol . '2')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFDDDDDD');
        $sheet->getStyle('C2:' . $lastCol . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Header tanggal (baris 3) ---
        for ($i = 0; $i < $totalDates; $i++) {
          $colIndex = 3 + $i;
          $colLetter = Coordinate::stringFromColumnIndex($colIndex);
          $sheet->setCellValue($colLetter . '3', $dates[$i]->format('d'));
        }
        $sheet->getStyle('C3:' . $lastCol . '3')->getFont()->setBold(true);
        $sheet->getStyle('C3:' . $lastCol . '3')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF4A90E2');
        $sheet->getStyle('C3:' . $lastCol . '3')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('C3:' . $lastCol . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Warnai header tanggal jika libur ---
        $holidayService = app(HolidayService::class);
        $holidayDates = $holidayService->getHolidayDates();
        for ($i = 0; $i < $totalDates; $i++) {
          $dateStr = $dates[$i]->format('Y-m-d');
          if (in_array($dateStr, $holidayDates)) {
            $colIndex = 3 + $i;
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $cell = $sheet->getCell($colLetter . '3');
            $cell->getStyle()->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFe74c3c');
            $cell->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
          }
        }

        // --- Data karyawan & shift (mulai baris 4) ---
        $row = 4;
        foreach ($employees as $employee) {
          $sheet->setCellValue('A' . $row, $employee->nrp);
          $sheet->setCellValue('B' . $row, $employee->name);

          $empSchedules = $schedules->get($employee->id, collect())->keyBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
          });

          for ($i = 0; $i < $totalDates; $i++) {
            $dateStr = $dates[$i]->format('Y-m-d');
            $schedule = $empSchedules->get($dateStr);
            $colIndex = 3 + $i;
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);

            $value = '';
            if ($schedule) {
              $value = match ($schedule->shift) {
                ShiftType::Day => 'D',
                ShiftType::Night => 'N',
                ShiftType::Off => 'O',
                ShiftType::Leave => 'CT',
                default => '',
                };
              }
              $sheet->setCellValue($colLetter . $row, $value);
              $sheet->getStyle($colLetter . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $row++;
          }

          // --- Hitung total per shift per tanggal ---
          $totals = [];
          foreach ($dates as $date) {
            $dateStr = $date->format('Y-m-d');
            $totals[$dateStr] = ['D' => 0,
              'N' => 0,
              'O' => 0,
              'CT' => 0];
          }
          foreach ($schedules as $empId => $empSchedules) {
            foreach ($empSchedules as $schedule) {
              $dateStr = Carbon::parse($schedule->date)->format('Y-m-d');
              if (isset($totals[$dateStr])) {
                switch ($schedule->shift) {
                case ShiftType::Day: $totals[$dateStr]['D']++; break;
                case ShiftType::Night: $totals[$dateStr]['N']++; break;
                case ShiftType::Off: $totals[$dateStr]['O']++; break;
                case ShiftType::Leave: $totals[$dateStr]['CT']++; break;
                }
              }
            }
          }

          // --- Tulis baris TOTAL ---
          $totalStartRow = $row;
          $sheet->mergeCells('A' . $totalStartRow . ':A' . ($totalStartRow + 3));
          $sheet->setCellValue('A' . $totalStartRow, 'TOTAL');
          $sheet->getStyle('A' . $totalStartRow . ':A' . ($totalStartRow + 3))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
          $sheet->getStyle('A' . $totalStartRow . ':A' . ($totalStartRow + 3))->getFont()->setBold(true);

          $labels = ['Day',
            'Night',
            'Off',
            'Cuti'];
          $keys = ['D',
            'N',
            'O',
            'CT'];
          for ($sub = 0; $sub < 4; $sub++) {
            $currentRow = $totalStartRow + $sub;
            $sheet->setCellValue('B' . $currentRow, $labels[$sub]);
            $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true);
            for ($i = 0; $i < $totalDates; $i++) {
              $dateStr = $dates[$i]->format('Y-m-d');
              $colIndex = 3 + $i;
              $colLetter = Coordinate::stringFromColumnIndex($colIndex);
              $totalValue = $totals[$dateStr][$keys[$sub]] ?? 0;
              $sheet->setCellValue($colLetter . $currentRow, $totalValue);
              $sheet->getStyle($colLetter . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
          }

          $highestRow = $totalStartRow + 3;

          // --- Warna latar belakang untuk data shift (hanya baris karyawan) ---
          $colorMap = [
            'D' => 'FF2ecc71',
            'N' => 'FF000000',
            'O' => 'FFe74c3c',
            'CT' => 'FFf1c40f',
          ];
          for ($r = 4; $r < $totalStartRow; $r++) {
            for ($c = 3; $c <= $lastColIndex; $c++) {
              $cell = $sheet->getCellByColumnAndRow($c, $r);
              $cellValue = $cell->getValue();
              if (isset($colorMap[$cellValue])) {
                $cell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($colorMap[$cellValue]);
                if ($cellValue === 'N') {
                  $cell->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
                }
              }
            }
          }

          // --- Border untuk seluruh tabel ---
          $styleArray = [
            'borders' => [
              'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
              ],
            ],
          ];
          $sheet->getStyle('A1:' . $lastCol . $highestRow)->applyFromArray($styleArray);

          // --- Auto-size kolom ---
          for ($col = 1; $col <= $lastColIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
          }
        },
      ];
    }
  }