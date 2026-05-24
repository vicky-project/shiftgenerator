<?php

namespace Modules\ShiftGenerator\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HolidayService
{
  const CACHE_KEY = 'shiftgenerator.holidays';
  const CACHE_DURATION = 525600; // 1 tahun dalam menit

  /**
  * Ambil semua data hari libur (tanggal + nama).
  */
  public function getHolidays(): array
  {
    return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
      $response = Http::get('https://vickyserver.my.id/data/local_data.json');
      if ($response->failed()) {
        \Log::error('Gagal mengambil data libur nasional', ['status' => $response->status()]);
        return [];
      }
      $data = $response->json();
      \Log::debug('Data holidays', $data ?? []);
      $holidays = $data['holidays'] ?? [];
      return array_map(fn($item) => [
        'date' => $item['date'],
        'name' => $item['name'],
      ], $holidays);
    });
  }

  public function clearCache(): void
  {
    Cache::forget(self::CACHE_KEY);
  }
}