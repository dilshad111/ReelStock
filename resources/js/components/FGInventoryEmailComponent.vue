<template>
  <div class="fg-report-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-envelope-at-fill"></i> Inventory Email Report</h2>
      <div class="d-flex gap-2">
        <el-button type="success" @click="exportToExcel" :disabled="!hasData">
          <i class="bi bi-file-earmark-excel me-2"></i> Export Excel
        </el-button>
        <el-button type="danger" @click="exportToPDF" :disabled="!hasData">
          <i class="bi bi-file-earmark-pdf me-2"></i> PDF
        </el-button>
        <el-button type="primary" @click="printReport" :disabled="!hasData">
          <i class="bi bi-printer me-2"></i> Print
        </el-button>
      </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body bg-light-soft">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="small text-muted fw-bold">Customer</label>
            <select v-model="filters.customer_id" @change="fetchReport" class="form-select form-select-sm shadow-sm">
              <option value="">All Customers</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="small text-muted fw-bold">Item / Code</label>
            <div class="input-group input-group-sm shadow-sm">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input v-model="filters.item_search" @input="debouncedFetch" type="text" class="form-control border-start-0" placeholder="Search by name or code...">
            </div>
          </div>
          <div class="col-md-2">
            <button @click="resetFilters" class="btn btn-sm btn-outline-secondary w-100 shadow-sm">
              <i class="bi bi-x-circle me-1"></i> Clear Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Fetching inventory data...</p>
    </div>

    <div v-else class="card shadow-sm border-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="bg-dark text-white">
            <tr>
              <th class="ps-3 py-2">Customer</th>
              <th class="py-2">Item Code</th>
              <th class="py-2" style="width: 40%;">Item Name</th>
              <th class="text-end pe-3 py-2">Quantity</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in reportData" :key="index">
              <td class="ps-3">{{ item.customer_name }}</td>
              <td class="fw-bold">{{ item.item_code }}</td>
              <td>{{ item.item_name }}</td>
              <td class="text-end pe-3 fw-bold text-primary">{{ fmt(item.quantity) }}</td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="4" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No items with balance found.
              </td>
            </tr>
          </tbody>
          <tfoot v-if="reportData.length > 0">
            <tr class="table-dark">
              <td colspan="3" class="text-end fw-bold">Grand Total:</td>
              <td class="text-end pe-3 fw-bold fs-6">{{ fmt(totalQuantity) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import * as XLSX from 'xlsx';

export default {
  props: { user: { type: Object, default: null } },
  data() {
    return {
      loading: false,
      customers: [],
      reportData: [],
      filters: { customer_id: '', item_search: '' },
      searchTimeout: null,
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg'
    };
  },
  computed: {
    hasData() { return this.reportData.length > 0; },
    totalQuantity() { return this.reportData.reduce((sum, item) => sum + item.quantity, 0); },
    printFilterSummary() {
      const parts = [];
      if (this.filters.customer_id) {
        const c = this.customers.find(x => x.id == this.filters.customer_id);
        if (c) parts.push(`Customer: <strong>${c.name}</strong>`);
      }
      if (this.filters.item_search) {
        parts.push(`Search: <strong>${this.filters.item_search}</strong>`);
      }
      return parts.length > 0 ? parts.join(' | ') : 'All Customers';
    }
  },
  mounted() {
    this.fetchFilters();
    this.fetchReport();
    this.fetchCompanySettings();
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
      }).catch(error => { console.error('Error fetching company settings:', error); });
    },
    fetchFilters() {
      axios.get('/api/fg-reports/filters').then(r => { this.customers = r.data.customers; });
    },
    fetchReport() {
      this.loading = true;
      const params = { ...this.filters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-reports/inventory-email', { params }).then(r => {
        this.reportData = r.data;
        this.loading = false;
      }).catch(() => { this.loading = false; });
    },
    debouncedFetch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => this.fetchReport(), 400);
    },
    resetFilters() {
      this.filters = { customer_id: '', item_search: '' };
      this.fetchReport();
    },
    fmt(v) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 0 }); },
    
    printReport() {
      const printWindow = window.open('', '_blank');
      const timestamp = new Date().toLocaleString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
      
      printWindow.document.write(`
        <html>
          <head>
            <title>Available Inventory - Quality Cartons</title>
            <style>
              @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
              @page { size: A4; margin: 8mm; }
              body { 
                font-family: 'Inter', -apple-system, sans-serif; 
                margin: 0; 
                padding: 0; 
                color: #1a1a1a; 
                line-height: 1.3; 
                -webkit-print-color-adjust: exact;
              }
              .header-section {
                display: flex;
                align-items: center;
                gap: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #333;
                margin-bottom: 0;
              }
              .logo { width: 75px; height: 75px; object-fit: contain; }
              .company-info { flex-grow: 1; }
              .company-name { font-size: 28px; font-weight: 800; color: #000; margin: 0; line-height: 1.1; }
              .company-address { font-size: 13px; color: #555; margin-top: 4px; font-weight: 500; }
              
              .report-title-section {
                text-align: center;
                margin: 20px 0 15px 0;
              }
              .report-title { 
                font-size: 20px; 
                font-weight: 800; 
                text-transform: uppercase; 
                letter-spacing: 1.5px; 
                margin-bottom: 4px;
                display: inline-block;
                padding-bottom: 4px;
                border-bottom: 2px solid #000;
              }
              .filter-info { font-size: 12px; color: #666; font-style: italic; font-weight: 500; }
              
              table { width: 100%; border-collapse: collapse; margin-top: 5px; }
              th, td { border: 1px solid #333; padding: 6px 10px; font-size: 11px; }
              th { 
                background-color: #f1f1f1 !important; 
                font-weight: 800; 
                text-transform: uppercase; 
                text-align: center; 
                font-size: 11px; 
                letter-spacing: 0.3px;
              }
              .text-end { text-align: right !important; }
              .text-center { text-align: center !important; }
              .fw-bold { font-weight: 700 !important; }
              
              .grand-total-row { background-color: #f9f9f9 !important; }
              .grand-total-label { font-size: 13px; font-weight: 800; }
              .grand-total-value { font-size: 14px; font-weight: 800; border-bottom: 3px double #000 !important; }
              
              .footer { 
                position: fixed; 
                bottom: 5px; 
                left: 0; 
                right: 0; 
                font-size: 10px; 
                text-align: center; 
                color: #999; 
                padding-top: 5px;
                border-top: 1px solid #eee;
              }
              
              @media print {
                .no-print { display: none; }
              }
            </style>
          </head>
          <body>
            <div class="header-section">
              <img src="${this.companyLogo}" class="logo" alt="Logo">
              <div class="company-info">
                <div class="company-name">${this.companyName}</div>
                <div class="company-address">${this.companyAddress}</div>
              </div>
            </div>
            
            <div class="report-title-section">
              <div class="report-title">Available Inventory Report</div>
              <div class="filter-info">${this.printFilterSummary}</div>
            </div>
            
            <table class="main-report-table">
              <thead>
                <tr>
                  <th style="width: 35%;">CUSTOMER</th>
                  <th style="width: 12%;">ITEM CODE</th>
                  <th style="width: 41%;">ITEM NAME</th>
                  <th class="text-end" style="width: 12%;">QUANTITY</th>
                </tr>
              </thead>
              <tbody>
                ${this.reportData.map(item => `
                  <tr>
                    <td style="white-space: nowrap;">${item.customer_name}</td>
                    <td class="fw-bold text-center">${item.item_code}</td>
                    <td>${item.item_name}</td>
                    <td class="text-end fw-bold">${this.fmt(item.quantity)}</td>
                  </tr>
                `).join('')}
              </tbody>
              <tfoot>
                <tr class="grand-total-row">
                  <td colspan="3" class="text-end grand-total-label">Grand Total:</td>
                  <td class="text-end grand-total-value">${this.fmt(this.totalQuantity)}</td>
                </tr>
              </tfoot>
            </table>
            
            <div class="footer">
              This is a computer generated report printed on ${timestamp}
            </div>
          </body>
        </html>
      `);
      printWindow.document.close();
      setTimeout(() => {
        printWindow.print();
        // Close window after printing if needed, but usually user wants to see it
      }, 500);
    },

    exportToPDF() {
      this.printReport();
    },

    exportToExcel() {
      const data = this.reportData.map(item => ({
        'Customer': item.customer_name,
        'Item Code': item.item_code,
        'Item Name': item.item_name,
        'Quantity': item.quantity
      }));

      const worksheet = XLSX.utils.json_to_sheet(data);
      
      // Set column widths
      const wscols = [
        { wch: 30 }, // Customer
        { wch: 15 }, // Item Code
        { wch: 50 }, // Item Name
        { wch: 15 }, // Quantity
      ];
      worksheet['!cols'] = wscols;

      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Inventory');
      XLSX.writeFile(workbook, `Available_Inventory_${new Date().toISOString().slice(0, 10)}.xlsx`);
      this.$message.success('Report exported to Excel successfully');
    }
  }
};
</script>

<style scoped>
.fg-report-wrapper { padding: 5px; }
.bg-light-soft { background-color: rgba(248, 249, 250, 0.8); }
.table thead th { font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px; }
.table tbody td { vertical-align: middle; padding: 10px 8px; font-size: 0.9rem; }
.card { border-radius: 12px; }
</style>
