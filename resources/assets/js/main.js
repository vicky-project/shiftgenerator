// main.js
(function() {
  const {
    APP_BASE, setToken,
    deleteEmployee, addOverride, deleteOverride,
    generateRoster, exportExcel
  } = window.AppCore;
  const {
    renderEmployeeList, renderEmployeeForm, renderOverrides,
    renderGenerate, loadRosterData
  } = window.PageRender;
  const showToast = window.TelegramApp?.showToast || window.tgApp?.showToast || ((msg, type) => console.log(msg));
  const showLoading = window.TelegramApp?.showLoading || (() => {});
  const hideLoading = window.TelegramApp?.hideLoading || (() => {});

  // =========== Router ===========
  const routes = {
    '/': () => navigateTo('/employees', false), // fallback
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
    console.log('[navigateTo] path:', path); // DEBUG
    const fullPath = APP_BASE + (path.startsWith('/') ? path: '/' + path);
    if (addToHistory) {
      window.history.pushState({
        path
      }, '', fullPath);
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
      console.warn('[navigateTo] Route tidak ditemukan untuk path:', path);
      document.getElementById('app-content').innerHTML = `<div class="alert alert-warning">Halaman tidak ditemukan (${path}).</div>`;
    }

    // Atur visibilitas tombol back
    const btnBack = document.getElementById('btn-back');
    if (btnBack) {
      const isRoot = (path === '/employees' || path === '/generate');
      btnBack.classList.toggle('d-none', isRoot);
      console.log('[navigateTo] Tombol back ' + (isRoot ? 'disembunyikan': 'ditampilkan'));
    }
  }

  function updateNavActive(path) {
    document.querySelectorAll('[data-route]').forEach(link => {
      const routePath = link.dataset.route;
      if (routePath) {
        link.classList.toggle('active', path.startsWith(routePath));
      }
    });
  }

  // =========== Global Event Delegation ===========
  document.addEventListener('click',
    async (e) => {
      // 1. Navigasi via button dengan data-nav
      const navButton = e.target.closest('[data-nav]');
      if (navButton) {
        const path = navButton.dataset.nav;
        if (path) {
          navigateTo(path);
        }
        return;
      }

      // 2. Tombol hapus karyawan
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

      // 3. Tombol hapus override
      const delOverrideBtn = e.target.closest('[data-delete-override]');
      if (delOverrideBtn) {
        const id = delOverrideBtn.dataset.deleteOverride;
        if (!confirm('Hapus override ini?')) return;
        try {
          await deleteOverride(id);
          showToast('Override dihapus.', 'success');
          const currentPath = window.location.pathname.replace(APP_BASE, '') || '/employees';
          navigateTo(currentPath, false);
        } catch (err) {
          showToast('Gagal: ' + err.message, 'danger');
        }
        return;
      }
    });

  // =========== Form Submission Delegation ===========
  document.addEventListener('submit',
    async (e) => {
      if (e.target.id === 'employee-form') {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        data.work_days = parseInt(data.work_days);
        data.leave_days = parseInt(data.leave_days);
        const path = window.location.pathname.replace(APP_BASE, '');
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

      if (e.target.id === 'override-form') {
        e.preventDefault();
        const startDate = e.target.start_date.value;
        const path = window.location.pathname.replace(APP_BASE, '');
        const match = path.match(/\/employees\/(\d+)\/overrides/);
        if (!match) return;
        const employeeId = match[1];
        try {
          await addOverride(employeeId, startDate);
          showToast('Override ditambahkan.', 'success');
          e.target.reset();
          navigateTo(`/employees/${employeeId}/overrides`, false);
        } catch (err) {
          showToast('Gagal: ' + err.message, 'danger');
        }
        return;
      }
    });

  // =========== Tombol Generate & Export ===========
  document.getElementById('app-content').addEventListener('click',
    async (e) => {
      if (e.target.id === 'btn-generate') {
        const start = document.getElementById('start_date')?.value;
        const end = document.getElementById('end_date')?.value;
        const holidaysStr = document.getElementById('holidays')?.value || '';
        if (!start || !end) {
          showToast('Isi rentang tanggal.', 'warning');
          return;
        }
        const holidays = holidaysStr.split(',').map(s => s.trim()).filter(s => s);
        showLoading('Menghasilkan roster...');
        try {
          await generateRoster(start, end, holidays);
          showToast('Roster dibuat.');
          await loadRosterData(start, end);
        } catch (err) {
          showToast('Gagal: ' + err.message, 'danger');
        } finally {
          hideLoading();
        }
        return;
      }

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
  function initApp() {
    console.log('[initApp] Mulai inisialisasi');
    // Token dari URL (jika ada)
    const urlParams = new URLSearchParams(window.location.search);
    const tokenFromUrl = urlParams.get('token');
    if (tokenFromUrl) {
      setToken(tokenFromUrl);
      window.history.replaceState({}, '', window.location.pathname);
    }

    window.addEventListener('popstate', (e) => {
      if (e.state && e.state.path) {
        navigateTo(e.state.path, false);
      } else {
        navigateTo('/employees', false);
      }
    });

    let initialPath = window.location.pathname.replace(new RegExp('^' + APP_BASE),
      '');
    console.log('[initApp] initialPath:',
      initialPath);
    if (!initialPath || initialPath === '/') {
      initialPath = '/employees';
    }
    navigateTo(initialPath, false);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
  } else {
    initApp();
  }
})();