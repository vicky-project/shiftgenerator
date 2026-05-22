// main.js
(function() {
  const {
    state, APP_BASE, setToken, deleteEmployee, addOverride, deleteOverride, generateRoster, exportExcel
  } = window.AppCore;
  const {
    renderEmployeeList, renderEmployeeForm, renderOverrides, renderGenerate, loadRosterData
  } = window.PageRender;
  const showToast = window.TelegramApp?.showToast || window.tgApp?.showToast || ((msg, type) => console.log(msg));

  // =========== Router ===========
  const routes = {
    '/employees': () => renderEmployeeList(),
    '/employees/create': () => renderEmployeeForm(),
    '/employees/:id/edit': (params) => renderEmployeeForm(params),
    '/employees/:id/overrides': (params) => renderOverrides(params),
    '/generate': () => renderGenerate(),
  };

  function matchRoute(path) {
    for (const pattern in routes) {
      const regex = pattern.replace(/:(\w+)/g, '(?<$1>[^/]+)');
      const match = path.match(new RegExp('^' + regex + '$'));
      if (match) {
        return {
          handler: routes[pattern],
          params: match.groups
        };
      }
    }
    return null;
  }

  async function navigateTo(path, addToHistory = true) {
    if (addToHistory) {
      window.history.pushState({
        path
      }, '', path);
    }
    updateNavActive(path);
    const route = matchRoute(path);
    if (route) {
      try {
        await route.handler(route.params);
      } catch (err) {
        document.getElementById('app-content').innerHTML = `<div class="alert alert-danger">Gagal memuat halaman: ${err.message}</div>`;
      }
    } else {
      document.getElementById('app-content').innerHTML = `<div class="alert alert-warning">Halaman tidak ditemukan.</div>`;
    }

    // Toggle back button
    const btnBack = document.getElementById('btn-back');
    if (path !== '/employees' && path !== '/generate') {
      btnBack.classList.remove('d-none');
    } else {
      btnBack.classList.add('d-none');
    }
  }

  function updateNavActive(path) {
    document.querySelectorAll('[data-route]').forEach(link => {
      const href = link.getAttribute('href');
      if (path.startsWith(href)) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  // =========== Global Event Delegation ===========
  document.addEventListener('click',
    async (e) => {
      // Tangani link internal
      const anchor = e.target.closest('a');
      if (anchor) {
        const href = anchor.getAttribute('href');
        if (href && href.startsWith('/') && !href.startsWith('//')) {
          e.preventDefault();
          navigateTo(href);
          return;
        }
      }

      // Tombol hapus karyawan
      const deleteBtn = e.target.closest('[data-delete-employee]');
      if (deleteBtn) {
        const id = deleteBtn.dataset.deleteEmployee;
        if (!confirm('Yakin hapus karyawan? Semua jadwal terkait akan ikut terhapus.')) return;
        try {
          await deleteEmployee(id);
          showToast('Karyawan dihapus.', 'success');
          navigateTo('/employees', false);
        } catch (err) {
          showToast('Gagal: ' + err.message, 'danger');
        }
        return;
      }

      // Tombol hapus override
      const delOverrideBtn = e.target.closest('[data-delete-override]');
      if (delOverrideBtn) {
        const id = delOverrideBtn.dataset.deleteOverride;
        if (!confirm('Hapus override ini?')) return;
        try {
          await deleteOverride(id);
          showToast('Override dihapus.', 'success');
          // Reload halaman override saat ini
          const currentPath = window.location.pathname;
          navigateTo(currentPath, false);
        } catch (err) {
          showToast('Gagal: ' + err.message, 'danger');
        }
        return;
      }
    });

  // Tangani submit form dengan delegation
  document.addEventListener('submit',
    async (e) => {
      // Form karyawan
      if (e.target.id === 'employee-form') {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        data.work_days = parseInt(data.work_days);
        data.leave_days = parseInt(data.leave_days);
        const path = window.location.pathname;
        const match = path.match(/\/employees\/(\d+)\/edit/);
        const id = match ? match[1]: null;
        try {
          await window.AppCore.saveEmployee(data, id);
          showToast('Data tersimpan.', 'success');
          navigateTo('/employees');
        } catch (err) {
          showToast('Error: ' + err.message, 'danger');
        }
        return;
      }

      // Form override
      if (e.target.id === 'override-form') {
        e.preventDefault();
        const startDate = e.target.start_date.value;
        const path = window.location.pathname;
        const match = path.match(/\/employees\/(\d+)\/overrides/);
        if (!match) return;
        const employeeId = match[1];
        try {
          await addOverride(employeeId, startDate);
          showToast('Override ditambahkan.', 'success');
          e.target.reset();
          // reload halaman
          navigateTo(path, false);
        } catch (err) {
          showToast('Gagal: ' + err.message, 'danger');
        }
        return;
      }
    });

  // Tombol generate & export (event delegation untuk klik biasa di dalam #app-content)
  document.getElementById('app-content').addEventListener('click',
    async (e) => {
      // Generate
      if (e.target.id === 'btn-generate') {
        const start = document.getElementById('start_date')?.value;
        const end = document.getElementById('end_date')?.value;
        const holidaysStr = document.getElementById('holidays')?.value || '';
        if (!start || !end) {
          showToast('Isi rentang tanggal.', 'warning');
          return;
        }
        const holidays = holidaysStr.split(',').map(s => s.trim()).filter(s => s);
        window.TelegramApp?.showLoading?.('Menghasilkan roster...');
        try {
          await generateRoster(start, end, holidays);
          showToast('Roster dibuat.');
          await loadRosterData(start, end);
        } catch (err) {
          showToast('Gagal: ' + err.message, 'danger');
        } finally {
          window.TelegramApp?.hideLoading?.();
        }
        return;
      }

      // Export
      if (e.target.id === 'btn-export') {
        const start = document.getElementById('start_date')?.value;
        const end = document.getElementById('end_date')?.value;
        try {
          const blob = await exportExcel(start, end);
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'shift_roster.xlsx';
          document.body.appendChild(a);
          a.click();
          a.remove();
          window.URL.revokeObjectURL(url);
          showToast('File terunduh.');
        } catch (err) {
          showToast('Export error: ' + err.message, 'danger');
        }
        return;
      }
    });

  // =========== Inisialisasi ===========
  // Cek token dari URL (biasanya dari Telegram)
  const urlParams = new URLSearchParams(window.location.search);
  const tokenFromUrl = urlParams.get('token');
  if (tokenFromUrl) {
    setToken(tokenFromUrl);
    // Hapus token dari URL tanpa reload
    const newUrl = window.location.pathname;
    window.history.replaceState({}, '', newUrl);
  }

  // Handle popstate (back/forward browser)
  window.addEventListener('popstate', (e) => {
    if (e.state && e.state.path) {
      navigateTo(e.state.path, false);
    } else {
      navigateTo('/employees', false);
    }
  });

  // Muat halaman awal
  const initialPath = window.location.pathname.replace(APP_BASE,
    '') || '/employees';
  navigateTo(initialPath,
    false);
})();