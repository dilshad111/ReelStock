<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-graph-up"></i> Monthly Consumption Report</h2>
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
    <div class="row mb-3">
      <div class="col-md-3">
        <label>From Date</label>
        <input ref="fromDateInput" v-model="dateFrom" type="date" class="form-control" :max="todayIso" @blur="validateDateFilters(false)">
        <small v-if="errors.dateFrom" class="text-danger">{{ errors.dateFrom }}</small>
      </div>
      <div class="col-md-3">
        <label>To Date</label>
        <input ref="toDateInput" v-model="dateTo" type="date" class="form-control" :max="todayIso" @blur="validateDateFilters(false)">
        <small v-if="errors.dateTo" class="text-danger">{{ errors.dateTo }}</small>
      </div>
      <div class="col-md-3">
        <label>Quality Filter</label>
        <input v-model="qualityFilter" type="text" class="form-control" placeholder="Filter by quality">
      </div>
      <div class="col-md-3">
        <label>&nbsp;</label>
        <button @click="fetchReport" class="btn btn-primary form-control">Generate Report</button>
      </div>
    </div>
    <table v-if="report.length" class="table table-striped">
      <thead>
        <tr>
          <th>Paper Quality with GSM</th>
          <th class="text-center">Size</th>
          <th class="text-center">No of Reels Used</th>
          <th class="text-center">Weight Kg Used</th>
          <th v-if="canSeeAmounts" class="text-end">Consumption Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in report" :key="item.paper_quality_with_gsm + item.reel_size">
          <td>{{ item.paper_quality_with_gsm }}</td>
          <td class="text-center">{{ formatReelSize(item.reel_size) }}</td>
          <td class="text-center fw-bold">{{ formatInteger(item.no_of_reels_used) }}</td>
          <td class="text-center">{{ formatInteger(item.weight_kg_used) }}</td>
          <td v-if="canSeeAmounts" class="text-end">{{ formatAmount(item.consumption_amount_pkr) }}</td>
        </tr>
        <!-- Subtotal Row -->
        <tr class="table-info fw-bold">
          <td><strong>TOTAL</strong></td>
          <td></td>
          <td class="text-center fw-bold">{{ formatInteger(totalReelsUsed) }}</td>
          <td class="text-center">{{ formatInteger(totalWeightUsed) }}</td>
          <td v-if="canSeeAmounts" class="text-end">{{ formatAmount(totalAmount) }}</td>
        </tr>
      </tbody>
    </table>
    <p v-else>No data for selected period.</p>
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
      qualityFilter: '',
      report: [],
      errors: { dateFrom: '', dateTo: '' },
      todayIso: new Date().toISOString().split('T')[0],
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg'
    };
  },
  mounted() {
    // Set default date range to current month
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    this.dateFrom = firstDay.toISOString().split('T')[0];
    this.dateTo = lastDay.toISOString().split('T')[0];
    
    this.fetchReport();
    this.fetchCompanySettings();
  },
  methods: {
    parseIsoDateStrict(value) {
      if (!value || typeof value !== 'string') return null;
      const match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (!match) return null;
      const year = Number(match[1]);
      const month = Number(match[2]);
      const day = Number(match[3]);
      const date = new Date(Date.UTC(year, month - 1, day));
      if (
        date.getUTCFullYear() !== year ||
        date.getUTCMonth() + 1 !== month ||
        date.getUTCDate() !== day
      ) {
        return null;
      }
      return date;
    },
    validateDateFilters(showAlert = true) {
      this.errors = { dateFrom: '', dateTo: '' };

      const raise = (field, message) => {
        this.errors[field] = message;
        if (showAlert) alert(message);
        this.$nextTick(() => {
          if (field === 'dateFrom' && this.$refs.fromDateInput) this.$refs.fromDateInput.focus();
          if (field === 'dateTo' && this.$refs.toDateInput) this.$refs.toDateInput.focus();
        });
      };

      if (!this.dateFrom && !this.dateTo) {
        raise('dateFrom', 'Please select both From Date and To Date.');
        return false;
      }
      if (!this.dateFrom) {
        raise('dateFrom', 'From Date is required.');
        return false;
      }
      if (!this.dateTo) {
        raise('dateTo', 'To Date is required.');
        return false;
      }

      if (this.dateFrom) {
        const parsedFrom = this.parseIsoDateStrict(this.dateFrom);
        if (!parsedFrom) {
          raise('dateFrom', 'Invalid date selected. Please choose a valid calendar date.');
          return false;
        }
      }

      if (this.dateTo) {
        const parsedTo = this.parseIsoDateStrict(this.dateTo);
        if (!parsedTo) {
          raise('dateTo', 'Invalid date selected. Please choose a valid calendar date.');
          return false;
        }
      }

      const parsedFrom = this.parseIsoDateStrict(this.dateFrom);
      const parsedTo = this.parseIsoDateStrict(this.dateTo);
      const parsedToday = this.parseIsoDateStrict(this.todayIso);

      if (parsedFrom && parsedToday && parsedFrom.getTime() > parsedToday.getTime()) {
        raise('dateFrom', 'Future dates are not allowed.');
        return false;
      }

      if (parsedTo && parsedToday && parsedTo.getTime() > parsedToday.getTime()) {
        raise('dateTo', 'Future dates are not allowed.');
        return false;
      }

      if (this.dateFrom && this.dateTo) {
        if (parsedFrom && parsedTo && parsedFrom.getTime() > parsedTo.getTime()) {
          raise('dateTo', 'From Date cannot be greater than To Date.');
          return false;
        }

        const rangeDays = Math.floor((parsedTo.getTime() - parsedFrom.getTime()) / (24 * 60 * 60 * 1000));
        if (rangeDays > 365) {
          raise('dateTo', 'Date range cannot exceed 365 days.');
          return false;
        }
      }

      return true;
    },
    fetchReport() {
      if (!this.validateDateFilters()) {
        return;
      }

      let url = '/api/reports/monthly-consumption';
      const params = [];
      
      if (this.dateFrom) params.push(`date_from=${this.dateFrom}`);
      if (this.dateTo) params.push(`date_to=${this.dateTo}`);
      if (this.qualityFilter) params.push(`quality=${this.qualityFilter}`);
      
      if (params.length > 0) {
        url += '?' + params.join('&');
      }
      
      axios.get(url).then(response => {
        this.report = response.data;
      }).catch(error => {
        console.error('Error fetching report:', error);
        this.report = [];
      });
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
    exportToExcel() {
      const csvContent = this.generateCSV();
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `monthly_consumption_${this.dateFrom || 'all'}_to_${this.dateTo || 'all'}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    exportToPDF() {
      const printWindow = window.open('', '_blank');
      const amountColumn = this.canSeeAmounts
        ? '<th style="width: 21%;">Consumption Amount (PKR)</th>'
        : '';
      printWindow.document.write(`
        <html>
          <head>
            <title>Monthly Consumption Report - Quality Cartons</title>
            <style>
              @page { size: A4 portrait; margin: 3mm 5mm 3mm 12mm; 
                @bottom-center { content: "Page " counter(page) " of " counter(pages); font-size: 10px; color: #000; } }
              body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #000; }
              .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; color: #000; }
              .logo-section { flex-shrink: 0; }
              .logo { width: 1in; height: 1in; }
              .company-info { flex-grow: 1; text-align: center; color: #000; }
              .company-name { font-size: 28px; font-weight: bold; color: #000; font-family: 'Georgia', serif; }
              .company-address { font-size: 14px; color: #000; font-family: 'Georgia', serif; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; color: #000; }
              th, td { border: 1px solid #ddd; padding: 8px; text-align: left; color: #000; }
              th { background-color: #f2f2f2; text-align: center; font-weight: bold; color: #000; }
              .total-row { background-color: #e3f2fd; font-weight: bold; color: #000; }
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
                <div class="report-title">Monthly Consumption Report</div>
                <div class="report-period">Period: ${this.formatDate(this.dateFrom) || 'All'} to ${this.formatDate(this.dateTo) || 'All'}</div>
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
            <title>Monthly Consumption Report - Print</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
              th { background-color: #f2f2f2; }
              .total-row { background-color: #e3f2fd; font-weight: bold; }
              h2 { color: #333; text-align: center; }
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
                <div class="report-title">Monthly Consumption Report</div>
                <div class="report-period">Period: ${this.dateFrom || 'All'} to ${this.dateTo || 'All'}</div>
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
      const headers = this.canSeeAmounts
        ? ['Paper Quality with GSM', 'Size', 'No of Reels Used', 'Weight Kg Used', 'Consumption Amount']
        : ['Paper Quality with GSM', 'Size', 'No of Reels Used', 'Weight Kg Used'];
      const rows = this.report.map(item => [
        item.paper_quality_with_gsm,
        this.formatReelSize(item.reel_size),
        this.toInteger(item.no_of_reels_used),
        this.toInteger(item.weight_kg_used),
        ...(this.canSeeAmounts ? [this.toAmount(item.consumption_amount_pkr)] : [])
      ]);
      
      // Add total row
      const totalRow = ['TOTAL', '', this.toInteger(this.totalReelsUsed), this.toInteger(this.totalWeightUsed)];
      if (this.canSeeAmounts) {
        totalRow.push(this.toAmount(this.totalAmount));
      }
      rows.push(totalRow);

      const csvRows = [headers, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    generateHTMLTable() {
      const amountHeader = this.canSeeAmounts
        ? '<th style="width: 21%; text-align: right;">Consumption Amount</th>'
        : '';
      return `
        <table>
          <thead>
            <tr>
              <th style="width: 35%;">Paper Quality with GSM</th>
              <th style="width: 10%;">Size</th>
              <th style="width: 16%;">No of Reels Used</th>
              <th style="width: 18%;">Weight Kg Used</th>
              ${amountHeader}
            </tr>
          </thead>
          <tbody>
            ${this.report.map(item => `
              <tr>
                <td>${item.paper_quality_with_gsm}</td>
                <td style="text-align: center;">${this.formatReelSize(item.reel_size)}</td>
                <td style="text-align: center; font-weight: bold;">${this.formatInteger(item.no_of_reels_used)}</td>
                <td style="text-align: center;">${this.formatInteger(item.weight_kg_used)}</td>
                ${this.canSeeAmounts ? `<td style="text-align: right;">${this.formatAmount(item.consumption_amount_pkr)}</td>` : ''}
              </tr>
            `).join('')}
            <tr class="total-row">
              <td><strong>TOTAL</strong></td>
              <td></td>
              <td style="text-align: center;"><strong>${this.formatInteger(this.totalReelsUsed)}</strong></td>
              <td style="text-align: center;"><strong>${this.formatInteger(this.totalWeightUsed)}</strong></td>
              ${this.canSeeAmounts ? `<td style="text-align: right;"><strong>${this.formatAmount(this.totalAmount)}</strong></td>` : ''}
            </tr>
          </tbody>
        </table>
      `;
    },
    formatDate(dateString) {
      if (!dateString) return null;
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) return null;
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
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
    toInteger(value) {
      const num = Number(value);
      if (!Number.isFinite(num)) {
        return 0;
      }
      return Math.round(num);
    },
    toAmount(value) {
      const num = Number(value);
      if (!Number.isFinite(num)) {
        return 0;
      }
      return Number(num.toFixed(2));
    },
    formatInteger(value) {
      const integer = this.toInteger(value);
      return integer.toLocaleString('en-US');
    },
    formatAmount(value) {
      const amount = this.toAmount(value);
      return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  },
  computed: {
    totalReelsUsed() {
      return this.report.reduce((sum, item) => sum + (parseInt(item.no_of_reels_used) || 0), 0);
    },
    totalWeightUsed() {
      return this.report.reduce((sum, item) => sum + (parseFloat(item.weight_kg_used) || 0), 0);
    },
    totalAmount() {
      return this.report.reduce((sum, item) => sum + (parseFloat(item.consumption_amount_pkr) || 0), 0);
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
