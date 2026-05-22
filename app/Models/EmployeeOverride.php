<?php
namespace Modules\ShiftGenerator\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeOverride extends Model
{
  protected $fillable = ['employee_id',
    'start_date'];

  protected $casts = [
    'start_date' => 'date',
  ];

  public function employee() {
    return $this->belongsTo(Employee::class);
  }
}