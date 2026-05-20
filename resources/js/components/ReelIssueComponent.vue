<template>
  <div class="container-fluid small">
    <div class="mb-2">
      <h2 class="mb-0"><i class="bi bi-arrow-left-right"></i> Paper Reel Issue</h2>
    </div>
    <div class="mb-3">
      <button @click="printTable" class="btn btn-secondary">Print Tables</button>
    </div>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>Issue Reel</h5>
        <form @submit.prevent="saveIssue()">
          <div class="row">
            <div class="col-md-8">
              <div class="mb-3">
                <label>Reel No.</label>
                <input v-model="formData.reel_no" type="text" class="form-control" required @blur="fetchReel" :disabled="isEditing">
              </div>
              <div v-if="reel" class="mb-3">
                <p>Quality: {{ getQuality(reel) }}</p>
                <p>Supplier: {{ getSupplierName(reel) }}</p>
                <p>Size: {{ reel.reel_size }}</p>
                <p>Balance Weight: {{ reel.balance_weight }} kg</p>
              </div>
              <div v-if="reelHistory.length" class="reel-history card">
                <div class="card-body p-3">
                  <h6 class="card-title">Reel History</h6>
                  <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Type</th>
                          <th>Details</th>
                          <th class="text-end">Weight</th>
                          <th class="text-end">Balance</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(entry, idx) in reelHistory" :key="idx">
                          <td>{{ formatDate(entry.date) }}</td>
                          <td>{{ entry.type }}</td>
                          <td>{{ entry.details }}</td>
                          <td class="text-end">{{ entry.weight }} kg</td>
                          <td class="text-end">{{ entry.balance }} kg</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label>Issue Date</label>
                <input v-model="formData.issue_date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Quantity Issued (kg)</label>
                <input v-model.number="formData.quantity_issued" type="number" step="0.01" class="form-control" readonly>
              </div>
              <div class="mb-3">
                <label>Return to Stock (kg)</label>
                <input v-model.number="formData.return_to_stock_weight" @input="normalizeQuantities" type="number" step="0.01" min="0" class="form-control">
              </div>
              <div v-if="formData.return_to_stock_weight > 0" class="mb-3">
                <label>Return Location</label>
                <select v-model="formData.return_location" class="form-control" required>
                  <option value="">Select Location</option>
                  <option value="GoDown">GoDown</option>
                  <option value="Factory">Factory</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Consumed Weight (kg)</label>
                <input :value="formattedBalance" type="text" class="form-control" readonly>
              </div>
              <div class="mb-3">
                <label>Issued To</label>
                <input v-model="formData.issued_to" type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Remarks</label>
                <textarea v-model="formData.remarks" class="form-control"></textarea>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-success">Save</button>
          <button @click="cancel" class="btn btn-secondary">Cancel</button>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="row g-2 align-items-end mb-3">
          <div class="col-md-3 col-sm-6">
            <label class="form-label mb-1">Search Reel No.</label>
            <input v-model.trim="issueSearch" type="text" class="form-control form-control-sm" placeholder="Search..." @input="handleSearch">
          </div>
          <div class="col-md-3 col-sm-6">
            <label class="form-label mb-1">From Date</label>
            <input v-model="filters.date_from" type="date" class="form-control form-control-sm" @change="fetchIssues(1)">
          </div>
          <div class="col-md-3 col-sm-6">
            <label class="form-label mb-1">To Date</label>
            <input v-model="filters.date_to" type="date" class="form-control form-control-sm" @change="fetchIssues(1)">
          </div>
          <div class="col-md-3 col-sm-6 text-end">
            <button @click="clearFilters" class="btn btn-sm btn-outline-secondary w-100">Clear Filters</button>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h4 class="mb-0">Issues</h4>
          <button @click="toggleForm" class="btn btn-primary btn-sm">
            <i class="bi" :class="showForm ? 'bi-dash-circle' : 'bi-plus-circle'"></i>
            {{ showForm ? 'Close Form' : 'New Reel Issue' }}
          </button>
        </div>
        <table class="table table-striped table-sm text-nowrap">
          <thead>
            <tr class="table-dark">
              <th>Reel No.</th>
              <th>Quality</th>
              <th class="text-center">Size</th>
              <th class="text-center">Issue Date</th>
              <th class="text-end">Quantity Issued</th>
              <th class="text-end">Return to Stock</th>
              <th>Location</th>
              <th class="text-end">Net Consumed</th>
              <th>Issued To</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="i in filteredIssues" :key="i.id">
              <td>
                <a href="#" @click.prevent="viewHistory(i.reel.reel_no)" class="text-decoration-none">
                  {{ i.reel.reel_no }}
                </a>
              </td>
              <td>{{ getQuality(i.reel) }}</td>
              <td class="text-center">{{ formatReelSize(i.reel.reel_size) }}</td>
              <td class="text-center">{{ formatDate(i.issue_date) }}</td>
              <td class="text-end">{{ formatNumber(i.quantity_issued) }} kg</td>
              <td class="text-end">{{ formatNumber(i.return_to_stock_weight || 0) }} kg</td>
              <td>{{ i.return_location || '-' }}</td>
              <td class="text-end">{{ formatNumber(i.net_consumed_weight || 0) }} kg</td>
              <td>{{ i.issued_to }}</td>
              <td class="text-center">
                <button class="btn btn-xxs btn-warning me-1" @click="editIssue(i)">Edit</button>
                <button class="btn btn-xxs btn-danger" @click="deleteIssue(i)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-3" v-if="pagination.last_page > 1">
          <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm">
              <li class="page-item" :class="{ disabled: pagination.current_page == 1 }">
                <a class="page-link" href="#" @click.prevent="fetchIssues(1)">First</a>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page == 1 }">
                <a class="page-link" href="#" @click.prevent="fetchIssues(pagination.current_page - 1)">Previous</a>
              </li>
              <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page == pagination.current_page }">
                <a class="page-link" href="#" @click.prevent="fetchIssues(page)">{{ page }}</a>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page == pagination.last_page }">
                <a class="page-link" href="#" @click.prevent="fetchIssues(pagination.current_page + 1)">Next</a>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page == pagination.last_page }">
                <a class="page-link" href="#" @click.prevent="fetchIssues(pagination.last_page)">Last</a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { mapState, mapWritableState, mapActions } from 'pinia';
