@extends('shiftgenerator::layouts.web')
@section('title', 'Generate Roster')
@section('content')
<h4 class="mb-4"><i class="bi bi-calendar-check me-2"></i>Generate Roster</h4>

<div class="card p-4 mb-4">
  <form id="generate-form">
    @csrf
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Tanggal Mulai</label>
        <input type="date" id="start_date" name="start_date" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tanggal Selesai</label>
        <input type="date" id="end_date" name="end_date" class="form-control" required>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-success w-100">
          <i class="bi bi-gear me-1"></i> Generate
        </button>
      </div>
    </div>
  </form>
</div>

<div id="result-container" style="display: none;">
  <div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Hasil Roster</h5>
      <button id="export-btn" class="btn btn-outline-info btn-sm">
        <i class="bi bi-download me-1"></i> Export Excel
      </button>
    </div>

    <!-- Legenda -->
    <div class="d-flex gap-3 mb-3 flex-wrap">
      <span class="d-flex align-items-center gap-1"><span class="legend-dot" style="background:#34D399;"></span> Day</span>
      <span class="d-flex align-items-center gap-1"><span class="legend-dot" style="background:#38BDF8;"></span> Night</span>
      <span class="d-flex align-items-center gap-1"><span class="legend-dot" style="background:#F87171;"></span> Off</span>
      <span class="d-flex align-items-center gap-1"><span class="legend-dot" style="background:#FBBF24;"></span> Cuti</span>
    </div>

    <!-- Kalender -->
    <div id="calendar-instance"></div>
  </div>
</div>

<!-- Loading -->
<div id="loading" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
  <div class="spinner-border text-info" role="status"></div>
  <span class="text-white ms-2">Memproses...</span>
</div>
@endsection

