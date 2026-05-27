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
  --gradient-start: #667eea;
  --gradient-end: #764ba2;
  --glass-bg: rgba(255, 255, 255, 0.05);
  --glass-border: rgba(255, 255, 255, 0.08);
  --shadow-soft: 0 8px 32px rgba(0, 0, 0, 0.2);
}

  body, #app-shell {
    background: linear-gradient(135deg, #0f0c29, #302b63, #24243e) !important;
    color: var(--tg-theme-text-color, #e0e0e0) !important;
    }

    /* Header */
    #app-header {
    background: rgba(15, 12, 41, 0.7) !important;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--glass-border);
    }

    /* Tab Bar Fixed */
    #app-tabbar {
    background: rgba(15, 12, 41, 0.8) !important;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-top: 1px solid var(--glass-border);
    }
    #app-tabbar .nav-link {
    color: var(--tg-theme-hint-color, #a0a0c0) !important;
    transition: all 0.3s ease;
    border-radius: 12px;
    }
    #app-tabbar .nav-link.active {
    background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end)) !important;
    color: #fff !important;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    /* Kartu */
    .card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    transition: all 0.3s ease;
    }
    .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
    border-color: rgba(255, 255, 255, 0.15);
    }

    /* Tombol Umum */
    .btn {
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    }
    .btn-primary {
    background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end)) !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.35);
    }
    .btn-primary:hover {
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.55);
    transform: translateY(-1px);
    }
    .btn-success {
    background: linear-gradient(135deg, #11998e, #38ef7d) !important;
    border: none !important;
    box-shadow: 0 4px 15px rgba(56, 239, 125, 0.3);
    }
    .btn-outline-info, .btn-outline-warning, .btn-outline-danger {
    backdrop-filter: blur(4px);
    background: rgba(255,255,255,0.03);
    }
    .btn-outline-info {
    color: #3498db;
    border-color: #3498db;
    }
    .btn-outline-info:hover {
    background: #3498db;
    color: #fff;
    }
    .btn-outline-warning {
    color: #f39c12;
    border-color: #f39c12;
    }
    .btn-outline-warning:hover {
    background: #f39c12;
    color: #fff;
    }
    .btn-outline-danger {
    color: #e74c3c;
    border-color: #e74c3c;
    }
    .btn-outline-danger:hover {
    background: #e74c3c;
    color: #fff;
    }

    /* Form */
    .form-control, .form-select {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    border-radius: 12px;
    backdrop-filter: blur(4px);
    }
    .form-control:focus, .form-select:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--gradient-start);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
    }

    /* Modal */
    .modal-content {
    background: linear-gradient(145deg, #1e1e2f, #2a2a40) !important;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(15px);
    }

    /* Kalender (wrapper saja) */
    #calendar-instance {
    background: var(--tg-theme-secondary-bg-color, #1e1e2f);
    border-radius: 16px;
    padding: 0.5rem;
    }

    /* Legend dot dengan gradien */
    #calendar-legend .legend-dot.day {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    }
    #calendar-legend .legend-dot.night {
    background: linear-gradient(135deg, #1e3799, #0c2461);
    }
    #calendar-legend .legend-dot.off {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    }
    #calendar-legend .legend-dot.leave {
    background: linear-gradient(135deg, #f1c40f, #f39c12);
    }

    /* Alert */
    .alert {
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    }
    </style>
    <style>
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

    /* Tanggal di luar bulan tampil redup, termasuk yang libur */
    [data-vc-date-month="prev"] .vc-date__btn,
    [data-vc-date-month="next"] .vc-date__btn {
    opacity: 0.5;
    }

    /* Pastikan dot shift juga ikut redup */
    [data-vc-date-month="prev"] .vc-date__btn::after,
    [data-vc-date-month="next"] .vc-date__btn::after {
    opacity: 0.5;
    }

    /* Text color helper */
    .text-color {
    color: var(--tg-theme-text-color) !important;
    }
    </style>
    @endpush