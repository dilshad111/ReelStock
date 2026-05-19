<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-file-earmark-text"></i> Paper Quality Management</h2>
      <div class="d-flex gap-2">
        <button @click="printQualities" class="btn btn-outline-primary px-3">
          <i class="bi bi-printer-fill me-1"></i> Print Master
        </button>
        <button @click="showForm = !showForm" class="btn btn-primary shadow-sm px-3">
          <i class="bi bi-plus-circle-fill me-1"></i> Add Paper Quality
        </button>
      </div>
    </div>

    <div v-if="showForm" class="card mb-3 animate__animated animate__fadeIn">
      <div class="card-body">
        <h5>{{ editing ? 'Edit Paper Quality' : 'Add Paper Quality' }}</h5>
        <form @submit.prevent="saveQuality">
          <div class="row">
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label fw-bold">Quality Name</label>
                <input v-model="quality.quality" type="text" class="form-control" placeholder="e.g. Testliner" required>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label fw-bold">Item Code <span class="badge bg-info text-dark ms-1" style="font-size:0.65rem">Auto</span></label>
                <input v-model="quality.item_code" type="text" class="form-control font-monospace fw-bold" placeholder="Auto-generated" readonly style="background:#f0f4ff;border-color:#b6c5f7;color:#3b5bdb">
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label fw-bold">GSM Range</label>
                <input v-model="quality.gsm_range" type="text" class="form-control" placeholder="e.g. 100-150" required>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label fw-bold">Paper Color</label>
                <div class="input-group">
                  <select v-model="quality.paper_color_id" class="form-select">
                    <option :value="null">Select Color</option>
                    <option v-for="c in colors" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                  <button class="btn btn-outline-primary" type="button" @click="showColorPrompt" title="Add New Color">
                    <i class="bi bi-plus-lg"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <!-- GSM -->
            <div class="col-md-3">
              <div class="card bg-light mb-3">
                <div class="card-body p-2">
                  <label class="fw-bold small d-block mb-1 text-center">GSM</label>
                  <div class="row g-1">
                    <div class="col-6">
                      <input v-model="quality.min_gsm" type="number" step="0.01" class="form-control form-control-sm" placeholder="Min">
                    </div>
                    <div class="col-6">
                      <input v-model="quality.max_gsm" type="number" step="0.01" class="form-control form-control-sm" placeholder="Max">
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Bursting -->
            <div class="col-md-3">
              <div class="card bg-light mb-3">
                <div class="card-body p-2">
                  <label class="fw-bold small d-block mb-1 text-center">Bursting</label>
                  <div class="row g-1">
                    <div class="col-6">
                      <input v-model="quality.min_bursting" type="number" step="0.01" class="form-control form-control-sm" placeholder="Min">
                    </div>
                    <div class="col-6">
                      <input v-model="quality.max_bursting" type="number" step="0.01" class="form-control form-control-sm" placeholder="Max">
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Moisture -->
            <div class="col-md-3">
              <div class="card bg-light mb-3">
                <div class="card-body p-2">
                  <label class="fw-bold small d-block mb-1 text-center">Moisture</label>
                  <div class="row g-1">
                    <div class="col-6">
                      <input v-model="quality.min_moisture" type="number" step="0.01" class="form-control form-control-sm" placeholder="Min">
                    </div>
                    <div class="col-6">
                      <input v-model="quality.max_moisture" type="number" step="0.01" class="form-control form-control-sm" placeholder="Max">
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Cobb -->
            <div class="col-md-3">
              <div class="card bg-light mb-3">
                <div class="card-body p-2">
                  <label class="fw-bold small d-block mb-1 text-center">Cobb</label>
                  <div class="row g-1">
                    <div class="col-6">
                      <input v-model="quality.min_cobb" type="number" step="0.01" class="form-control form-control-sm" placeholder="Min">
                    </div>
                    <div class="col-6">
                      <input v-model="quality.max_cobb" type="number" step="0.01" class="form-control form-control-sm" placeholder="Max">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">{{ editing ? 'Update Quality' : 'Save Quality' }}</button>
            <button type="button" @click="cancel" class="btn btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-bordered table-sm small align-middle">
        <thead class="table-dark">
          <tr>
            <th rowspan="2" class="align-middle text-center">S#</th>
            <th rowspan="2" class="align-middle">Item Code</th>
            <th rowspan="2" class="align-middle">Quality</th>
            <th rowspan="2" class="align-middle">GSM Range</th>
            <th rowspan="2" class="align-middle">Color</th>
            <th colspan="2" class="text-center border-bottom-0">GSM</th>
            <th colspan="2" class="text-center border-bottom-0">Bursting</th>
            <th colspan="2" class="text-center border-bottom-0">Moisture</th>
            <th colspan="2" class="text-center border-bottom-0">Cobb</th>
            <th rowspan="2" class="align-middle text-center">Actions</th>
          </tr>
          <tr class="table-secondary text-dark">
            <th class="text-center small">Min</th>
            <th class="text-center small">Max</th>
            <th class="text-center small">Min</th>
            <th class="text-center small">Max</th>
            <th class="text-center small">Min</th>
            <th class="text-center small">Max</th>
            <th class="text-center small">Min</th>
            <th class="text-center small">Max</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(q, index) in qualities" :key="q.id">
            <td class="text-center text-muted small">{{ index + 1 }}</td>
            <td class="fw-bold text-primary">{{ q.item_code }}</td>
            <td>{{ q.quality }}</td>
            <td>{{ q.gsm_range }}</td>
            <td>
              <span v-if="q.paper_color" class="badge bg-info text-dark">{{ q.paper_color.name }}</span>
              <span v-else-if="q.paper_color_id" class="badge bg-secondary">Unknown Color</span>
              <span v-else>-</span>
            </td>
            <!-- GSM -->
            <td class="text-center">{{ q.min_gsm || '-' }}</td>
            <td class="text-center">{{ q.max_gsm || '-' }}</td>
            <!-- Burst -->
            <td class="text-center">{{ q.min_bursting || '-' }}</td>
            <td class="text-center">{{ q.max_bursting || '-' }}</td>
            <!-- Moist -->
            <td class="text-center">{{ q.min_moisture || '-' }}</td>
            <td class="text-center">{{ q.max_moisture || '-' }}</td>
            <!-- Cobb -->
            <td class="text-center">{{ q.min_cobb || '-' }}</td>
            <td class="text-center">{{ q.max_cobb || '-' }}</td>
            
            <td class="text-center">
              <div class="btn-group">
                <button @click="editQuality(q)" class="btn btn-sm btn-warning">
                  <i class="bi bi-pencil"></i>
                </button>
                <button @click="deleteQuality(q.id)" class="btn btn-sm btn-danger">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  props: ['user'],
  data() {
    return {
      qualities: [],
      colors: [],
      quality: { 
        quality: '', 
        item_code: '',
        gsm_range: '', 
        paper_color_id: null, 
        min_gsm: '', 
        max_gsm: '',
        min_bursting: '', 
        max_bursting: '',
        min_moisture: '',
        max_moisture: '', 
        min_cobb: '',
        max_cobb: '' 
      },
      showForm: false,
      editing: false,
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi'
    };
  },
  mounted() { if (this.user) this.setAuthAndFetch(); },
  watch: { 
    user(v) { if (v) this.setAuthAndFetch(); },
    'quality.quality'(newVal) {
      this.autoGenerateItemCode(newVal);
    }
  },
  methods: {
    setAuthAndFetch() {
      if (localStorage.getItem('token')) axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
      this.fetchQualities();
      this.fetchColors();
      this.fetchSettings();
    },
    fetchSettings() {
      axios.get('/api/setup/settings').then(response => {
        const data = response.data || {};
        if (data.company_name) this.companyName = data.company_name;
        if (data.company_address) this.companyAddress = data.company_address;
      }).catch(error => console.error('Error fetching settings:', error));
    },
    fetchQualities() { axios.get('/api/paper-qualities').then(r => { this.qualities = r.data; }); },
    fetchColors() { axios.get('/api/paper-colors').then(r => { this.colors = r.data; }); },
    autoGenerateItemCode(qualityName) {
      if (!qualityName || !qualityName.trim()) {
        this.quality.item_code = '';
        return;
      }
      // Build prefix from first letter of each word (up to 3 words)
      const words = qualityName.trim().split(/\s+/);
      let prefix = '';
      let count = 0;
      for (const word of words) {
        const clean = word.replace(/[^A-Za-z0-9]/g, '');
        if (clean) {
          prefix += clean.charAt(0).toUpperCase();
          count++;
          if (count >= 3) break;
        }
      }
      if (!prefix) {
        this.quality.item_code = '';
        return;
      }
      // Find the next available sequence number by checking existing qualities
      const currentId = this.quality.id || null;
      const existingCodes = this.qualities
        .filter(q => q.id !== currentId && q.item_code && q.item_code.startsWith(prefix))
        .map(q => {
          const numPart = q.item_code.substring(prefix.length);
          return parseInt(numPart, 10) || 0;
        });
      const nextNum = existingCodes.length > 0 ? Math.max(...existingCodes) + 1 : 1;
      this.quality.item_code = prefix + String(nextNum).padStart(3, '0');
    },
    showColorPrompt() {
      const name = prompt('Enter new color name:');
      if (name) {
        axios.post('/api/paper-colors', { name }).then(r => {
          this.fetchColors();
          this.quality.paper_color_id = r.data.id;
        }).catch(err => {
          alert(err.response?.data?.message || 'Error adding color');
        });
      }
    },
    saveQuality() {
      const action = this.editing
        ? axios.put(`/api/paper-qualities/${this.quality.id}`, this.quality)
        : axios.post('/api/paper-qualities', this.quality);
      action.then(() => { this.fetchQualities(); this.cancel(); });
    },
    editQuality(q) { 
      this.quality = { ...q }; 
      this.editing = true; 
      this.showForm = true; 
    },
    deleteQuality(id) { if (confirm('Are you sure?')) axios.delete(`/api/paper-qualities/${id}`).then(() => this.fetchQualities()); },
    cancel() {
      this.quality = { 
        quality: '', 
        item_code: '',
        gsm_range: '', 
        paper_color_id: null, 
        min_gsm: '', 
        max_gsm: '',
        min_bursting: '', 
        max_bursting: '',
        min_moisture: '',
        max_moisture: '', 
        min_cobb: '',
        max_cobb: '' 
      };
      this.showForm = false; this.editing = false;
    },
    printQualities() {
      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        alert('Please allow pop-ups to print.');
        return;
      }
      const now = new Date();
      const timestamp = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
      
      let tableRows = '';
      this.qualities.forEach((q, index) => {
        tableRows += `
          <tr>
            <td class="text-center">${index + 1}</td>
            <td class="item-code">${q.item_code || '-'}</td>
            <td class="quality-name">${q.quality}${q.gsm_range ? ' <span class="gsm-range">(' + q.gsm_range + ')</span>' : ''}</td>
            <td class="text-center">${q.min_gsm || '-'}</td>
            <td class="text-center">${q.max_gsm || '-'}</td>
            <td class="text-center">${q.min_bursting || '-'}</td>
            <td class="text-center">${q.max_bursting || '-'}</td>
            <td class="text-center">${q.min_moisture || '-'}</td>
            <td class="text-center">${q.max_moisture || '-'}</td>
            <td class="text-center">${q.min_cobb || '-'}</td>
            <td class="text-center">${q.max_cobb || '-'}</td>
          </tr>
        `;
      });

      printWindow.document.write(`
        <html>
          <head>
            <title>Paper Quality Master List - ${this.companyName}</title>
            <style>
              @page { size: A4 portrait; margin: 12mm 10mm; }
              * { box-sizing: border-box; margin: 0; padding: 0; }
              body { font-family: 'Segoe UI', Arial, sans-serif; color: #000; font-size: 9px; line-height: 1.3; padding: 0; }
              .report-page { width: 100%; max-width: 210mm; margin: 0 auto; padding: 15px; }

              .report-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 12px; }
              .company-name { font-size: 20px; font-weight: 800; color: #000; letter-spacing: 1px; }
              .company-address { font-size: 9px; color: #333; margin-top: 2px; }
              .report-title { font-size: 14px; font-weight: 700; color: #000; text-transform: uppercase; letter-spacing: 2px; margin-top: 6px; padding: 5px 0; }
              .print-date { font-size: 9px; color: #555; margin-top: 4px; }

              table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 8.5px; border: 1px solid #000; }
              th { background: #e5e7eb; color: #000; font-weight: 700; padding: 5px 4px; text-align: center; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.3px; border: 1px solid #000; }
              th.group-header { background: #d1d5db; border-bottom: 1px solid #000; }
              th.sub-header { background: #e5e7eb; font-size: 7px; border: 1px solid #000; }
              td { border: 1px solid #000; padding: 4px 5px; color: #000; }
              tbody tr:nth-child(even) { background: #f9fafb; }
              .text-center { text-align: center; }
              .fw-bold { font-weight: 700; }
              .item-code { font-weight: 700; color: #000; font-family: 'Consolas', monospace; }
              .quality-name { font-weight: 600; color: #000; }
              .gsm-range { color: #555; font-weight: 400; font-size: 8px; }
              .color-badge { background: #e5e7eb; color: #000; padding: 1px 6px; border-radius: 8px; font-size: 7.5px; font-weight: 600; }

              .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #000; display: flex; justify-content: space-between; font-size: 8px; color: #555; }
              .total-count { font-size: 9px; color: #000; font-weight: 600; margin-top: 8px; }

              @media print {
                body { padding: 0; }
                .report-page { padding: 0; max-width: 100%; }
              }
            </style>
          </head>
          <body>
            <div class="report-page">
              <div class="report-header">
                <div class="company-name">${this.companyName}</div>
                ${this.companyAddress ? '<div class="company-address">' + this.companyAddress + '</div>' : ''}
                <div class="report-title">Paper Quality Standards Master</div>
                <div class="print-date">Generated: ${timestamp}</div>
              </div>
              <table>
                <thead>
                  <tr>
                    <th rowspan="2" style="width:25px">S#</th>
                    <th rowspan="2" style="width:60px">Code</th>
                    <th rowspan="2">Quality Name</th>
                    <th colspan="2" class="group-header">GSM</th>
                    <th colspan="2" class="group-header">Bursting</th>
                    <th colspan="2" class="group-header">Moisture</th>
                    <th colspan="2" class="group-header">Cobb</th>
                  </tr>
                  <tr>
                    <th class="sub-header">Min</th><th class="sub-header">Max</th>
                    <th class="sub-header">Min</th><th class="sub-header">Max</th>
                    <th class="sub-header">Min</th><th class="sub-header">Max</th>
                    <th class="sub-header">Min</th><th class="sub-header">Max</th>
                  </tr>
                </thead>
                <tbody>
                  ${tableRows}
                </tbody>
              </table>
              <div class="total-count">Total Qualities: ${this.qualities.length}</div>
              <div class="footer">
                <span>Computer generated report — ${this.companyName}</span>
                <span>${timestamp}</span>
              </div>
            </div>
          </body>
        </html>
      `);
      printWindow.document.close();
      setTimeout(() => printWindow.print(), 300);
    }
  }
};
</script>

<style scoped>
.table th {
  vertical-align: middle;
}
.input-group .btn {
  z-index: 0;
}
</style>
