<template>
  <div class="fg-report-wrapper">
    <div class="report-header">
      <h2 class="report-title">
        <span class="report-title-icon"><i class="bi bi-envelope-at-fill"></i></span>
        Inventory Email Report
      </h2>
      <div class="report-actions">
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

    <div class="report-filters">
      <div class="filter-field filter-customer">
        <label>Customer</label>
        <v-select
          v-model="filters.customer_id"
          :options="customers"
          label="name"
          :reduce="c => c.id"
          placeholder="All Customers"
          :clearable="true"
          class="v-select-sm"
          @option:selected="fetchReport"
          @option:deselected="fetchReport"
        ></v-select>
      </div>
      <div class="filter-field filter-search">
        <label>Item / Code</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input v-model="filters.item_search" @input="debouncedFetch" type="text" class="form-control" placeholder="Search by name or code...">
        </div>
      </div>
      <div class="filter-field filter-clear">
        <button @click="resetFilters" class="btn btn-sm btn-clear btn-clear-filters">
          Clear
        </button>
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
              <td class="text-end pe-3 fw-bold">
                <a v-if="item.quantity > 0" href="#" @click.prevent="showProductJobs(item)" class="text-decoration-none text-primary">
                  {{ fmt(item.quantity) }}
                </a>
                <span v-else class="text-danger">{{ fmt(item.quantity) }}</span>
              </td>
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

    <!-- Job Detail Modal -->
    <el-dialog v-model="showJobModal" title="Job Detail" width="800px" :close-on-click-modal="false">
      <div v-if="jobDetail">
        <div class="row mb-3">
          <div class="col-md-4"><strong>Job #:</strong> {{ jobDetail.job_number }}</div>
          <div class="col-md-4"><strong>Customer:</strong> {{ jobDetail.customer?.name }}</div>
          <div class="col-md-4"><strong>Product:</strong> {{ jobDetail.product?.item_code }} - {{ jobDetail.product?.item_name }}</div>
        </div>
        <div class="row mb-3">
          <div class="col-md-4"><span class="badge bg-success fs-6">Produced: {{ fmt(jobDetail.total_produced) }}</span></div>
          <div class="col-md-4"><span class="badge bg-danger fs-6">Dispatched: {{ fmt(jobDetail.total_dispatched) }}</span></div>
          <div class="col-md-4"><span class="badge bg-primary fs-6">Remaining: {{ fmt(jobDetail.remaining_balance) }}</span></div>
        </div>
        <h6 class="mt-3 mb-2 fw-bold">Production Entries</h6>
        <table class="table table-sm table-bordered small">
          <thead><tr><th>Date</th><th class="text-end">Qty Produced</th><th class="text-end">Price</th><th class="text-end">Wastage</th><th>Remarks</th></tr></thead>
          <tbody>
            <tr v-for="r in jobDetail.receipts" :key="r.id">
              <td>{{ formatDate(r.date) }}</td>
              <td class="text-end text-success fw-bold">{{ fmt(r.quantity_produced) }}</td>
              <td class="text-end fw-bold text-dark">{{ fmtRate(r.carton_price) }}</td>
              <td class="text-end">{{ fmt(r.wastage) }}</td>
              <td>{{ r.remarks || '-' }}</td>
            </tr>
          </tbody>
        </table>
        <h6 class="mt-3 mb-2 fw-bold">Dispatch Entries</h6>
        <table class="table table-sm table-bordered small">
          <thead><tr><th>Date</th><th>DC #</th><th class="text-end">Qty</th><th class="text-end">Running Bal.</th><th>Remarks</th></tr></thead>
          <tbody>
            <tr v-for="d in jobDetail.dispatches" :key="d.id">
              <td>{{ formatDate(d.date) }}</td><td class="fw-bold">{{ d.dc_number }}</td><td class="text-end text-danger">{{ fmt(d.quantity_dispatched) }}</td>
              <td class="text-end fw-bold" :class="d.running_balance > 0 ? 'text-primary' : 'text-danger'">{{ fmt(d.running_balance) }}</td><td>{{ d.remarks || '-' }}</td>
            </tr>
            <tr v-if="jobDetail.dispatches.length === 0"><td colspan="5" class="text-center text-muted">No dispatches yet.</td></tr>
          </tbody>
        </table>
        <div class="alert alert-info mt-3 mb-0 fw-bold text-center">
          <i class="bi bi-info-circle me-1"></i> Remaining stock for Job {{ jobDetail.job_number }}: <span class="fs-5">{{ fmt(jobDetail.remaining_balance) }}</span>
        </div>
      </div>
    </el-dialog>

    <!-- Product Jobs Modal (Drilldown from Balance) -->
    <el-dialog v-model="showProductJobsModal" :title="'Active Jobs Balance for ' + (selectedProductForJobs?.item_name || 'Product')" width="850px" :close-on-click-modal="false">
      <div v-if="loadingProductJobs" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted mt-2">Fetching active jobs breakdown...</p>
      </div>
      <div v-else-if="productJobsData">
        <div class="mb-3 small">
          <strong>Item Code:</strong> {{ selectedProductForJobs?.item_code }} &nbsp;|&nbsp;
          <strong>Total Balance:</strong> <span class="fw-bold text-primary">{{ fmt(selectedProductForJobs?.quantity) }}</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-striped table-bordered small mb-0">
            <thead>
              <tr class="table-dark">
                <th>Job #</th>
                <th>Customer</th>
                <th class="text-end">Produced</th>
                <th class="text-end">Dispatched</th>
                <th class="text-end">Balance Contribution</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="job in productJobsData" :key="job.job_number + '-' + job.product_id">
                <td class="fw-bold text-primary">{{ job.job_number }}</td>
                <td>{{ job.customer?.name || 'Unknown' }}</td>
                <td class="text-end text-success fw-bold">{{ fmt(job.total_produced) }}</td>
                <td class="text-end text-danger">{{ fmt(job.total_dispatched) }}</td>
                <td class="text-end fw-bold text-primary">{{ fmt(job.remaining_balance) }}</td>
                <td class="text-center">
                  <button @click="showJobDetail(job); showProductJobsModal = false" class="btn btn-sm btn-outline-primary py-0 px-2">
                    <i class="bi bi-eye"></i> View Detail
                  </button>
                </td>
              </tr>
              <tr v-if="productJobsData.length === 0">
                <td colspan="6" class="text-center text-muted py-3">No active jobs contributing to this balance (might be opening balance stock).</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </el-dialog>
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
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg',
      showJobModal: false,
      jobDetail: null,
      showProductJobsModal: false,
      productJobsData: null,
      loadingProductJobs: false,
      selectedProductForJobs: null
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
    showJobDetail(job) {
      axios.get('/api/fg-reports/job-detail', { params: { job_number: job.job_number, product_id: job.product_id } })
        .then(r => { this.jobDetail = r.data; this.showJobModal = true; })
        .catch(err => {
          if (axios.isCancel(err)) return;
          this.$message.error(err.response?.data?.error || err.response?.data?.message || 'Error fetching job details.');
        });
    },
    showProductJobs(product) {
      if (!product) return;
      this.selectedProductForJobs = product;
      this.loadingProductJobs = true;
      this.showProductJobsModal = true;
      this.productJobsData = null;
      axios.get('/api/fg-reports/job', { params: { product_id: product.product_id } })
        .then(r => {
          this.productJobsData = r.data.data || r.data;
          this.loadingProductJobs = false;
        })
        .catch(err => {
          this.loadingProductJobs = false;
          this.$message.error(err.response?.data?.error || err.response?.data?.message || 'Error fetching active jobs.');
        });
    },
    formatDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-GB'); },
    fmtRate(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    
    printReport() {
      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        this.$message.error('Please allow pop-ups to print');
        return;
      }
      
      const itemsPerPage = 35; 
      const pages = [];
      for (let i = 0; i < this.reportData.length; i += itemsPerPage) {
        pages.push(this.reportData.slice(i, i + itemsPerPage));
      }

      let pagesHtml = '';
      pages.forEach((pageItems, pageIdx) => {
        const pageTotal = pageItems.reduce((sum, item) => sum + item.quantity, 0);
        const isLastPage = pageIdx === pages.length - 1;
        const isSinglePage = pages.length === 1;

        pagesHtml += `
          <div class="print-page" style="${!isLastPage ? 'page-break-after: always;' : ''}">
            ${pageIdx === 0 ? `
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
            ` : `
              <div class="header-section" style="padding-bottom: 5px; margin-bottom: 10px;">
                <div class="company-name" style="font-size: 16px;">${this.companyName} - Inventory Report (Contd.)</div>
              </div>
            `}
            
            <table class="main-report-table">
              <thead>
                <tr>
                  <th style="width: 35%;">CUSTOMER</th>
                  <th style="width: 15%;">ITEM CODE</th>
                  <th style="width: 38%;">ITEM NAME</th>
                  <th class="text-end" style="width: 12%;">QUANTITY</th>
                </tr>
              </thead>
              <tbody>
                ${pageItems.map(item => `
                  <tr>
                    <td>${item.customer_name}</td>
                    <td class="fw-bold text-center">${item.item_code}</td>
                    <td>${item.item_name}</td>
                    <td class="text-end fw-bold">${this.fmt(item.quantity)}</td>
                  </tr>
                `).join('')}
              </tbody>
              <tfoot>
                ${!isSinglePage ? `
                  <tr class="summary-row">
                    <td colspan="3" class="text-end fw-bold">Page Total:</td>
                    <td class="text-end fw-bold">${this.fmt(pageTotal)}</td>
                  </tr>
                ` : ''}
                ${isLastPage ? `
                  <tr class="grand-total-row">
                    <td colspan="3" class="text-end grand-total-label">Grand Total:</td>
                    <td class="text-end grand-total-value">${this.fmt(this.totalQuantity)}</td>
                  </tr>
                ` : ''}
              </tfoot>
            </table>
          </div>
        `;
      });

      printWindow.document.write(`
        <html>
          <head>
            <title>Available Inventory - ${this.companyName}</title>
            <style>
              @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
              @page { size: A4; margin: 10mm; }
              body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; color: #000; }
              .header-section { display: flex; align-items: center; gap: 20px; padding-bottom: 10px; border-bottom: 2px solid #000; }
              .logo { width: 60px; height: 60px; object-fit: contain; }
              .company-name { font-size: 24px; font-weight: 800; color: #000; margin: 0; }
              .company-address { font-size: 11px; color: #333; margin-top: 2px; }
              .report-title-section { text-align: center; margin: 15px 0; }
              .report-title { font-size: 18px; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 2px; }
              .filter-info { font-size: 11px; color: #333; font-style: italic; }
              table { width: 100%; border-collapse: collapse; table-layout: fixed; }
              th, td { border: 1px solid #000; padding: 5px 8px; font-size: 10px; word-wrap: break-word; }
              th { background-color: #f2f2f2 !important; font-weight: 800; text-transform: uppercase; text-align: center; }
              .text-end { text-align: right !important; }
              .text-center { text-align: center !important; }
              .fw-bold { font-weight: 700 !important; }
              .summary-row { background-color: #f2f2f2 !important; font-weight: bold; }
              .grand-total-row { background-color: #eee !important; }
              .grand-total-label { font-size: 12px; font-weight: 800; }
              .grand-total-value { font-size: 12px; font-weight: 800; }
            </style>
          </head>
          <body>
            ${pagesHtml}
          </body>
        </html>
      `);
      printWindow.document.close();
      setTimeout(() => { printWindow.print(); }, 500);
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
.fg-report-wrapper {
  padding: 5px;
}

.report-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 26px;
}

