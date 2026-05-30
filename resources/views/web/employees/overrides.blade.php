@extends('shiftgenerator::layouts.web')
@section('title', 'Pengajuan Cuti - ' . $employee->name)
@section('content')
<h4 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Pengajuan Cuti: {{ $employee->name }}</h4>

<div class="card p-4 mb-4">
  <form method="POST" action="{{ route('shift.employees.overrides.store', $employee) }}">
    @csrf
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Tanggal Mulai Cuti</label>
        <input type="date" name="start_date" class="form-control" required>
      </div>
      <div class="col-md-4">
        <p class="text-muted mb-0">
          Durasi: {{ $employee->leave_days }} hari
        </p>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-primary">Tambah</button>
      </div>
    </div>
  </form>
</div>

@if($overrides->isEmpty())
<div class="card p-4 text-center text-muted">
  Belum ada pengajuan cuti.
</div>
@else
<div class="row g-3">
  @foreach($overrides as $ov)
  <div class="col-md-6">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <strong>{{ \Carbon\Carbon::parse($ov->start_date)->format('d F Y') }}</strong>
          – {{ \Carbon\Carbon::parse($ov->start_date)->addDays($employee->leave_days - 1)->format('d F Y') }}
        </div>
        <form action="{{ route('shift.employees.overrides.destroy', $ov) }}" method="POST" onsubmit="return confirm('Hapus?')">
          @csrf @method('DELETE')
          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
      </div>
    </div>
  </div>
  @endforeach
</div>
@endif

<a href="{{ route('shift.employees.web') }}" class="btn btn-secondary mt-3">Kembali</a>
@endsection