@push('scripts')
<script>
  const API_BASE = '{{ rtrim(config("app.url"), "/") }}';
  let calendar = null;
  let currentObserver = null;
  let currentEmpKey = null;

  // Form submit dengan fetch
  document.getElementById('generate-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const startDate = document.getElementById('start_date').value;
  const endDate = document.getElementById('end_date').value;

  if (!startDate || !endDate) return;

  showLoading(true);
  try {
  // Generate roster
  const genRes = await fetch(API_BASE + '/shift/generate-api', {
  method: 'POST',
  headers: {
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
  'Accept': 'application/json'
  },
  body: JSON.stringify({ start_date: startDate, end_date: endDate })
  });

  if (!genRes.ok) {
  const errorData = await genRes.json().catch(() => ({ message: 'Unknown error' }));
  throw new Error(errorData.message || 'Generate gagal');
  }

  // Ambil data schedules dan holidays
  const schedRes = await fetch(`${API_BASE}/shift/schedules-api?start_date=${startDate}&end_date=${endDate}`, {
  headers: {
  'Accept': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  }
  });

  if (!schedRes.ok) {
  const errorData = await schedRes.json().catch(() => ({ message: 'Unknown error' }));
  throw new Error(errorData.message || 'Gagal mengambil data roster');
  }

  const data = await schedRes.json();
  const schedules = data.schedules;
  const holidays = data.holidays || [];

  document.getElementById('result-container').style.display = 'block';

  // Render kalender
  renderCalendar(startDate, endDate, schedules, holidays);

  } catch (err) {
  alert('Error: ' + err.message);
  console.error(err);
  } finally {
  showLoading(false);
  }
  });

  // Tombol export dengan validasi
  document.getElementById('export-btn').addEventListener('click', async function(e) {
  e.preventDefault();
  const startDate = document.getElementById('start_date').value;
  const endDate = document.getElementById('end_date').value;

  if (!startDate || !endDate) {
  alert('Silakan generate roster terlebih dahulu.');
  return;
  }

  try {
  // Validasi kelayakan export
  const validateRes = await fetch(`${API_BASE}/shift/validate-export?start_date=${startDate}&end_date=${endDate}`, {
  headers: {
  'Accept': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  }
  });

  if (!validateRes.ok) {
  const errorData = await validateRes.json().catch(() => ({ message: 'Gagal validasi' }));
  alert('Export gagal: ' + (errorData.message || 'Periksa kembali data Anda.'));
  return;
  }

  const validationResult = await validateRes.json();
  if (!validationResult.valid) {
  alert(validationResult.message || 'Export tidak dapat dilakukan.');
  return;
  }

  // Jika valid, langsung download
  window.open(`{{ route("shift.generate.export") }}?start_date=${startDate}&end_date=${endDate}`, '_blank');

  } catch (err) {
  alert('Terjadi kesalahan: ' + err.message);
  }
  });

  function showLoading(show) {
    const el = document.getElementById('loading');
    if (show) {
      el.style.display = 'flex';
    } else {
      el.style.display = 'none';
    }
  }

  function renderCalendar(start, end, schedules, holidays) {
    const container = document.getElementById('calendar-instance');
    container.innerHTML = '';

    // Kelompokkan per karyawan
    const byEmployee = {};
    schedules.forEach(s => {
    const key = s.employee.nrp + ' - ' + s.employee.name;
    if (!byEmployee[key]) {
    byEmployee[key] = { schedules: [], employee: s.employee };
    }
    byEmployee[key].schedules.push(s);
    });
    const employeeKeys = Object.keys(byEmployee);

    // Fungsi untuk membangun popups
    function buildPopups(empKey) {
      const empSchedules = byEmployee[empKey].schedules;
      const holidayDates = new Set(holidays.map(h => h.date));
      const popups = {};
      empSchedules.forEach(s => {
      const dateKey = s.date.substring(0, 10);
      let modifier = s.shift === 'Day' ? 'shift-day' :
      s.shift === 'Night' ? 'shift-night' :
      s.shift === 'Leave' ? 'shift-leave' : 'shift-off';
      if (holidayDates.has(dateKey)) modifier += ' shift-holiday';
      popups[dateKey] = { modifier, html: `<div><strong>${s.shift}</strong></div>` };
      });
      return popups;
    }

    // Dropdown jika banyak karyawan
    if (employeeKeys.length > 1) {
      const dropdownHtml = `
      <div class="mb-3">
      <select id="employee-select" class="form-select">
      ${employeeKeys.map((k, i) => `<option value="${i}">${escapeHtml(k)}</option>`).join('')}
      </select>
      </div>`;
      container.innerHTML = dropdownHtml + '<div id="calendar-instance-inner"></div>';
      document.getElementById('employee-select').addEventListener('change', function(e) {
      const idx = parseInt(e.target.value);
      if (currentEmpKey !== employeeKeys[idx]) {
      updateCalendar(employeeKeys[idx]);
      }
      });
    } else {
      container.innerHTML = '<div id="calendar-instance-inner"></div>';
    }

    // Fungsi update kalender (dipanggil pertama kali dan saat ganti karyawan)
    function updateCalendar(empKey) {
      const inner = document.getElementById('calendar-instance-inner');
      if (!inner) return;

      // Hancurkan observer lama
      if (currentObserver) {
        currentObserver.disconnect();
        currentObserver = null;
      }

      // Hancurkan instance kalender sebelumnya
      if (calendar) {
        calendar.destroy();
        calendar = null;
      }

      currentEmpKey = empKey;
      const popups = buildPopups(empKey);

      calendar = new VanillaCalendarPro.Calendar(inner, {
      type: 'default',
      firstDayOfWeek: 1,
      selectedWeekends: [0],
      settings: { visibility: { daysOutsideMonth: true }, selection: { day: 'none' } },
      classes: {
      calendar: 'bg-transparent',
      calendarHeader: 'bg-transparent',
      calendarHeaderMonth: 'text-color',
      calendarHeaderYear: 'text-color',
      dayBtn: 'text-color',
      },
      dateMin: start,
      dateMax: end,
      displayDateMin: start,
      displayDateMax: end,
      popups,
      });
      calendar.init();

      // Fungsi untuk menerapkan modifier class
      function applyModifiers() {
        const dateElements = inner.querySelectorAll('[data-vc-date]');
        dateElements.forEach(el => {
        // Hapus class shift sebelumnya
        el.classList.remove('shift-day', 'shift-night', 'shift-off', 'shift-leave', 'shift-holiday');
        const date = el.getAttribute('data-vc-date');
        if (popups[date] && popups[date].modifier) {
        const classes = popups[date].modifier.split(' ');
        el.classList.add(...classes);
        }
        });
      }

      // Terapkan segera
      applyModifiers();

      // Pasang observer untuk mempertahankan modifier saat navigasi bulan
      currentObserver = new MutationObserver(() => {
        applyModifiers();
      });
      currentObserver.observe(inner, { childList: true, subtree: true });
    }

    // Tampilkan karyawan pertama
    updateCalendar(employeeKeys[0]);
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
</script>
@endpush