// page.js
(function() {
  const {
    fetchEmployees, fetchEmployee, saveEmployee, deleteEmployee,
    fetchOverrides, addOverride, deleteOverride,
    generateRoster, fetchSchedules, exportExcel
  } = window.AppCore;
  const showToast = window.TelegramApp?.showToast || tgApp.showToast || ((msg, type) => console.log(msg));
  const escapeHtml = window.TelegramApp?.escapeHtml || tgApp.escapeHtml || ((str) => str);

  let calendarInstance = null;

  // ---------- Render daftar karyawan ----------
  async function renderEmployeeList() {
    document.getElementById('app-title').innerText = 'Karyawan Saya';
    const content = document.getElementById('app-content');
    let html = `<div class="d-flex justify-content-end mb-3">
    <button data-nav="create-employee" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah</button>
    </div>`;
    try {
      const employees = await fetchEmployees();
      if (!employees.length) {
        html += '<div class="text-center text-muted py-4">Belum ada karyawan.</div>';
      } else {
        employees.forEach(emp => {
          html += `
          <div class="card mb-2 shadow-sm">
          <div class="card-body d-flex justify-content-between align-items-center">
          <div>
          <strong>${escapeHtml(emp.name)}</strong>
          <div class="text-muted small">NRP: ${escapeHtml(emp.nrp)}</div>
          <div class="text-muted small">Pola: ${escapeHtml(emp.shift_pattern)} | Siklus: ${emp.work_days}H/${emp.leave_days}H</div>
          </div>
          <div class="btn-group btn-group-sm">
          <button data-nav="overrides:${emp.id}" class="btn btn-outline-info"><i class="bi bi-pencil-square"></i> Cuti</button>
          <button data-nav="edit-employee:${emp.id}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-outline-danger" data-delete-employee="${emp.id}"><i class="bi bi-trash"></i></button>
          </div>
          </div>
          </div>`;
        });
      }
    } catch (err) {
      html += `<div class="alert alert-danger">Gagal memuat: ${err.message}</div>`;
    }
    content.innerHTML = html;
  }

  // ---------- Render form tambah/edit ----------
  async function renderEmployeeForm(params) {
    const id = params?.id;
    const isEdit = !!id;
    document.getElementById('app-title').innerText = isEdit ? 'Edit Karyawan': 'Tambah Karyawan';
    let employee = null;
    if (isEdit) {
      try {
        employee = await fetchEmployee(id);
      } catch (err) {
        document.getElementById('app-content').innerHTML = `<div class="alert alert-danger">Gagal memuat data.</div>`;
        return;
      }
    }

    const content = document.getElementById('app-content');
    content.innerHTML = `
    <form id="employee-form">
    <div class="mb-3">
    <label class="form-label">Nama</label>
    <input type="text" name="name" class="form-control" value="${employee ? escapeHtml(employee.name): ''}" required>
    </div>
    <div class="mb-3">
    <label class="form-label">NRP</label>
    <input type="text" name="nrp" class="form-control" value="${employee ? escapeHtml(employee.nrp): ''}" required>
    </div>
    <div class="mb-3">
    <label class="form-label">
    Pola Shift
    <button type="button" class="btn btn-sm btn-link p-0 ms-1"
    data-info-title="Pola Shift"
    data-info-content="<p>String yang mewakili shift harian dalam satu siklus.</p><ul><li><strong>D</strong> = Day (siang)</li><li><strong>N</strong> = Night (malam)</li><li><strong>O</strong> atau <strong>-</strong> = Off (libur)</li></ul><p>Contoh: <code>DDDDDDDDNNNNNO</code> berarti 8 Day, 5 Night, 1 Off.</p>">
    <i class="bi bi-info-circle"></i>
    </button>
    </label>
    <input type="text" name="shift_pattern" class="form-control" value="${employee ? escapeHtml(employee.shift_pattern): 'DDDDDDDDNNNNNO'}" required>
    </div>
    <div class="row mb-3">
    <div class="col">
    <label class="form-label">
    Shift Start Date
    <button type="button" class="btn btn-sm btn-link p-0 ms-1"
    data-info-title="Shift Start Date"
    data-info-content="<p>Tanggal acuan dimulainya pola shift. Pada tanggal ini, karyawan dianggap mulai bekerja dengan shift yang dipilih di sampingnya (<strong>Shift Start</strong>).</p>">
    <i class="bi bi-info-circle"></i>
    </button>
    </label>
    <input type="date" name="shift_start_date" class="form-control" value="${employee?.shift_start_date ? String(employee.shift_start_date).substring(0, 10): ''}" required>
    </div>
    <div class="col">
    <label class="form-label">
    Shift Start
    <button type="button" class="btn btn-sm btn-link p-0 ms-1"
    data-info-title="Shift Start"
    data-info-content="<p>Shift pada <strong>Shift Start Date</strong>. Pilih <strong>Day</strong> atau <strong>Night</strong>. Posisi ini akan menjadi awal perhitungan siklus pola.</p>">
    <i class="bi bi-info-circle"></i>
    </button>
    </label>
    <select name="shift_start" class="form-select">
    <option value="Day" ${employee && employee.shift_start === 'Day' ? 'selected': ''}>Day</option>
    <option value="Night" ${employee && employee.shift_start === 'Night' ? 'selected': ''}>Night</option>
    </select>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col">
    <label class="form-label">
    Work Days
    <button type="button" class="btn btn-sm btn-link p-0 ms-1"
    data-info-title="Work Days"
    data-info-content="<p>Jumlah hari kerja berturut-turut dalam satu siklus kerja-cuti (hitungan kalender, termasuk offday). Setelah hari kerja habis, masuk ke masa cuti (<strong>Leave Days</strong>).</p>">
    <i class="bi bi-info-circle"></i>
    </button>
    </label>
    <input type="number" name="work_days" class="form-control" value="${employee ? employee.work_days: 70}">
    </div>
    <div class="col">
    <label class="form-label">
    Leave Days
    <button type="button" class="btn btn-sm btn-link p-0 ms-1"
    data-info-title="Leave Days"
    data-info-content="<p>Jumlah hari cuti setelah masa kerja (<strong>Work Days</strong>). Cuti ini otomatis berulang setiap siklus.</p>">
    <i class="bi bi-info-circle"></i>
    </button>
    </label>
    <input type="number" name="leave_days" class="form-control" value="${employee ? employee.leave_days: 14}">
    </div>
    </div>
    <div class="mb-3">
    <label class="form-label">
    Pattern Start Date
    <button type="button" class="btn btn-sm btn-link p-0 ms-1"
    data-info-title="Pattern Start Date"
    data-info-content="<p>Tanggal dimulainya siklus kerja-cuti pertama. Sistem akan menghitung ${employee ? employee.work_days: 70} hari kerja (atau sesuai <strong>Work Days</strong>) mulai tanggal ini, lalu otomatis cuti selama ${employee ? employee.leave_days: 14} hari.</p>">
    <i class="bi bi-info-circle"></i>
    </button>
    </label>
    <input type="date" name="pattern_start_date" class="form-control" value="${employee?.pattern_start_date ? String(employee.pattern_start_date).substring(0, 10): ''}" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Simpan</button>
    </form>`;
  }

  // ---------- Render halaman override ----------
  async function renderOverrides(params) {
    const employeeId = params.id;
    document.getElementById('app-title').innerText = 'Pengajuan Cuti';
    let employee;
    try {
      employee = await fetchEmployee(employeeId);
    } catch (err) {
      document.getElementById('app-content').innerHTML = `<div class="alert alert-danger">Gagal memuat data.</div>`;
      return;
    }

    const content = document.getElementById('app-content');
    content.innerHTML = `
    <div class="mb-3">
    <label class="form-label">Tambah Pengajuan Cuti (mulai)</label>
    <form id="override-form" class="row g-2">
    <div class="col-8"><input type="date" name="start_date" class="form-control" required></div>
    <div class="col-4"><button type="submit" class="btn btn-primary w-100">Tambah</button></div>
    </form>
    <small class="text-muted">Durasi cuti: ${employee.leave_days} hari. Toleransi ±14 hari dari cuti normal.</small>
    </div>
    <div id="override-list"><div class="text-center text-muted py-3">Memuat...</div></div>`;

    async function loadOverrides() {
      try {
        const overrides = await fetchOverrides(employeeId);
        const container = document.getElementById('override-list');
        if (!overrides.length) {
          container.innerHTML = '<div class="text-muted text-center py-3">Belum ada pengajuan.</div>';
          return;
        }
        let html = '';
        overrides.forEach(ov => {
          const end = new Date(ov.start_date);
          end.setDate(end.getDate() + employee.leave_days - 1);
          html += `<div class="card mb-2"><div class="card-body d-flex justify-content-between align-items-center">
          <div>${ov.start_date} – ${end.toISOString().slice(0, 10)}</div>
          <button class="btn btn-sm btn-outline-danger" data-delete-override="${ov.id}"><i class="bi bi-trash"></i></button>
          </div></div>`;
        });
        container.innerHTML = html;
      } catch (err) {
        document.getElementById('override-list').innerHTML = `<div class="alert alert-danger">Gagal memuat.</div>`;
      }
    }

    await loadOverrides();
  }

  // ---------- Render halaman generate ----------
  function renderGenerate() {
    document.getElementById('app-title').innerText = 'Generate Roster';
    const content = document.getElementById('app-content');
    content.innerHTML = `
    <div class="card mb-3">
    <div class="card-body">
    <div class="row g-2">
    <div class="col-6"><label class="form-label">Mulai</label><input type="date" id="start_date" class="form-control"></div>
    <div class="col-6"><label class="form-label">Selesai</label><input type="date" id="end_date" class="form-control"></div>
    </div>
    <div class="mt-2"><label class="form-label">Hari Libur Nasional (YYYY-MM-DD, koma)</label>
    <input type="text" id="holidays" class="form-control" placeholder="2026-08-17, 2026-12-25"></div>
    <button class="btn btn-success mt-3 w-100" id="btn-generate"><i class="bi bi-gear"></i> Generate</button>
    </div>
    </div>
    <div id="result-container" class="d-none">
    <div id="calendar-legend">
    <div class="legend-item"><span class="legend-dot day"></span> Day</div>
    <div class="legend-item"><span class="legend-dot night"></span> Night</div>
    <div class="legend-item"><span class="legend-dot off"></span> Off</div>
    <div class="legend-item"><span class="legend-dot holiday"></span> Libur Nasional</div>
    </div>
    <div id="shift-calendar-wrapper">
    <div id="shift-calendar" style="margin-bottom: 1rem;"></div>
    </div>
    <div class="d-flex justify-content-end">
    <button class="btn btn-sm btn-outline-primary" id="btn-export"><i class="bi bi-download"></i> Export Excel</button>
    </div>
    </div>`;
  }

  // ---------- Render kalender dengan data shift ----------
  async function renderShiftCalendar(start, end) {
    const container = document.getElementById('shift-calendar');
    if (!container) return;

    let schedules = [];
    try {
      schedules = await fetchSchedules(start, end);
    } catch (err) {
      container.innerHTML = `<div class="alert alert-danger">Gagal memuat data roster.</div>`;
      return;
    }

    if (!schedules.length) {
      container.innerHTML = `<div class="alert alert-warning">Belum ada data roster. Silakan generate terlebih dahulu.</div>`;
      return;
    }

    const byEmployee = {};
    schedules.forEach(s => {
      const empKey = `${s.employee.nrp} - ${s.employee.name}`;
      if (!byEmployee[empKey]) {
        byEmployee[empKey] = {
          schedules: [],
          employee: s.employee
        };
      }
      byEmployee[empKey].schedules.push(s);
    });

    const employeeKeys = Object.keys(byEmployee);
    if (employeeKeys.length === 0) return;

    window.__shiftData = {
      byEmployee,
      employeeKeys,
      start,
      end
    };
    renderCalendarForEmployee(0);
  }

  function renderCalendarForEmployee(index) {
    const container = document.getElementById('shift-calendar');
    const data = window.__shiftData;
    if (!data || !data.employeeKeys.length) return;

    const empKey = data.employeeKeys[index];
    const empData = data.byEmployee[empKey];
    const schedules = empData.schedules;

    // Hancurkan instance lama
    if (calendarInstance && typeof calendarInstance.destroy === 'function') {
      calendarInstance.destroy();
      calendarInstance = null;
    }

    // Bangun popups (seperti di Notes)
    const popups = {};
    schedules.forEach(s => {
      popups[s.date] = {
        modifier: s.shift === 'Day' ? 'shift-day':
        s.shift === 'Night' ? 'shift-night': 'shift-off',
        html: `<div><strong>${s.shift}</strong> | ${escapeHtml(empData.employee.name)}</div>`
      };
    });

    // Render dropdown
    let dropdownHtml = '';
    if (data.employeeKeys.length > 1) {
      dropdownHtml = `
      <div class="mb-3">
      <select id="employee-select" class="form-select">
      ${data.employeeKeys.map((k, i) => `<option value="${i}" ${i === index ? 'selected': ''}>${escapeHtml(k)}</option>`).join('')}
      </select>
      </div>`;
    }

    container.innerHTML = dropdownHtml + '<div id="calendar-instance"></div>';

    const selectEl = document.getElementById('employee-select');
    if (selectEl) {
      selectEl.addEventListener('change', (e) => {
        renderCalendarForEmployee(parseInt(e.target.value));
      });
    }

    // Inisialisasi Vanilla Calendar Pro
    const {
      Calendar
    } = window.VanillaCalendarPro;
    calendarInstance = new Calendar('#calendar-instance', {
      type: 'default',
      firstDayOfWeek: 1,
      settings: {
        visibility: {
          daysOutsideMonth: true,
        },
        selection: {
          day: 'none',
        },
      },
      classes: {
        calendar: 'bg-transparent',
        calendarHeader: 'bg-transparent',
        calendarHeaderMonth: 'text-color',
        calendarHeaderYear: 'text-color',
        dayBtn: 'text-color',
      },
      popups: popups, // <-- ini yang akan menambahkan class
    });

    calendarInstance.init();
  }

  window.PageRender = {
    renderEmployeeList,
    renderEmployeeForm,
    renderOverrides,
    renderGenerate,
    renderShiftCalendar,
    loadRosterData: async (start,
      end) => {
      await renderShiftCalendar(start,
        end);
    }
  };
})();