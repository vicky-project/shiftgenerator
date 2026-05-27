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
  --bg-start: #1a1a2e;
  --bg-end: #16213e;
  --accent-start: #a8e6cf;
  --accent-end: #dcedc1;
  --glass-bg: rgba(255, 255, 255, 0.03);
  --glass-border: rgba(255, 255, 255, 0.06);
  --text-primary: #e0e0e0;
  --text-secondary: #a0a0c0;
  --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.15);
}

  body, #app-shell {
    background: linear-gradient(135deg, var(--bg-start), var(--bg-end)) !important;
    color: var(--text-primary) !important;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
  }

  /* Header */
  #app-header {
    background: rgba(26, 26, 46, 0.6) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--glass-border);
  }

  /* Tab Bar */
  #app-tabbar {
    background: rgba(26, 26, 46, 0.7) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-top: 1px solid var(--glass-border);
  }
  #app-tabbar .nav-link {
    color: var(--text-secondary) !important;
    transition: all 0.3s ease;
    border-radius: 12px;
    padding: 0.5rem 1rem;
  }
  #app-tabbar .nav-link.active {
    color: #fff !important;
    font-weight: 600;
    background: linear-gradient(135deg, rgba(168, 230, 207, 0.2), rgba(220, 237, 193, 0.1)) !important;
    box-shadow: 0 2px 10px rgba(168, 230, 207, 0.1);
  }

  /* Kartu */
  .card {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    transition: all 0.25s ease;
  }
  .card:hover {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(168, 230, 207, 0.15);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
  }

  /* Tombol Umum */
  .btn {
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.25s ease;
  }
  .btn-primary {
    background: linear-gradient(135deg, rgba(168, 230, 207, 0.25), rgba(220, 237, 193, 0.15)) !important;
    border: 1px solid rgba(168, 230, 207, 0.3) !important;
    color: var(--text-primary) !important;
  }
  .btn-primary:hover {
    background: linear-gradient(135deg, rgba(168, 230, 207, 0.4), rgba(220, 237, 193, 0.25)) !important;
    box-shadow: 0 4px 15px rgba(168, 230, 207, 0.15);
  }
  .btn-success {
    background: linear-gradient(135deg, rgba(168, 230, 207, 0.3), rgba(119, 221, 119, 0.2)) !important;
    border: 1px solid rgba(168, 230, 207, 0.35) !important;
    color: var(--text-primary) !important;
  }
  .btn-success:hover {
    background: linear-gradient(135deg, rgba(168, 230, 207, 0.45), rgba(119, 221, 119, 0.3)) !important;
  }
  .btn-outline-info, .btn-outline-warning, .btn-outline-danger {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(4px);
  }
  .btn-outline-info {
    color: #7ec8e3;
    border-color: rgba(126, 200, 227, 0.5);
  }
  .btn-outline-info:hover {
    background: rgba(126, 200, 227, 0.15);
    color: #fff;
  }
  .btn-outline-warning {
    color: #f4d03f;
    border-color: rgba(244, 208, 63, 0.5);
  }
  .btn-outline-warning:hover {
    background: rgba(244, 208, 63, 0.15);
    color: #fff;
  }
  .btn-outline-danger {
    color: #f8a5a5;
    border-color: rgba(248, 165, 165, 0.5);
  }
  .btn-outline-danger:hover {
    background: rgba(248, 165, 165, 0.15);
    color: #fff;
  }

  /* Form */
  .form-control, .form-select {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #fff;
    border-radius: 12px;
  }
  .form-control:focus, .form-select:focus {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(168, 230, 207, 0.4);
    box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
  }

  /* Modal */
  .modal-content {
    background: linear-gradient(145deg, #1e1e2f, #2a2a40) !important;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(15px);
    border-radius: 16px;
    }

    /* Kalender (pembungkus) */
    #calendar-instance {
    background: var(--tg-theme-secondary-bg-color, rgba(26, 26, 46, 0.8));
    border-radius: 16px;
    padding: 0.5rem;
    }

    /* Legend dot */
    #calendar-legend .legend-dot.day {
    background: linear-gradient(135deg, #a8e6cf, #88d8b0);
    }
    #calendar-legend .legend-dot.night {
    background: linear-gradient(135deg, #6c7ce0, #4a5bc0);
    }
    #calendar-legend .legend-dot.off {
    background: linear-gradient(135deg, #f8a5a5, #e87a7a);
    }
    #calendar-legend .legend-dot.leave {
    background: linear-gradient(135deg, #f4d03f, #e6b800);
    }

    /* Alert */
    .alert {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    }

    /* Tombol info (ikon ?) */
    [data-info-title] {
    color: var(--text-secondary);
    transition: color 0.2s;
    }
    [data-info-title]:hover {
    color: #a8e6cf;
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