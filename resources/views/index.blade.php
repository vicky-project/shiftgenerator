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
  <div id="app-content" class="flex-fill py-3" style="padding-bottom: 70px !important;margin-bottom: 70px;">
    <!-- Konten akan di-render oleh JavaScript -->
  </div>
  <div id="app-tabbar" class="border-top py-2 d-flex justify-content-around fixed-bottom" style="background: var(--tg-theme-secondary-bg-color); z-index: 1030;">
    <button class="btn btn-link text-decoration-none text-center nav-link" data-nav="employees" data-route="employees">
      <i class="bi bi-people fs-5"></i><br><small>Karyawan</small>
    </button>
    <button class="btn btn-link text-decoration-none text-center nav-link" data-nav="generate" data-route="generate">
      <i class="bi bi-calendar-check fs-5"></i><br><small>Generate</small>
    </button>
  </div>
</div>

<!-- Modal Informasi Global -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: var(--tg-theme-secondary-bg-color); color: var(--tg-theme-text-color);">
      <div class="modal-header" style="border-bottom: 1px solid var(--tg-theme-section-separator-color);">
        <h6 class="modal-title" id="infoModalLabel">Informasi</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(0.5);"></button>
      </div>
      <div class="modal-body" id="infoModalBody">
        <!-- Konten akan diisi dinamis -->
      </div>
      <div class="modal-footer" style="border-top: 1px solid var(--tg-theme-section-separator-color);">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
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
<script src="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/index.js"></script>
<script src="{{ secure_url('/apps/shift/js/core.js') }}"></script>
<script src="{{ secure_url('/apps/shift/js/page.js') }}"></script>
<script src="{{ secure_url('/apps/shift/js/main.js') }}"></script>
@endpush

@push('styles')
<!-- Vanilla Calendar Pro CSS -->
<link href="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/styles/index.css" rel="stylesheet">
<style>
:root {
  --shift-primary: #4A90E2;
  --shift-primary-hover: #357ABD;
  --shift-danger: #E74C3C;
  --shift-warning: #F39C12;
  --shift-info: #3498DB;
}

  /* Tab bar */
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
  }
  .btn-outline-warning {
    color: var(--shift-warning);
    border-color: var(--shift-warning);
  }
  .btn-outline-warning:hover {
    background-color: var(--shift-warning);
    border-color: var(--shift-warning);
  }
  .btn-outline-danger {
    color: var(--shift-danger);
    border-color: var(--shift-danger);
  }
  .btn-outline-danger:hover {
    background-color: var(--shift-danger);
    border-color: var(--shift-danger);
  }

  /* Form */
  .form-control, .form-select {
    border-radius: 8px;
    border: 1px solid var(--tg-theme-section-separator-color);
    background: var(--tg-theme-bg-color);
    color: var(--tg-theme-text-color);
  }

  /* Alert */
  .alert {
    border-radius: 8px;
  }

  /* Tombol info */
  [data-info-title] {
    color: var(--tg-theme-hint-color);
    text-decoration: none;
    font-size: 1rem;
    vertical-align: middle;
  }
  [data-info-title]:hover {
    color: var(--shift-primary);
  }

  /* ========== KALENDER ========== */
  /* Pastikan kalender tidak transparan */
  #calendar-instance {
    background: var(--tg-theme-secondary-bg-color);
    border-radius: 12px;
    padding: 0.5rem;
  }

  /* Dot warna shift */
  .vc-date__btn {
    position: relative;
  }

  /* ---------- LEGENDA ---------- */
  #calendar-legend {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
  }
  #calendar-legend .legend-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
  }
  #calendar-legend .legend-dot {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    display: inline-block;
  }
  #calendar-legend .legend-dot.day {
    background: #2ecc71;
  }
  #calendar-legend .legend-dot.night {
    background: #000000;
  }
  #calendar-legend .legend-dot.off {
    background: #e74c3c;
  }
  #calendar-legend .legend-dot.leave {
    background: #f1c40f;
  }
  .shift-holiday .vc-date__btn {
    color: #e74c3c !important;
    font-weight: 600;
  }
  .shift-holiday .vc-date__btn:hover {
    color: #fff !important;
    font-weight: 600;
  }
  .shift-holiday[data-vc-date-selected] .vc-date__btn {
    color: #fff !important;
    font-weight: 600;
  }
  .shift-day .vc-date__btn::after,
  .shift-night .vc-date__btn::after,
  .shift-leave .vc-date__btn::after,
  .shift-off .vc-date__btn::after {
    content: '';
    position: absolute;
    bottom: 3px;
    left: 50%;
    transform: translateX(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    pointer-events: none;
  }

  /* ---------- DOT WARNA SHIFT ---------- */
  .shift-day .vc-date__btn::after {
    background-color: #2ecc71;
    /* Hijau */
  }
  .shift-night .vc-date__btn::after {
    background-color: #1e3799;
    /* Biru tua */
  }
  .shift-off .vc-date__btn::after {
    background-color: #e74c3c;
    /* Merah */
  }
  .shift-leave .vc-date__btn::after {
    background-color: #f1c40f;
    /* Kuning */
  }

  /* ---------- WARNA MERAH HANYA UNTUK LIBUR DI BULAN AKTIF ---------- */
  [data-vc-date-month="current"].shift-holiday .vc-date__btn {
    color: #e74c3c !important;
    font-weight: 600;
  }

  /* Tanggal di luar bulan tidak ikut berwarna merah */
  [data-vc-date-month="prev"].shift-holiday .vc-date__btn,
  [data-vc-date-month="next"].shift-holiday .vc-date__btn {
    color: inherit !important;
    font-weight: normal !important;
  }

  /* Tanggal di luar bulan tampil redup (opsional) */
  [data-vc-date-month="prev"] .vc-date__btn,
  [data-vc-date-month="next"] .vc-date__btn {
    color: #64748b;
    font-weight: normal !important;
  }

  /* Text color helper */
  .text-color {
    color: var(--tg-theme-text-color) !important;
  }

  /* Pastikan kalender tidak overflow */
  #shift-calendar {
    max-width: 100%;
  }
</style>
@endpush