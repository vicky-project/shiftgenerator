// page.js
(function() {
  const {
    fetchEmployees, fetchEmployee, saveEmployee, deleteEmployee,
    fetchOverrides, addOverride, deleteOverride,
    generateRoster, fetchSchedules, exportExcel
  } = window.AppCore;
  const showToast = window.TelegramApp?.showToast || tgApp.showToast || ((msg, type) => console.log(msg));
  const escapeHtml = window.TelegramApp?.escapeHtml || tgApp.escapeHtml || ((str) => str);

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
    <label class="form-label">Pola Shift</label>
    <input type="text" name="shift_pattern" class="form-control" value="${employee ? escapeHtml(employee.shift_pattern): 'DDDDDDDDNNNNNO'}" required>
    </div>
    <div class="row mb-3">
    <div class="col">
    <label class="form-label">Shift Start Date</label>
    <input type="date" name="shift_start_date" class="form-control" value="${employee ? employee.shift_start_date: ''}" required>
    </div>
    <div class="col">
    <label class="form-label">Shift Start</label>
    <select name="shift_start" class="form-select">
    <option value="Day" ${employee && employee.shift_start === 'Day' ? 'selected': ''}>Day</option>
    <option value="Night" ${employee && employee.shift_start === 'Night' ? 'selected': ''}>Night</option>
    </select>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col">
    <label class="form-label">Work Days</label>
    <input type="number" name="work_days" class="form-control" value="${employee ? employee.work_days: 70}">
    </div>
    <div class="col">
    <label class="form-label">Leave Days</label>
    <input type="number" name="leave_days" class="form-control" value="${employee ? employee.leave_days: 14}">
    </div>
    </div>
    <div class="mb-3">
    <label class="form-label">Pattern Start Date</label>
    <input type="date" name="pattern_start_date" class="form-control" value="${employee ? employee.pattern_start_date: ''}" required>
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
    <div class="d-flex justify-content-between align-items-center mb-2">
    <h6>Hasil Roster</h6>
    <button class="btn btn-sm btn-outline-primary" id="btn-export"><i class="bi bi-download"></i> Export Excel</button>
    </div>
    <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
    <table class="table table-sm table-bordered">
    <thead><tr><th>Tanggal</th><th>NRP</th><th>Nama</th><th>Shift</th></tr></thead>
    <tbody id="roster-body"></tbody>
    </table>
    </div>
    </div>`;
  }

  // Publikasi render functions
  window.PageRender = {
    renderEmployeeList,
    renderEmployeeForm,
    renderOverrides,
    renderGenerate,
    loadRosterData: async (start, end) => {
      try {
        const data = await fetchSchedules(start, end);
        const tbody = document.getElementById('roster-body');
        tbody.innerHTML = data.map(s => `<tr><td>${s.date}</td><td>${escapeHtml(s.employee.nrp)}</td><td>${escapeHtml(s.employee.name)}</td><td>${s.shift}</td></tr>`).join('');
        document.getElementById('result-container').classList.remove('d-none');
      } catch (err) {
        showToast('Gagal memuat data roster.', 'danger');
      }
    }
  };
})();