<?php

namespace Modules\ShiftGenerator\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HolidayService
{
  const CACHE_KEY = 'shiftgenerator.holidays';
  const CACHE_DURATION = 525600; // 1 tahun dalam menit

  protected string $apiUrl = 'https://use.api.co.id/holidays/indonesia/';
  protected ?string $apiKey;

  public function __construct() {
    $this->apiKey = config('shiftgenerator.holiday.holiday_api_key') ?? env('HOLIDAY_API_KEY');
  }

  /**
  * Ambil semua data hari libur (tanggal + nama) dari API, di-cache.
  */
  public function getHolidays(): array
  {
    return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
      $allHolidays = [];
      $currentYear = (int) date('Y');
      for ($year = 2020; $year <= $currentYear + 2; $year++) {
        $holidays = $this->fetchYear($year);
        $allHolidays = array_merge($allHolidays, $holidays);
      }
      return $allHolidays;
    });
  }

  /**
  * Ambil hanya tanggal-tanggal hari libur (array of string Y-m-d).
  */
  public function getHolidayDates(): array
  {
    return array_map(fn($item) => $item['date'],
      $this->getHolidays());
  }

  /**
  * Fetch data libur untuk satu tahun dari API.
  */
  protected function fetchYear(int $year): array
  {
    $response = Http::withHeaders([
      'x-api-co-id' => $this->apiKey,
    ])->get($this->apiUrl,
      [
        'year' => $year,
        'start_date' => "{$year}-01-01",
        'end_date' => "{$year}-12-31",
        'page' => 1,
      ]);

    if ($response->failed()) {
      \Log::error('Gagal mengambil data libur nasional', [
        'year' => $year,
        'status' => $response->status(),
      ]);
      return [];
    }

    $data = $response->json();

    if (!($data['is_success'] ?? false)) {
      \Log::error('API libur nasional gagal', ['response' => $data]);
      return [];
    }

    $holidays = $data['data'] ?? [];

    return array_map(fn($item) => [
      'date' => $item['date'],
      'name' => $item['name'],
    ], $holidays);
  }

  /**
  * Hapus cache (untuk force refresh manual).
  */
  public function clearCache(): void
  {
    Cache::forget(self::CACHE_KEY);
  }
}