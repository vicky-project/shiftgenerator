@extends('telegram::layouts.mini-app')

@section('title', 'Shift Generator')

@section('content')
<div id="app-shell" class="d-flex flex-column min-vh-100">
  <div id="app-header" class="px-3 py-2 d-flex align-items-center border-bottom" style="background: var(--tg-theme-secondary-bg-color);">
    <button id="btn-back" class="btn btn-sm btn-outline-secondary me-2 d-none">
      <i class="bi bi-arrow-left"></i>
    </button>
    <h5 id="app-title" class="mb-0">Shift Generator</h5>
  </div>
  <div id="app-content" class="flex-fill p-3">
    <!-- Konten akan di-render oleh JavaScript -->
  </div>
  <div id="app-tabbar" class="border-top py-2 d-flex justify-content-around" style="background: var(--tg-theme-secondary-bg-color);">
    <a href="/employees" class="text-decoration-none text-center nav-link" data-route>
      <i class="bi bi-people fs-5"></i><br><small>Karyawan</small>
    </a>
    <a href="/generate" class="text-decoration-none text-center nav-link" data-route>
      <i class="bi bi-calendar-check fs-5"></i><br><small>Generate</small>
    </a>
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
<script src="{{ secure_url('/shift/js/core.js') }}"></script>
<script src="{{ secure_url('/shift/js/page.js') }}"></script>
<script src="{{ secure_url('/shift/js/main.js') }}"></script>
@endpush