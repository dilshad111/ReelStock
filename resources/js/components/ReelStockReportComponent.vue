<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-boxes"></i> Reel Stock Report</h2>
      <div class="d-flex gap-2">
        <button @click="exportToExcel" class="btn btn-success btn-lg" :disabled="!report.length">
          <i class="bi bi-file-earmark-excel"></i> Export Excel
        </button>
        <button @click="exportToPDF" class="btn btn-danger btn-lg" :disabled="!report.length">
          <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
        <button @click="printTable" class="btn btn-secondary btn-lg" :disabled="!report.length">
          <i class="bi bi-printer"></i> Print Table
        </button>
      </div>
    </div>
    <div class="card shadow-sm border-0 mb-3" style="overflow: visible !important; position: relative; z-index: 1070;">
      <div class="card-body p-2" style="overflow: visible !important;">
        <div class="row g-2 align-items-end reel-filter-bar">
          <div class="col-md-2">
            <label class="small text-muted mb-1 ps-1">Quality</label>
            <div class="searchable-select-container">
              <input 
                v-model="qualitySearch" 
                type="text" 
                class="form-control form-control-sm filter-control" 
                placeholder="Search Quality..."
                @focus="showQualityDrop = true"
                @blur="handleBlur('quality')"
              >
              <div v-if="showQualityDrop" class="custom-dropdown shadow-sm">
                <div class="dropdown-item-custom small" @mousedown="selectQuality('')">All Qualities</div>
                <div v-for="q in filteredQualities" :key="q.id" class="dropdown-item-custom small" @mousedown="selectQuality(q)">
                  {{ q.quality }} {{ q.gsm_range ? '(' + q.gsm_range + ')' : '' }}
                </div>
                <div v-if="filteredQualities.length === 0" class="dropdown-item-custom disabled small text-muted">No matches</div>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <label class="small text-muted mb-1 ps-1">Supplier</label>
            <div class="searchable-select-container">
              <input 
                v-model="supplierSearch" 
                type="text" 
                class="form-control form-control-sm filter-control" 
                placeholder="Search Supplier..."
                @focus="showSupplierDrop = true"
                @blur="handleBlur('supplier')"
              >
              <div v-if="showSupplierDrop" class="custom-dropdown shadow-sm">
                <div class="dropdown-item-custom small" @mousedown="selectSupplier('')">All Suppliers</div>
                <div v-for="s in filteredSuppliers" :key="s.id" :value="s.id" class="dropdown-item-custom small" @mousedown="selectSupplier(s)">
                  {{ s.name }}
                </div>
                <div v-if="filteredSuppliers.length === 0" class="dropdown-item-custom disabled small text-muted">No matches</div>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <label class="small text-muted mb-1 ps-1">Status</label>
            <select v-model="selectedStatus" @change="handleStatusChange" class="form-select form-select-sm filter-control">
              <option value="">In Stock (All)</option>
              <option value="in_stock">In Stock (Fresh)</option>
              <option value="partially_used">Partially Used</option>
              <option value="fully_used">Fully Used</option>
            </select>
          </div>
          <div class="col-md-1">
            <label class="small text-muted mb-1 ps-1">Size</label>
            <select v-model="selectedSize" @change="handleSizeChange" class="form-select form-select-sm filter-control">
              <option value="">All</option>
              <option v-for="size in sizes" :key="size" :value="size">{{ size }}"</option>
            </select>
          </div>
          <div class="col-md-1">
            <label class="small text-muted mb-1 ps-1">Min Wt</label>
            <input v-model="balanceMin" type="number" step="0.01" class="form-control form-control-sm filter-control" placeholder="Min">
          </div>
          <div class="col-md-1">
            <label class="small text-muted mb-1 ps-1">Max Wt</label>
            <input v-model="balanceMax" type="number" step="0.01" class="form-control form-control-sm filter-control" placeholder="Max">
          </div>
          <div class="col-md-3 d-flex gap-1 justify-content-end">
            <button @click="fetchReport" class="btn btn-primary btn-sm px-3 filter-btn">Apply</button>
            <button @click="clearAllFilters" class="btn btn-clear-filters btn-sm filter-btn reel-clear-btn">Clear</button>
          </div>
        </div>
      </div>
    </div>
    <table v-if="report.length" class="table table-striped align-middle table-sticky-header">
      <thead class="table-light">
        <tr>
          <th style="width: 3%; text-align: center; font-size: 0.75rem;">Reel No.</th>
          <th style="width: 24%; text-align: center; font-size: 0.75rem;">Supplier</th>
          <th style="width: 39%; text-align: center; font-size: 0.75rem;">Quality</th>
          <th style="width: 4%; text-align: center; font-size: 0.75rem;">Reel Size</th>
          <th style="width: 8%; text-align: center; font-size: 0.75rem;">Original Weight Kg</th>
          <th style="width: 7%; text-align: center; font-size: 0.75rem;">Consumed Weight Kg</th>
          <th style="width: 8%; text-align: center; font-size: 0.75rem;">Balance Weight Kg</th>
          <th v-if="canSeeAmounts" style="width: 5%; text-align: center; font-size: 0.75rem;">Amount PKR</th>
          <th style="width: 2%; text-align: center; font-size: 0.75rem;">Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in report" :key="item.reel_no">
          <td class="text-center">
            <a href="#" @click.prevent="showHistory(item.reel_no)" class="reel-link">
              {{ item.reel_no }}
            </a>
          </td>
          <td class="supplier-cell">{{ item.supplier }}</td>
          <td>{{ item.quality }}</td>
          <td class="text-center">{{ formatReelSizeValue(item.reel_size) }}</td>
          <td class="text-center fw-bold">{{ formatWholeNumber(item.original_weight) }}</td>
          <td class="text-center fw-bold">{{ formatWholeNumber(item.consumed_weight) }}</td>
          <td class="text-center fw-bold">{{ formatWholeNumber(item.balance_weight) }}</td>
          <td v-if="canSeeAmounts" class="text-end">{{ formatAmount(item.amount, false) }}</td>
          <td class="text-center status-cell">{{ item.status }}</td>
        </tr>
        <!-- Subtotal Row -->
        <tr class="stock-total-row fw-bold">
          <td colspan="4" class="text-center">TOTAL</td>
          <td class="text-center">{{ formatWholeNumberWithSeparators(totalOriginalWeight) }}</td>
          <td class="text-center">{{ formatWholeNumberWithSeparators(totalConsumedWeight) }}</td>
          <td class="text-center">{{ formatWholeNumberWithSeparators(totalBalanceWeight) }}</td>
          <td v-if="canSeeAmounts" class="text-end">{{ formatAmount(totalAmount, false) }}</td>
          <td class="text-center">{{ report.length }}<br>reels</td>
        </tr>
      </tbody>
    </table>
    <p v-else>No reel stock data.</p>

  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    user: {
      type: Object,
      default: null
    },
    canSeeAmounts: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      report: [],
      qualities: [],
      sizes: [],
      suppliers: [],
      selectedQuality: '',
      selectedSize: '',
      selectedSupplier: '',
      selectedStatus: '',
      qualitySearch: '',
      supplierSearch: '',
      showQualityDrop: false,
      showSupplierDrop: false,
      balanceMin: '',
      balanceMax: '',
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg'
    };
  },
  mounted() {
    this.fetchQualities();
    this.fetchSizes();
    this.fetchSuppliers();
    this.fetchReport();
    this.fetchCompanySettings();
  },
  computed: {
    totalOriginalWeight() {
      return this.report.reduce((sum, item) => sum + (parseFloat(item.original_weight) || 0), 0);
    },
    totalConsumedWeight() {
      return this.report.reduce((sum, item) => sum + (parseFloat(item.consumed_weight) || 0), 0);
    },
    totalBalanceWeight() {
      return this.report.reduce((sum, item) => sum + (parseFloat(item.balance_weight) || 0), 0);
    },
    totalAmount() {
      return this.report.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
    },
    filteredQualities() {
      const search = (this.qualitySearch || '').toLowerCase();
      // If we have an exact match selected and not searching, show all
      if (!this.showQualityDrop && this.selectedQuality) return this.qualities;
      
      return this.qualities.filter(q => 
        (q.quality && q.quality.toLowerCase().includes(search)) || 
        (q.gsm_range && q.gsm_range.toLowerCase().includes(search))
      );
    },
    filteredSuppliers() {
      const search = (this.supplierSearch || '').toLowerCase();
      if (!this.showSupplierDrop && this.selectedSupplier) return this.suppliers;

      return this.suppliers.filter(s => 
        s.name && s.name.toLowerCase().includes(search)
      );
    }
  },
  methods: {
    formatAmount(value, withPrefix = true) {
      const amount = parseFloat(value);
      const formatted = Number.isFinite(amount)
        ? amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : '0.00';
      return withPrefix ? `PKR ${formatted}` : formatted;
    },
    fetchCompanySettings() {
      axios.get('/api/setup/settings').then(response => {
        const data = response.data || {};
        if (data.company_name) this.companyName = data.company_name;
        if (data.company_address) this.companyAddress = data.company_address;
        if (data.company_logo) {
          this.companyLogo = window.location.origin + '/storage/' + data.company_logo;
        } else {
          this.companyLogo = window.location.origin + '/images/quality-cartons-logo.svg';
        }
      }).catch(error => {
        console.error('Error fetching company settings:', error);
      });
    },
    formatWholeNumber(value) {
      const num = parseFloat(value);
      return Number.isFinite(num) ? Math.round(num).toString() : '0';
    },
    formatWholeNumberWithSeparators(value) {
      const num = parseFloat(value);
      return Number.isFinite(num)
        ? Math.round(num).toLocaleString('en-US', { maximumFractionDigits: 0 })
        : '0';
    },
    formatReelSizeValue(value) {
      const size = parseFloat(value);
      if (!Number.isFinite(size)) {
        return value ? `${value}”` : '';
      }
      return `${size.toFixed(2)}”`;
    },
    getReelPrefix(reelNo) {
      if (!reelNo) return '';
      const match = reelNo.match(/^[a-zA-Z]+/);
      return match ? match[0] : '';
    },
    getReelSerial(reelNo) {
      if (!reelNo) return '';
      const match = reelNo.match(/[0-9]+$/);
      return match ? match[0] : '';
    },
    fetchQualities() {
      let url = '/api/reports/reel-stock/qualities';
      const params = [];
      if (this.selectedSupplier) params.push('supplier=' + this.selectedSupplier);
      if (this.selectedSize) params.push('size=' + this.selectedSize);
      if (this.selectedStatus) params.push('status=' + this.selectedStatus);
      if (params.length > 0) url += '?' + params.join('&');

      axios.get(url).then(response => {
        this.qualities = response.data;
      }).catch(error => {
        console.error('Error fetching qualities:', error);
      });
    },
    fetchSuppliers() {
      let url = '/api/reports/reel-stock/suppliers';
      const params = [];
      if (this.selectedQuality) params.push('quality=' + this.selectedQuality);
      if (this.selectedSize) params.push('size=' + this.selectedSize);
      if (this.selectedStatus) params.push('status=' + this.selectedStatus);
      if (params.length > 0) url += '?' + params.join('&');

      axios.get(url).then(response => {
        this.suppliers = response.data;
      }).catch(error => {
        console.error('Error fetching suppliers:', error);
      });
    },
    fetchSizes() {
      let url = '/api/reports/reel-stock/sizes';
      const params = [];
      if (this.selectedSupplier) params.push('supplier=' + this.selectedSupplier);
      if (this.selectedQuality) params.push('quality=' + this.selectedQuality);
      if (this.selectedStatus) params.push('status=' + this.selectedStatus);
      
      if (params.length > 0) url += '?' + params.join('&');

      axios.get(url).then(response => {
        this.sizes = response.data || [];
      }).catch(error => {
        console.error('Error fetching sizes:', error);
      });
    },
    fetchReport() {
      let url = '/api/reports/reel-stock';
      const params = [];
      if (this.selectedQuality) {
        params.push('quality=' + this.selectedQuality);
      }
      if (this.selectedSize) {
        params.push('size=' + this.selectedSize);
      }
      if (this.selectedSupplier) {
        params.push('supplier=' + this.selectedSupplier);
      }
      if (this.selectedStatus) {
        params.push('status=' + this.selectedStatus);
      }
      if (this.balanceMin !== '') {
        params.push('balance_min=' + this.balanceMin);
      }
      if (this.balanceMax !== '') {
        params.push('balance_max=' + this.balanceMax);
      }
      if (params.length > 0) {
        url += '?' + params.join('&');
      }
      axios.get(url).then(response => {
        this.report = response.data;
      }).catch(error => {
        console.error('Error fetching report:', error);
      });
    },
    selectQuality(q) {
      if (!q) {
        this.selectedQuality = '';
        this.qualitySearch = '';
      } else {
        this.selectedQuality = q.id;
        this.qualitySearch = `${q.quality} (${q.gsm_range})`;
      }
      this.showQualityDrop = false;
      this.fetchSuppliers();
      this.fetchSizes();
      this.fetchReport();
    },
    selectSupplier(s) {
      if (!s) {
        this.selectedSupplier = '';
        this.supplierSearch = '';
      } else {
        this.selectedSupplier = s.id;
        this.supplierSearch = s.name;
      }
      this.showSupplierDrop = false;
      this.fetchQualities();
      this.fetchSizes();
      this.fetchReport();
    },
    handleSizeChange() {
      this.fetchQualities();
      this.fetchSuppliers();
      this.fetchReport();
    },
    handleStatusChange() {
      this.fetchQualities();
      this.fetchSuppliers();
      this.fetchSizes();
      this.fetchReport();
    },
    handleBlur(type) {
      // Use timeout to allow click event on dropdown items to fire first
      setTimeout(() => {
        if (type === 'quality') {
          this.showQualityDrop = false;
          // If search text doesn't match selected, reset or clear
          if (this.selectedQuality) {
            const q = this.qualities.find(item => item.id == this.selectedQuality);
            if (q) {
              this.qualitySearch = `${q.quality} (${q.gsm_range})`;
            }
          }
        } else if (type === 'supplier') {
          this.showSupplierDrop = false;
          if (this.selectedSupplier) {
            const s = this.suppliers.find(item => item.id == this.selectedSupplier);
            if (s) {
              this.supplierSearch = s.name;
            }
          }
        }
      }, 200);
    },
    clearAllFilters() {
      this.selectedQuality = '';
      this.qualitySearch = '';
      this.selectedSupplier = '';
      this.supplierSearch = '';
      this.selectedSize = '';
      this.selectedStatus = '';
      this.balanceMin = '';
      this.balanceMax = '';
      this.fetchQualities();
      this.fetchSuppliers();
      this.fetchSizes();
      this.fetchReport();
    },
    showHistory(reelNo) {
      axios.get(`/api/reports/reel-stock/${reelNo}/history`).then(response => {
        const reel = response.data.reel;
        const history = response.data.history;
        this.openHistoryInNewTab(reel, history);
      }).catch(error => {
        console.error('Error fetching history:', error);
        alert('Error loading reel history');
      });
    },
    formatDate(dateString) {
      if (!dateString) {
        return '';
      }
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) {
        return '';
      }
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    },
    getTimestamp() {
      const now = new Date();
      const day = String(now.getDate()).padStart(2, '0');
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const year = now.getFullYear();
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    },
    getSizeName(reelSize) {
      return reelSize + ' inches';
    },
    exportToExcel() {
      const csvContent = this.generateCSV();
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      let filename = 'reel_stock';
      if (this.selectedQuality) {
        filename += '_quality_' + this.selectedQuality;
      }
      if (this.selectedSize) {
        filename += '_reel_size_' + this.selectedSize;
      }
      if (this.selectedSupplier) {
        filename += '_supplier_' + this.selectedSupplier;
      }
      if (this.balanceMin !== '') {
        filename += '_balance_min_' + this.balanceMin;
      }
      if (this.balanceMax !== '') {
        filename += '_balance_max_' + this.balanceMax;
      }
      if (this.selectedStatus) {
        filename += '_status_' + this.selectedStatus;
      }
      if (!this.selectedQuality && !this.selectedSize && !this.selectedSupplier && !this.selectedStatus) {
        filename += '_all';
      }
      filename += '.csv';
      link.setAttribute('href', url);
      link.setAttribute('download', filename);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    exportToPDF() {
      const printWindow = window.open('', '_blank');
      const filters = [];
      if (this.selectedQuality) {
        const quality = this.qualities.find(q => q.id === this.selectedQuality);
        if (quality) {
          filters.push(`Quality: ${quality.quality} (${quality.gsm_range})`);
        }
      }
      if (this.selectedSize) {
        filters.push(`Reel Size: ${this.selectedSize} inches`);
      }
      if (this.selectedSupplier) {
        const supplier = this.suppliers.find(s => s.id === this.selectedSupplier);
        if (supplier) {
          filters.push(`Supplier: ${supplier.name}`);
        }
      }
      if (this.balanceMin !== '' || this.balanceMax !== '') {
        filters.push(`Balance: ${this.balanceMin || 'any'} to ${this.balanceMax || 'any'} kg`);
      }
      if (this.selectedStatus) {
        const statusMap = {
          'in_stock': 'In Stock (Fresh)',
          'partially_used': 'Partially Used',
          'fully_used': 'Fully Used'
        };
        filters.push(`Status: ${statusMap[this.selectedStatus] || this.selectedStatus}`);
      }

      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Stock Report - Quality Cartons</title>
            <style>
              @page { size: A4 landscape; margin: 3mm 5mm 3mm 5mm;
                @bottom-center { content: "Page " counter(page) " of " counter(pages); font-size: 10px; color: #000; } }
              body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #000; }
              .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; color: #000; }
              .logo-section { flex-shrink: 0; }
              .logo { width: 1in; height: 1in; }
              .company-info { flex-grow: 1; text-align: center; color: #000; }
              .company-name { font-size: 28px; font-weight: bold; color: #000; font-family: 'Georgia', serif; }
              .company-address { font-size: 14px; color: #000; font-family: 'Georgia', serif; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; color: #000; }
              th, td { border: 1px solid #000; padding: 4px 8px; vertical-align: top; color: #000; font-size: 12px; white-space: nowrap; }
              th { background-color: #f8f9fa; color: #000; border-color: #000; font-weight: 700; text-align: center; font-size: 13.2px; }
              tbody tr:nth-child(odd) td { background-color: rgba(0,0,0,.05); }
              .total-row { background-color: #cff4fc; color: #000; font-weight: bold; }
              .report-title { font-size: 20px; font-weight: bold; color: #000; margin-top: 10px; }
              .report-period { font-size: 14px; color: #000; margin-top: 5px; }
            </style>
          </head>
          <body>
            <div class="header">
              <div class="logo-section">
                <img src="${this.companyLogo}" alt="Company Logo" class="logo">
              </div>
              <div class="company-info">
                <div class="company-name">${this.companyName}</div>
                <div class="company-address">${this.companyAddress}</div>
                <div class="report-title">Reel Stock Report</div>
                <div class="report-period">As on: ${this.getTimestamp()}${filters.length > 0 ? ' | ' + filters.join(' | ') : ''}</div>
              </div>
            </div>
            ${this.generateHTMLTable()}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    printTable() {
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Stock Report - Print</title>
            <style>
              @page { size: A4 landscape; margin: 3mm 5mm 3mm 5mm; @bottom-center { content: "Page " counter(page) " of " counter(pages); font-size: 10px; color: #000; } }
              body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #000; }
              h2 { color: #333; text-align: center; margin-bottom: 12px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; color: #000; }
              th, td { border: 1px solid #000; padding: 4px 8px; vertical-align: top; color: #000; font-size: 12px; white-space: nowrap; }
              th { background-color: #f8f9fa; text-align: center; font-weight: bold; color: #000; font-size: 13.2px; }
              tbody tr:nth-child(odd) td { background-color: rgba(0,0,0,.05); }
              .total-row { background-color: #cff4fc; font-weight: bold; color: #000; }
              .report-info { text-align: center; margin-top: 8px; font-size: 14px; }
              @media print { body { margin: 0; } }
            </style>
          </head>
          <body>
            <div class="header">
               <div class="logo-section">
                <img src="${this.companyLogo}" alt="Company Logo" class="logo">
              </div>
              <div class="company-info">
                <div class="company-name">${this.companyName}</div>
                <div class="company-address">${this.companyAddress}</div>
                <div class="report-title">Reel Stock Report</div>
                <div class="report-period">As on: ${this.getTimestamp()}</div>
              </div>
            </div>
            ${this.generateHTMLTable()}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    generateCSV() {
      const headers = ['Reel No.', 'Supplier', 'Quality', 'Reel Size', 'Original Weight Kg', 'Consumed Weight Kg', 'Balance Weight Kg', 'Amount PKR', 'Status'];
      const rows = this.report.map(item => {
        const row = [
          item.reel_no,
          item.supplier,
          item.quality,
          this.formatReelSizeValue(item.reel_size),
          this.formatWholeNumber(item.original_weight),
          this.formatWholeNumber(item.consumed_weight),
          this.formatWholeNumber(item.balance_weight)
        ];
        if (this.canSeeAmounts) {
          row.push(this.formatAmount(item.amount, false));
        }
        row.push(item.status);
        return row;
      });

      // Add total row
      const totalRow = [
        'TOTAL',
        '',
        '',
        '',
        this.formatWholeNumber(this.totalOriginalWeight),
        this.formatWholeNumber(this.totalConsumedWeight),
        this.formatWholeNumber(this.totalBalanceWeight)
      ];
      if (this.canSeeAmounts) {
        totalRow.push(this.formatAmount(this.totalAmount, false));
      }
      totalRow.push(this.report.length + ' reels');
      rows.push(totalRow);

      const csvHeaders = this.canSeeAmounts
        ? headers
        : ['Reel No.', 'Supplier', 'Quality', 'Reel Size', 'Original Weight Kg', 'Consumed Weight Kg', 'Balance Weight Kg', 'Status'];

      const csvRows = [csvHeaders, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    generateHTMLTable() {
      const headers = this.canSeeAmounts
        ? '<th>Reel No.</th><th>Supplier</th><th>Quality</th><th>Reel Size</th><th>Original Wt.</th><th>Consumed Wt.</th><th>Balance Wt.</th><th>Amount PKR</th><th>Status</th>'
        : '<th>Reel No.</th><th>Supplier</th><th>Quality</th><th>Reel Size</th><th>Original Wt.</th><th>Consumed Wt.</th><th>Balance Wt.</th><th>Status</th>';

      const rows = this.report.map(item => {
        const reelNo = item.reel_no;
        const prefixMatch = reelNo.match(/^[a-zA-Z]+/);
        const prefix = prefixMatch ? prefixMatch[0] : '';
        const numberMatch = reelNo.match(/[0-9]+$/);
        const number = numberMatch ? numberMatch[0] : '';
        const reelDisplay = number ? `${prefix}${number}` : prefix;
        const amountCell = this.canSeeAmounts
          ? `<td style="text-align: right;">${this.formatAmount(item.amount, false)}</td>`
          : '';
        return `<tr>
            <td style="text-align: center;">${reelDisplay}</td>
            <td>${item.supplier}</td>
            <td>${item.quality}</td>
            <td style="text-align: center;">${this.formatReelSizeValue(item.reel_size)}</td>
            <td style="text-align: center; font-weight: 700;">${this.formatWholeNumber(item.original_weight)}</td>
            <td style="text-align: center; font-weight: 700;">${this.formatWholeNumber(item.consumed_weight)}</td>
            <td style="text-align: center; font-weight: 700;">${this.formatWholeNumber(item.balance_weight)}</td>
            ${amountCell}
            <td style="text-align: center;">${item.status}</td>
          </tr>`;
      }).join('');

      const totalRow = `
        <tr class="total-row">
          <td colspan="${this.canSeeAmounts ? 9 : 8}">
            <strong>
              TOTAL Reels: ${this.report.length}&nbsp;&nbsp;&nbsp;&nbsp;
              Total Original Wt: ${this.formatWholeNumberWithSeparators(this.totalOriginalWeight)} kg&nbsp;&nbsp;&nbsp;&nbsp;
              Total Consumed Wt: ${this.formatWholeNumberWithSeparators(this.totalConsumedWeight)} kg&nbsp;&nbsp;&nbsp;&nbsp;
              Total Balance Wt: ${this.formatWholeNumberWithSeparators(this.totalBalanceWeight)} kg
              ${this.canSeeAmounts ? `&nbsp;&nbsp;&nbsp;&nbsp;Total Amount: ${this.formatAmount(this.totalAmount, false)}` : ''}
            </strong>
          </td>
        </tr>`;

      return `<table>
          <thead>
            <tr>${headers}</tr>
          </thead>
          <tbody>
            ${rows}
            ${totalRow}
          </tbody>
        </table>`;
    },
    openHistoryInNewTab(reel, history) {
      if (!reel) {
        return;
      }
      const newWindow = window.open('', '_blank');
      if (!newWindow) {
        alert('Please allow pop-ups to open the reel history.');
        return;
      }
      const historyRows = (history || []).map(h => `
          <tr>
            <td>${this.formatDate(h.date)}</td>
            <td>${h.type}</td>
            <td>${h.details}</td>
            <td>${h.weight} kg</td>
            <td>${h.balance} kg</td>
          </tr>
        `).join('');

      newWindow.document.write(`
        <html>
          <head>
            <title>Reel History - ${reel.reel_no}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" />
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              .reel-metadata { margin-bottom: 20px; }
              .reel-metadata dt { font-weight: 600; }
              .qc-section { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
              .qc-section h5 { margin-top: 0; color: #333; }
              @media print {
                @page { margin: 5mm; }
                body { margin: 0; }
              }
            </style>
          </head>
          <body>
            <div class="container">
              <h2 class="mb-4">Reel History - ${reel.reel_no}</h2>
              <div class="row">
                <div class="col-md-8">
                  <dl class="row reel-metadata">
                    <dt class="col-sm-4">Reel No.</dt>
                    <dd class="col-sm-8">${reel.reel_no}</dd>
                    <dt class="col-sm-4">Reel Size</dt>
                    <dd class="col-sm-8">${this.formatReelSizeValue(reel.reel_size)}</dd>
                    <dt class="col-sm-4">Quality</dt>
                    <dd class="col-sm-8">${reel.quality}</dd>
                    <dt class="col-sm-4">Supplier</dt>
                    <dd class="col-sm-8">${reel.supplier}</dd>
                    <dt class="col-sm-4">Original Weight</dt>
                    <dd class="col-sm-8">${reel.original_weight} kg</dd>
                    <dt class="col-sm-4">Current Balance</dt>
                    <dd class="col-sm-8">${reel.current_balance} kg</dd>
                  </dl>
                  <h4>Transaction History</h4>
                  <table class="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Weight Change</th>
                        <th>Running Balance</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${historyRows || '<tr><td colspan="5" class="text-center">No history found.</td></tr>'}
                    </tbody>
                  </table>
                </div>
                <div class="col-md-4">
                  <div class="qc-section">
                    <h5>QC Record</h5>
                    <dl class="row">
                      <dt class="col-sm-6">GSM</dt>
                      <dd class="col-sm-6">${reel.gsm || 'N/A'}</dd>
                      <dt class="col-sm-6">Bursting Strength</dt>
                      <dd class="col-sm-6">${reel.bursting_strength || 'N/A'}</dd>
                      <dt class="col-sm-6">QC Status</dt>
                      <dd class="col-sm-6">${reel.qc_status || 'N/A'}</dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
          </body>
        </html>
      `);
      newWindow.document.close();
      newWindow.focus();
    },
    destroyCharts() {
      // Chart cleanup method for beforeUnmount
    }
  },
  beforeUnmount() {
    this.destroyCharts();
  }
};
</script>

<style scoped>
.searchable-select-container {
  position: relative;
  z-index: 1060;
}

.custom-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 2000;
  background: white;
  border: 1px solid #ced4da;
  border-radius: 4px;
  max-height: 250px;
  overflow-y: auto;
  margin-top: 2px;
}

.dropdown-item-custom {
  padding: 6px 12px;
  cursor: pointer;
  border-bottom: 1px solid #f1f1f1;
  color: #1e293b;
}

.dropdown-item-custom:hover {
  background-color: #f8f9fa;
  color: #6366f1;
}

.dropdown-item-custom:last-child {
  border-bottom: none;
}

.container {
  color: #1e293b;
  max-width: 100% !important;
  padding: 0 25px;
}

h2 {
  font-weight: 800;
  color: #1e293b;
  letter-spacing: -0.5px;
}

.table.table-striped.align-middle td {
  padding: 3px 8px;
  color: #1e293b;
  font-weight: 500;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13.5px;
}

.table.table-striped.align-middle th {
  padding: 8px 8px;
  background-color: #f8fafc;
  color: #475569;
  font-weight: 800;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #e2e8f0;
}

.reel-link {
  text-decoration: none;
  font-weight: 700;
  color: #6366f1;
  display: block;
}

.reel-link:hover {
  text-decoration: underline;
}

.table-sticky-header thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background-color: #f8fafc;
  text-align: center;
  vertical-align: middle;
}

.table.table-striped.align-middle .stock-total-row > td {
  --bs-table-bg: #e8f0ff !important;
  --bs-table-color: #0f172a !important;
  --bs-table-striped-bg: #e8f0ff !important;
  --bs-table-striped-color: #0f172a !important;
  background-color: #e8f0ff !important;
  box-shadow: inset 0 0 0 9999px #e8f0ff !important;
  color: #0f172a !important;
  border-top: 2px solid #93a4c8 !important;
  padding: 8px !important;
}

:global([data-theme="dark"] .table.table-striped.align-middle tbody .stock-total-row > td) {
  --bs-table-bg: #2f3f63 !important;
  --bs-table-color: #ffffff !important;
  --bs-table-striped-bg: #2f3f63 !important;
  --bs-table-striped-color: #ffffff !important;
  background-color: #2f3f63 !important;
  box-shadow: inset 0 0 0 9999px #2f3f63 !important;
  color: #ffffff !important;
  border-top-color: #7f91bf !important;
  text-shadow: none !important;
}

:global([data-theme="dark"] .table.table-striped.align-middle tbody .stock-total-row > td *) {
  color: #ffffff !important;
  text-shadow: none !important;
}

.status-cell {
  font-weight: 700 !important;
  font-size: 12px !important;
}

.reel-filter-bar .filter-control,
.reel-filter-bar .filter-btn {
  height: 40px !important;
  min-height: 40px !important;
}

.reel-filter-bar .filter-btn {
  padding-top: 0 !important;
  padding-bottom: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}

:global([data-theme="dark"] .reel-clear-btn),
:global([data-theme="dark"] .reel-clear-btn:hover),
:global([data-theme="dark"] .reel-clear-btn:focus),
:global([data-theme="dark"] .reel-clear-btn:active) {
  background: linear-gradient(135deg, #fde047 0%, #f59e0b 100%) !important;
  border: 1px solid #facc15 !important;
  color: #0f172a !important;
  font-weight: 800 !important;
  box-shadow: 0 6px 16px rgba(245, 158, 11, 0.35) !important;
  opacity: 1 !important;
  visibility: visible !important;
}
</style>
