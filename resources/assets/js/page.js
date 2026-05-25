// page.js (DEBUG – pastikan kalender terlihat)
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
  }

  function applyModifiers() {
    const popups = window.__currentPopups || {};
    const dateElements = document.querySelectorAll('#calendar-instance [data-vc-date]');
    dateElements.forEach(el => {
      const date = el.getAttribute('data-vc-date');
      el.classList.remove('shift-day', 'shift-night', 'shift-off', 'shift-holiday');
      if (popups[date] && popups[date].modifier) {
        const classes = popups[date].modifier.split(' ');
        el.classList.add(...classes);
      }
    });

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

  // ... semua fungsi render lainnya (renderEmployeeList, renderEmployeeForm, renderOverrides, renderGenerate)
  // tidak berubah, jadi tidak perlu ditulis ulang ...

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

    // Bangun ulang struktur kalender
    container.innerHTML = '';
    const dropdownWrapper = document.createElement('div');
    dropdownWrapper.id = 'dropdown-wrapper';
    container.appendChild(dropdownWrapper);
    const calendarEl = document.createElement('div');
    calendarEl.id = 'calendar-instance';
    container.appendChild(calendarEl);

    renderCalendarForEmployee(window.__shiftData.currentIndex || 0);
  }

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

    destroyCalendar();

    // ** DEBUG: cek apakah VanillaCalendarPro tersedia **
    if (!window.VanillaCalendarPro) {
      console.error('VanillaCalendarPro tidak tersedia!');
      document.getElementById('calendar-instance').innerHTML = '<div class="alert alert-danger">Library kalender tidak termuat.</div>';
      return;
    }

    const {
      Calendar
    } = window.VanillaCalendarPro;

    // ** DEBUG: cek apakah elemen target ada **
    const targetEl = document.getElementById('calendar-instance');
    if (!targetEl) {
      console.error('Target #calendar-instance tidak ada saat akan membuat Calendar');
      return;
    }

    console.log('Membuat Calendar dengan target:', targetEl);
    calendarInstance = new Calendar('#calendar-instance', {
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
    console.log('Calendar init selesai. Isi #calendar-instance:', targetEl.innerHTML.substring(0, 200));

    // Jika setelah init masih kosong, coba render ulang dengan timeout
    if (!targetEl.innerHTML.trim()) {
      console.warn('Kalender kosong setelah init, mencoba render ulang...');
      setTimeout(() => {
        if (calendarInstance && typeof calendarInstance.update === 'function') {
          calendarInstance.update({
            dates: true, month: true, year: true
          });
        }
      },
        100);
    }

    const targetNode = document.getElementById('calendar-instance');
    if (targetNode) {
      if (currentObserver) currentObserver.disconnect();
      currentObserver = new MutationObserver(() => applyModifiers());
      currentObserver.observe(targetNode, {
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