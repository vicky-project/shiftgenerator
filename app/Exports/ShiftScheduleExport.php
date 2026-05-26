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
        $lastColIndex = 2 + $totalDates;
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

        // Siapkan data libur
        $holidayService = app(HolidayService::class);
        $holidayDates = $holidayService->getHolidayDates();

        // --- Baris 1: Judul ---
        $sheet->setCellValue('A1', 'Roster Shift');
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Header NRP & Nama (merge vertikal 3 baris: 2,3,4) ---
        $sheet->mergeCells('A2:A4');
        $sheet->setCellValue('A2', 'NRP');
        $sheet->mergeCells('B2:B4');
        $sheet->setCellValue('B2', 'Nama');
        $sheet->getStyle('A2:B4')->getFont()->setBold(true);
        $sheet->getStyle('A2:B4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A2:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:B4')->getFill()
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

        // --- Header hari (baris 4) dengan teks vertikal ---
        for ($i = 0; $i < $totalDates; $i++) {
          $colIndex = 3 + $i;
          $colLetter = Coordinate::stringFromColumnIndex($colIndex);
          $dayName = $dates[$i]->isoFormat('dddd'); // Senin, Selasa, ...
          $cell = $sheet->getCell($colLetter . '4');
          $cell->setValue($dayName);
          // Teks vertikal (rotasi 90 derajat)
          $cell->getStyle()->getAlignment()->setTextRotation(90);
          $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
          $cell->getStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getStyle('C4:' . $lastCol . '4')->getFont()->setBold(true);
        $sheet->getStyle('C4:' . $lastCol . '4')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF4A90E2');
        $sheet->getStyle('C4:' . $lastCol . '4')->getFont()->getColor()->setARGB('FFFFFFFF');

        // --- Warnai header tanggal dan hari jika libur atau Minggu ---
        for ($i = 0; $i < $totalDates; $i++) {
          $date = $dates[$i];
          $dateStr = $date->format('Y-m-d');
          $isSunday = $date->dayOfWeek === Carbon::SUNDAY;
          $isHoliday = in_array($dateStr, $holidayDates);
          if ($isSunday || $isHoliday) {
            $colIndex = 3 + $i;
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

        // --- Data karyawan & shift (mulai baris 5) ---
        $row = 5;
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

          // --- Satu baris kosong sebelum total ---
          $row++; // lewati satu baris

          // --- Hitung total per shift per tanggal ---
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

            // Isi nilai total per tanggal
            for ($i = 0; $i < $totalDates; $i++) {
              $dateStr = $dates[$i]->format('Y-m-d');
              $colIndex = 3 + $i;
              $colLetter = Coordinate::stringFromColumnIndex($colIndex);
              $totalValue = $totals[$dateStr][$keys[$sub]] ?? 0;
              $cell = $sheet->getCell($colLetter . $currentRow);
              $cell->setValue($totalValue);
              $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Warnai seluruh baris (kolom B hingga akhir) dengan warna latar
            $barisRange = 'B' . $currentRow . ':' . $lastCol . $currentRow;
            $sheet->getStyle($barisRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($color);

            if ($labels[$sub] === 'Night') {
              $sheet->getStyle($barisRange)->getFont()->getColor()->setARGB('FFFFFFFF');
            }
          }

          $highestRow = $totalStartRow + 3;

          // --- Warna latar belakang untuk sel data shift (baris 5 s/d sebelum baris kosong) ---
          $colorMap = [
            'D' => 'FF2ecc71',
            'N' => 'FF000000',
            'O' => 'FFe74c3c',
            'CT' => 'FFf1c40f',
          ];
          $finalDataRow = $totalStartRow - 2; // karena ada satu baris kosong, data terakhir di $totalStartRow - 2
          for ($r = 5; $r <= $finalDataRow; $r++) {
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

          // --- Auto-size untuk kolom A dan B, set width manual untuk kolom tanggal ---
          $sheet->getColumnDimension('A')->setAutoSize(true);
          $sheet->getColumnDimension('B')->setAutoSize(true);
          // Untuk kolom tanggal, atur lebar sekitar 4 karakter (cukup untuk teks vertikal & data)
          for ($col = 3; $col <= $lastColIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setWidth(5);
          }

          // --- Freeze pane di C5 ---
          $sheet->freezePane('C5');

          // --- Auto-filter pada seluruh area data (A4 sampai kolom terakhir, baris akhir data) ---
          $filterRange = 'A4:' . $lastCol . $finalDataRow;
          $sheet->setAutoFilter($filterRange);
        },
      ];
    }
  }