.report-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
  font-size: 2.45rem;
  line-height: 1.1;
  font-weight: 800;
  color: #111827;
}

.report-title-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #111827;
  color: #ffffff;
  font-size: 1.45rem;
  flex: 0 0 auto;
}

.report-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.report-actions :deep(.el-button) {
  min-height: 42px;
  border-radius: 10px;
  padding: 10px 18px;
  font-size: 1rem;
  font-weight: 700;
}

.report-filters {
  display: grid;
  grid-template-columns: minmax(220px, 280px) minmax(280px, 420px) minmax(90px, 120px);
  align-items: end;
  gap: 10px;
  margin-bottom: 20px;
}

.filter-field label {
  display: block;
  margin-bottom: 5px;
  font-size: 1rem;
  color: #64748b;
}

.filter-field .form-select,
.filter-field .form-control,
.filter-field .input-group-text {
  height: 52px;
  font-size: 1rem;
  font-weight: 700;
  border-radius: 10px;
}

.filter-field .input-group-text {
  width: 44px;
  justify-content: center;
}

.filter-field .input-group .input-group-text {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.filter-field .input-group .form-control {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

.btn-clear {
  height: 52px;
  min-width: 96px;
  border-radius: 10px;
  color: #475569;
  font-weight: 700;
  border: 0;
}

.table thead th { font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px; }
.table tbody td { vertical-align: middle; padding: 10px 8px; font-size: 0.9rem; }
.card { border-radius: 12px; }

:global([data-theme="dark"]) .fg-report-wrapper {
  color: #f8fafc;
}

:global([data-theme="dark"]) .fg-report-wrapper .report-title {
  color: #ffffff;
}

:global([data-theme="dark"]) .fg-report-wrapper .report-title-icon {
  background: #ffffff;
  color: #020617;
}

:global([data-theme="dark"]) .fg-report-wrapper .report-filters {
  background: transparent;
}

:global([data-theme="dark"]) .fg-report-wrapper .filter-field label {
  color: #7890b5 !important;
  font-weight: 500;
}

:global([data-theme="dark"]) .fg-report-wrapper .form-select,
:global([data-theme="dark"]) .fg-report-wrapper .form-control {
  background-color: #1c283b;
  border-color: #41516a;
  color: #ffffff;
  box-shadow: none !important;
}

:global([data-theme="dark"]) .fg-report-wrapper .form-select:focus,
:global([data-theme="dark"]) .fg-report-wrapper .form-control:focus {
  border-color: #6ea8ff;
  box-shadow: 0 0 0 0.18rem rgba(110, 168, 255, 0.18) !important;
}

:global([data-theme="dark"]) .fg-report-wrapper .form-control::placeholder {
  color: #7f91ad;
  opacity: 1;
}

:global([data-theme="dark"]) .fg-report-wrapper .input-group-text {
  background-color: #1c283b !important;
  border-color: #41516a;
  color: #8fa2bf;
}

:global([data-theme="dark"]) .fg-report-wrapper .card {
  background-color: #111827;
  border: 1px solid rgba(148, 163, 184, 0.18) !important;
}

@media (max-width: 992px) {
  .report-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .report-actions {
    justify-content: flex-start;
  }

  .report-filters {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 640px) {
  .report-title {
    font-size: 1.9rem;
  }

  .report-title-icon {
    width: 38px;
    height: 38px;
    font-size: 1.2rem;
  }

  .report-filters {
    grid-template-columns: 1fr;
  }

  .report-actions :deep(.el-button) {
    width: 100%;
    margin-left: 0;
  }
}
</style>
