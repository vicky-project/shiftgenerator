@extends('telegram::layouts.mini-app')

@section('title', 'Shift Generator')

@section('content')
<div id="app-shell" class="d-flex flex-column min-vh-100">
  <div id="app-header" class="px-3 py-2 d-flex align-items-center border-bottom" style="background: var(--tg-theme-secondary-bg-color);">
    <button id="btn-back" class="btn btn-sm btn-outline-secondary me-2 d-none" onclick="window.goToPage('employees')">
      <i class="bi bi-arrow-left"></i>
    </button>
    <h5 id="app-title" class="mb-0">Shift Generator</h5>
  </div>
  <div id="app-content" class="flex-fill p-3">
    <!-- Konten di-render oleh JS -->
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
<script>
  window.API_BASE = '{{ rtrim(config("app.url"), "/") }}';
</script>
<script src="{{ secure_url('/apps/shift/js/core.js') }}"></script>
<script src="{{ secure_url('/apps/shift/js/page.js') }}"></script>
<script src="{{ secure_url('/apps/shift/js/main.js') }}"></script>
@endpush