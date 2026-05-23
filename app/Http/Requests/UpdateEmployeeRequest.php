<?php

namespace Modules\ShiftGenerator\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ShiftGenerator\Enums\ShiftType;

class UpdateEmployeeRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => 'sometimes|string|max:255',
      'nrp' => 'sometimes|string|max:50|unique:employees,nrp,' . $this->employee->id,
      'shift_pattern' => 'sometimes|string|max:100',
      'shift_start_date' => 'sometimes|date',
      'shift_start' => [
        'sometimes',
        Rule::enum(ShiftType::class)
      ],
      'work_days' => 'sometimes|integer|min:1',
      'leave_days' => 'sometimes|integer|min:1',
      'pattern_start_date' => 'sometimes|date',
    ];
  }
}