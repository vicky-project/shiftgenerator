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
}