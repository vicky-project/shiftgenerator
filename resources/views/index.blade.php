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
  <div id="app-content" class="flex-fill" style="padding-bottom: 70px !important; margin-bottom: 70px;">
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
<script>
  window.API_BASE = '{{ rtrim(config("app.url"), "/") }}';
</script>
<script src="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/index.js"></script>
<script src="{{ secure_url(rtrim(config("app.url"), "/") .'/apps/shift/js/core.js') }}"></script>
<script src="{{ secure_url(rtrim(config("app.url"), "/") .'/apps/shift/js/page.js') }}"></script>
<script src="{{ secure_url(rtrim(config("app.url"), "/") .'/apps/shift/js/main.js') }}"></script>
@endpush

@push('styles')
<!-- Vanilla Calendar Pro CSS -->
<link href="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/styles/index.css" rel="stylesheet">
<style>
:root {
  --bg-deep: #1a231a;
  --bg-surface: #243024;
  --accent-green: #b8d0a8;
  --accent-gold: #c9b079;
  --accent-cream: #e6d5a8;
  --text-main: #f0f2e8;
  --text-muted: #b0b8a0;
  --border-subtle: rgba(180, 200, 160, 0.15);
  --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.4);
}

  body, #app-shell {
    background: linear-gradient(155deg, #1a231a 0%, #243024 50%, #1a231a 100%) !important;
    color: var(--text-main) !important;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    }

    /* Header */
    #app-header {
    background: rgba(26, 35, 26, 0.8) !important;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border-subtle);
    }

    /* Tab Bar */
    #app-tabbar {
    background: rgba(26, 35, 26, 0.85) !important;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-top: 1px solid var(--border-subtle);
    }
    #app-tabbar .nav-link {
    color: var(--text-muted) !important;
    transition: all 0.3s ease;
    border-radius: 14px;
    padding: 0.4rem 1.2rem;
    margin: 0 0.2rem;
    }
    #app-tabbar .nav-link.active {
    color: #FFFFFF !important;
    font-weight: 600;
    background: linear-gradient(135deg, rgba(184, 208, 168, 0.3), rgba(201, 176, 121, 0.2)) !important;
    box-shadow: 0 2px 15px rgba(184, 208, 168, 0.2);
    }

    /* Kartu */
    .card {
    background: rgba(255, 255, 255, 0.04);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--border-subtle);
    border-radius: 18px;
    transition: all 0.3s ease;
    }
    .card:hover {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(184, 208, 168, 0.4);
    box-shadow: var(--shadow-card);
    transform: translateY(-2px);
    }

    /* Tombol Umum */
    .btn {
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    letter-spacing: 0.3px;
    }
    .btn-primary {
    background: linear-gradient(135deg, rgba(184, 208, 168, 0.4), rgba(201, 176, 121, 0.3)) !important;
    border: 1px solid rgba(184, 208, 168, 0.5) !important;
    color: #1a231a !important;
    }
    .btn-primary:hover {
    background: linear-gradient(135deg, rgba(184, 208, 168, 0.7), rgba(201, 176, 121, 0.6)) !important;
    box-shadow: 0 4px 20px rgba(184, 208, 168, 0.3);
    }
    .btn-success {
    background: linear-gradient(135deg, rgba(184, 208, 168, 0.5), rgba(230, 213, 168, 0.4)) !important;
    border: 1px solid rgba(230, 213, 168, 0.5) !important;
    color: #1a231a !important;
    }
    .btn-success:hover {
    background: linear-gradient(135deg, rgba(184, 208, 168, 0.8), rgba(230, 213, 168, 0.7)) !important;
    box-shadow: 0 4px 20px rgba(230, 213, 168, 0.3);
    }
    .btn-outline-info {
    color: #b8d0a8;
    border-color: rgba(184, 208, 168, 0.5);
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(4px);
    }
    .btn-outline-info:hover {
    background: rgba(184, 208, 168, 0.2);
    color: #FFFFFF;
    border-color: rgba(184, 208, 168, 0.8);
    }
    .btn-outline-warning {
    color: #e6d5a8;
    border-color: rgba(230, 213, 168, 0.5);
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(4px);
    }
    .btn-outline-warning:hover {
    background: rgba(230, 213, 168, 0.2);
    color: #FFFFFF;
    border-color: rgba(230, 213, 168, 0.8);
    }
    .btn-outline-danger {
    color: #d4a5a5;
    border-color: rgba(212, 165, 165, 0.5);
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(4px);
    }
    .btn-outline-danger:hover {
    background: rgba(212, 165, 165, 0.2);
    color: #FFFFFF;
    border-color: rgba(212, 165, 165, 0.8);
    }

    /* Form */
    .form-control, .form-select {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(184, 208, 168, 0.15);
    color: #FFFFFF;
    border-radius: 14px;
    padding: 0.7rem 1rem;
    }
    .form-control:focus, .form-select:focus {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(184, 208, 168, 0.6);
    box-shadow: 0 0 0 3px rgba(184, 208, 168, 0.15);
    }

    /* Modal */
    .modal-content {
    background: linear-gradient(145deg, #243024, #1a231a) !important;
    border: 1px solid var(--border-subtle);
    backdrop-filter: blur(24px);
    border-radius: 20px;
    }

    /* Kalender (pembungkus) */
    #calendar-instance {
    background: rgba(36, 48, 36, 0.7);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 0.5rem;
    }

    /* Legend dot */
    #calendar-legend .legend-dot.day {
    background: linear-gradient(135deg, #8fbc8f, #7ca87c);
    }
    #calendar-legend .legend-dot.night {
    background: linear-gradient(135deg, #7b9eb3, #5d7f96);
    }
    #calendar-legend .legend-dot.off {
    background: linear-gradient(135deg, #d4a5a5, #b87c7c);
    }
    #calendar-legend .legend-dot.leave {
    background: linear-gradient(135deg, #e6c97e, #d4a843);
    }

    /* Alert */
    .alert {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-subtle);
    backdrop-filter: blur(8px);
    border-radius: 14px;
    color: var(--text-main);
    }

    /* Tombol info (ikon ?) */
    [data-info-title] {
    color: var(--text-muted);
    transition: color 0.2s;
    }
    [data-info-title]:hover {
    color: #b8d0a8;
    }

    /* ========== KALENDER ========== */
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
    color: var(--text-muted);
    }
    #calendar-legend .legend-dot {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    display: inline-block;
    }

    /* ---------- DOT WARNA SHIFT ---------- */
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
    .shift-day .vc-date__btn::after {
    background-color: #8fbc8f;
    }
    .shift-night .vc-date__btn::after {
    background-color: #7b9eb3;
    }
    .shift-off .vc-date__btn::after {
    background-color: #d4a5a5;
    }
    .shift-leave .vc-date__btn::after {
    background-color: #e6c97e;
    }

    /* ---------- WARNA MERAH HANYA UNTUK LIBUR DI BULAN AKTIF ---------- */
    [data-vc-date-month="current"].shift-holiday .vc-date__btn {
    color: #d4a5a5 !important;
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

    /* Tanggal di luar bulan tampil redup */
    [data-vc-date-month="prev"] .vc-date__btn,
    [data-vc-date-month="next"] .vc-date__btn {
    opacity: 0.4;
    }
    [data-vc-date-month="prev"] .vc-date__btn::after,
    [data-vc-date-month="next"] .vc-date__btn::after {
    opacity: 0.4;
    }

    /* Text color helper */
    .text-color {
    color: var(--tg-theme-text-color) !important;
    }
    </style>
    @endpush