<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Reel Balance Report</h2>
      <div class="d-flex gap-2">
        <input type="date" v-model="selectedDate" @change="fetchReport" class="form-control">
        <button @click="exportToExcel" class="btn btn-success" :disabled="!pivotData.length">
          <i class="bi bi-file-earmark-excel"></i> Export Excel
        </button>
        <button @click="exportToPDF" class="btn btn-danger" :disabled="!pivotData.length">
          <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading reel balance report...</p>
    </div>

    <div v-else>
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Available Stock as on {{ formatDate(selectedDate) }}</h5>
        </div>
        <div class="card-body">
          <div v-if="pivotData.length === 0" class="text-muted">
            No stock data available for this date
          </div>
          <div v-else>
            <div class="table-responsive">
              <table class="table table-sm table-striped table-bordered">
                <thead class="table-dark">
                  <tr>
                    <th class="align-middle" style="min-width: 250px;">Paper Quality</th>
                    <th v-for="size in reelSizes" :key="size" class="text-center" style="min-width: 60px;">{{ size }}</th>
                    <th class="text-center" style="min-width: 80px;">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in pivotData.slice(0, -1)" :key="row.quality">
                    <td class="fw-bold">{{ row.quality }}</td>
                    <td v-for="size in reelSizes" :key="size" class="text-end">
                      {{ row[size] > 0 ? row[size].toFixed(2) : '-' }}
                    </td>
                    <td class="text-end fw-bold bg-light">{{ row.total.toFixed(2) }}</td>
                  </tr>
                  <!-- Totals row -->
                  <tr class="table-info fw-bold">
                    <td class="fw-bold">TOTAL</td>
                    <td v-for="size in reelSizes" :key="size" class="text-end bg-light">
                      {{ pivotData[pivotData.length - 1][size].toFixed(2) }}
                    </td>
                    <td class="text-end fw-bold bg-primary text-white">{{ pivotData[pivotData.length - 1].total.toFixed(2) }}</td>
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
      reelSizes: []
    };
  },
  mounted() {
    this.fetchReport();
  },
  methods: {
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
          console.error('Error fetching reel balance report:', error);
          this.loading = false;
          alert('Error loading reel balance report');
        });
    },
    exportToExcel() {
      const csvContent = this.generateCSV();
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `reel_balance_report_${this.selectedDate}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    exportToPDF() {
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Balance Report - ${this.formatDate(this.selectedDate)}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
              .company-name { font-size: 18px; font-weight: bold; color: #2c3e50; }
              .company-address { font-size: 12px; color: #666; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ddd; padding: 4px; text-align: left; font-size: 10px; }
              th { background-color: #f2f2f2; font-weight: bold; }
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
              <div class="company-name">QUALITY CARTONS (PVT.) LTD.</div>
              <div class="company-address">Plot# 46, Sector 24, Korangi Industrial Area Karachi</div>
            </div>
            <h2>Reel Balance Report</h2>
            <div class="report-info">
              Available Stock as on ${this.formatDate(this.selectedDate)}
            </div>
            ${this.generateHTMLContent()}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    generateCSV() {
      const headers = ['Paper Quality', ...this.reelSizes, 'Total'];
      const rows = [];

      // Add data rows (excluding the totals row)
      this.pivotData.slice(0, -1).forEach(row => {
        const csvRow = [row.quality];
        this.reelSizes.forEach(size => {
          csvRow.push(row[size] > 0 ? row[size].toFixed(2) : '0');
        });
        csvRow.push(row.total.toFixed(2));
        rows.push(csvRow);
      });

      // Add totals row
      const totalsRow = this.pivotData[this.pivotData.length - 1];
      const csvTotalsRow = ['TOTAL'];
      this.reelSizes.forEach(size => {
        csvTotalsRow.push(totalsRow[size].toFixed(2));
      });
      csvTotalsRow.push(totalsRow.total.toFixed(2));
      rows.push(csvTotalsRow);

      const csvRows = [headers, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    generateHTMLContent() {
      if (this.pivotData.length === 0) {
        return '<p class="text-muted">No stock data available for this date</p>';
      }

      return `
        <table border="1" style="border-collapse: collapse; width: 100%; font-size: 10px;">
          <thead>
            <tr style="background-color: #f8f9fa;">
              <th rowspan="2" style="padding: 8px; min-width: 200px;">Paper Quality</th>
              <th colspan="${this.reelSizes.length}" style="text-align: center; padding: 8px;">Reel Sizes</th>
              <th rowspan="2" style="padding: 8px;">Total</th>
            </tr>
            <tr style="background-color: #f8f9fa;">
              ${this.reelSizes.map(size => `<th style="text-align: center; padding: 4px; min-width: 50px;">${size}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${this.pivotData.slice(0, -1).map(row => `
              <tr>
                <td style="padding: 6px; font-weight: bold;">${row.quality}</td>
                ${this.reelSizes.map(size => `
                  <td style="text-align: right; padding: 6px;">${row[size] > 0 ? row[size].toFixed(2) : '-'}</td>
                `).join('')}
                <td style="text-align: right; padding: 6px; font-weight: bold; background-color: #f8f9fa;">${row.total.toFixed(2)}</td>
              </tr>
            `).join('')}
            <tr style="background-color: #e3f2fd; font-weight: bold;">
              <td style="padding: 6px; font-weight: bold;">TOTAL</td>
              ${this.reelSizes.map(size => `
                <td style="text-align: right; padding: 6px; background-color: #f8f9fa;">${this.pivotData[this.pivotData.length - 1][size].toFixed(2)}</td>
              `).join('')}
              <td style="text-align: right; padding: 6px; font-weight: bold; background-color: #007bff; color: white;">${this.pivotData[this.pivotData.length - 1].total.toFixed(2)}</td>
            </tr>
          </tbody>
        </table>
      `;
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
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
</style>
