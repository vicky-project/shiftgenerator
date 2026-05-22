<?php
namespace Modules\ShiftGenerator\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSchedule extends Model
{
  protected $fillable = ['employee_id',
    'date',
    'shift'];

  protected $casts = [
    'date' => 'date',
  ];

  public function employee() {
    return $this->belongsTo(Employee::class);
  }
}