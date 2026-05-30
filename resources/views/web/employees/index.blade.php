@extends('shiftgenerator::layouts.web')
@section('title', 'Daftar Karyawan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4><i class="bi bi-people me-2"></i>Daftar Karyawan</h4>
  <a href="{{ route('shift.employees.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah</a>
</div>
@if($employees->isEmpty())
<div class="card p-5 text-center text-muted">
  <i class="bi bi-people fs-1 mb-3" style="opacity:0.3;"></i>
  <h5>Belum ada karyawan</h5>
</div>
@else
<div class="row g-3">
  @foreach($employees as $emp)
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h5>{{ $emp->name }}</h5>
            <p class="text-muted mb-1">
              NRP: {{ $emp->nrp }}
            </p>
          </div>
          <div class="btn-group btn-group-sm align-self-start">
            <a href="{{ route('shift.employees.overrides', $emp) }}" class="btn btn-outline-info" title="Pengajuan Cuti">
              <i class="bi bi-calendar-minus"></i>
            </a>
            <a href="{{ route('shift.employees.edit', $emp) }}" class="btn btn-outline-warning" title="Edit Karyawan">
              <i class="bi bi-pencil"></i>
            </a>
            <form action="{{ route('shift.employees.destroy', $emp) }}" method="POST" onsubmit="return confirm('Yakin hapus?')">
              @csrf @method('DELETE')
              <button class="btn btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
        <p class="text-muted mb-1">
          Pola: {{ $emp->formatted_pattern }} | Siklus: {{ $emp->work_days }}H/{{ $emp->leave_days }}H
        </p>
        <p class="text-muted">
          Shift Start: {{ $emp->shift_start_date->format('d/m/Y') }} ({{ $emp->shift_start->value }})
        </p>
      </div>
    </div>
  </div>
  @endforeach
</div>
<div class="mt-4">
  {{ $employees->links() }}
</div>
@endif
@endsection