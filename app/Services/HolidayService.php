<?php

namespace Modules\ShiftGenerator\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HolidayService
{
  const CACHE_KEY = 'shiftgenerator.holidays';
  const CACHE_DURATION = 525600; // 1 tahun dalam menit (365 * 24 * 60)

  /**
  * Ambil semua tanggal hari libur (nasional & cuti bersama) dari sumber eksternal.
  *
  * @return array of string (Y-m-d)
  */
  public function getHolidayDates(): array
  {
    return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
      $response = Http::get('https://vickyserver.my.id/data/local_data.json');

      if ($response->failed()) {
        \Log::error('Gagal mengambil data libur nasional', ['status' => $response->status()]);
        return [];
      }

      $data = $response->json();
      $holidays = $data['holidays'] ?? [];

      // Ambil semua tanggal, baik Libur Nasional maupun Cuti Bersama
      return array_map(fn($item) => $item['date'], $holidays);
    });
  }

  /**
  * Hapus cache (untuk force refresh manual).
  */
  public function clearCache(): void
  {
    Cache::forget(self::CACHE_KEY);
  }
}