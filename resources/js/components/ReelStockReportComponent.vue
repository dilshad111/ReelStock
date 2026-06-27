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
            <label class="small text-muted mb-1 ps-1">Location</label>
            <select v-model="selectedLocation" @change="handleLocationChange" class="form-select form-select-sm filter-control">
              <option value="">All</option>
              <option value="Warehouse">Warehouse</option>
              <option value="Factory">Factory</option>
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
          <div class="col-md-2 d-flex gap-1 justify-content-end">
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
          <th style="width: 20%; text-align: center; font-size: 0.75rem;">Supplier</th>
          <th style="width: 33%; text-align: center; font-size: 0.75rem;">Quality</th>
          <th style="width: 4%; text-align: center; font-size: 0.75rem;">Reel Size</th>
          <th style="width: 8%; text-align: center; font-size: 0.75rem;">Original Weight Kg</th>
          <th style="width: 7%; text-align: center; font-size: 0.75rem;">Consumed Weight Kg</th>
          <th style="width: 8%; text-align: center; font-size: 0.75rem;">Balance Weight Kg</th>
          <th v-if="canSeeAmounts" style="width: 5%; text-align: center; font-size: 0.75rem;">Amount PKR</th>
          <th style="width: 6%; text-align: center; font-size: 0.75rem;">Location</th>
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
          <td class="text-center">
            <span class="badge" :class="item.current_location === 'Factory' ? 'bg-warning text-dark' : 'bg-info text-dark'">
              {{ item.current_location }}
            </span>
          </td>
          <td class="text-center status-cell">{{ item.status }}</td>
        </tr>
        <!-- Subtotal Row -->
        <tr class="stock-total-row fw-bold">
          <td colspan="4" class="text-center">TOTAL</td>
          <td class="text-center">{{ formatWholeNumberWithSeparators(totalOriginalWeight) }}</td>
          <td class="text-center">{{ formatWholeNumberWithSeparators(totalConsumedWeight) }}</td>
          <td class="text-center">{{ formatWholeNumberWithSeparators(totalBalanceWeight) }}</td>
          <td v-if="canSeeAmounts" class="text-end">{{ formatAmount(totalAmount, false) }}</td>
          <td class="text-center" colspan="2">{{ report.length }}<br>reels</td>
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
      selectedLocation: '',
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
      if (this.selectedLocation) params.push('location=' + this.selectedLocation);
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
      if (this.selectedLocation) params.push('location=' + this.selectedLocation);
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
      if (this.selectedLocation) params.push('location=' + this.selectedLocation);
      
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
      if (this.selectedLocation) {
        params.push('location=' + this.selectedLocation);
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
    handleLocationChange() {
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
      this.selectedLocation = '';
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
      if (this.selectedLocation) {
        filters.push(`Location: ${this.selectedLocation}`);
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
      const headers = ['Reel No.', 'Supplier', 'Quality', 'Reel Size', 'Original Weight Kg', 'Consumed Weight Kg', 'Balance Weight Kg', 'Amount PKR', 'Location', 'Status'];
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
        row.push(item.current_location || 'Warehouse');
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
      totalRow.push(''); // Empty value for Location total cell
      totalRow.push(this.report.length + ' reels');
      rows.push(totalRow);

      const csvHeaders = this.canSeeAmounts
        ? headers
        : ['Reel No.', 'Supplier', 'Quality', 'Reel Size', 'Original Weight Kg', 'Consumed Weight Kg', 'Balance Weight Kg', 'Location', 'Status'];

      const csvRows = [csvHeaders, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    generateHTMLTable() {
      const headers = this.canSeeAmounts
        ? '<th>Reel No.</th><th>Supplier</th><th>Quality</th><th>Reel Size</th><th>Original Wt.</th><th>Consumed Wt.</th><th>Balance Wt.</th><th>Amount PKR</th><th>Location</th><th>Status</th>'
        : '<th>Reel No.</th><th>Supplier</th><th>Quality</th><th>Reel Size</th><th>Original Wt.</th><th>Consumed Wt.</th><th>Balance Wt.</th><th>Location</th><th>Status</th>';

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
            <td style="text-align: center;">${item.current_location || 'Warehouse'}</td>
            <td style="text-align: center;">${item.status}</td>
          </tr>`;
      }).join('');

      const totalRow = `
        <tr class="total-row">
          <td colspan="${this.canSeeAmounts ? 10 : 9}">
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

      const getTypeBadge = (type) => {
        switch (type) {
          case 'Receipt':
            return '<span class="badge badge-success">Receipt</span>';
          case 'Issue':
            return '<span class="badge badge-warning">Issue</span>';
          case 'Return':
            return '<span class="badge badge-info">Return</span>';
          case 'Return to Supplier':
            return '<span class="badge badge-danger">Supplier Return</span>';
          case 'Location Transfer':
            return '<span class="badge badge-purple">Transfer</span>';
          default:
            return `<span class="badge badge-secondary">${type}</span>`;
        }
      };

      const formatWeightChange = (weight) => {
        const val = parseFloat(weight);
        if (Number.isNaN(val)) return '<span class="weight-zero">-</span>';
        const formatted = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kg';
        if (val > 0) {
          return `<span class="weight-plus">+${formatted}</span>`;
        } else if (val < 0) {
          return `<span class="weight-minus">${formatted}</span>`;
        } else {
          return `<span class="weight-zero">${formatted}</span>`;
        }
      };

      const formatRunningBalance = (balance) => {
        const val = parseFloat(balance);
        if (Number.isNaN(val)) return '0.00 kg';
        return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kg';
      };

      const getQCBadge = (status) => {
        const s = (status || '').toLowerCase();
        if (s === 'pass' || s === 'approved') return '<span class="badge badge-success">Pass</span>';
        if (s === 'fail' || s === 'rejected') return '<span class="badge badge-danger">Fail</span>';
        if (s === 'pending') return '<span class="badge badge-warning">Pending</span>';
        return `<span class="badge badge-secondary">${status || 'N/A'}</span>`;
      };

      const historyRows = (history || []).map(h => `
          <tr>
            <td style="font-weight: 500;">${this.formatDate(h.date)}</td>
            <td>${getTypeBadge(h.type)}</td>
            <td style="color: #475569;">${h.details}</td>
            <td style="text-align: right;">${formatWeightChange(h.weight)}</td>
            <td style="text-align: right; font-weight: 600; color: #1e293b;">${formatRunningBalance(h.balance)}</td>
          </tr>
        `).join('');

      newWindow.document.write(`
        <html>
          <head>
            <title>Reel History - ${reel.reel_no}</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
            <style>
              :root {
                --primary: #6366f1;
                --primary-hover: #4f46e5;
                --bg-main: #f8fafc;
                --bg-card: #ffffff;
                --border: #e2e8f0;
                --text-main: #0f172a;
                --text-muted: #64748b;
                --success: #10b981;
                --success-bg: #ecfdf5;
                --warning: #f59e0b;
                --warning-bg: #fffbeb;
                --danger: #ef4444;
                --danger-bg: #fef2f2;
                --info: #0ea5e9;
                --info-bg: #f0f9ff;
                --purple: #8b5cf6;
                --purple-bg: #f5f3ff;
              }

              body {
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                background-color: var(--bg-main);
                color: var(--text-main);
                padding: 30px 20px;
                line-height: 1.5;
                margin: 0;
              }

              .container {
                max-width: 1200px;
                margin: 0 auto;
              }

              .header-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
                border-bottom: 1px solid var(--border);
                padding-bottom: 16px;
              }

              .title-section h1 {
                font-size: 24px;
                font-weight: 700;
                color: var(--text-main);
                margin: 0;
                letter-spacing: -0.02em;
              }

              .title-section p {
                color: var(--text-muted);
                margin: 4px 0 0 0;
                font-size: 14px;
              }

              .btn-print {
                background-color: var(--primary);
                color: white;
                border: none;
                padding: 10px 18px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
                box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.15);
              }

              .btn-print:hover {
                background-color: var(--primary-hover);
                transform: translateY(-1px);
              }

              .row-layout {
                display: flex;
                gap: 24px;
                margin-bottom: 24px;
              }

              .col-left {
                flex: 8;
                display: flex;
                flex-direction: column;
              }

              .col-right {
                flex: 4;
              }

              .card {
                background-color: var(--bg-card);
                border: 1px solid var(--border);
                border-radius: 12px;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
                padding: 24px;
                margin-bottom: 24px;
              }

              .card-title {
                font-size: 16px;
                font-weight: 700;
                margin: 0 0 16px 0;
                color: var(--text-main);
                display: flex;
                align-items: center;
                gap: 8px;
              }

              .grid-metadata {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
              }

              .meta-item {
                display: flex;
                flex-direction: column;
              }

              .meta-label {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--text-muted);
                font-weight: 600;
                margin-bottom: 4px;
              }

              .meta-value {
                font-size: 14px;
                font-weight: 600;
                color: var(--text-main);
              }

              .badge {
                display: inline-flex;
                align-items: center;
                padding: 4px 10px;
                border-radius: 9999px;
                font-size: 12px;
                font-weight: 600;
                text-transform: capitalize;
                width: fit-content;
              }

              .badge-success { background-color: var(--success-bg); color: var(--success); }
              .badge-warning { background-color: var(--warning-bg); color: var(--warning); }
              .badge-danger { background-color: var(--danger-bg); color: var(--danger); }
              .badge-info { background-color: var(--info-bg); color: var(--info); }
              .badge-purple { background-color: var(--purple-bg); color: var(--purple); }
              .badge-secondary { background-color: #f1f5f9; color: #475569; }

              .table-responsive {
                overflow-x: auto;
              }

              table.modern-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
              }

              table.modern-table th {
                padding: 12px 16px;
                background-color: #f8fafc;
                border-bottom: 1px solid var(--border);
                font-weight: 600;
                font-size: 12px;
                color: var(--text-muted);
                text-transform: uppercase;
                letter-spacing: 0.05em;
              }

              table.modern-table td {
                padding: 14px 16px;
                border-bottom: 1px solid var(--border);
                font-size: 14px;
                color: var(--text-main);
              }

              table.modern-table tr:hover td {
                background-color: #f8fafc;
              }

              table.modern-table tr:last-child td {
                border-bottom: none;
              }

              .weight-plus {
                color: var(--success);
                font-weight: 600;
              }

              .weight-minus {
                color: var(--danger);
                font-weight: 600;
              }

              .weight-zero {
                color: var(--text-muted);
                font-weight: 500;
              }

              @media (max-width: 992px) {
                .row-layout {
                  flex-direction: column;
                }
              }

              @media print {
                body {
                  background-color: white;
                  padding: 0;
                }
                .btn-print {
                  display: none;
                }
                .card {
                  border: 1px solid #000;
                  box-shadow: none;
                  margin-bottom: 20px;
                  page-break-inside: avoid;
                }
                table.modern-table th {
                  background-color: #f8fafc !important;
                  color: #000 !important;
                  border-bottom: 2px solid #000 !important;
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                }
                .badge {
                  border: 1px solid #ccc;
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                }
              }
            </style>
          </head>
          <body>
            <div class="container">
              <div class="header-bar">
                <div class="title-section">
                  <h1>Reel History Explorer</h1>
                  <p>Comprehensive transaction log for Reel <strong>${reel.reel_no}</strong></p>
                </div>
                <div>
                  <button onclick="window.print()" class="btn-print">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle;">
                      <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    Print Record
                  </button>
                </div>
              </div>

              <div class="row-layout">
                <div class="col-left">
                  <div class="card">
                    <div class="card-title">
                      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                      </svg>
                      Reel Identity & Specifications
                    </div>
                    <div class="grid-metadata">
                      <div class="meta-item">
                        <span class="meta-label">Reel Number</span>
                        <span class="meta-value" style="font-size: 16px; color: var(--primary);">${reel.reel_no}</span>
                      </div>
                      <div class="meta-item">
                        <span class="meta-label">Paper Quality</span>
                        <span class="meta-value">${reel.quality}</span>
                      </div>
                      <div class="meta-item">
                        <span class="meta-label">Reel Size</span>
                        <span class="meta-value">${this.formatReelSizeValue(reel.reel_size)}</span>
                      </div>
                      <div class="meta-item">
                        <span class="meta-label">Supplier Partner</span>
                        <span class="meta-value">${reel.supplier}</span>
                      </div>
                      <div class="meta-item">
                        <span class="meta-label">Original Weight</span>
                        <span class="meta-value">${parseFloat(reel.original_weight).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg</span>
                      </div>
                      <div class="meta-item">
                        <span class="meta-label">Current Balance</span>
                        <span class="meta-value" style="color: ${parseFloat(reel.current_balance) > 0 ? 'var(--success)' : 'var(--text-muted)'};">
                          ${parseFloat(reel.current_balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg
                        </span>
                      </div>
                      <div class="meta-item">
                        <span class="meta-label">Current Location</span>
                        <span class="meta-value">${reel.current_location || 'Warehouse'}</span>
                      </div>
                    </div>
                  </div>

                  <div class="card">
                    <div class="card-title">
                      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                      </svg>
                      Transaction Ledger
                    </div>
                    <div class="table-responsive">
                      <table class="modern-table">
                        <thead>
                          <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th style="text-align: right;">Weight Change</th>
                            <th style="text-align: right;">Running Balance</th>
                          </tr>
                        </thead>
                        <tbody>
                          ${historyRows || '<tr><td colspan="5" class="text-center" style="color: var(--text-muted); padding: 30px;">No transaction history found for this reel.</td></tr>'}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="col-right">
                  <div class="card">
                    <div class="card-title">
                      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-13.332 9-8.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                      </svg>
                      Quality Control (QC) Certificate
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                      <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-size: 13px;">Target GSM</span>
                        <span style="font-weight: 600;">${reel.gsm || 'N/A'}</span>
                      </div>
                      <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-size: 13px;">Bursting Strength</span>
                        <span style="font-weight: 600;">${reel.bursting_strength || 'N/A'}</span>
                      </div>
                      <div style="display: flex; justify-content: space-between; padding-top: 4px; align-items: center;">
                        <span style="color: var(--text-muted); font-size: 13px;">Approval Status</span>
                        <span>${getQCBadge(reel.qc_status)}</span>
                      </div>
                    </div>
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
