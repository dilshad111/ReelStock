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
    <div class="row mb-3 g-3">
      <div class="col-md-3">
        <label>Filter by Quality</label>
        <select v-model="selectedQuality" @change="fetchReport" class="form-control">
          <option value="">All Qualities</option>
          <option v-for="quality in qualities" :key="quality.id" :value="quality.id">{{ quality.quality }} ({{ quality.gsm_range }})</option>
        </select>
      </div>
      <div class="col-md-2">
        <label>Filter by Reel Size</label>
        <select v-model="selectedSize" @change="fetchReport" class="form-control">
          <option value="">All Sizes</option>
          <option v-for="size in sizes" :key="size.id" :value="size.id">{{ size.name }}</option>
        </select>
      </div>
      <div class="col-md-3 col-sm-4">
        <label>Balance Weight Min (kg)</label>
        <input v-model="balanceMin" type="number" min="0" step="0.01" class="form-control" placeholder="Min">
      </div>
      <div class="col-md-3 col-sm-4">
        <label>Balance Weight Max (kg)</label>
        <input v-model="balanceMax" type="number" min="0" step="0.01" class="form-control" placeholder="Max">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button @click="fetchReport" class="btn btn-primary w-100">Apply Filters</button>
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
            <a href="#" @click.prevent="showHistory(item.reel_no)" class="d-block text-decoration-none">
              <div>{{ getReelPrefix(item.reel_no) }}</div>
              <div>{{ getReelSerial(item.reel_no) }}</div>
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
        <tr class="table-info fw-bold">
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
      selectedQuality: '',
      selectedSize: '',
      balanceMin: '',
      balanceMax: ''
    };
  },
  mounted() {
    this.fetchQualities();
    this.fetchSizes();
    this.fetchReport();
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
      return `${Math.round(size)}”`;
    },
    getReelPrefix(reelNo) {
      if (!reelNo || reelNo.length <= 6) {
        return reelNo || '';
      }
      return reelNo.substring(0, 6);
    },
    getReelSerial(reelNo) {
      if (!reelNo || reelNo.length <= 6) {
        return '';
      }
      return reelNo.substring(6);
    },
    fetchQualities() {
      axios.get('/api/paper-qualities').then(response => {
        this.qualities = response.data;
      }).catch(error => {
        console.error('Error fetching qualities:', error);
      });
    },
    fetchSizes() {
      axios.get('/api/reel-receipts').then(response => {
        // Extract unique reel sizes from receipts (response.data is paginated)
        const receipts = response.data.data || response.data || [];
        const uniqueSizes = [...new Set(receipts.map(r => r.reel?.reel_size).filter(size => size))].sort((a, b) => {
          const numA = parseFloat(a);
          const numB = parseFloat(b);
          if (Number.isFinite(numA) && Number.isFinite(numB)) {
            return numA - numB;
          }
          return String(a).localeCompare(String(b));
        });
        // Convert to the expected format with id and name, and store the actual size
        this.sizes = uniqueSizes.map((size, index) => ({
          id: index + 1,
          name: size + ' inches',
          reel_size: size
        }));
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
        const selectedSizeObj = this.sizes.find(s => s.id == this.selectedSize);
        if (selectedSizeObj) {
          params.push('size=' + selectedSizeObj.reel_size);
        }
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
        this.report = response.data.filter(item => item.balance_weight > 0);
      }).catch(error => {
        console.error('Error fetching report:', error);
      });
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
      const size = this.sizes.find(s => s.reel_size == reelSize);
      return size ? size.name : reelSize + ' inches';
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
      if (this.balanceMin !== '') {
        filename += '_balance_min_' + this.balanceMin;
      }
      if (this.balanceMax !== '') {
        filename += '_balance_max_' + this.balanceMax;
      }
      if (!this.selectedQuality && !this.selectedSize) {
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
        const size = this.sizes.find(s => s.id === this.selectedSize);
        if (size) {
          filters.push(`Reel Size: ${size.name}`);
        }
      }
      if (this.balanceMin !== '' || this.balanceMax !== '') {
        filters.push(`Balance: ${this.balanceMin || 'any'} to ${this.balanceMax || 'any'} kg`);
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
                <img src="/reelStock/images/quality-cartons-logo.svg" alt="Quality Cartons Logo" class="logo">
              </div>
              <div class="company-info">
                <div class="company-name">QUALITY CARTONS (PVT.) LTD.</div>
                <div class="company-address">Plot# 46, Sector 24, Korangi Industrial Area Karachi</div>
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
            <h2>Reel Stock Report as on ${this.getTimestamp()}</h2>
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
        const prefix = reelNo.substring(0, 6);
        const number = reelNo.substring(6);
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
.table.table-striped.align-middle td,
.table.table-striped.align-middle th {
  padding: 4px 6px;
}

.table-sticky-header thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background-color: #f8f9fa;
  text-align: center;
  vertical-align: middle;
}
</style>
