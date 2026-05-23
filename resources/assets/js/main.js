// main.js (final dengan tgApp langsung)
(function() {
  const {
    setToken, deleteEmployee, addOverride, deleteOverride,
    generateRoster, exportExcel
  } = window.AppCore;
  const {
    renderEmployeeList, renderEmployeeForm, renderOverrides,
    renderGenerate, loadRosterData
  } = window.PageRender;

  // Gunakan tgApp langsung dari layout Telegram Mini App
  const showToast = tgApp.showToast || ((msg, type) => console.log(msg));
  const showLoading = tgApp.showLoading || ((msg) => {
    const overlay = document.getElementById('global-loading');
    if (overlay) {
      overlay.style.display = 'flex';
      const msgEl = overlay.querySelector('div:last-child');
      if (msgEl) msgEl.innerText = msg || 'Memuat...';
    }
  });
  const hideLoading = tgApp.hideLoading || (() => {
    const overlay = document.getElementById('global-loading');
    if (overlay) overlay.style.display = 'none';
  });
  const escapeHtml = tgApp.escapeHtml || ((str) => str);

  let currentPage = 'employees';
  let currentParams = null;

  // Fungsi navigasi halaman
  async function goTo(navString) {
    showLoading('Memuat halaman...');

    const [page, param] = navString.split(':');
    currentPage = page;
    currentParams = param ? {
      id: param
    }: null;

    updateBackButton();
    updateTabActive();

    try {
      switch (page) {
        case 'employees':
          await renderEmployeeList();
          break;
        case 'create-employee':
          await renderEmployeeForm();
          break;
        case 'edit-employee':
          await renderEmployeeForm(currentParams);
          break;
        case 'overrides':
          await renderOverrides(currentParams);
          break;
        case 'generate':
          renderGenerate();
          break;
        default:
          document.getElementById('app-content').innerHTML = '<div class="alert alert-warning">Halaman tidak ditemukan.</div>';
        }
      } catch (err) {
        document.getElementById('app-content').innerHTML = `<div class="alert alert-danger">Gagal memuat: ${err.message}</div>`;
      } finally {
        hideLoading();
      }
    }

    function updateBackButton() {
      const btnBack = document.getElementById('btn-back');
      if (!btnBack) return;
      const hide = (currentPage === 'employees' || currentPage === 'generate');
      btnBack.classList.toggle('d-none', hide);
    }

    function updateTabActive() {
      document.querySelectorAll('[data-route]').forEach(el => {
        el.classList.remove('active');
        const route = el.dataset.route;
        if (route === 'employees' && ['employees', 'create-employee', 'edit-employee', 'overrides'].includes(currentPage)) {
          el.classList.add('active');
        } else if (route === 'generate' && currentPage === 'generate') {
          el.classList.add('active');
        }
      });
    }

    window.goToPage = goTo;

    // =========== Event Delegation ===========
    document.addEventListener('click',
      async (e) => {
        // 1. Navigasi via data-nav
        const navButton = e.target.closest('[data-nav]');
        if (navButton) {
          const nav = navButton.dataset.nav.trim();
          goTo(nav);
          return;
        }

        // 2. Hapus karyawan
        const deleteBtn = e.target.closest('[data-delete-employee]');
        if (deleteBtn) {
          const id = deleteBtn.dataset.deleteEmployee;
          if (!confirm('Yakin hapus karyawan? Semua jadwal terkait akan ikut terhapus.')) return;
          try {
            showLoading('Menghapus...');
            await deleteEmployee(id);
            showToast('Karyawan dihapus.', 'success');
            goTo('employees');
          } catch (err) {
            showToast('Gagal: ' + err.message, 'danger');
          } finally {
            hideLoading();
          }
          return;
        }

        // 3. Hapus override
        const delOverrideBtn = e.target.closest('[data-delete-override]');
        if (delOverrideBtn) {
          const id = delOverrideBtn.dataset.deleteOverride;
          if (!confirm('Hapus override ini?')) return;
          try {
            showLoading('Menghapus...');
            await deleteOverride(id);
            showToast('Override dihapus.', 'success');
            if (currentPage === 'overrides' && currentParams) {
              goTo(`overrides:${currentParams.id}`);
            } else {
              goTo('employees');
            }
          } catch (err) {
            showToast('Gagal: ' + err.message, 'danger');
          } finally {
            hideLoading();
          }
          return;
        }

        // 4. Generate & Export
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
          showLoading('Menyiapkan file...');
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
          } finally {
            hideLoading();
          }
          return;
        }
      });

    // =========== Form Submissions ===========
    document.addEventListener('submit',
      async (e) => {
        if (e.target.id === 'employee-form') {
          e.preventDefault();
          const formData = new FormData(e.target);
          const data = Object.fromEntries(formData.entries());
          data.work_days = parseInt(data.work_days);
          data.leave_days = parseInt(data.leave_days);
          const id = currentParams?.id || null;
          showLoading('Menyimpan...');
          try {
            await window.AppCore.saveEmployee(data, id);
            showToast('Data tersimpan.', 'success');
            goTo('employees');
          } catch (err) {
            showToast('Error: ' + err.message, 'danger');
          } finally {
            hideLoading();
          }
          return;
        }

        if (e.target.id === 'override-form') {
          e.preventDefault();
          const startDate = e.target.start_date.value;
          const employeeId = currentParams?.id;
          if (!employeeId) return;
          showLoading('Menambahkan...');
          try {
            await addOverride(employeeId, startDate);
            showToast('Override ditambahkan.', 'success');
            e.target.reset();
            goTo(`overrides:${employeeId}`);
          } catch (err) {
            showToast('Gagal: ' + err.message, 'danger');
          } finally {
            hideLoading();
          }
          return;
        }
      });

    // =========== Inisialisasi ===========
    function initApp() {
      const urlParams = new URLSearchParams(window.location.search);
      const tokenFromUrl = urlParams.get('token');
      if (tokenFromUrl) {
        setToken(tokenFromUrl);
        window.history.replaceState({}, '', window.location.pathname);
      }
      // Tampilkan halaman awal
      goTo('employees');
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initApp);
    } else {
      initApp();
    }
  })();