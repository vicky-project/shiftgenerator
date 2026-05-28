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
  --bg-deep: #0B1A2A;
  --bg-surface: #13273B;
  --accent-electric: #38BDF8;
  --accent-mint: #34D399;
  --accent-soft: #A7F3D0;
  --text-main: #F0F9FF;
  --text-muted: #94A3B8;
  --border-subtle: rgba(56, 189, 248, 0.12);
  --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.5);
}

  body, #app-shell {
    background: linear-gradient(160deg, #0B1A2A 0%, #13273B 45%, #0B1A2A 100%) !important;
    color: var(--text-main) !important;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    }

    /* Header */
    #app-header {
    background: rgba(11, 26, 42, 0.85) !important;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border-subtle);
    }

    /* Tab Bar */
    #app-tabbar {
    background: rgba(11, 26, 42, 0.9) !important;
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
    background: linear-gradient(135deg, rgba(56, 189, 248, 0.3), rgba(52, 211, 153, 0.2)) !important;
    box-shadow: 0 2px 18px rgba(56, 189, 248, 0.25);
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
    border-color: rgba(56, 189, 248, 0.4);
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
    background: linear-gradient(135deg, rgba(56, 189, 248, 0.45), rgba(52, 211, 153, 0.35)) !important;
    border: 1px solid rgba(56, 189, 248, 0.6) !important;
    color: #FFFFFF !important;
    }
    .btn-primary:hover {
    background: linear-gradient(135deg, rgba(56, 189, 248, 0.7), rgba(52, 211, 153, 0.6)) !important;
    box-shadow: 0 4px 22px rgba(56, 189, 248, 0.35);
    }
    .btn-success {
    background: linear-gradient(135deg, rgba(52, 211, 153, 0.5), rgba(56, 189, 248, 0.4)) !important;
    border: 1px solid rgba(52, 211, 153, 0.6) !important;
    color: #FFFFFF !important;
    }
    .btn-success:hover {
    background: linear-gradient(135deg, rgba(52, 211, 153, 0.8), rgba(56, 189, 248, 0.7)) !important;
    box-shadow: 0 4px 22px rgba(52, 211, 153, 0.35);
    }
    .btn-outline-info {
    color: #38BDF8;
    border-color: rgba(56, 189, 248, 0.5);
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(4px);
    }
    .btn-outline-info:hover {
    background: rgba(56, 189, 248, 0.2);
    color: #FFFFFF;
    border-color: rgba(56, 189, 248, 0.8);
    }
    .btn-outline-warning {
    color: #34D399;
    border-color: rgba(52, 211, 153, 0.5);
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(4px);
    }
    .btn-outline-warning:hover {
    background: rgba(52, 211, 153, 0.2);
    color: #FFFFFF;
    border-color: rgba(52, 211, 153, 0.8);
    }
    .btn-outline-danger {
    color: #FCA5A5;
    border-color: rgba(252, 165, 165, 0.5);
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(4px);
    }
    .btn-outline-danger:hover {
    background: rgba(252, 165, 165, 0.2);
    color: #FFFFFF;
    border-color: rgba(252, 165, 165, 0.8);
    }

    /* Form */
    .form-control, .form-select {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(56, 189, 248, 0.15);
    color: #FFFFFF;
    border-radius: 14px;
    padding: 0.7rem 1rem;
    }
    .form-control:focus, .form-select:focus {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(56, 189, 248, 0.6);
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }

    /* Modal */
    .modal-content {
    background: linear-gradient(145deg, #13273B, #0B1A2A) !important;
    border: 1px solid var(--border-subtle);
    backdrop-filter: blur(24px);
    border-radius: 20px;
    }

    /* Kalender (pembungkus) */
    #calendar-instance {
    background: rgba(19, 39, 59, 0.7);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 0.5rem;
    }

    /* Legend dot */
    #calendar-legend .legend-dot.day {
    background: linear-gradient(135deg, #34D399, #10B981);
    }
    #calendar-legend .legend-dot.night {
    background: linear-gradient(135deg, #38BDF8, #0EA5E9);
    }
    #calendar-legend .legend-dot.off {
    background: linear-gradient(135deg, #F87171, #EF4444);
    }
    #calendar-legend .legend-dot.leave {
    background: linear-gradient(135deg, #FBBF24, #F59E0B);
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
    color: #38BDF8;
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
    background-color: #34D399;
    }
    .shift-night .vc-date__btn::after {
    background-color: #38BDF8;
    }
    .shift-off .vc-date__btn::after {
    background-color: #F87171;
    }
    .shift-leave .vc-date__btn::after {
    background-color: #FBBF24;
    }

    /* ---------- WARNA MERAH HANYA UNTUK LIBUR DI BULAN AKTIF ---------- */
    [data-vc-date-month="current"].shift-holiday .vc-date__btn {
    color: #F87171 !important;
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