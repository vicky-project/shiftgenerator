// page.js (FINAL – menggunakan elemen langsung untuk Calendar)
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

  function destroyCalendar() {
    if (currentObserver) {
      currentObserver.disconnect();
      currentObserver = null;
    }
    if (calendarInstance && typeof calendarInstance.destroy === 'function') {
      calendarInstance.destroy();
      calendarInstance = null;
    }
    // Jangan hapus elemen DOM di sini, biarkan renderShiftCalendar yang mengatur ulang
  }

  // ... semua fungsi render lainnya (renderEmployeeList, renderEmployeeForm, renderOverrides, renderGenerate) sama persis seperti sebelumnya ...

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
      end,
      holidays: window.__shiftData?.holidays || []
    };

    // Bersihkan container & buat elemen baru
    container.innerHTML = '';
    const dropdownWrapper = document.createElement('div');
    dropdownWrapper.id = 'dropdown-wrapper';
    container.appendChild(dropdownWrapper);
    const calendarEl = document.createElement('div');
    calendarEl.id = 'calendar-instance';
    // Pastikan elemen terlihat
    calendarEl.style.display = 'block';
    calendarEl.style.minHeight = '400px';
    calendarEl.style.background = '#fff'; // debug, nanti dihapus
    container.appendChild(calendarEl);

    // Reset instance & observer karena elemen baru
    calendarInstance = null;
    if (currentObserver) {
      currentObserver.disconnect();
      currentObserver = null;
    }

    renderCalendarForEmployee(window.__shiftData.currentIndex || 0);
  }

  function renderHolidayBoxForMonth(year, month, holidays) {
    // ... tidak berubah ...
  }

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
      s.shift === 'Night' ? 'shift-night': 'shift-off';
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

    // Hapus instance sebelumnya (jika masih ada)
    destroyCalendar();

    // Gunakan elemen langsung, bukan selector string
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

    // Panggil init dengan delay kecil untuk memastikan DOM siap
    setTimeout(() => {
      calendarInstance.init();
      // Setelah init, langsung pasang observer & apply modifiers
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
    },
      10);
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
    },
    destroyCalendar
  };
})();