<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-bar-chart"></i> Closing reel stock as on {{ formatDateDMY(selectedDate) }}</h2>
      <div class="d-flex gap-2">
        <input type="date" v-model="selectedDate" @change="fetchReport" class="form-control" style="width: 25%;">
        <button @click="exportToExcel" class="btn btn-success btn-lg" :disabled="!pivotData.length">
          <i class="bi bi-file-earmark-excel"></i> Export Excel
        </button>
        <button @click="exportToPDF" class="btn btn-danger btn-lg" :disabled="!pivotData.length">
          <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
        <button @click="printTable" class="btn btn-secondary btn-lg" :disabled="!pivotData.length">
          <i class="bi bi-printer"></i> Print Table
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading closing reel stock report...</p>
    </div>

    <div v-else>
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Closing reel stock summary as on {{ formatDate(selectedDate) }}</h5>
        </div>
        <div class="card-body">
          <div v-if="pivotData.length === 0" class="text-muted">
            No stock data available for this date
          </div>
          <div v-else>
            <div class="table-responsive">
              <table class="table table-sm table-striped table-bordered closing-table">
                <colgroup>
                  <col style="width: 60%;">
                  <col v-for="size in reelSizes" :key="'col-' + size" :style="`width: ${sizeColumnWidth}%`">
                  <col style="width: 10%;">
                </colgroup>
                <thead class="table-dark">
                  <tr>
                    <th class="align-middle text-start">Paper Quality</th>
                    <th v-for="size in reelSizes" :key="size" class="text-center">{{ size }}</th>
                    <th class="text-center">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in pivotData.slice(0, -1)" :key="row.quality">
                    <td class="fw-bold" style="white-space: nowrap;">{{ row.quality }}</td>
                    <td v-for="size in reelSizes" :key="size" class="text-end">
                      {{ row[size] > 0 ? formatNumber(row[size]) : '-' }}
                    </td>
                    <td class="text-end fw-bold bg-light">{{ formatNumber(row.total) }}</td>
                  </tr>
                  <!-- Totals row -->
                  <tr class="table-info fw-bold">
                    <td class="fw-bold" style="white-space: nowrap;">TOTAL</td>
                    <td v-for="size in reelSizes" :key="size" class="text-end bg-light">
                      {{ formatNumber(pivotData[pivotData.length - 1][size]) }}
                    </td>
                    <td class="text-end fw-bold bg-primary text-white">{{ formatNumber(pivotData[pivotData.length - 1].total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: ['user'],
  data() {
    return {
      pivotData: [],
      selectedDate: new Date().toISOString().substr(0, 10), // Current date in YYYY-MM-DD format
      loading: false,
      reelSizes: [],
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      companyLogo: '',
      companySettingsLoaded: false
    };
  },
  mounted() {
    this.fetchReport();
    this.fetchCompanySettings();
  },
  computed: {
    sizeColumnWidth() {
      if (!Array.isArray(this.reelSizes) || this.reelSizes.length === 0) {
        return 0;
      }
      const availableWidth = 30; // Percentage (100 - 60 for first column - 10 for total)
      return availableWidth / this.reelSizes.length;
    },
    formattedTitle() {
      return `Closing reel stock as on ${this.formatDateDMY(this.selectedDate)}`;
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
    fetchReport() {
      this.loading = true;
      axios.get(`/api/reports/monthly-closing?date=${this.selectedDate}`)
        .then(response => {
          // API now returns pivot table data
          this.pivotData = response.data.pivot_data || [];
          this.reelSizes = response.data.reel_sizes || [];
          this.loading = false;
        })
        .catch(error => {
          console.error('Error fetching closing stock report:', error);
          this.loading = false;
          alert('Error loading closing stock report');
        });
    },
    exportToExcel() {
      const csvContent = this.generateCSV();
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `closing_reel_stock_${this.selectedDate}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    getCompanyLogoSrc() {
      if (this.companyLogo) {
        return `${window.location.origin}/storage/${this.companyLogo}`;
      }
      return '/images/quality-cartons-logo.svg';
    },
    async exportToPDF() {
      await this.fetchCompanySettings();
      const activeSizes = this.getActiveSizes();
      const logoSrc = this.getCompanyLogoSrc();
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>${this.formattedTitle} - ${this.companyName}</title>
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
              .report-title { font-size: 20px; font-weight: bold; color: #000; margin-top: 10px; }
              .report-period { font-size: 14px; color: #000; margin-top: 5px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; color: #000; }
              th, td { border: 1px solid #000; padding: 4px 8px; vertical-align: top; border-top: 1px solid #000; color: #000; font-size: 12px; white-space: nowrap; }
              th { background-color: #f8f9fa; color: #000; border-color: #000; font-weight: 700; text-align: center; }
              tbody tr:nth-child(odd) td { background-color: rgba(0,0,0,.05); }
              .total-row { background-color: #cff4fc; color: #000; font-weight: bold; }
              .text-center { text-align: center; }
              .text-end { text-align: right; }
              .fw-bold { font-weight: bold; }
              .table-info { background-color: #e3f2fd; }
              h2 { color: #333; text-align: center; }
              .report-info { text-align: center; margin-bottom: 20px; }
              @media print { body { margin: 0; } }
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
                <div class="report-title">${this.formattedTitle}</div>
                <div class="report-period">Closing Date: ${this.formatDateDMY(this.selectedDate)}</div>
              </div>
            </div>
            ${this.generateHTMLContent(activeSizes)}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    async printTable() {
      await this.fetchCompanySettings();
      const activeSizes = this.getActiveSizes();
      const logoSrc = this.getCompanyLogoSrc();
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>${this.formattedTitle} - ${this.companyName}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #000; }
              .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; color: #000; }
              .logo-section { flex-shrink: 0; }
              .logo { width: 1in; height: 1in; }
              .company-info { flex-grow: 1; text-align: center; color: #000; }
              .company-name { font-size: 28px; font-weight: bold; color: #000; font-family: 'Georgia', serif; }
              .company-address { font-size: 14px; color: #000; font-family: 'Georgia', serif; }
              .report-title { font-size: 20px; font-weight: bold; color: #000; margin-top: 10px; }
              .report-period { font-size: 14px; color: #000; margin-top: 5px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10px; }
              th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 11px; height: 20px; white-space: nowrap; }
              th { background-color: #f2f2f2; font-weight: bold; }
              .text-center { text-align: center; }
              .text-end { text-align: right; }
              .fw-bold { font-weight: bold; }
              .table-info { background-color: #e3f2fd; }
              h2 { color: #333; text-align: center; }
              .report-info { text-align: center; margin-bottom: 20px; }
              @media print { body { margin: 0; } @page { size: A4 landscape; margin: 3mm 5mm 3mm 5mm; } @page { @bottom-center { content: "Page " counter(page) " of " counter(pages); font-size: 10px; color: #000; } } }
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
                <div class="report-title">${this.formattedTitle}</div>
                <div class="report-period">Closing Date: ${this.formatDateDMY(this.selectedDate)}</div>
              </div>
            </div>
            ${this.generateHTMLContent(activeSizes)}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    generateCSV() {
      const activeSizes = this.getActiveSizes();
      const headers = ['Paper Quality', ...activeSizes, 'Total'];
      const rows = [];

      // Add data rows (excluding the totals row)
      this.pivotData.slice(0, -1).forEach(row => {
        const csvRow = [row.quality];
        activeSizes.forEach(size => {
          const value = Number(row[size]) || 0;
          csvRow.push(value > 0 ? this.formatNumber(value) : '0');
        });
        csvRow.push(this.formatNumber(Number(row.total) || 0));
        rows.push(csvRow);
      });

      // Add totals row
      const totalsRow = this.pivotData[this.pivotData.length - 1];
      const csvTotalsRow = ['TOTAL'];
      activeSizes.forEach(size => {
        const value = Number(totalsRow[size]) || 0;
        csvTotalsRow.push(this.formatNumber(value));
      });
      csvTotalsRow.push(this.formatNumber(Number(totalsRow.total) || 0));
      rows.push(csvTotalsRow);

      const csvRows = [headers, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    getActiveSizes() {
      if (!Array.isArray(this.reelSizes) || this.reelSizes.length === 0) {
        return [];
      }
      if (!Array.isArray(this.pivotData) || this.pivotData.length === 0) {
        return [];
      }
      const dataRows = this.pivotData.slice(0, -1);
      const sizesWithData = this.reelSizes.filter(size =>
        dataRows.some(row => {
          const value = Number(row[size]) || 0;
          return value > 0;
        })
      );
      return sizesWithData;
    },
    generateHTMLContent(activeSizes = null) {
      if (!this.pivotData.length) {
        return '<p class="text-muted">No stock data available for this date</p>';
      }
      const sizes = Array.isArray(activeSizes) && activeSizes.length ? activeSizes : this.getActiveSizes();
      const dataRows = this.pivotData.slice(0, -1);
      const totalsRow = this.pivotData[this.pivotData.length - 1] || {};

      if (!sizes.length) {
        if (!dataRows.length) {
          return '<p class="text-muted">No stock data available for this date</p>';
        }

        return `
          <table border="1" style="border-collapse: collapse; width: 100%; font-size: 8px;">
            <thead>
              <tr style="background-color: #f8f9fa;">
                <th style="padding: 8px; min-width: 300px;">Paper Quality</th>
                <th style="padding: 8px; min-width: 80px;">Total</th>
              </tr>
            </thead>
            <tbody>
              ${dataRows.map(row => `
                <tr>
                  <td style="padding: 6px; font-weight: bold;">${row.quality}</td>
                  <td style="text-align: right; padding: 6px; font-weight: bold; background-color: #f8f9fa;">${this.formatNumber(Number(row.total) || 0)}</td>
                </tr>
              `).join('')}
              <tr style="background-color: #e3f2fd; font-weight: bold;">
                <td style="padding: 6px; font-weight: bold;">TOTAL</td>
                <td style="text-align: right; padding: 6px; font-weight: bold; background-color: #007bff; color: white;">${this.formatNumber(Number(totalsRow.total) || 0)}</td>
              </tr>
            </tbody>
          </table>
        `;
      }

      return `
        <table border="1" style="border-collapse: collapse; width: 100%; font-size: 8px;">
          <thead>
            <tr style="background-color: #f8f9fa;">
              <th rowspan="2" style="padding: 2px;">Paper Quality</th>
              <th colspan="${sizes.length}" style="text-align: center; padding: 2px;">Reel Sizes</th>
              <th rowspan="2" style="padding: 2px;">Total</th>
            </tr>
            <tr style="background-color: #f8f9fa;">
              ${sizes.map(size => `<th style="text-align: center; padding: 2px; min-width: 25px;">${size}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${dataRows.map(row => `
              <tr>
                <td style="padding: 2px; font-weight: bold;">${row.quality}</td>
                ${sizes.map(size => {
                  const value = Number(row[size]) || 0;
                  return `<td style=\"text-align: right; padding: 2px;\">${value > 0 ? this.formatNumber(value) : '-'}</td>`;
                }).join('')}
                <td style="text-align: right; padding: 2px; font-weight: bold; background-color: #f8f9fa;">${this.formatNumber(Number(row.total) || 0)}</td>
              </tr>
            `).join('')}
            <tr style="background-color: #e3f2fd; font-weight: bold;">
              <td style="padding: 2px; font-weight: bold; white-space: nowrap;">TOTAL</td>
              ${sizes.map(size => {
                const value = Number(totalsRow[size]) || 0;
                return `<td style=\"text-align: right; padding: 2px; background-color: #f8f9fa;\">${this.formatNumber(value)}</td>`;
              }).join('')}
              <td style="text-align: right; padding: 2px; font-weight: bold; background-color: #007bff; color: white;">${this.formatNumber(Number(totalsRow.total) || 0)}</td>
            </tr>
          </tbody>
        </table>
      `;
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    },
    formatDateDMY(dateString) {
      const date = new Date(dateString);
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    },
    formatNumber(value) {
      const num = Number(value) || 0;
      return num.toLocaleString('en-US', { maximumFractionDigits: 0 });
    }
  }
};
</script>

<style scoped>
.month-section {
  margin-bottom: 30px;
}

.month-header {
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 5px;
  margin-bottom: 15px;
  border: 1px solid #dee2e6;
}

.month-title {
  margin: 0;
  font-size: 18px;
  color: #495057;
}

.month-summary {
  margin-top: 5px;
  font-size: 14px;
  color: #6c757d;
}

.table-responsive {
  max-height: 400px;
  overflow-y: auto;
}

.btn {
  line-height: 0.5;
  font-size: 12px;
  white-space: nowrap;
}
</style>
