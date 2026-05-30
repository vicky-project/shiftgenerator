<?php
namespace Modules\ShiftGenerator\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Telegram\Models\TelegramUser;
use Modules\ShiftGenerator\Enums\ShiftType;

class Employee extends Model
{
  protected $fillable = [
    'telegram_user_id',
    'name',
    'nrp',
    'shift_pattern',
    'shift_start_date',
    'shift_start',
    'work_days',
    'leave_days',
    'pattern_start_date'
  ];

  protected $casts = [
    'shift_start' => ShiftType::class,
    'shift_start_date' => 'date',
    'pattern_start_date' => 'date',
  ];

  public function user() {
    return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
  }

  public function overrides() {
    return $this->hasMany(EmployeeOverride::class)->orderBy('start_date');
  }

  public function schedules() {
    return $this->hasMany(ShiftSchedule::class);
  }

  /**
  * Accessor untuk menampilkan pola shift dalam format singkat (misal 8D5N1O).
  */
  public function getFormattedPatternAttribute(): string
  {
    $pattern = $this->shift_pattern;
    if (empty($pattern)) {
      return '';
    }

    // Ubah semua karakter selain D/N menjadi O
    $normalized = strtoupper($pattern);
    $normalized = preg_replace('/[^DN]/', 'O', $normalized);

    $counts = [];
    $currentChar = null;
    $count = 0;
    $len = strlen($normalized);
    for ($i = 0; $i < $len; $i++) {
      $char = $normalized[$i];
      if ($char !== $currentChar) {
        if ($currentChar !== null) {
          $counts[] = $count . $currentChar;
        }
        $currentChar = $char;
        $count = 1;
      } else {
        $count++;
      }
    }
    if ($currentChar !== null) {
      $counts[] = $count . $currentChar;
    }

    return implode('', $counts);
  }
}