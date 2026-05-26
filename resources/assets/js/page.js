// page.js
(function() {
  const {
    fetchEmployees, fetchEmployee, saveEmployee, deleteEmployee,
    fetchOverrides, addOverride, deleteOverride,
    generateRoster, fetchSchedules, exportExcel
  } = window.AppCore;
  const showToast = window.tgApp?.showToast || window.TelegramApp?.showToast || ((msg, type) => console.log(msg));
  const escapeHtml = window.tgApp?.escapeHtml || window.TelegramApp?.escapeHtml || ((str) => str);

  let calendarInstance = null;
  let currentObserver = null;
  let employeePagination = {
    currentPage: 1,
    lastPage: 1
  };

  // ---------- destroyCalendar ----------
  function destroyCalendar() {
    if (currentObserver) {
      currentObserver.disconnect();
      currentObserver = null;
    }
    if (calendarInstance && typeof calendarInstance.destroy === 'function') {
      calendarInstance.destroy();
      calendarInstance = null;
    }
  }

  // ---------- applyModifiers ----------
  function applyModifiers() {
    const popups = window.__currentPopups || {};
    const dateElements = document.querySelectorAll('#calendar-instance [data-vc-date]');
    dateElements.forEach(el => {
      const date = el.getAttribute('data-vc-date');
      el.classList.remove('shift-day', 'shift-night', 'shift-off', 'shift-leave', 'shift-holiday');
      if (popups[date] && popups[date].modifier) {
        const classes = popups[date].modifier.split(' ');
        el.classList.add(...classes);
      }
    });

    // Update holiday box
    const monthBtn = document.querySelector('#calendar-instance [data-vc="month"]');
    const yearBtn = document.querySelector('#calendar-instance [data-vc="year"]');
    if (monthBtn && yearBtn && window.__shiftData && window.__shiftData.holidays) {
      const month = parseInt(monthBtn.getAttribute('data-vc-month'));
      const year = parseInt(yearBtn.getAttribute('data-vc-year'));
      if (!isNaN(month) && !isNaN(year)) {
        renderHolidayBoxForMonth(year, month, window.__shiftData.holidays);
      }
    }
  }

  // ---------- Render daftar karyawan ----------
  async function renderEmployeeList(page = 1) {
    destroyCalendar();
    document.getElementById('app-title').innerText = 'Karyawan Saya';
    const content = document.getElementById('app-content');

    // Tampilkan loading sementara
    content.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;

    try {
      const response = await fetchEmployees(page);
      // response dari paginate() Laravel memiliki struktur: { data: [...], current_page, last_page, ... }
      const employees = response.data;
      const currentPage = response.current_page;
      const lastPage = response.last_page;
      employeePagination = {
        currentPage,
        lastPage
      };

      let html = `<div class="d-flex justify-content-end mb-3">
      <button data-nav="create-employee" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah</button>
      </div>`;

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

      // Tambahkan container pagination
      html += '<div id="employee-pagination" class="d-flex justify-content-center mt-3"></div>';

      content.innerHTML = html;

      // Render pagination menggunakan fungsi dari layout Telegram
      if (typeof tgApp !== 'undefined' && typeof tgApp.renderPagination === 'function') {
        tgApp.renderPagination('employee-pagination', currentPage, lastPage, (newPage) => {
          renderEmployeeList(newPage);
          window.scrollTo({
            top: 0, behavior: 'smooth'
          });
        });
      } else {
        // Fallback manual jika tgApp.renderPagination tidak tersedia
        renderPaginationFallback('employee-pagination', currentPage, lastPage);
      }
    } catch (err) {
      content.innerHTML = `<div class="alert alert-danger">Gagal memuat: ${err.message}</div>`;
    }
  }

  // Fungsi fallback untuk pagination (jika tgApp.renderPagination tidak ada)
  function renderPaginationFallback(containerId, currentPage, lastPage) {
    const container = document.getElementById(containerId);
    if (!container || lastPage <= 1) return;

    let html = '<ul class="pagination pagination-sm justify-content-center">';
    for (let i = 1; i <= lastPage; i++) {
      html += `<li class="page-item ${i === currentPage ? 'active': ''}">
      <a class="page-link" href="#" data-page="${i}">${i}</a>
      </li>`;
    }
    html += '</ul>';
    container.innerHTML = html;

    container.querySelectorAll('.page-link').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        renderEmployeeList(page);
        window.scrollTo({
          top: 0, behavior: 'smooth'
        });
      });
    });
  }

  // ---------- Render form tambah/edit ----------
  async function renderEmployeeForm(params) {
    destroyCalendar();
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
    data-info-content="<p>Huruf yang mewakili shift harian dalam satu siklus.</p><ul><li><strong>D</strong> = Day (siang)</li><li><strong>N</strong> = Night (malam)</li><li><strong>O</strong> atau <strong>-</strong> = Off (libur)</li></ul><p>Contoh: <code>DDDDDDDDNNNNNO</code> berarti 8 Day, 5 Night, 1 Off.</p>">
    <i class="bi bi-info-circle"></i>
    </button>
    </label>
    <input type="text" name="shift_pattern" class="form-control"
    value="${employee ? escapeHtml(employee.shift_pattern): 'DDDDDDDDNNNNNO'}"
    required pattern="[DNO]+"
    title="Hanya boleh huruf D, N, O (kapital)">
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
    destroyCalendar();
    const employeeId = params.id;
    document.getElementById('app-title').innerText = 'Pengajuan Cuti';
    let employee;
    try {
      employee = await fetchEmployee(employeeId);
    } catch (err) {
      console.error(err);
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
    <div id="override-preview" class="mt-2 small text-info"></div>
    </div>
    <div id="override-list"><div class="text-center text-muted py-3">Memuat...</div></div>`;

    // Preview tanggal akhir saat user memilih start_date
    const startDateInput = document.querySelector('#override-form [name="start_date"]');
    const previewDiv = document.getElementById('override-preview');
    if (startDateInput && previewDiv) {
      startDateInput.addEventListener('change', function () {
        const val = this.value;
        if (!val) {
          previewDiv.textContent = '';
          return;
        }
        const start = new Date(val + 'T00:00:00');
        if (isNaN(start.getTime())) {
          previewDiv.textContent = 'Tanggal tidak valid.';
          return;
        }
        const end = new Date(start);
        end.setDate(end.getDate() + employee.leave_days - 1);
        const fmt = {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        };
        previewDiv.innerHTML = `
        Mulai: ${start.toLocaleDateString('id-ID', fmt)}<br>
        Selesai: ${end.toLocaleDateString('id-ID', fmt)}<br>
        <span class="badge bg-info">${employee.leave_days} hari</span>
        `;
      });
    }

    // Fungsi untuk memuat daftar override
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
          const dateStr = String(ov.start_date).substring(0, 10);
          const startDate = new Date(dateStr + 'T00:00:00');
          if (isNaN(startDate.getTime())) {
            // fallback jika gagal
            html += `<div class="card mb-2"><div class="card-body">Data tanggal tidak valid: ${escapeHtml(ov.start_date)}</div></div>`;
            return;
          }
          const endDate = new Date(startDate);
          endDate.setDate(endDate.getDate() + employee.leave_days - 1);
          const fmt = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
          };
          html += `<div class="card mb-2"><div class="card-body d-flex justify-content-between align-items-center">
          <div>
          <strong>${startDate.toLocaleDateString('id-ID', fmt)}</strong> – ${endDate.toLocaleDateString('id-ID', fmt)}
          <br><small class="text-muted">${dateStr} s/d ${endDate.toISOString().slice(0, 10)}</small>
          </div>
          <button class="btn btn-sm btn-outline-danger" data-delete-override="${ov.id}"><i class="bi bi-trash"></i></button>
          </div></div>`;
        });
        container.innerHTML = html;
      } catch (err) {
        console.error(err);
        document.getElementById('override-list').innerHTML = `<div class="alert alert-danger">Gagal memuat.</div>`;
      }
    }

    await loadOverrides();
  }

  // ---------- Render halaman generate ----------
  function renderGenerate() {
    destroyCalendar();
    document.getElementById('app-title').innerText = 'Generate Roster';
    const content = document.getElementById('app-content');
    content.innerHTML = `
    <div class="card mb-3">
    <div class="card-body">
    <div class="row g-2">
    <div class="col-6"><label class="form-label">Mulai</label><input type="date" id="start_date" class="form-control" placeholder="01/01/2026"></div>
    <div class="col-6"><label class="form-label">Selesai</label><input type="date" id="end_date" class="form-control" placeholder="01/01/2026"></div>
    </div>
    <button class="btn btn-success mt-3 w-100" id="btn-generate"><i class="bi bi-gear"></i> Generate</button>
    </div>
    </div>
    <div id="result-container" class="d-none">
    <div id="calendar-legend" class="d-flex justify-content-center align-items-center">
    <div class="legend-item"><span class="legend-dot day"></span> Day</div>
    <div class="legend-item"><span class="legend-dot night"></span> Night</div>
    <div class="legend-item"><span class="legend-dot off"></span> Off</div>
    <div class="legend-item"><span class="legend-dot leave"></span> Cuti</div>
    </div>
    <div id="shift-calendar-wrapper">
    <div id="shift-calendar" style="margin-bottom: 1rem;"></div>
    </div>
    <div id="holiday-box" class="mt-3 d-none">
    <h6>Hari Libur Nasional</h6>
    <div id="holiday-list" class="d-flex flex-wrap gap-2"></div>
    </div>
    <div class="d-flex justify-content-end mt-3">
    <button class="btn btn-sm btn-outline-primary" id="btn-export"><i class="bi bi-download"></i> Export Excel</button>
    </div>
    </div>`;
  }

  // ---------- Render kalender dengan data shift ----------
  async function renderShiftCalendar(start,
    end) {
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
      end,
      holidays: window.__shiftData?.holidays || []
    };

    // Hapus isi container, lalu bangun ulang
    container.innerHTML = '';
    const dropdownWrapper = document.createElement('div');
    dropdownWrapper.id = 'dropdown-wrapper';
    container.appendChild(dropdownWrapper);
    const calendarEl = document.createElement('div');
    calendarEl.id = 'calendar-instance';
    container.appendChild(calendarEl);

    // Reset instance & observer
    calendarInstance = null;
    if (currentObserver) {
      currentObserver.disconnect();
      currentObserver = null;
    }

    renderCalendarForEmployee(window.__shiftData.currentIndex || 0);
  }

  // ---------- Tampilkan libur untuk bulan tertentu ----------
  function renderHolidayBoxForMonth(year, month, holidays) {
    const box = document.getElementById('holiday-box');
    const list = document.getElementById('holiday-list');
    if (!box || !list) return;

    const filtered = holidays.filter(h => {
      const d = new Date(h.date + 'T00:00:00');
      return d.getFullYear() === year && d.getMonth() === month;
    });

    if (filtered.length === 0) {
      box.classList.add('d-none');
      return;
    }

    box.classList.remove('d-none');
    list.innerHTML = filtered.map(h => `
      <span class="badge bg-danger bg-opacity-25 text-danger border border-danger px-3 py-2 text-wrap" style="word-break: break-word;">
      ${h.date} – ${escapeHtml(h.name)}
      </span>
      `).join('');
  }

  // ---------- Render kalender untuk satu karyawan ----------
  function renderCalendarForEmployee(index) {
    const data = window.__shiftData;
    if (!data || !data.employeeKeys.length) return;

    data.currentIndex = index;
    const empKey = data.employeeKeys[index];
    const empData = data.byEmployee[empKey];
    const schedules = empData.schedules;
    const holidays = data.holidays || [];
    const holidayDates = new Set(holidays.map(h => h.date));

    const popups = {};
    schedules.forEach(s => {
      const dateKey = String(s.date).substring(0, 10);
      let modifier = s.shift === 'Day' ? 'shift-day':
      s.shift === 'Night' ? 'shift-night':
      s.shift === 'Leave' ? 'shift-leave': 'shift-off';
      if (holidayDates.has(dateKey)) {
        modifier += ' shift-holiday';
      }
      popups[dateKey] = {
        modifier: modifier,
        html: `<div><strong>${s.shift}</strong></div>`
      };
    });

    window.__currentPopups = popups;

    // Dropdown
    const dropdownWrapper = document.getElementById('dropdown-wrapper');
    if (dropdownWrapper) {
      if (data.employeeKeys.length > 1) {
        let selectEl = document.getElementById('employee-select');
        if (!selectEl) {
          dropdownWrapper.innerHTML = `
          <div class="mb-3">
          <select id="employee-select" class="form-select">
          ${data.employeeKeys.map((k, i) => `<option value="${i}" ${i === index ? 'selected': ''}>${escapeHtml(k)}</option>`).join('')}
          </select>
          </div>`;
          selectEl = document.getElementById('employee-select');
          selectEl.addEventListener('change', (e) => {
            renderCalendarForEmployee(parseInt(e.target.value));
          });
        } else {
          selectEl.value = index;
          if (selectEl.options.length !== data.employeeKeys.length) {
            selectEl.innerHTML = data.employeeKeys.map((k, i) =>
              `<option value="${i}" ${i === index ? 'selected': ''}>${escapeHtml(k)}</option>`
            ).join('');
          }
        }
      } else {
        dropdownWrapper.innerHTML = '';
      }
    }

    const start = data.start || new Date().toISOString().substring(0, 10);
    const end = data.end || new Date().toISOString().substring(0, 10);
    const startDate = new Date(start + 'T00:00:00');

    const targetEl = document.getElementById('calendar-instance');
    if (!targetEl) {
      console.error('Target #calendar-instance tidak ada');
      return;
    }

    if (!window.VanillaCalendarPro) {
      targetEl.innerHTML = '<div class="alert alert-danger">Library kalender tidak termuat.</div>';
      return;
    }

    // Jika instance sudah ada, gunakan set() untuk memperbarui popups
    if (calendarInstance) {
      calendarInstance.set({
        popups: popups,
      }, {
        dates: true,
      });

      // Terapkan modifier
      applyModifiers();
      setTimeout(() => applyModifiers(), 100);
      return;
    }

    // Jika belum ada instance, buat baru
    const {
      Calendar
    } = window.VanillaCalendarPro;
    calendarInstance = new Calendar(targetEl, {
      type: 'default',
      firstDayOfWeek: 1,
      selectedWeekends: [0],
      settings: {
        visibility: {
          daysOutsideMonth: true
        },
        selection: {
          day: 'none'
        },
      },
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
      popups: popups,
    });

    calendarInstance.init();

    // Pasang observer setelah elemen ada
    const node = document.getElementById('calendar-instance');
    if (node) {
      if (currentObserver) currentObserver.disconnect();
      currentObserver = new MutationObserver(() => applyModifiers());
      currentObserver.observe(node, {
        childList: true, subtree: true
      });
    }

    applyModifiers();
    setTimeout(() => applyModifiers(), 100);
  }

  window.PageRender = {
    renderEmployeeList,
    renderEmployeeForm,
    renderOverrides,
    renderGenerate,
    renderShiftCalendar,
    loadRosterData: async (start, end) => {
      await renderShiftCalendar(start, end);
    },
    destroyCalendar
  };
})();