import { useReelIssueStore } from '../stores/useReelIssueStore';

export default {
  data() {
    return {
      reel: null,
      reelHistory: [],
      formData: {
        reel_no: '',
        issue_date: new Date().toISOString().substr(0,10),
        quantity_issued: '',
        return_to_stock_weight: 0,
        return_location: '',
        issued_to: 'Corrugation Plant',
        remarks: ''
      },
      showForm: false,
      editingIssueId: null,
      originalNetConsumed: 0,
      issueSearch: '',
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg',
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      searchTimeout: null,
    };
  },
  mounted() {
    this.fetchIssues();
    this.fetchSettings();
    this.setupRealtimeListener();
  },
  computed: {
    ...mapState(useReelIssueStore, ['issues', 'pagination', 'isLoading', 'error']),
    ...mapWritableState(useReelIssueStore, ['filters']),
    calculatedBalance() {
      const issued = Number(this.formData.quantity_issued) || 0;
      const returned = Number(this.formData.return_to_stock_weight) || 0;
      const balance = issued - returned;
      return balance < 0 ? 0 : balance;
    },
    formattedBalance() {
      return this.formatNumber(this.calculatedBalance);
    },
    isEditing() {
      return this.editingIssueId !== null;
    },
    filteredIssues() {
      return this.issues;
    },
    pages() {
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      let start = Math.max(1, current - 2);
      let end = Math.min(last, current + 2);
      let pages = [];
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    }
  },
  methods: {
    ...mapActions(useReelIssueStore, {
      fetchIssuesStore: 'fetchIssues',
      saveIssueStore: 'saveIssue',
      updateIssueStore: 'updateIssue',
      deleteIssueStore: 'deleteIssue',
      setFiltersStore: 'setFilters',
      clearFiltersStore: 'clearFilters',
      setupRealtimeListener: 'setupRealtimeListener'
    }),
    fetchIssues(page = 1) {
      if (this.issueSearch !== this.filters.search) {
        this.filters.search = this.issueSearch;
      }
      this.fetchIssuesStore(page);
    },
    clearFilters() {
      this.issueSearch = '';
      this.clearFiltersStore();
    },
    handleSearch() {
      if (this.searchTimeout) clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchIssues(1);
      }, 500);
    },
    fetchSettings() {
      axios.get('/api/setup/settings').then(response => {
        const settings = response.data || {};
        if (settings.company_name) this.companyName = settings.company_name;
        if (settings.company_address) this.companyAddress = settings.company_address;
        
        if (settings.company_logo) {
          this.companyLogo = window.location.origin + '/storage/' + settings.company_logo;
        } else {
          this.companyLogo = window.location.origin + '/images/quality-cartons-logo.svg';
        }
      }).catch(error => {
        console.error('Error fetching settings:', error);
      });
    },
    fetchReel() {
      const normalizedReelNo = this.normalizeReelNo(this.formData.reel_no);
      if (normalizedReelNo) {
        this.formData.reel_no = normalizedReelNo;
        axios.get(`/api/fetch-reel/${encodeURIComponent(normalizedReelNo)}`).then(response => {
          this.reel = response.data.data;
          const balance = parseFloat(this.reel.balance_weight) || 0;
          this.formData.quantity_issued = balance;
          this.formData.return_to_stock_weight = 0;
          this.originalNetConsumed = 0;
          this.loadReelHistory();
        }).catch(() => {
          alert('Reel not found or invalid');
          this.reel = null;
          this.reelHistory = [];
        });
      }
    },
    normalizeQuantities() {
      let issued = Number(this.formData.quantity_issued);
      let returned = Number(this.formData.return_to_stock_weight);

      if (!Number.isFinite(issued) || issued < 0) {
        issued = 0;
      }

      let maxIssued = Number(this.reel?.balance_weight);
      if (this.isEditing) {
        const allowance = Number(this.reel?.balance_weight) + Number(this.originalNetConsumed);
        maxIssued = Number.isFinite(allowance) ? allowance : issued;
      }
      if (!Number.isFinite(maxIssued) || maxIssued <= 0) {
        maxIssued = issued;
      }
      if (issued > maxIssued) {
        issued = maxIssued;
      }

      if (!Number.isFinite(returned) || returned < 0) {
        returned = 0;
      }

      if (returned > issued) {
        returned = issued;
      }

      this.formData.quantity_issued = issued;
      this.formData.return_to_stock_weight = returned;
    },
    loadReelHistory() {
      axios.get(`/api/reports/reel-stock/${this.formData.reel_no}/history`).then(response => {
        this.reelHistory = response.data.history || [];
      }).catch(error => {
        console.error('Error fetching reel history:', error);
        this.reelHistory = [];
      });
    },
    saveIssue() {
      const payload = { ...this.formData, reel_no: this.normalizeReelNo(this.formData.reel_no) };
      payload.quantity_issued = Number(payload.quantity_issued) || 0;
      payload.return_to_stock_weight = Number(payload.return_to_stock_weight) || 0;
      payload.net_consumed_weight = this.calculatedBalance;

      if (payload.return_to_stock_weight > payload.quantity_issued) {
        alert('Return to stock weight cannot exceed quantity issued.');
        return;
      }

      const action = this.editingIssueId
        ? this.updateIssueStore(this.editingIssueId, payload)
        : this.saveIssueStore(payload);

      action.then(() => {
        alert('Issue saved successfully.');
        this.cancel();
      }).catch(error => {
        const message = error.response?.data?.error || error.response?.data?.message || 'Failed to save issue.';
        alert(message);
      });
    },
    cancel() {
      this.showForm = false;
      this.resetForm();
    },
    resetForm() {
      this.formData = {
        reel_no: '',
        issue_date: new Date().toISOString().substr(0,10),
        quantity_issued: '',
        return_to_stock_weight: 0,
        return_location: '',
        issued_to: 'Corrugation Plant',
        remarks: ''
      };
      this.reel = null;
      this.reelHistory = [];
      this.editingIssueId = null;
      this.originalNetConsumed = 0;
    },
    toggleForm() {
      this.showForm = !this.showForm;
      if (!this.showForm) {
        this.resetForm();
      }
    },
    editIssue(issue) {
      if (!issue) return;
      this.editingIssueId = issue.id;
      this.originalNetConsumed = Number(issue.net_consumed_weight) || 0;
      this.formData = {
        reel_no: issue.reel?.reel_no || '',
        issue_date: issue.issue_date,
        quantity_issued: Number(issue.quantity_issued) || 0,
        return_to_stock_weight: Number(issue.return_to_stock_weight) || 0,
        return_location: issue.return_location || '',
        issued_to: issue.issued_to,
        remarks: issue.remarks || ''
      };
      this.reel = issue.reel ? { ...issue.reel } : null;
      this.showForm = true;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    deleteIssue(issue) {
      if (!issue || !issue.id) {
        return;
      }
      if (!confirm('Are you sure you want to delete this issue?')) {
        return;
      }
      this.deleteIssueStore(issue.id)
        .then(() => {
          alert('Issue deleted successfully.');
          if (this.editingIssueId === issue.id) {
            this.cancel();
          }
        })
        .catch(error => {
          const message = error.response?.data?.error || error.response?.data?.message || 'Failed to delete issue.';
          alert(message);
        });
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) return '-';
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    },
    normalizeReelNo(value) {
      if (!value) return '';
      let reelNo = String(value).trim().toUpperCase();
      if (reelNo === '') return '';
      reelNo = reelNo.replace(/\s+/g, '');
      if (reelNo.startsWith('RL')) {
        return reelNo;
      }
      return `RL${reelNo.replace(/^RL/i, '')}`;
    },
    getQuality(reel) {
      if (!reel) return 'N/A';
      const pq = reel.paperQuality || reel.paper_quality;
      if (!pq) return 'N/A';
      const name = pq.quality || pq.item_code || 'N/A';
      const gsm = pq.gsm_range ? ` (${pq.gsm_range})` : '';
      return `${name}${gsm}`;
    },
    getSupplierName(reel) {
      if (!reel || !reel.supplier) return 'N/A';
      return reel.supplier.name || 'N/A';
    },
    formatReelSize(size) {
      if (size === null || size === undefined || size === '') {
        return 'N/A';
      }
      const numericSize = Number(size);
      if (Number.isNaN(numericSize)) {
        return `${size}"`;
      }
      return `${numericSize.toFixed(2)}"`;
    },
    formatNumber(value) {
      const number = Number(value);
      if (!Number.isFinite(number)) {
        return '-';
      }
      return number.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    },
    viewHistory(reelNo) {
      if (!reelNo) return;
      axios.get(`/api/reports/reel-stock/${reelNo}/history`).then(response => {
        const reel = response.data.reel;
        const history = response.data.history || [];
        this.openHistoryWindow(reel, history);
      }).catch(error => {
        console.error('Error fetching history:', error);
        alert('Error loading reel history');
      });
    },
    openHistoryWindow(reel, history) {
      const historyWindow = window.open('', '_blank');
      if (!historyWindow) {
        alert('Unable to open history window. Please allow pop-ups for this site.');
        return;
      }
      historyWindow.document.write(this.generateHistoryHTML(reel, history));
      historyWindow.document.close();
    },
    escapeHtml(value) {
      if (value === null || value === undefined) return '';
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },
    formatWeight(value) {
      if (value === null || value === undefined) return '-';
      const num = Number(value);
      if (Number.isNaN(num)) return '-';
      return num >= 0 ? `${num.toFixed(2)}` : `(${Math.abs(num).toFixed(2)})`;
    },
    generateHistoryHTML(reel, history) {
      if (!reel) return '<html><body><p>No history available.</p></body></html>';

      const rows = history.length
        ? history.map(entry => `
          <tr>
            <td>${this.escapeHtml(this.formatDate(entry.date))}</td>
            <td>${this.escapeHtml(entry.type)}</td>
            <td>${this.escapeHtml(entry.details)}</td>
            <td style="text-align: right;">${this.escapeHtml(this.formatWeight(entry.weight))}</td>
            <td style="text-align: right;">${this.escapeHtml(this.formatWeight(entry.balance))}</td>
          </tr>
        `).join('')
        : '<tr><td colspan="5" style="text-align: center;">No history found.</td></tr>';

      return `
        <html>
          <head>
            <title>Reel History - ${this.escapeHtml(reel.reel_no)}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
              h1 { margin-bottom: 20px; color: #000; }
              h2 { margin-top: 30px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ccc; padding: 10px; font-size: 14px; }
              th { background: #f5f5f5; font-weight: bold; text-align: left; }
              .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 30px; }
              .summary-card { border: 1px solid #ddd; padding: 12px; border-radius: 4px; background: #fafafa; }
              .label { font-weight: bold; color: #555; font-size: 13px; margin-bottom: 4px; }
              .value { font-size: 1.1em; font-weight: bold; color: #111; }
              .meta { margin-top: 6px; color: #666; font-size: 0.9em; }
            </style>
          </head>
          <body>
            <h1>Reel History</h1>
            <div class="summary-grid">
              <div class="summary-card">
                <div class="label">Reel No.</div>
                <div class="value">${this.escapeHtml(reel.reel_no)}</div>
                <div class="meta">Supplier: ${this.escapeHtml(reel.supplier)}</div>
              </div>
              <div class="summary-card">
                <div class="label">Quality</div>
                <div class="value">${this.escapeHtml(reel.quality)}</div>
                <div class="meta">GSM: ${this.escapeHtml(reel.gsm ?? 'N/A')}</div>
              </div>
              <div class="summary-card">
                <div class="label">Reel Size</div>
                <div class="value">${this.escapeHtml(reel.reel_size ?? 'N/A')}"</div>
              </div>
              <div class="summary-card">
                <div class="label">Original Weight</div>
                <div class="value">${this.escapeHtml(this.formatWeight(reel.original_weight))} kg</div>
                <div class="meta">Current Balance: ${this.escapeHtml(this.formatWeight(reel.current_balance ?? 0))} kg</div>
              </div>
              <div class="summary-card">
                <div class="label">Bursting Strength</div>
                <div class="value">${this.escapeHtml(reel.bursting_strength ?? 'N/A')}</div>
              </div>
            </div>

            <h2>Consumption History</h2>
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Details</th>
                  <th style="text-align: right;">Weight (kg)</th>
                  <th style="text-align: right;">Balance (kg)</th>
                </tr>
              </thead>
              <tbody>
                ${rows}
              </tbody>
            </table>
          </body>
        </html>
      `;
    },
    printTable() {
      const printWindow = window.open('', '_blank');
      const logoHtml = this.companyLogo 
        ? `<div class="logo-section"><img src="${this.companyLogo}" alt="Logo" class="logo"></div>` 
        : '';
        
      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Issue and Return - Print</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
            <style>
              body { margin: 20px; font-family: Arial, sans-serif; }
              .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
              .logo-section { flex-shrink: 0; }
              .logo { width: 1in; height: 1in; object-fit: contain; }
              .company-info { flex-grow: 1; text-align: center; }
              .company-name { font-size: 24px; font-weight: bold; margin: 0; }
              .company-address { font-size: 14px; margin: 5px 0 0 0; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 12px; }
              th { background-color: #f2f2f2; font-weight: bold; }
              h4 { margin-top: 20px; text-align: center; }
              @media print { body { margin: 0; } .header { border-bottom: 2px solid #000; } }
            </style>
          </head>
          <body>
            <div class="header">
              ${logoHtml}
              <div class="company-info">
                <div class="company-name">${this.companyName}</div>
                <div class="company-address">${this.companyAddress}</div>
              </div>
            </div>
            
            <h4>Paper Reel Issue Report</h4>

            <table class="table">
              <thead>
                <tr>
                  <th>Reel No.</th>
                  <th>Quality</th>
                  <th>Issue Date</th>
                  <th>Quantity Issued</th>
                  <th>Return to Stock</th>
                  <th>Return Location</th>
                  <th>Net Consumed</th>
                  <th>Issued To</th>
                </tr>
              </thead>
              <tbody>
                ${this.issues.map(i => `
                  <tr>
                    <td>${i.reel.reel_no}</td>
                    <td>${this.getQuality(i.reel)}</td>
                    <td>${this.formatDate(i.issue_date)}</td>
                    <td>${this.formatNumber(i.quantity_issued)} kg</td>
                    <td>${this.formatNumber(i.return_to_stock_weight || 0)} kg</td>
                    <td>${i.return_location || '-'}</td>
                    <td>${this.formatNumber(i.net_consumed_weight || 0)} kg</td>
                    <td>${i.issued_to}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
            <div style="margin-top: 20px; font-size: 10px; text-align: right;">
              Printed on: ${new Date().toLocaleString()}
            </div>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
      }, 500);
    }
  }
};
</script>

<style scoped>
/* Use smaller font sizes */
.small {
  font-size: 0.85rem;
}
.table-sm td, .table-sm th {
  padding: 0.3rem;
  font-size: 0.8rem;
}
.text-nowrap {
  white-space: nowrap !important;
}
.btn-xxs {
  padding: 0.1rem 0.25rem;
  font-size: 0.75rem;
  line-height: 1;
  border-radius: 0.15rem;
}
.form-control-sm {
  height: calc(1.5em + 0.5rem + 2px);
  padding: 0.25rem 0.5rem;
  font-size: 0.8rem;
}
</style>
