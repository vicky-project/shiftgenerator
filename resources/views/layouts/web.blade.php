<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Shift Generator')</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/styles/index.css" rel="stylesheet">
  <style>
:root {
    --bg-deep: #0B1A2A;
    --bg-surface: #13273B;
    --accent-electric: #38BDF8;
    --accent-mint: #34D399;
    --text-main: #F0F9FF;
    --text-muted: #94A3B8;
    --border-subtle: rgba(56, 189, 248, 0.12);
  }
    body {
      background: linear-gradient(160deg, #0B1A2A 0%, #13273B 45%, #0B1A2A 100%);
      color: var(--text-main);
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      min-height: 100vh;
      margin: 0;
      }
      .app-container {
      display: flex;
      min-height: 100vh;
      }
      .sidebar {
      width: 260px;
      background: rgba(11, 26, 42, 0.95);
      backdrop-filter: blur(20px);
      border-right: 1px solid var(--border-subtle);
      padding: 2rem 0;
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      z-index: 1000;
      overflow-y: auto;
      }
      .sidebar-logo {
      padding: 0 1.5rem 2rem;
      border-bottom: 1px solid var(--border-subtle);
      margin-bottom: 1.5rem;
      }
      .sidebar-logo h4 {
      color: #fff;
      font-weight: 600;
      margin: 0;
      }
      .sidebar .nav-link {
      color: var(--text-muted);
      padding: 0.8rem 1.5rem;
      border-radius: 12px;
      margin: 0.3rem 1rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      font-weight: 500;
      }
      .sidebar .nav-link:hover {
      background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(52, 211, 153, 0.1));
      color: #fff;
      }
      .sidebar .nav-link.active {
      background: linear-gradient(135deg, rgba(56, 189, 248, 0.35), rgba(52, 211, 153, 0.25));
      color: #fff;
      box-shadow: 0 2px 18px rgba(56, 189, 248, 0.25);
      }
      .sidebar .nav-link i {
      font-size: 1.2rem;
      }
      .main-content {
      margin-left: 260px;
      flex: 1;
      padding: 2rem;
      }
      @media (max-width: 768px) {
      .sidebar {
      transform: translateX(-100%);
      width: 80%;
      z-index: 1050;
      }
      .sidebar.open {
      transform: translateX(0);
      }
      .main-content {
      margin-left: 0;
      padding: 1rem;
      }
      }
      /* Tombol close sidebar (mobile) */
      .sidebar-close-btn {
      display: none;
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-size: 1.5rem;
      cursor: pointer;
      z-index: 2;
      }
      @media (max-width: 768px) {
      .sidebar-close-btn {
      display: block;
      }
      }
      .card {
      background: rgba(255, 255, 255, 0.04);
      backdrop-filter: blur(12px);
      border: 1px solid var(--border-subtle);
      border-radius: 18px;
      transition: all 0.3s ease;
      color: var(--text-main);
      }
      .card:hover {
      background: rgba(255, 255, 255, 0.07);
      border-color: rgba(56, 189, 248, 0.4);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
      transform: translateY(-2px);
      }
      .btn {
      border-radius: 12px;
      font-weight: 500;
      transition: all 0.3s ease;
      }
      .btn-primary {
      background: linear-gradient(135deg, rgba(56, 189, 248, 0.45), rgba(52, 211, 153, 0.35));
      border: 1px solid rgba(56, 189, 248, 0.6);
      color: #fff;
      }
      .btn-primary:hover {
      background: linear-gradient(135deg, rgba(56, 189, 248, 0.7), rgba(52, 211, 153, 0.6));
      box-shadow: 0 4px 22px rgba(56, 189, 248, 0.35);
      }
      .btn-success {
      background: linear-gradient(135deg, rgba(52, 211, 153, 0.5), rgba(56, 189, 248, 0.4));
      border: 1px solid rgba(52, 211, 153, 0.6);
      color: #fff;
      }
      .btn-success:hover {
      background: linear-gradient(135deg, rgba(52, 211, 153, 0.8), rgba(56, 189, 248, 0.7));
      box-shadow: 0 4px 22px rgba(52, 211, 153, 0.35);
      }
      .btn-outline-info {
      color: var(--accent-electric);
      border-color: rgba(56, 189, 248, 0.5);
      }
      .btn-outline-info:hover {
      background: rgba(56, 189, 248, 0.2);
      color: #fff;
      }
      .btn-outline-warning {
      color: #FBBF24;
      border-color: rgba(251, 191, 36, 0.5);
      }
      .btn-outline-warning:hover {
      background: rgba(251, 191, 36, 0.2);
      color: #fff;
      }
      .btn-outline-danger {
      color: #F87171;
      border-color: rgba(248, 113, 113, 0.5);
      }
      .btn-outline-danger:hover {
      background: rgba(248, 113, 113, 0.2);
      color: #fff;
      }
      .form-control, .form-select {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(56, 189, 248, 0.15);
      color: #fff;
      border-radius: 14px;
      padding: 0.7rem 1rem;
      }
      .form-control:focus, .form-select:focus {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(56, 189, 248, 0.6);
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
      color: #fff;
      }
      .form-label {
      color: var(--text-muted);
      font-weight: 500;
      }
      .table {
      color: var(--text-main);
      }
      .alert {
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border-subtle);
      backdrop-filter: blur(8px);
      border-radius: 14px;
      }
      #calendar-instance {
      background: rgba(19, 39, 59, 0.7);
      backdrop-filter: blur(8px);
      border-radius: 16px;
      padding: 0.5rem;
      }
      .legend-dot {
      width: 14px;
      height: 14px;
      border-radius: 4px;
      display: inline-block;
      }

      /* Perbaikan bug */
      .text-muted {
      color: var(--text-muted) !important;
      }
      a, .btn-link {
      color: var(--accent-electric);
      }
      .btn-close {
      filter: invert(1) grayscale(100%) brightness(200%);
      }
      </style>
      @stack('styles')
      </head>
      <body>
      <div class="app-container">
      <!-- Sidebar -->
      <aside class="sidebar">
      <!-- Tombol close (hanya mobile) -->
      <button class="sidebar-close-btn" onclick="document.querySelector('.sidebar').classList.remove('open')">
      <i class="bi bi-x-lg"></i>
      </button>

      <div class="sidebar-logo">
      <h4><i class="bi bi-calendar-check me-2" style="color: var(--accent-mint);"></i>Shift Generator</h4>
      </div>
      <nav class="nav flex-column">
      <a href="{{ route('shift.web') }}" class="nav-link {{ request()->routeIs('shift.web') && !request()->routeIs('shift.employees.*', 'shift.generate.*') ? 'active' : '' }}">
      <i class="bi bi-house-door"></i> Dashboard
      </a>
      <a href="{{ route('shift.employees.web') }}" class="nav-link {{ request()->routeIs('shift.employees.*') ? 'active' : '' }}">
      <i class="bi bi-people"></i> Karyawan
      </a>
      <a href="{{ route('shift.generate.web') }}" class="nav-link {{ request()->routeIs('shift.generate.*') ? 'active' : '' }}">
      <i class="bi bi-calendar-check"></i> Generate Roster
      </a>
      @if(config('shiftgenerator.back_home_route'))
      <a href="{{ route(config('shiftgenerator.back_home_route')) }}" class="nav-link mt-auto mx-3">
      @endif
      <i class="bi bi-arrow-left"></i> Beranda
      </a>
      </nav>
      </aside>

      <!-- Main Content -->
      <main class="main-content">
      {{-- Tombol menu mobile --}}
      <div class="d-md-none mb-3">
      <button class="btn btn-sm btn-outline-info" onclick="document.querySelector('.sidebar').classList.toggle('open')">
      <i class="bi bi-list"></i> Menu
      </button>
      </div>

      @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
      </div>
      @endif
      @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
      </div>
      @endif

      @yield('content')
      </main>
      </div>
      <script src="//cdn.jsdelivr.net/npm/eruda"></script>
      <script>eruda.init();</script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/index.js"></script>
      <script>
      // Menutup sidebar saat klik di luar area sidebar
      (function() {
      const sidebar = document.querySelector('.sidebar');
      document.addEventListener('click', function(e) {
      if (sidebar.classList.contains('open') &&
      !sidebar.contains(e.target) &&
      !e.target.closest('.btn-outline-info') &&
      !e.target.closest('.sidebar-close-btn')) {
      sidebar.classList.remove('open');
      }
      });
      })();
      </script>
      @stack('scripts')
      </body>
      </html>