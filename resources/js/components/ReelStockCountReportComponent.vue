<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-list-ol"></i> Reel Stock Count Report</h2>
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
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="small text-muted mb-1 ps-1">Search Quality</label>
            <div class="searchable-select-container">
              <input 
                v-model="qualitySearch" 
                type="text" 
                class="form-control form-control-sm" 
                placeholder="Search/Select Quality..."
                @focus="showQualityDrop = true"
                @blur="handleBlur"
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
          <div class="col-md-3">
            <label class="small text-muted mb-1 ps-1">Reel Size</label>
            <select v-model="selectedSize" class="form-select form-select-sm" @change="fetchReport">
              <option value="">All Sizes</option>
              <option v-for="size in availableSizes" :key="size" :value="size">{{ formatReelSize(size) }}</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-1">
            <button @click="fetchReport" class="btn btn-primary btn-sm px-3">Apply Filter</button>
            <button @click="clearFilters" class="btn btn-clear-filters btn-sm">Clear</button>
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table v-if="report.length" class="table table-striped align-middle table-sticky-header">
        <thead class="table-light">
          <tr>
            <th style="width: 5%; text-align: center;">S.No.</th>
            <th style="width: 40%; text-align: left;">Quality</th>
            <th style="width: 15%; text-align: center;">Reel Size</th>
            <th style="width: 15%; text-align: center;">No. of Reels</th>
            <th style="width: 25%; text-align: right;">Balance Weight Kg</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in report" :key="index">
            <td class="text-center">{{ index + 1 }}</td>
            <td>{{ item.quality_name }}</td>
            <td class="text-center">{{ formatReelSize(item.reel_size) }}</td>
            <td class="text-center">{{ item.no_of_reels }}</td>
            <td class="text-end fw-bold">{{ formatWeight(item.total_balance_weight) }}</td>
          </tr>
          <!-- Summary Row -->
          <tr class="table-info fw-bold">
            <td colspan="3" class="text-center">GRAND TOTAL</td>
            <td class="text-center">{{ totalReels }}</td>
            <td class="text-end">{{ formatWeight(totalWeight) }}</td>
          </tr>
        </tbody>
      </table>
      <div v-else class="text-center py-5">
        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
        <p class="mt-2 text-muted">No data found matching your criteria.</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      report: [],
      qualities: [],
      selectedQualityId: '',
      qualitySearch: '',
      showQualityDrop: false,
      selectedSize: '',
      availableSizes: [],
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg'
    };
  },
  mounted() {
    this.fetchQualities();
    this.fetchSizes();
    this.fetchReport();
    this.fetchCompanySettings();
  },
  computed: {
    filteredQualities() {
      const search = (this.qualitySearch || '').toLowerCase();
      if (!this.showQualityDrop && this.selectedQualityId) return this.qualities;
      
      return this.qualities.filter(q => 
        (q.quality && q.quality.toLowerCase().includes(search)) || 
        (q.gsm_range && q.gsm_range.toLowerCase().includes(search))
      );
    },
    totalReels() {
      return this.report.reduce((sum, item) => sum + item.no_of_reels, 0);
    },
    totalWeight() {
      return this.report.reduce((sum, item) => sum + item.total_balance_weight, 0);
    }
  },
  methods: {
    fetchCompanySettings() {
      axios.get('/api/setup/settings').then(response => {
        const data = response.data || {};
        if (data.company_name) this.companyName = data.company_name;
        if (data.company_address) this.companyAddress = data.company_address;
        if (data.company_logo) {
          this.companyLogo = window.location.origin + '/storage/' + data.company_logo;
        }
      }).catch(error => {
        console.error('Error fetching company settings:', error);
      });
    },
    fetchQualities() {
      axios.get('/api/paper-qualities').then(response => {
        this.qualities = response.data;
      }).catch(error => {
        console.error('Error fetching qualities:', error);
      });
    },
    fetchSizes() {
      axios.get('/api/reports/reel-stock/sizes').then(response => {
        this.availableSizes = response.data;
      }).catch(error => {
        console.error('Error fetching sizes:', error);
      });
    },
    fetchReport() {
      let params = [];
      if (this.selectedQualityId) params.push('quality=' + this.selectedQualityId);
      if (this.selectedSize) params.push('size=' + this.selectedSize);
      
      let url = '/api/reports/reel-stock-count' + (params.length ? '?' + params.join('&') : '');
      axios.get(url).then(response => {
        this.report = response.data;
      }).catch(error => {
        console.error('Error fetching report:', error);
      });
    },
    selectQuality(q) {
      if (!q) {
        this.selectedQualityId = '';
        this.qualitySearch = '';
      } else {
        this.selectedQualityId = q.id;
        this.qualitySearch = q.quality + (q.gsm_range ? ' (' + q.gsm_range + ')' : '');
      }
      this.showQualityDrop = false;
      this.fetchReport();
    },
    handleBlur() {
      setTimeout(() => {
        this.showQualityDrop = false;
        if (this.selectedQualityId) {
          const q = this.qualities.find(item => item.id == this.selectedQualityId);
          if (q) {
            this.qualitySearch = q.quality + (q.gsm_range ? ' (' + q.gsm_range + ')' : '');
          }
        }
      }, 200);
    },
    clearFilters() {
      this.selectedQualityId = '';
      this.qualitySearch = '';
      this.selectedSize = '';
      this.fetchReport();
    },
    formatWeight(value) {
      if (value === undefined || value === null) return '0';
      return Math.round(value).toLocaleString();
    },
    formatReelSize(value) {
      if (!value) return '';
      return Number(value).toFixed(2) + '”';
    },
    getTimestamp() {
      return new Date().toLocaleString();
    },
    exportToExcel() {
      const rows = [
        ['S.No.', 'Quality', 'Reel Size', 'No. of Reels', 'Balance Weight Kg']
      ];
      this.report.forEach((item, index) => {
        rows.push([
          index + 1,
          item.quality_name,
          this.formatReelSize(item.reel_size),
          item.no_of_reels,
          Math.round(item.total_balance_weight)
        ]);
      });
      rows.push(['TOTAL', '', '', this.totalReels, Math.round(this.totalWeight)]);

      const csvContent = rows.map(r => r.map(f => `"${f}"`).join(',')).join('\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `reel_stock_count_${new Date().getTime()}.csv`;
      link.click();
    },
    exportToPDF() {
      this.printTable();
    },
    printTable() {
      const printWindow = window.open('', '_blank');
      const tableHtml = this.generatePrintTable();
      
      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Stock Count Report</title>
            <style>
              @page { size: A4 portrait; margin: 10mm; }
              body { font-family: Arial, sans-serif; padding: 0; color: #333; }
              .header { display: flex; align-items: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
              .logo { width: 80px; height: 80px; margin-right: 20px; }
              .company-info { flex-grow: 1; text-align: center; }
              .company-info h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
              .company-info p { margin: 5px 0; font-size: 12px; }
              .company-info h2 { margin: 5px 0; font-size: 18px; text-decoration: underline; }
              table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
              th, td { border: 1px solid #000; padding: 6px 4px; text-align: center; }
              th { background-color: #f2f2f2; font-weight: bold; }
              .text-left { text-align: left !important; padding-left: 8px !important; }
              .text-right { text-align: right !important; padding-right: 8px !important; }
              .total-row { font-weight: bold; background-color: #eee; }
              .report-info { margin-bottom: 10px; font-size: 11px; display: flex; justify-content: space-between; }
            </style>
          </head>
          <body>
            <div class="header">
              <img src="${this.companyLogo}" class="logo" alt="Logo">
              <div class="company-info">
                <h1>${this.companyName}</h1>
                <p>${this.companyAddress}</p>
                <h2>Reel Stock Count Report</h2>
              </div>
            </div>
            <div class="report-info">
              <span>Generated on: ${this.getTimestamp()}</span>
              <span>
                ${this.selectedQualityId ? 'Quality: ' + this.qualitySearch : 'All Qualities'}
                ${this.selectedSize ? ' | Size: ' + this.formatReelSize(this.selectedSize) : ''}
              </span>
            </div>
            ${tableHtml}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 250);
    },
    generatePrintTable() {
      let html = `
        <table>
          <thead>
            <tr>
              <th>S.No.</th>
              <th class="text-left">Quality</th>
              <th>Reel Size</th>
              <th>No. of Reels</th>
              <th class="text-right">Balance Weight Kg</th>
            </tr>
          </thead>
          <tbody>
      `;
      
      this.report.forEach((item, index) => {
        html += `
          <tr>
            <td>${index + 1}</td>
            <td class="text-left">${item.quality_name}</td>
            <td>${this.formatReelSize(item.reel_size)}</td>
            <td>${item.no_of_reels}</td>
            <td class="text-right">${this.formatWeight(item.total_balance_weight)}</td>
          </tr>
        `;
      });
      
      html += `
          <tr class="total-row">
            <td colspan="3">GRAND TOTAL</td>
            <td>${this.totalReels}</td>
            <td class="text-right">${this.formatWeight(this.totalWeight)}</td>
          </tr>
        </tbody>
      </table>
      `;
      return html;
    }
  }
};
</script>

<style scoped>
.searchable-select-container {
  position: relative;
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
  padding: 8px 12px;
  cursor: pointer;
  border-bottom: 1px solid #f1f1f1;
}

.dropdown-item-custom:hover {
  background-color: #f8f9fa;
  color: #0d6efd;
}

.table-sticky-header thead th {
  position: sticky;
  top: 0;
  z-index: 10;
  background-color: #f8f9fa;
}
</style>
