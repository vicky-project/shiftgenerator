@extends('shiftgenerator::layouts.web')
@section('title', isset($employee) ? 'Edit Karyawan' : 'Tambah Karyawan')
@section('content')
<h4 class="mb-4"><i class="bi bi-{{ isset($employee) ? 'pencil' : 'plus-lg' }} me-2"></i>{{ isset($employee) ? 'Edit' : 'Tambah' }} Karyawan</h4>
<div class="card p-4">
  <form method="POST" action="{{ isset($employee) ? route('shift.employees.update', $employee) : route('shift.employees.store') }}">
    @csrf
    @if(isset($employee)) @method('PUT') @endif
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name ?? '') }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">NRP</label>
        <input type="text" name="nrp" class="form-control" value="{{ old('nrp', $employee->nrp ?? '') }}" required>
      </div>
      <div class="col-12">
        <label class="form-label">Pola Shift</label>
        <input type="text" name="shift_pattern" class="form-control" value="{{ old('shift_pattern', $employee->shift_pattern ?? 'DDDDDDDDNNNNNO') }}" required pattern="[DNO\-]+" title="Hanya D, N, O, atau -">
      </div>
      <div class="col-md-4">
        <label class="form-label">Shift Start Date</label>
        <input type="date" name="shift_start_date" class="form-control" value="{{ old('shift_start_date', isset($employee) ? $employee->shift_start_date->format('Y-m-d') : '') }}" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Shift Start</label>
        <select name="shift_start" class="form-select">
          <option value="Day" {{ old('shift_start', $employee->shift_start->value ?? '') == 'Day' ? 'selected' : '' }}>Day</option>
          <option value="Night" {{ old('shift_start', $employee->shift_start->value ?? '') == 'Night' ? 'selected' : '' }}>Night</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Work Days</label>
        <input type="number" name="work_days" class="form-control" value="{{ old('work_days', $employee->work_days ?? 70) }}" min="1">
      </div>
      <div class="col-md-3">
        <label class="form-label">Leave Days</label>
        <input type="number" name="leave_days" class="form-control" value="{{ old('leave_days', $employee->leave_days ?? 14) }}" min="1">
      </div>
      <div class="col-12">
        <label class="form-label">Pattern Start Date</label>
        <input type="date" name="pattern_start_date" class="form-control" value="{{ old('pattern_start_date', isset($employee) ? $employee->pattern_start_date->format('Y-m-d') : '') }}" required>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-success">Simpan</button>
        <a href="{{ route('shift.employees.web') }}" class="btn btn-secondary ms-2">Batal</a>
      </div>
    </div>
  </form>
</div>
@endsection