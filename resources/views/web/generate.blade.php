@extends('shiftgenerator::layouts.web')
@section('title', 'Generate Roster')
@section('content')
<h4 class="mb-4"><i class="bi bi-calendar-check me-2"></i>Generate Roster</h4>

<div class="card p-4 mb-4">
  <form method="POST" action="{{ route('shift.generate.run') }}">
    @csrf
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Tanggal Mulai</label>
        <input type="date" name="start_date" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tanggal Selesai</label>
        <input type="date" name="end_date" class="form-control" required>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-success w-100"><i class="bi bi-gear me-1"></i> Generate</button>
      </div>
    </div>
  </form>
</div>

<div class="card p-4">
  <h5 class="mb-3">Export Excel</h5>
  <form method="GET" action="{{ route('shift.generate.export') }}" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Mulai</label>
      <input type="date" name="start_date" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
    </div>
    <div class="col-md-4">
      <label class="form-label">Selesai</label>
      <input type="date" name="end_date" class="form-control" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
    </div>
    <div class="col-md-4">
      <button type="submit" class="btn btn-outline-info w-100"><i class="bi bi-download me-1"></i> Download Excel</button>
    </div>
  </form>
</div>
@endsection