<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-receipt"></i> Reel Receipt Report</h2>
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
    <div class="row gy-3 mb-3">
      <div class="col-md-3">
        <label>From Date</label>
        <input v-model="dateFrom" type="date" class="form-control">
      </div>
      <div class="col-md-3">
        <label>To Date</label>
        <input v-model="dateTo" type="date" class="form-control">
      </div>
      <div class="col-md-4">
        <label>Supplier</label>
        <select v-model="selectedSupplier" class="form-control">
          <option value="">All Suppliers</option>
          <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
            {{ supplier.supplier_id ? supplier.supplier_id + ' - ' : '' }}{{ supplier.name }}
          </option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button @click="fetchReport" class="btn btn-primary w-100">Apply Filters</button>
      </div>
    </div>
    <table v-if="report.length" class="table table-striped">
      <thead>
        <tr>
          <th>Receipt Date</th>
          <th>Reel No.</th>
          <th>Supplier</th>
          <th>Paper Quality</th>
          <th>Reel Size</th>
          <th>Weight (Kg)</th>
          <th v-if="canSeeAmounts">Rate (PKR/kg)</th>
          <th v-if="canSeeAmounts" class="text-end">Amount (PKR)</th>
          <th>QC Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in report" :key="item.id">
          <td>{{ formatDate(item.receiving_date) }}</td>
          <td>{{ item.reel_no || 'N/A' }}</td>
          <td>{{ item.supplier || 'N/A' }}</td>
          <td>{{ formatQuality(item) }}</td>
          <td>{{ formatReelSize(item.reel_size) }}</td>
          <td class="text-center">{{ formatWeight(item.weight) }}</td>
          <td v-if="canSeeAmounts">{{ formatNumber(item.rate_per_kg) }}</td>
          <td v-if="canSeeAmounts" class="text-end">{{ formatCurrency(item.amount) }}</td>
          <td>{{ item.qc_status || 'N/A' }}</td>
        </tr>
        <!-- Subtotal Row -->
        <tr class="table-info fw-bold">
          <td :colspan="canSeeAmounts ? 9 : 7">
            <strong>
              TOTAL Reels: {{ report.length }}&nbsp;&nbsp;&nbsp;&nbsp;
              Total Weight: {{ formatNumber(totalWeight) }} kg
              <span v-if="canSeeAmounts">&nbsp;&nbsp;&nbsp;&nbsp;Total Amount: {{ formatCurrency(totalAmount) }}</span>
            </strong>
          </td>
        </tr>
      </tbody>
    </table>
    <p v-else>No receipt data for selected dates.</p>
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
      dateFrom: '',
      dateTo: '',
      selectedSupplier: '',
      suppliers: [],
      report: [],
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      companyLogo: '',
      companySettingsLoaded: false
    };
  },
  mounted() {
    this.fetchSuppliers();
    this.fetchReport();
    this.fetchCompanySettings();
  },
  computed: {
    totalWeight() {
      return this.report.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0);
    },
    totalAmount() {
      return this.report.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
    }
  },
  methods: {
    async fetchCompanySettings(force = false) {
      if (this.companySettingsLoaded && !force) {
        return;
      }
      try {
        const { data } = await axios.get('/api/setup/settings');
        this.companyName = data.company_name || this.companyName;
        this.companyAddress = data.company_address || this.companyAddress;
        this.companyLogo = data.company_logo || '';
        this.companySettingsLoaded = true;
      } catch (error) {
        console.error('Error fetching company settings:', error);
      }
    },
    fetchSuppliers() {
      axios.get('/api/suppliers').then(response => {
        this.suppliers = response.data;
      }).catch(error => {
        console.error('Error fetching suppliers:', error);
      });
    },
    fetchReport() {
      let url = '/api/reports/reel-receipt';
      const params = [];
      if (this.dateFrom && this.dateTo) {
        params.push(`date_from=${this.dateFrom}`);
        params.push(`date_to=${this.dateTo}`);
      }
      if (this.selectedSupplier) {
        params.push(`supplier_id=${this.selectedSupplier}`);
      }
      if (params.length > 0) {
        url += `?${params.join('&')}`;
      }
      axios.get(url).then(response => {
        this.report = response.data;
      });
    },
    exportToExcel() {
      const csvContent = this.generateCSV();
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      let filename = `reel_receipt_${this.dateFrom || 'all'}_to_${this.dateTo || 'all'}`;
      if (this.selectedSupplier) {
        filename += `_supplier_${this.selectedSupplier}`;
      }
      filename += '.csv';
      link.setAttribute('href', url);
      link.setAttribute('download', filename);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    getCompanyLogoSrc() {
      if (this.companyLogo) {
        return `${window.location.origin}/storage/${this.companyLogo}`;
      }
      return '/reelStock/images/quality-cartons-logo.svg';
    },
    async exportToPDF() {
      await this.fetchCompanySettings();
      const logoSrc = this.getCompanyLogoSrc();
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Receipt Report - ${this.companyName}</title>
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
              th, td { border: 1px solid #000; padding: 4px 8px; vertical-align: top; border-top: 1px solid #000; color: #000; font-size: 12px; white-space: nowrap; }
              th { background-color: #f8f9fa; color: #000; border-color: #000; font-weight: 700; text-align: center; }
              tbody tr:nth-child(odd) td { background-color: rgba(0,0,0,.05); }
              .total-row { background-color: #cff4fc; color: #000; font-weight: bold; }
              .report-title { font-size: 20px; font-weight: bold; color: #000; margin-top: 10px; }
              .report-period { font-size: 14px; color: #000; margin-top: 5px; }
            </style>
          </head>
          <body>
            <div class="header">
              <div class="logo-section">
                <img src="${logoSrc}" alt="Company Logo" class="logo">
              </div>
              <div class="company-info">
                <div class="company-name">${this.companyName}</div>
                <div class="company-address">${this.companyAddress}</div>
                <div class="report-title">Reel Receipt Report</div>
                <div class="report-period">Period: ${this.dateFrom ? this.formatDate(this.dateFrom) : 'All'} to ${this.dateTo ? this.formatDate(this.dateTo) : 'All'}${this.selectedSupplier ? ' | Supplier: ' + this.getSupplierName(this.selectedSupplier) : ''}</div>
              </div>
            </div>
            ${this.generateHTMLTable()}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    async printTable() {
      await this.fetchCompanySettings();
      const logoSrc = this.getCompanyLogoSrc();
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Receipt Report - ${this.companyName}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #000; }
              .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; color: #000; }
              .logo-section { flex-shrink: 0; }
              .logo { width: 1in; height: 1in; }
              .company-info { flex-grow: 1; text-align: center; color: #000; }
              .company-name { font-size: 28px; font-weight: bold; color: #000; font-family: 'Georgia', serif; }
              .company-address { font-size: 14px; color: #000; font-family: 'Georgia', serif; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; color: #000; }
              th, td { border: 1px solid #000; padding: 4px 8px; vertical-align: top; border-top: 1px solid #000; color: #000; font-size: 12px; white-space: nowrap; }
              th { background-color: #f8f9fa; color: #000; border-color: #000; font-weight: 700; text-align: center; }
              tbody tr:nth-child(odd) td { background-color: rgba(0,0,0,.05); }
              .total-row { background-color: #cff4fc; color: #000; font-weight: bold; }
              .report-title { font-size: 20px; font-weight: bold; color: #000; margin-top: 10px; }
              .report-period { font-size: 14px; color: #000; margin-top: 5px; }
              @media print { body { margin: 0; } }
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
                <div class="report-title">Reel Receipt Report</div>
                <div class="report-period">Period: ${this.dateFrom ? this.formatDate(this.dateFrom) : 'All'} to ${this.dateTo ? this.formatDate(this.dateTo) : 'All'}${this.selectedSupplier ? ' | Supplier: ' + this.getSupplierName(this.selectedSupplier) : ''}</div>
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
      const headers = ['Receipt Date', 'Reel No.', 'Supplier', 'Paper Quality', 'Size', 'Weight (kg)', 'Rate (PKR/kg)', 'Amount (PKR)', 'QC Status'];
      let rows = [];
      for (let i = 0; i < this.report.length; i++) {
        const item = this.report[i];
        const row = [
          this.formatDate(item.receiving_date),
          item.reel_no || 'N/A',
          item.supplier || 'N/A',
          this.formatQuality(item),
          item.reel_size || 'N/A',
          this.formatNumber(item.weight, false)
        ];
        if (this.canSeeAmounts) {
          row.push(this.formatNumber(item.rate_per_kg, false));
          row.push(this.formatNumber(item.amount, false));
        }
        row.push(item.qc_status || 'N/A');
        rows.push(row);
      }

      // Add total row
      const totalRow = ['TOTAL', '', '', '', '', this.formatNumber(this.totalWeight, false)];
      if (this.canSeeAmounts) {
        totalRow.push('', this.formatNumber(this.totalAmount, false));
      }
      totalRow.push('');
      rows.push(totalRow);

      const csvHeaders = this.canSeeAmounts
        ? headers
        : ['Receipt Date', 'Reel No.', 'Supplier', 'Paper Quality', 'Size', 'Weight (kg)', 'QC Status'];

      const csvRows = [csvHeaders, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    generateHTMLTable() {
      const headers = this.canSeeAmounts
        ? '<th style="width: 50px;">Receipt Date</th><th style="width: 50px;">Reel No.</th><th style="width: 120px;">Supplier</th><th style="width: 150px;">Paper Quality</th><th style="width: 40px;">Size</th><th style="width: 80px;">Weight (kg)</th><th style="width: 40px;">Rate (PKR/kg)</th><th style="width: 80px;">Amount (PKR)</th><th style="width: 80px;">QC Status</th>'
        : '<th style="width: 50px;">Receipt Date</th><th style="width: 50px;">Reel No.</th><th style="width: 120px;">Supplier</th><th style="width: 150px;">Paper Quality</th><th style="width: 40px;">Size</th><th style="width: 80px;">Weight (kg)</th><th style="width: 80px;">QC Status</th>';

      return `<table>
        <thead>
          <tr>${headers}</tr>
        </thead>
        <tbody>
          ${this.report.map(item => `
            <tr>
              <td>${this.formatDate(item.receiving_date)}</td>
              <td>${item.reel_no || 'N/A'}</td>
              <td>${item.supplier || 'N/A'}</td>
              <td>${this.formatQuality(item)}</td>
              <td style="text-align: center;">${this.formatReelSize(item.reel_size)}</td>
              <td style="text-align: center;">${this.formatWeight(item.weight)}</td>
              ${this.canSeeAmounts ? `<td style="text-align: right;">${this.formatNumber(item.rate_per_kg)}</td><td style="text-align: right;">${this.formatCurrency(item.amount)}</td>` : ''}
              <td>${item.qc_status || 'N/A'}</td>
            </tr>
          `).join('')}
          <tr class="total-row">
            <td colspan="${this.canSeeAmounts ? 9 : 7}">
              <strong>
                TOTAL Reels: ${this.report.length}&nbsp;&nbsp;&nbsp;&nbsp;
                Total Weight: ${Math.round(this.totalWeight).toLocaleString()} kg
                ${this.canSeeAmounts ? '&nbsp;&nbsp;&nbsp;&nbsp;Total Amount: ' + this.formatCurrency(this.totalAmount) : ''}
              </strong>
            </td>
          </tr>
        </tbody>
      </table>`;
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) return 'N/A';
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    },
    formatNumber(value, withUnit = true) {
      const number = parseFloat(value);
      if (Number.isNaN(number)) {
        return withUnit ? '0.00' : '0.00';
      }
      const formatted = number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      return withUnit ? formatted : formatted;
    },
    formatQuality(item) {
      const quality = item.paper_quality || item.paperQuality || '';
      const gsm = item.paper_quality_gsm || item.gsm_range || '';
      if (!quality && !gsm) {
        return 'N/A';
      }
      if (quality && gsm) {
        return `${quality} ${gsm}`.trim();
      }
      return quality || gsm || 'N/A';
    },
    formatReelSize(value) {
      if (value === null || value === undefined || value === '') {
        return 'N/A';
      }
      const numeric = Number(value);
      if (Number.isNaN(numeric)) {
        return `${value}`;
      }
      return `${Math.round(numeric)}"`;
    },
    formatWeight(value) {
      if (value === null || value === undefined || value === '') {
        return '0';
      }
      const numeric = Number(value);
      if (Number.isNaN(numeric)) {
        return String(value);
      }
      return Math.round(numeric).toLocaleString('en-US');
    },
    formatCurrency(value) {
      const number = parseFloat(value);
      if (Number.isNaN(number)) {
        return '0.00';
      }
      return number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    getSupplierName(id) {
      const supplier = this.suppliers.find(s => String(s.id) === String(id));
      return supplier ? supplier.name : 'Unknown';
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
