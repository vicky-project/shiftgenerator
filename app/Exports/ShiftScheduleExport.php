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
use Modules\ShiftGenerator\Services\ShiftGeneratorService;

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

        // --- Ambil data ---
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
          $dates[] = $date;
        }
        $totalDates = count($dates);
        $firstDateColIndex = 4; // kolom D adalah indeks ke-4
        $lastColIndex = 3 + $totalDates;
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

        // Siapkan data libur
        $holidayService = app(HolidayService::class);
        $holidayDates = $holidayService->getHolidayDates();

        // Service untuk menghitung plan
        $shiftService = app(ShiftGeneratorService::class);

        // --- Baris 1: Judul ---
        $sheet->setCellValue('A1', 'Roster Shift');
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Header NRP, Nama, Plan/Actual (merge vertikal 4 baris: 2,3,4,5) ---
        $sheet->mergeCells('A2:A5');
        $sheet->setCellValue('A2', 'NRP');
        $sheet->mergeCells('B2:B5');
        $sheet->setCellValue('B2', 'Nama');
        $sheet->mergeCells('C2:C5');
        $sheet->setCellValue('C2', 'Plan / Actual');
        $sheet->getStyle('A2:C5')->getFont()->setBold(true);
        $sheet->getStyle('A2:C5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A2:C5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:C5')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFDDDDDD');

        // --- Header bulan (baris 2) ---
        $currentMonth = null;
        $startMonthCol = $firstDateColIndex;
        for ($i = 0; $i < $totalDates; $i++) {
          $month = $dates[$i]->format('F');
          $colIndex = $firstDateColIndex + $i;
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
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '2:' . $lastCol . '2')->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '2:' . $lastCol . '2')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFDDDDDD');
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '2:' . $lastCol . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Header tanggal (baris 3) ---
        for ($i = 0; $i < $totalDates; $i++) {
          $colIndex = $firstDateColIndex + $i;
          $colLetter = Coordinate::stringFromColumnIndex($colIndex);
          $sheet->setCellValue($colLetter . '3', $dates[$i]->format('d'));
        }
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '3:' . $lastCol . '3')->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '3:' . $lastCol . '3')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF4A90E2');
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '3:' . $lastCol . '3')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '3:' . $lastCol . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Header hari (baris 4) dengan teks vertikal ---
        for ($i = 0; $i < $totalDates; $i++) {
          $colIndex = $firstDateColIndex + $i;
          $colLetter = Coordinate::stringFromColumnIndex($colIndex);
          $dayName = $dates[$i]->isoFormat('dddd');
          $cell = $sheet->getCell($colLetter . '4');
          $cell->setValue($dayName);
          $cell->getStyle()->getAlignment()->setTextRotation(90);
          $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
          $cell->getStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '4:' . $lastCol . '4')->getFont()->setBold(true);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '4:' . $lastCol . '4')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF4A90E2');
        $sheet->getStyle(Coordinate::stringFromColumnIndex($firstDateColIndex) . '4:' . $lastCol . '4')->getFont()->getColor()->setARGB('FFFFFFFF');

        // --- Warnai header tanggal dan hari jika libur atau Minggu ---
        for ($i = 0; $i < $totalDates; $i++) {
          $date = $dates[$i];
          $dateStr = $date->format('Y-m-d');
          $isSunday = $date->dayOfWeek === Carbon::SUNDAY;
          $isHoliday = in_array($dateStr, $holidayDates);
          if ($isSunday || $isHoliday) {
            $colIndex = $firstDateColIndex + $i;
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            // Baris tanggal
            $cellDate = $sheet->getCell($colLetter . '3');
            $cellDate->getStyle()->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFe74c3c');
            $cellDate->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
            // Baris hari
            $cellDay = $sheet->getCell($colLetter . '4');
            $cellDay->getStyle()->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFe74c3c');
            $cellDay->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
          }
        }

        // --- Data karyawan (Plan & Actual) mulai baris 6 ---
        $row = 6;
        foreach ($employees as $employee) {
          // Merge NRP dan Nama untuk 2 baris
          $sheet->mergeCells('A' . $row . ':A' . ($row + 1));
          $sheet->setCellValue('A' . $row, $employee->nrp);
          $sheet->mergeCells('B' . $row . ':B' . ($row + 1));
          $sheet->setCellValue('B' . $row, $employee->name);

          // Tulis "Plan" dan "Actual" langsung di kolom C tanpa merge
          $sheet->setCellValue('C' . $row, 'Plan');
          $sheet->setCellValue('C' . ($row + 1), 'Actual');

          // Ambil jadwal aktual dari database
          $empSchedules = $schedules->get($employee->id, collect())->keyBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
          });

          // Hitung Plan dan Actual
          for ($i = 0; $i < $totalDates; $i++) {
            $date = $dates[$i];
            $dateStr = $date->format('Y-m-d');
            $colIndex = $firstDateColIndex + $i;
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);

            // Plan shift (tanpa override)
            $planShift = $shiftService->calculatePlanShift($employee, $date);

            // Actual shift (dari database)
            $schedule = $empSchedules->get($dateStr);
            $actualShift = $schedule ? $schedule->shift : null;

            // Tulis Plan di baris pertama
            $planValue = $this->shiftToCode($planShift);
            $cellPlan = $sheet->getCell($colLetter . $row);
            $cellPlan->setValue($planValue);
            $cellPlan->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Tulis Actual di baris kedua
            $actualValue = $actualShift ? $this->shiftToCode($actualShift) : '';
            $cellActual = $sheet->getCell($colLetter . ($row + 1));
            $cellActual->setValue($actualValue);
            $cellActual->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
          }

          $row += 2; // pindah ke karyawan berikutnya
        }

        // --- Satu baris kosong sebelum total ---
        $row++;

        // --- Hitung total hanya untuk Actual ---
        $totals = [];
        foreach ($dates as $date) {
          $dateStr = $date->format('Y-m-d');
          $totals[$dateStr] = ['D' => 0,
            'N' => 0,
            'O' => 0,
            'CT' => 0];
        }
        foreach ($schedules as $empSchedules) {
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

        // --- Baris TOTAL ---
        $totalStartRow = $row;
        $sheet->mergeCells('A' . $totalStartRow . ':A' . ($totalStartRow + 3));
        $sheet->setCellValue('A' . $totalStartRow, 'TOTAL');
        $sheet->getStyle('A' . $totalStartRow . ':A' . ($totalStartRow + 3))
        ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A' . $totalStartRow . ':A' . ($totalStartRow + 3))->getFont()->setBold(true);

        $labels = ['Day',
          'Night',
          'Off',
          'Cuti'];
        $keys = ['D',
          'N',
          'O',
          'CT'];
        $totalColors = [
          'FF2ecc71',
          // hijau
          'FF000000',
          // hitam
          'FFe74c3c',
          // merah
          'FFf1c40f',
          // kuning
        ];
        for ($sub = 0; $sub < 4; $sub++) {
          $currentRow = $totalStartRow + $sub;
          $color = $totalColors[$sub];

          // Label di kolom B
          $sheet->setCellValue('B' . $currentRow, $labels[$sub]);
          $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true);

          // Singkatan di kolom C
          $sheet->setCellValue('C' . $currentRow, $keys[$sub]);

          // Isi nilai total per tanggal
          for ($i = 0; $i < $totalDates; $i++) {
            $dateStr = $dates[$i]->format('Y-m-d');
            $colIndex = $firstDateColIndex + $i;
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $totalValue = $totals[$dateStr][$keys[$sub]] ?? 0;
            $cell = $sheet->getCell($colLetter . $currentRow);
            $cell->setValue($totalValue);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
          }

          $barisRange = 'B' . $currentRow . ':' . $lastCol . $currentRow;
          $sheet->getStyle($barisRange)->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB($color);

          if ($labels[$sub] === 'Night') {
            $sheet->getStyle($barisRange)->getFont()->getColor()->setARGB('FFFFFFFF');
          }
        }

        $highestRow = $totalStartRow + 3;

        // --- Warna latar belakang untuk sel data Plan dan Actual ---
        $colorMap = [
          'D' => 'FF2ecc71',
          'N' => 'FF000000',
          'O' => 'FFe74c3c',
          'CT' => 'FFf1c40f',
        ];
        $finalDataRow = $totalStartRow - 2;
        for ($r = 6; $r <= $finalDataRow; $r++) {
          for ($c = $firstDateColIndex; $c <= $lastColIndex; $c++) {
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

        // --- Border ---
        $styleArray = [
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color' => ['argb' => 'FF000000'],
            ],
          ],
        ];
        $sheet->getStyle('A1:' . $lastCol . $highestRow)->applyFromArray($styleArray);

        // --- Auto-size untuk kolom A, B, C; width manual untuk kolom tanggal ---
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        for ($col = $firstDateColIndex; $col <= $lastColIndex; $col++) {
          $colLetter = Coordinate::stringFromColumnIndex($col);
          $sheet->getColumnDimension($colLetter)->setWidth(5);
        }

        // --- Freeze pane di D6 ---
        $sheet->freezePane('D6');

        // --- Auto-filter pada seluruh area data (A5 sampai kolom terakhir, baris akhir data) ---
        $filterRange = 'A5:' . $lastCol . $finalDataRow;
        $sheet->setAutoFilter($filterRange);
      },
    ];
  }

  /**
  * Konversi enum ShiftType ke kode pendek.
  */
  private function shiftToCode(ShiftType|string|null $shift): string
  {
    if ($shift instanceof ShiftType) {
      return match ($shift) {
        ShiftType::Day => 'D',
        ShiftType::Night => 'N',
        ShiftType::Off => 'O',
        ShiftType::Leave => 'CT',
      };
    }
    return match ($shift) {
      'Day' => 'D',
      'Night' => 'N',
      'Off' => 'O',
      'Leave' => 'CT',
      default => '',
      };
    }
  }