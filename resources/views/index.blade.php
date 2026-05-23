@extends('telegram::layouts.mini-app')

@section('title', 'Shift Generator')

@section('content')
{{-- Loading Overlay Global --}}
<div id="global-loading" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:10000; flex-direction:column;">
  <div class="spinner-border text-light" role="status">
    <span class="visually-hidden">Loading...</span>
  </div>
  <div style="color:white; margin-top:10px;">
    Memuat...
  </div>
</div>

<div id="app-shell" class="d-flex flex-column min-vh-100">
  <div id="app-header" class="px-3 py-2 d-flex align-items-center border-bottom" style="background: var(--tg-theme-secondary-bg-color);">
    <button id="btn-back" class="btn btn-sm btn-outline-secondary me-2 d-none" onclick="window.goToPage('employees')">
      <i class="bi bi-arrow-left"></i>
    </button>
    <h5 id="app-title" class="mb-0">Shift Generator</h5>
  </div>
  <div id="app-content" class="flex-fill p-3">
    <!-- Konten akan di-render oleh JavaScript -->
  </div>
  <div id="app-tabbar" class="border-top py-2 d-flex justify-content-around" style="background: var(--tg-theme-secondary-bg-color);">
    <button class="btn btn-link text-decoration-none text-center nav-link" data-nav="employees" data-route="employees">
      <i class="bi bi-people fs-5"></i><br><small>Karyawan</small>
    </button>
    <button class="btn btn-link text-decoration-none text-center nav-link" data-nav="generate" data-route="generate">
      <i class="bi bi-calendar-check fs-5"></i><br><small>Generate</small>
    </button>
  </div>
</div>
@endsection

@push('scripts')
<script src="//cdn.jsdelivr.net/npm/eruda"></script>
<script>
  eruda.init();
</script>
<script>
  window.API_BASE = '{{ rtrim(config("app.url"), "/") }}';
</script>
<script src="{{ secure_url('/apps/shift/js/core.js') }}"></script>
<script src="{{ secure_url('/apps/shift/js/page.js') }}"></script>
<script src="{{ secure_url('/apps/shift/js/main.js') }}"></script>
@endpush

@push('styles')
<style>
:root {
  --shift-primary: #4A90E2;
  --shift-primary-hover: #357ABD;
  --shift-danger: #E74C3C;
  --shift-warning: #F39C12;
  --shift-info: #3498DB;
}

  /* Tab bar - tanpa background, hanya teks */
  #app-tabbar .nav-link {
    color: var(--tg-theme-hint-color) !important;
    transition: color 0.2s;
    border: none !important;
    background: transparent !important;
    padding: 0.5rem;
    border-radius: 8px;
  }
  #app-tabbar .nav-link.active {
    color: var(--shift-primary) !important;
    font-weight: 600;
    background: transparent !important;
  }
  #app-tabbar .nav-link.active i {
    color: var(--shift-primary) !important;
  }

  /* Card */
  .card {
    border-radius: 12px;
    border: 1px solid var(--tg-theme-section-separator-color);
    background: var(--tg-theme-secondary-bg-color);
    transition: all 0.2s ease;
  }
  .card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }

  /* Buttons */
  .btn-primary {
    background-color: var(--shift-primary);
    border-color: var(--shift-primary);
  }
  .btn-primary:hover {
    background-color: var(--shift-primary-hover);
    border-color: var(--shift-primary-hover);
  }
  .btn-outline-info {
    color: var(--shift-info);
    border-color: var(--shift-info);
  }
  .btn-outline-info:hover {
    background-color: var(--shift-info);
    border-color: var(--shift-info);
    color: #fff;
  }
  .btn-outline-warning {
    color: var(--shift-warning);
    border-color: var(--shift-warning);
  }
  .btn-outline-warning:hover {
    background-color: var(--shift-warning);
    border-color: var(--shift-warning);
    color: #fff;
  }
  .btn-outline-danger {
    color: var(--shift-danger);
    border-color: var(--shift-danger);
  }
  .btn-outline-danger:hover {
    background-color: var(--shift-danger);
    border-color: var(--shift-danger);
    color: #fff;
  }

  /* Form */
  .form-control, .form-select {
    border-radius: 8px;
    border: 1px solid var(--tg-theme-section-separator-color);
    background: var(--tg-theme-bg-color);
    color: var(--tg-theme-text-color);
  }

  /* Table */
  .table {
    background: var(--tg-theme-secondary-bg-color);
    border-radius: 8px;
    overflow: hidden;
  }
  .table thead {
    background: var(--tg-theme-bg-color);
    border-bottom: 2px solid var(--tg-theme-section-separator-color);
  }

  /* Alert */
  .alert {
    border-radius: 8px;
  }
</style>
@endpush