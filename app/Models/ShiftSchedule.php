<?php
namespace Modules\ShiftGenerator\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\ShiftGenerator\Enums\ShiftType;

class ShiftSchedule extends Model
{
  protected $fillable = ['employee_id',
    'date',
    'shift'];

  protected $casts = [
    'date' => 'date',
    'shift' => ShiftType::class
  ];

  public function employee() {
    return $this->belongsTo(Employee::class);
  }
}