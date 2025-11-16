<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Reel Receipt Report</h2>
      <div class="d-flex gap-2">
        <button @click="exportToExcel" class="btn btn-success" :disabled="!report.length">
          <i class="bi bi-file-earmark-excel"></i> Export Excel
        </button>
        <button @click="exportToPDF" class="btn btn-danger" :disabled="!report.length">
          <i class="bi bi-file-earmark-pdf"></i> Export PDF
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
          <th>Size</th>
          <th>Weight (kg)</th>
          <th>Rate (PKR/kg)</th>
          <th>Amount (PKR)</th>
          <th>QC Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in report" :key="item.id">
          <td>{{ formatDate(item.receiving_date) }}</td>
          <td>{{ item.reel_no || 'N/A' }}</td>
          <td>{{ item.supplier || 'N/A' }}</td>
          <td>{{ item.paper_quality || 'N/A' }}</td>
          <td>{{ item.reel_size || 'N/A' }}</td>
          <td>{{ formatNumber(item.weight) }}</td>
          <td>{{ formatNumber(item.rate_per_kg) }}</td>
          <td>{{ formatCurrency(item.amount) }}</td>
          <td>{{ item.qc_status || 'N/A' }}</td>
        </tr>
        <!-- Subtotal Row -->
        <tr class="table-info fw-bold">
          <td colspan="5"><strong>TOTAL</strong></td>
          <td>{{ formatNumber(totalWeight) }}</td>
          <td>{{ formatCurrency(totalAmount) }}</td>
          <td></td>
        </tr>
      </tbody>
    </table>
    <p v-else>No receipt data for selected dates.</p>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      dateFrom: '',
      dateTo: '',
      selectedSupplier: '',
      suppliers: [],
      report: []
    };
  },
  mounted() {
    this.fetchSuppliers();
    this.fetchReport();
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
    exportToPDF() {
      const printWindow = window.open('', '_blank');
      printWindow.document.write(
        '<html>' +
        '<head>' +
        '<title>Reel Receipt Report - Quality Cartons</title>' +
        '<style>' +
        'body { font-family: Arial, sans-serif; margin: 20px; }' +
        '.header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }' +
        '.company-name { font-size: 18px; font-weight: bold; color: #2c3e50; }' +
        '.company-address { font-size: 12px; color: #666; }' +
        'table { width: 100%; border-collapse: collapse; margin-top: 20px; }' +
        'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }' +
        'th { background-color: #f2f2f2; }' +
        '.total-row { background-color: #e3f2fd; font-weight: bold; }' +
        'h2 { color: #333; text-align: center; }' +
        '.report-info { text-align: center; margin-bottom: 20px; }' +
        '</style>' +
        '</head>' +
        '<body>' +
        '<div class="header">' +
        '<div class="company-name">QUALITY CARTONS (PVT.) LTD.</div>' +
        '<div class="company-address">Plot# 46, Sector 24, Korangi Industrial Area Karachi</div>' +
        '</div>' +
        '<h2>Reel Receipt Report</h2>' +
        '<div class="report-info">Period: ' + (this.dateFrom || 'All') + ' to ' + (this.dateTo || 'All') + '<br>' +
        'Supplier: ' + (this.selectedSupplier ? this.getSupplierName(this.selectedSupplier) : 'All') + '</div>' +
        this.generateHTMLTable() +
        '</body>' +
        '</html>'
      );
      printWindow.document.close();
      printWindow.print();
    },
    generateCSV() {
      const headers = ['Receipt Date', 'Reel No.', 'Supplier', 'Paper Quality', 'Size', 'Weight (kg)', 'Rate (PKR/kg)', 'Amount (PKR)', 'QC Status'];
      let rows = [];
      for (let i = 0; i < this.report.length; i++) {
        const item = this.report[i];
        rows.push([
          this.formatDate(item.receiving_date),
          item.reel_no || 'N/A',
          item.supplier || 'N/A',
          item.paper_quality || 'N/A',
          item.reel_size || 'N/A',
          this.formatNumber(item.weight, false),
          this.formatNumber(item.rate_per_kg, false),
          this.formatNumber(item.amount, false),
          item.qc_status || 'N/A'
        ]);
      }

      // Add total row
      rows.push([
        'TOTAL',
        '',
        '',
        '',
        '',
        this.formatNumber(this.totalWeight, false),
        '',
        this.formatNumber(this.totalAmount, false),
        ''
      ]);

      const csvRows = [headers, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    generateHTMLTable() {
      return '<table>' +
        '<thead>' +
          '<tr>' +
            '<th>Receipt Date</th>' +
            '<th>Reel No.</th>' +
            '<th>Supplier</th>' +
            '<th>Paper Quality</th>' +
            '<th>Size</th>' +
            '<th>Weight (kg)</th>' +
            '<th>Rate (PKR/kg)</th>' +
            '<th>Amount (PKR)</th>' +
            '<th>QC Status</th>' +
          '</tr>' +
        '</thead>' +
        '<tbody>' +
          this.report.map(item => (
            '<tr>' +
              '<td>' + this.formatDate(item.receiving_date) + '</td>' +
              '<td>' + (item.reel_no || 'N/A') + '</td>' +
              '<td>' + (item.supplier || 'N/A') + '</td>' +
              '<td>' + (item.paper_quality || 'N/A') + '</td>' +
              '<td>' + (item.reel_size || 'N/A') + '</td>' +
              '<td>' + this.formatNumber(item.weight) + '</td>' +
              '<td>' + this.formatNumber(item.rate_per_kg) + '</td>' +
              '<td>' + this.formatCurrency(item.amount) + '</td>' +
              '<td>' + (item.qc_status || 'N/A') + '</td>' +
            '</tr>'
          )).join('') +
          '<tr class="total-row">' +
            '<td colspan="5"><strong>TOTAL</strong></td>' +
            '<td><strong>' + this.formatNumber(this.totalWeight) + '</strong></td>' +
            '<td></td>' +
            '<td><strong>' + this.formatCurrency(this.totalAmount) + '</strong></td>' +
            '<td></td>' +
          '</tr>' +
        '</tbody>' +
      '</table>';
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
      const formatted = number.toFixed(2);
      return withUnit ? formatted : formatted;
    },
    formatCurrency(value) {
      const number = parseFloat(value);
      if (Number.isNaN(number)) {
        return 'PKR 0.00';
      }
      return `PKR ${number.toFixed(2)}`;
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
