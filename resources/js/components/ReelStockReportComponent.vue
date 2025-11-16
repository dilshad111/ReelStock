<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Reel Stock Report</h2>
      <div class="d-flex gap-2">
        <button @click="exportToExcel" class="btn btn-success" :disabled="!report.length">
          <i class="bi bi-file-earmark-excel"></i> Export Excel
        </button>
        <button @click="exportToPDF" class="btn btn-danger" :disabled="!report.length">
          <i class="bi bi-file-earmark-pdf"></i> Export PDF
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
    <table v-if="report.length" class="table table-striped">
      <thead>
        <tr>
          <th>Reel No.</th>
          <th>Quality</th>
          <th>Reel Size</th>
          <th>Supplier</th>
          <th>Original Weight</th>
          <th>Consumed Weight</th>
          <th>Balance Weight</th>
          <th>Amount (PKR)</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in report" :key="item.reel_no">
          <td><a href="#" @click.prevent="showHistory(item.reel_no)" class="text-decoration-none">{{ item.reel_no }}</a></td>
          <td>{{ item.quality }}</td>
          <td>{{ getSizeName(item.reel_size) }}</td>
          <td>{{ item.supplier }}</td>
          <td>{{ item.original_weight }} kg</td>
          <td>{{ item.consumed_weight }} kg</td>
          <td>{{ item.balance_weight }} kg</td>
          <td>{{ formatAmount(item.amount) }}</td>
          <td>{{ item.status }}</td>
        </tr>
        <!-- Subtotal Row -->
        <tr class="table-info fw-bold">
          <td colspan="4"><strong>TOTAL</strong></td>
          <td>{{ totalOriginalWeight.toFixed(2) }} kg</td>
          <td>{{ totalConsumedWeight.toFixed(2) }} kg</td>
          <td>{{ totalBalanceWeight.toFixed(2) }} kg</td>
          <td>{{ formatAmount(totalAmount) }}</td>
          <td>{{ report.length }} reels</td>
        </tr>
      </tbody>
    </table>
    <p v-else>No reel stock data.</p>

    <!-- Reel History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="historyModalLabel">Reel History - {{ selectedReel ? selectedReel.reel_no : '' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedReel">
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Quality:</strong> {{ selectedReel.quality }}
                </div>
                <div class="col-md-6">
                  <strong>Supplier:</strong> {{ selectedReel.supplier }}
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Original Weight:</strong> {{ selectedReel.original_weight }} kg
                </div>
                <div class="col-md-6">
                  <strong>Current Balance:</strong> {{ selectedReel.current_balance }} kg
                </div>
              </div>
              <h6>Transaction History</h6>
              <table class="table table-sm">
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
                  <tr v-for="h in history" :key="h.date + h.type">
                    <td>{{ formatDate(h.date) }}</td>
                    <td>{{ h.type }}</td>
                    <td>{{ h.details }}</td>
                    <td>{{ h.weight }} kg</td>
                    <td>{{ h.balance }} kg</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
      report: [],
      qualities: [],
      sizes: [],
      selectedQuality: '',
      selectedSize: '',
      balanceMin: '',
      balanceMax: '',
      selectedReel: null,
      history: []
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
      if (Number.isNaN(amount)) {
        return withPrefix ? 'PKR 0.00' : '0.00';
      }
      const formatted = amount.toFixed(2);
      return withPrefix ? `PKR ${formatted}` : formatted;
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
        // Extract unique reel sizes from receipts
        const uniqueSizes = [...new Set(response.data.map(r => r.reel.reel_size).filter(size => size))];
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
        this.report = response.data;
      }).catch(error => {
        console.error('Error fetching report:', error);
      });
    },
    showHistory(reelNo) {
      axios.get(`/api/reports/reel-stock/${reelNo}/history`).then(response => {
        this.selectedReel = response.data.reel;
        this.history = response.data.history;
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();
      }).catch(error => {
        console.error('Error fetching history:', error);
        alert('Error loading reel history');
      });
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString();
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
      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Stock Report - Quality Cartons</title>
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
            <h2>Reel Stock Report</h2>
            <div class="report-info">
              Quality Filter: ${this.selectedQuality ? 'Quality ID ' + this.selectedQuality : 'All Qualities'}<br>
              Reel Size Filter: ${this.selectedSize ? 'Size ID ' + this.selectedSize : 'All Sizes'}
            </div>
            ${this.generateHTMLTable()}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    generateCSV() {
      const headers = ['Reel No.', 'Quality', 'Reel Size', 'Supplier', 'Original Weight', 'Consumed Weight', 'Balance Weight', 'Status'];
      const rows = this.report.map(item => [
        item.reel_no,
        item.quality,
        this.getSizeName(item.reel_size),
        item.supplier,
        item.original_weight,
        item.consumed_weight,
        item.balance_weight,
        this.formatAmount(item.amount, false),
        item.status
      ]);

      // Add total row
      rows.push([
        'TOTAL',
        '',
        '',
        '',
        this.totalOriginalWeight.toFixed(2),
        this.totalConsumedWeight.toFixed(2),
        this.totalBalanceWeight.toFixed(2),
        this.formatAmount(this.totalAmount, false),
        this.report.length + ' reels'
      ]);

      const csvRows = [headers, ...rows];
      return csvRows.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    },
    generateHTMLTable() {
      return '<table>' +
        '<thead>' +
          '<tr>' +
            '<th>Reel No.</th>' +
            '<th>Quality</th>' +
            '<th>Reel Size</th>' +
            '<th>Supplier</th>' +
            '<th>Original Weight</th>' +
            '<th>Consumed Weight</th>' +
            '<th>Balance Weight</th>' +
            '<th>Amount (PKR)</th>' +
            '<th>Status</th>' +
          '</tr>' +
        '</thead>' +
        '<tbody>' +
          this.report.map(item => 
            '<tr>' +
              '<td>' + item.reel_no + '</td>' +
              '<td>' + item.quality + '</td>' +
              '<td>' + this.getSizeName(item.reel_size) + '</td>' +
              '<td>' + item.supplier + '</td>' +
              '<td>' + item.original_weight + ' kg</td>' +
              '<td>' + item.consumed_weight + ' kg</td>' +
              '<td>' + item.balance_weight + ' kg</td>' +
              '<td>' + this.formatAmount(item.amount) + '</td>' +
              '<td>' + item.status + '</td>' +
            '</tr>'
          ).join('') +
          '<tr class="total-row">' +
            '<td colspan="4"><strong>TOTAL</strong></td>' +
            '<td><strong>' + this.totalOriginalWeight.toFixed(2) + ' kg</strong></td>' +
            '<td><strong>' + this.totalConsumedWeight.toFixed(2) + ' kg</strong></td>' +
            '<td><strong>' + this.totalBalanceWeight.toFixed(2) + ' kg</strong></td>' +
            '<td><strong>' + this.formatAmount(this.totalAmount) + '</strong></td>' +
            '<td><strong>' + this.report.length + ' reels</strong></td>' +
          '</tr>' +
        '</tbody>' +
      '</table>';
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
/* Add styles if needed */
</style>
