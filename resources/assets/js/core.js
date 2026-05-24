// core.js
(function() {
  // =========== State ===========
  const state = {
    currentUser: null,
    employees: [],
    currentRoute: '/employees',
    token: localStorage.getItem('telegram_token') || null
  };

  // =========== Konstanta ===========
  const API_BASE = window.API_BASE; // di-set dari view: app.url
  const APP_BASE = '/apps/shift';

  // =========== Fetch Helper ===========
  async function fetchAPI(url, options = {}) {
    const token = state.token;
    const headers = {
      'Accept': 'application/json',
      ...options.headers
    };
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    if (options.body && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
      ...options, headers
    });
    let data;
    try {
      data = await response.json();
    } catch (e) {
      data = null;
    }
    if (!response.ok) {
      const message = data?.message || data?.error || `HTTP ${response.status}`;
      const error = new Error(message);
      error.status = response.status;
      error.data = data;
      throw error;
    }
    return data;
  }

  // =========== Public API ===========
  window.AppCore = {
    state,
    fetchAPI,
    API_BASE,
    APP_BASE,
    getToken: () => state.token,
    setToken: (token) => {
      state.token = token;
      if (token) {
        localStorage.setItem('telegram_token', token);
      } else {
        localStorage.removeItem('telegram_token');
      }
    },
    // Fetch karyawan
    fetchEmployees: () => fetchAPI(`${API_BASE}/api/employees`),
    // Fetch satu karyawan
    fetchEmployee: (id) => fetchAPI(`${API_BASE}/api/employees/${id}`),
    // Simpan karyawan (POST/PUT)
    saveEmployee: (data, id = null) => {
      const url = id ? `${API_BASE}/api/employees/${id}`: `${API_BASE}/api/employees`;
      const method = id ? 'PUT': 'POST';
      return fetchAPI(url, {
        method,
        body: JSON.stringify(data)
      });
    },
    // Hapus karyawan
    deleteEmployee: (id) => fetchAPI(`${API_BASE}/api/employees/${id}`, {
      method: 'DELETE'
    }),
    // Fetch overrides
    fetchOverrides: (employeeId) => fetchAPI(`${API_BASE}/api/employees/${employeeId}/overrides`),
    // Tambah override
    addOverride: (employeeId, startDate) => fetchAPI(`${API_BASE}/api/employees/${employeeId}/overrides`, {
      method: 'POST',
      body: JSON.stringify({
        start_date: startDate
      })
    }),
    // Hapus override
    deleteOverride: (id) => fetchAPI(`${API_BASE}/api/overrides/${id}`, {
      method: 'DELETE'
    }),
    // Generate roster
    generateRoster: (start, end) => fetchAPI(`${API_BASE}/api/generate`, {
      method: 'POST',
      body: JSON.stringify({
        start_date: start, end_date: end
      })
    }),
    // Fetch schedules
    fetchSchedules: (start, end) => fetchAPI(`${API_BASE}/api/schedules?start_date=${start}&end_date=${end}`)
    .then(data => {
      // Potong date menjadi YYYY-MM-DD untuk hindari zona waktu
      return data.map(s => ({
        ...s,
        date: s.date?.substring(0, 10) || s.date
      }));
    }),
    // Export Excel (mengembalikan blob)
    exportExcel: async (start, end) => {
      const token = state.token;
      const response = await fetch(`${API_BASE}/api/export?start_date=${start}&end_date=${end}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/vnd.ms-excel'
        }
      });
      if (!response.ok) throw new Error('Export failed');
      return response.blob();
    }
  };
})();