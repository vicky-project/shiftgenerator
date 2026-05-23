// main.js
(function() {
  const {
    setToken, deleteEmployee, addOverride, deleteOverride,
    generateRoster, exportExcel
  } = window.AppCore;
  const {
    renderEmployeeList, renderEmployeeForm, renderOverrides,
    renderGenerate, loadRosterData
  } = window.PageRender;
  const showToast = window.TelegramApp?.showToast || window.tgApp?.showToast || ((msg, type) => console.log(msg));
  const showLoading = window.TelegramApp?.showLoading || (() => {});
  const hideLoading = window.TelegramApp?.hideLoading || (() => {});

  // State sederhana untuk halaman saat ini
  let currentPage = 'employees'; // 'employees', 'generate', 'form', 'overrides', dll.
  let currentParams = null;

  // Fungsi untuk mengubah halaman
  async function goTo(page, params = null) {
    currentPage = page;
    currentParams = params;
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
          await renderEmployeeForm(params);
          break;
        case 'overrides':
          await renderOverrides(params);
          break;
        case 'generate':
          renderGenerate();
          break;
        default:
          document.getElementById('app-content').innerHTML = '<div class="alert alert-warning">Halaman tidak ditemukan.</div>';
        }
      } catch (err) {
        document.getElementById('app-content').innerHTML = `<div class="alert alert-danger">Gagal memuat halaman: ${err.message}</div>`;
      }
    }

    // Perbarui visibilitas tombol kembali
    function updateBackButton() {
      const btnBack = document.getElementById('btn-back');
      if (!btnBack) return;
      if (currentPage === 'employees' || currentPage === 'generate') {
        btnBack.classList.add('d-none');
      } else {
        btnBack.classList.remove('d-none');
      }
    }

    // Perbarui tab aktif
    function updateTabActive() {
      document.querySelectorAll('[data-route]').forEach(el => {
        const route = el.dataset.route;
        el.classList.remove('active');
        if (route === 'employees' && (currentPage === 'employees' || currentPage === 'create-employee' || currentPage === 'edit-employee' || currentPage === 'overrides')) {
          el.classList.add('active');
        } else if (route === 'generate' && currentPage === 'generate') {
          el.classList.add('active');
        }
      });
    }

    // =========== Global Event Delegation ===========
    document.addEventListener('click',
      async (e) => {
        // 1. Navigasi via button data-nav
        const navButton = e.target.closest('[data-nav]');
        if (navButton) {
          const nav = navButton.dataset.nav; // format: "page:params" atau "page"
          const [page,
            param] = nav.split(':');
          let params = null;
          if (param) {
            // jika ada parameter, bisa berupa id misalnya "edit-employee:123"
            params = {
              id: param
            };
          }
          goTo(page, params);
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
            goTo('employees');
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
            // muat ulang halaman override dengan parameter yang sama
            if (currentPage === 'overrides' && currentParams) {
              goTo('overrides', currentParams);
            } else {
              goTo('employees');
            }
          } catch (err) {
            showToast('Gagal: ' + err.message, 'danger');
          }
          return;
        }

        // Tombol Generate & Export di halaman generate
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

    // =========== Form Submission Delegation ===========
    document.addEventListener('submit',
      async (e) => {
        if (e.target.id === 'employee-form') {
          e.preventDefault();
          const formData = new FormData(e.target);
          const data = Object.fromEntries(formData.entries());
          data.work_days = parseInt(data.work_days);
          data.leave_days = parseInt(data.leave_days);
          const id = currentParams?.id || null;
          try {
            await window.AppCore.saveEmployee(data, id);
            showToast('Data tersimpan.', 'success');
            goTo('employees');
          } catch (err) {
            showToast('Error: ' + err.message, 'danger');
          }
          return;
        }

        if (e.target.id === 'override-form') {
          e.preventDefault();
          const startDate = e.target.start_date.value;
          const employeeId = currentParams?.id;
          if (!employeeId) return;
          try {
            await addOverride(employeeId, startDate);
            showToast('Override ditambahkan.', 'success');
            e.target.reset();
            goTo('overrides', {
              id: employeeId
            });
          } catch (err) {
            showToast('Gagal: ' + err.message, 'danger');
          }
          return;
        }
      });

    // =========== Inisialisasi ===========
    function initApp() {
      // Token dari URL (jika ada)
      const urlParams = new URLSearchParams(window.location.search);
      const tokenFromUrl = urlParams.get('token');
      if (tokenFromUrl) {
        setToken(tokenFromUrl);
        // Hapus token dari URL tanpa reload
        const newUrl = window.location.pathname;
        window.history.replaceState({}, '', newUrl);
      }

      // Tampilkan halaman utama
      goTo('employees');
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initApp);
    } else {
      initApp();
    }
  })();