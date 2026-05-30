@extends('shiftgenerator::layouts.web')
@section('title', 'Dashboard')
@section('content')
<h4 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h4>
<div class="row g-4">
  <div class="col-md-4">
    <div class="card p-4 text-center">
      <i class="bi bi-people fs-1" style="color: #38BDF8;"></i>
      <h5 class="mt-3 mb-1">{{ $employeeCount }}</h5>
      <p class="text-muted mb-0">
        Total Karyawan
      </p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-4 text-center">
      <i class="bi bi-calendar-check fs-1" style="color: #34D399;"></i>
      <h5 class="mt-3 mb-1">{{ $rosterCount }}</h5>
      <p class="text-muted mb-0">
        Total Roster
      </p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-4 text-center">
      <i class="bi bi-calendar-x fs-1" style="color: #FBBF24;"></i>
      <h5 class="mt-3 mb-1">{{ $overrideCount }}</h5>
      <p class="text-muted mb-0">
        Pengajuan Cuti
      </p>
    </div>
  </div>
</div>
@endsection