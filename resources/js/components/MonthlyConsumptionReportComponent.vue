<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Monthly Consumption Report</h2>
      <div class="d-flex gap-2">
        <button @click="exportToExcel" class="btn btn-success" :disabled="!report.length">
          <i class="bi bi-file-earmark-excel"></i> Export Excel
        </button>
        <button @click="exportToPDF" class="btn btn-danger" :disabled="!report.length">
          <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
      </div>
    </div>
    <div class="row mb-3">
      <div class="col-md-3">
        <label>From Date</label>
        <input v-model="dateFrom" type="date" class="form-control">
      </div>
      <div class="col-md-3">
        <label>To Date</label>
        <input v-model="dateTo" type="date" class="form-control">
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
          <th>No of Reels Used</th>
          <th>Weight Kg Used</th>
          <th>Consumption Amount (PKR)</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in report" :key="item.paper_quality_with_gsm">
          <td>{{ item.paper_quality_with_gsm }}</td>
          <td>{{ item.no_of_reels_used }}</td>
          <td>{{ item.weight_kg_used ? parseFloat(item.weight_kg_used).toFixed(2) : '0.00' }} kg</td>
          <td>{{ item.consumption_amount_pkr ? 'PKR ' + parseFloat(item.consumption_amount_pkr).toFixed(2) : 'PKR 0.00' }}</td>
        </tr>
        <!-- Subtotal Row -->
        <tr class="table-info fw-bold">
          <td><strong>TOTAL</strong></td>
          <td>{{ totalReelsUsed }}</td>
          <td>{{ totalWeightUsed.toFixed(2) }} kg</td>
          <td>PKR {{ totalAmount.toFixed(2) }}</td>
        </tr>
      </tbody>
    </table>
    <p v-else>No data for selected period.</p>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      dateFrom: '',
      dateTo: '',
      qualityFilter: '',
      report: []
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
  },
  methods: {
    fetchReport() {
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
      printWindow.document.write(`
        <html>
          <head>
            <title>Monthly Consumption Report - Quality Cartons</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
              .company-name { font-size: 18px; font-weight: bold; color: #2c3e50; }
              .company-address { font-size: 12px; color: #666; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
              th { background-color: #f2f2f2; }
              .total-row { background-color: #e3f2fd; font-weight: bold; }
              h2 { color: #333; text-align: center; }
              .report-info { text-align: center; margin-bottom: 20px; }
            </style>
          </head>
          <body>
            <div class="header">
              <div class="company-name">QUALITY CARTONS (PVT.) LTD.</div>
              <div class="company-address">Plot# 46, Sector 24, Korangi Industrial Area Karachi</div>
            </div>
            <h2>Monthly Consumption Report</h2>
            <div class="report-info">Period: ${this.dateFrom || 'All'} to ${this.dateTo || 'All'}</div>
            ${this.generateHTMLTable()}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    generateCSV() {
      const headers = ['Paper Quality with GSM', 'No of Reels Used', 'Weight Kg Used', 'Consumption Amount (PKR)'];
      const rows = this.report.map(item => [
        item.paper_quality_with_gsm,
        item.no_of_reels_used,
        item.weight_kg_used || 0,
        item.consumption_amount_pkr || 0
      ]);
      
      // Add total row
      rows.push([
        'TOTAL',
        this.totalReelsUsed,
        this.totalWeightUsed.toFixed(2),
        this.totalAmount.toFixed(2)
      ]);
      
      const csvRows = [headers, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    generateHTMLTable() {
      return `
        <table>
          <thead>
            <tr>
              <th>Paper Quality with GSM</th>
              <th>No of Reels Used</th>
              <th>Weight Kg Used</th>
              <th>Consumption Amount (PKR)</th>
            </tr>
          </thead>
          <tbody>
            ${this.report.map(item => `
              <tr>
                <td>${item.paper_quality_with_gsm}</td>
                <td>${item.no_of_reels_used}</td>
                <td>${item.weight_kg_used ? parseFloat(item.weight_kg_used).toFixed(2) : '0.00'} kg</td>
                <td>PKR ${item.consumption_amount_pkr ? parseFloat(item.consumption_amount_pkr).toFixed(2) : '0.00'}</td>
              </tr>
            `).join('')}
            <tr class="total-row">
              <td><strong>TOTAL</strong></td>
              <td><strong>${this.totalReelsUsed}</strong></td>
              <td><strong>${this.totalWeightUsed.toFixed(2)} kg</strong></td>
              <td><strong>PKR ${this.totalAmount.toFixed(2)}</strong></td>
            </tr>
          </tbody>
        </table>
      `;
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
