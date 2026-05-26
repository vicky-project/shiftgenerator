<?php

namespace Modules\ShiftGenerator\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ShiftGenerator\Enums\ShiftType;

class StoreEmployeeRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // otentikasi sudah dilakukan via middleware
  }

  public function rules(): array
  {
    return [
      'name' => 'required|string|max:255',
      'nrp' => 'required|string|max:50|unique:employees,nrp',
      'shift_pattern' => 'required|string|max:100',
      'shift_start_date' => 'required|date',
      'shift_start' => [
        'required',
        Rule::enum(ShiftType::class)
      ],
      'work_days' => 'required|integer|min:1',
      'leave_days' => 'required|integer|min:1',
      'pattern_start_date' => 'required|date',
    ];
  }
}