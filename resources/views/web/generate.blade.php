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
      <a href="#" id="export-btn" class="btn btn-outline-info btn-sm" target="_blank">
        <i class="bi bi-download me-1"></i> Export Excel
      </a>
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
  let calendar = null;

  // Form submit dengan fetch
  document.getElementById('generate-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const startDate = document.getElementById('start_date').value;
  const endDate = document.getElementById('end_date').value;

  if (!startDate || !endDate) return;

  showLoading(true);
  try {
  // Generate roster
  const genRes = await fetch('{{ route("shift.generate.api") }}', {
  method: 'POST',
  headers: {
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content || '{{ csrf_token() }}'
  },
  body: JSON.stringify({ start_date: startDate, end_date: endDate })
  });
  if (!genRes.ok) throw new Error('Generate gagal');

  // Ambil data schedules
  const schedRes = await fetch(`{{ route("shift.generate.schedules-api") }}?start_date=${startDate}&end_date=${endDate}`,
  headers: {
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content || '{{ csrf_token() }}'
  }
  );
  if (!schedRes.ok) throw new Error('Gagal mengambil data roster');
  const data = await schedRes.json();
  const schedules = data.schedules;
  const holidays = data.holidays || [];

  // Tampilkan container hasil
  document.getElementById('result-container').style.display = 'block';
  // Atur link ekspor
  const exportBtn = document.getElementById('export-btn');
  exportBtn.href = `{{ route("shift.generate.export") }}?start_date=${startDate}&end_date=${endDate}`;

  // Render kalender
  renderCalendar(startDate, endDate, schedules, holidays);

  } catch (err) {
  alert('Gagal: ' + err.message);
  console.error(err);
  } finally {
  showLoading(false);
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

    // Bangun popups untuk karyawan pertama (atau bisa dropdown nanti)
    function buildPopups(empKey) {
      const empSchedules = byEmployee[empKey].schedules;
      const holidayDates = new Set(holidays.map(h => h.date));
      const popups = {};
      empSchedules.forEach(s => {
      const dateKey = s.date.substring(0,10);
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
      updateCalendar(employeeKeys[idx]);
      });
    } else {
      container.innerHTML = '<div id="calendar-instance-inner"></div>';
    }

    function updateCalendar(empKey) {
      const inner = document.getElementById('calendar-instance-inner');
      if (calendar) calendar.destroy();
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