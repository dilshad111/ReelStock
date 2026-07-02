<template>
  <div class="fg-report-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-clipboard-data-fill"></i> Finished Goods Reports</h2>
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

    <!-- Report Tabs -->
    <el-tabs v-model="activeTab" class="custom-dashboard-tabs" @tab-change="handleTabChange">

      <!-- A. Stock Report -->
      <el-tab-pane label="Stock Report" name="stock">
        <div class="row mb-3 g-2 align-items-end">
          <div class="col-md-3">
            <label class="small text-muted">Customer</label>
            <v-select
              v-model="stockFilters.customer_id"
              :options="customers"
              label="name"
              :reduce="c => c.id"
              placeholder="All Customers"
              :clearable="true"
              class="v-select-sm"
              @option:selected="fetchStockReport"
              @option:deselected="fetchStockReport"
            ></v-select>
          </div>
          <div class="col-md-2">
            <label class="small text-muted">Item / Code</label>
            <input v-model="stockFilters.item_search" @input="debouncedStockFetch" type="text" class="form-control form-control-sm" placeholder="Search...">
          </div>
          <div class="col-md-2"><label class="small text-muted">From</label><input v-model="stockFilters.date_from" type="date" class="form-control form-control-sm" @change="fetchStockReport"></div>
          <div class="col-md-2"><label class="small text-muted">To</label><input v-model="stockFilters.date_to" type="date" class="form-control form-control-sm" @change="fetchStockReport"></div>
          <div class="col-md-1"><button @click="stockFilters = { customer_id: '', date_from: '', date_to: '', item_search: '' }; fetchStockReport()" class="btn btn-sm btn-clear-filters w-100">Clear</button></div>
        </div>

        <div v-for="group in stockData" :key="group.customer" class="card mb-3 shadow-sm">
          <div class="card-header bg-light fw-bold"><i class="bi bi-person-circle me-2"></i>{{ group.customer }}</div>
          <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0 small">
              <thead><tr><th class="text-center">Item Code</th><th class="text-center" style="width: 35%;">Item Name</th><th class="text-center">Opening</th><th class="text-center">Produced</th><th class="text-center">Dispatched</th><th class="text-center text-warning">Damaged</th><th class="text-center fw-bold">Balance</th><th v-if="canSeeAmounts" class="text-center">Amount</th></tr></thead>
              <tbody>
                <tr v-for="p in group.products" :key="p.product_id">
                  <td class="fw-bold">{{ p.item_code }}</td><td>{{ p.item_name }}</td>
                  <td class="text-end">{{ fmt(p.opening_balance) }}</td>
                  <td class="text-end text-success">{{ fmt(p.total_produced) }}</td>
                  <td class="text-end text-danger">{{ fmt(p.total_dispatched) }}</td>
                  <td class="text-end text-warning">{{ fmt(p.total_damaged) }}</td>
                  <td class="text-end fw-bold">
                    <a v-if="p.current_balance > 0" href="#" @click.prevent="showProductJobs(p)" class="text-decoration-none" :class="p.current_balance > 0 ? 'text-primary' : 'text-danger'">
                      {{ fmt(p.current_balance) }}
                    </a>
                    <span v-else class="text-danger">{{ fmt(p.current_balance) }}</span>
                  </td>
                  <td v-if="canSeeAmounts" class="text-end fw-bold text-dark">{{ fmt(p.amount) }}</td>
                </tr>
                <tr class="table-secondary fw-bold">
                  <td colspan="3">Total</td>
                  <td class="text-end text-success">{{ fmt(group.total_produced) }}</td>
                  <td class="text-end text-danger">{{ fmt(group.total_dispatched) }}</td>
                  <td class="text-end text-warning">{{ fmt(group.total_damaged) }}</td>
                  <td class="text-end text-primary">{{ fmt(group.total_balance) }}</td>
                  <td v-if="canSeeAmounts" class="text-end text-success">{{ fmt(group.total_amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-if="stockData.length === 0" class="text-center text-muted py-5">No data found.</div>
      </el-tab-pane>

      <!-- B. Job-wise Report -->
      <el-tab-pane label="Job-wise Report" name="job">
        <div class="row mb-3 g-2 align-items-end">
          <div class="col-md-2"><label class="small text-muted">Customer</label>
            <v-select v-model="jobFilters.customer_id" :options="customers" label="name" :reduce="c => c.id" placeholder="All" :clearable="true" class="v-select-sm" @option:selected="fetchJobReport" @option:deselected="fetchJobReport"></v-select></div>
          <div class="col-md-2"><label class="small text-muted">Job #</label><input v-model="jobFilters.job_number" @input="debouncedJobFetch" type="text" class="form-control form-control-sm" placeholder="Search..."></div>
          <div class="col-md-2"><label class="small text-muted">From</label><input v-model="jobFilters.date_from" type="date" class="form-control form-control-sm" @change="fetchJobReport"></div>
          <div class="col-md-2"><label class="small text-muted">To</label><input v-model="jobFilters.date_to" type="date" class="form-control form-control-sm" @change="fetchJobReport"></div>
          <div class="col-md-2"><button @click="jobFilters = { customer_id: '', job_number: '', date_from: '', date_to: '' }; fetchJobReport()" class="btn btn-sm btn-clear-filters w-100">Clear</button></div>
        </div>

        <table class="table table-striped table-sm text-nowrap small table-sticky-header">
          <thead><tr><th>Job #</th><th>Customer</th><th>Product</th><th class="text-end">Produced</th><th class="text-end">Dispatched</th><th class="text-end text-warning">Damaged</th><th class="text-end text-danger-emphasis">Waste</th><th class="text-end">Remaining</th><th>Detail</th></tr></thead>
          <tbody>
            <tr v-for="j in jobData" :key="j.job_number + '-' + j.product_id">
              <td class="fw-bold text-primary">{{ j.job_number }}</td>
              <td>{{ j.customer?.name }}</td>
              <td>{{ j.product?.item_code }} - {{ j.product?.item_name }}</td>
              <td class="text-end text-success fw-bold">{{ fmt(j.total_produced) }}</td>
              <td class="text-end text-danger">{{ fmt(j.total_dispatched) }}</td>
              <td class="text-end text-warning">{{ fmt(j.total_damaged) }}</td>
              <td class="text-end text-danger-emphasis">{{ fmt(j.total_wastage) }}</td>
              <td class="text-end fw-bold" :class="j.remaining_balance > 0 ? 'text-primary' : 'text-muted'">{{ fmt(j.remaining_balance) }}</td>
              <td><button @click="showJobDetail(j)" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</button></td>
            </tr>
            <tr v-if="jobData.length === 0"><td colspan="9" class="text-center text-muted py-4">No jobs found.</td></tr>
          </tbody>
        </table>
      </el-tab-pane>

      <!-- C. Audit Report -->
      <el-tab-pane label="Inventory Ledger" name="audit">
        <div class="row mb-3 g-2 align-items-end">
          <div class="col-md-2"><label class="small text-muted">Customer</label>
            <v-select v-model="auditFilters.customer_id" :options="customers" label="name" :reduce="c => c.id" placeholder="All" :clearable="true" class="v-select-sm" @option:selected="fetchAuditReport" @option:deselected="fetchAuditReport"></v-select></div>
          <div class="col-md-2"><label class="small text-muted">Item / Code</label><input v-model="auditFilters.item_search" @input="debouncedAuditFetch" type="text" class="form-control form-control-sm" placeholder="Search..."></div>
          <div class="col-md-2"><label class="small text-muted">Type</label>
            <select v-model="auditFilters.transaction_type" @change="fetchAuditReport" class="form-control form-control-sm"><option value="">All</option><option value="opening">Opening</option><option value="receipt">Receipt</option><option value="dispatch">Dispatch</option><option value="damage">Damage</option><option value="receipt_reversal">Receipt Reversal</option><option value="dispatch_reversal">Dispatch Reversal</option><option value="damage_reversal">Damage Reversal</option><option value="adjustment">Adjustment</option></select></div>
          <div class="col-md-2"><label class="small text-muted">From</label><input v-model="auditFilters.date_from" type="date" class="form-control form-control-sm" @change="fetchAuditReport"></div>
          <div class="col-md-2"><label class="small text-muted">To</label><input v-model="auditFilters.date_to" type="date" class="form-control form-control-sm" @change="fetchAuditReport"></div>
          <div class="col-md-1"><button @click="auditFilters = { customer_id: '', transaction_type: '', date_from: '', date_to: '', item_search: '' }; fetchAuditReport()" class="btn btn-sm btn-clear-filters w-100">Clear</button></div>
        </div>

        <table class="table table-striped table-sm text-nowrap small table-sticky-header">
          <thead><tr><th>Date</th><th>Product</th><th>Job #</th><th>Type</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end fw-bold">Balance</th></tr></thead>
          <tbody>
            <tr v-for="a in auditData" :key="a.id">
              <td>{{ formatDate(a.transaction_date) }}</td>
              <td>{{ a.product?.item_code }} - {{ a.product?.item_name }}</td>
              <td>{{ a.job_number || '-' }}</td>
              <td><span class="badge" :class="typeBadge(a.transaction_type)">{{ a.transaction_type }}</span></td>
              <td class="text-end text-success">{{ a.quantity_in > 0 ? fmt(a.quantity_in) : '-' }}</td>
              <td class="text-end text-danger">{{ a.quantity_out > 0 ? fmt(a.quantity_out) : '-' }}</td>
              <td class="text-end fw-bold text-primary">{{ fmt(a.balance_after) }}</td>
            </tr>
            <tr v-if="auditData.length === 0"><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
          </tbody>
        </table>
      </el-tab-pane>
      
      <!-- D. Reversal Entries Report -->
      <el-tab-pane label="Reversal Report" name="reversal">
        <div class="row mb-3 g-2 align-items-end">
          <div class="col-md-2"><label class="small text-muted">Customer</label>
            <v-select v-model="reversalFilters.customer_id" :options="customers" label="name" :reduce="c => c.id" placeholder="All" :clearable="true" class="v-select-sm" @option:selected="fetchReversalReport" @option:deselected="fetchReversalReport"></v-select></div>
          <div class="col-md-2"><label class="small text-muted">Item / Code</label><input v-model="reversalFilters.item_search" @input="debouncedReversalFetch" type="text" class="form-control form-control-sm" placeholder="Search..."></div>
          <div class="col-md-2"><label class="small text-muted">Type</label>
            <select v-model="reversalFilters.reversal_type" @change="fetchReversalReport" class="form-control form-control-sm"><option value="">All Reversals</option><option value="receipt_reversal">Receipt Reversal</option><option value="dispatch_reversal">Dispatch Reversal</option><option value="damage_reversal">Damage Reversal</option></select></div>
          <div class="col-md-2"><label class="small text-muted">From</label><input v-model="reversalFilters.date_from" type="date" class="form-control form-control-sm" @change="fetchReversalReport"></div>
          <div class="col-md-2"><label class="small text-muted">To</label><input v-model="reversalFilters.date_to" type="date" class="form-control form-control-sm" @change="fetchReversalReport"></div>
          <div class="col-md-1"><button @click="reversalFilters = { customer_id: '', reversal_type: '', date_from: '', date_to: '', item_search: '' }; fetchReversalReport()" class="btn btn-sm btn-clear-filters w-100">Clear</button></div>
        </div>

        <table class="table table-striped table-sm text-nowrap small table-sticky-header">
          <thead><tr><th>Date</th><th>Document #</th><th>Product</th><th>Job #</th><th>Type</th><th class="text-end">Qty Restored</th><th>Remarks</th></tr></thead>
          <tbody>
            <tr v-for="a in reversalData" :key="a.id">
              <td>{{ formatDate(a.transaction_date) }}</td>
              <td class="fw-bold">{{ a.document_number }}</td>
              <td>{{ a.product?.item_code }} - {{ a.product?.item_name }}</td>
              <td>{{ a.job_number || '-' }}</td>
              <td><span class="badge" :class="typeBadge(a.transaction_type)">{{ a.transaction_type }}</span></td>
              <td class="text-end fw-bold" :class="a.quantity_in > 0 ? 'text-success' : 'text-danger'">
                {{ a.quantity_in > 0 ? fmt(a.quantity_in) : fmt(a.quantity_out) }}
              </td>
              <td>{{ a.remarks || '-' }}</td>
            </tr>
            <tr v-if="reversalData.length === 0"><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
          </tbody>
        </table>
      </el-tab-pane>
    </el-tabs>

    <!-- Job Detail Modal -->
    <el-dialog v-model="showJobModal" title="Job Detail" width="800px" :close-on-click-modal="false">
      <div v-if="jobDetail">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
          <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-info-circle-fill me-1"></i> Job Summary</h5>
          <el-button type="primary" size="small" @click="printJobDetail">
            <i class="bi bi-printer me-1"></i> Print Job Report
          </el-button>
        </div>
        <div class="row mb-3">
          <div class="col-md-4"><strong>Job #:</strong> {{ jobDetail.job_number }}</div>
          <div class="col-md-4"><strong>Customer:</strong> {{ jobDetail.customer?.name }}</div>
          <div class="col-md-4"><strong>Product:</strong> {{ jobDetail.product?.item_code }} - {{ jobDetail.product?.item_name }}</div>
        </div>
        <div class="row mb-3">
          <div class="col-md-3"><span class="badge bg-success fs-6 w-100 py-2">Produced: {{ fmt(jobDetail.total_produced) }}</span></div>
          <div class="col-md-3"><span class="badge bg-danger fs-6 w-100 py-2">Dispatched: {{ fmt(jobDetail.total_dispatched) }}</span></div>
          <div class="col-md-3"><span class="badge bg-warning fs-6 w-100 py-2 text-dark">Damaged: {{ fmt(jobDetail.total_damaged) }}</span></div>
          <div class="col-md-3"><span class="badge bg-primary fs-6 w-100 py-2">Remaining: {{ fmt(jobDetail.remaining_balance) }}</span></div>
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
        <h6 class="mt-3 mb-2 fw-bold" v-if="jobDetail.damages && jobDetail.damages.length">Damage Entries</h6>
        <table class="table table-sm table-bordered small" v-if="jobDetail.damages && jobDetail.damages.length">
          <thead><tr><th>Date</th><th>DMG #</th><th>Reason</th><th class="text-end">Qty</th><th>Remarks</th></tr></thead>
          <tbody>
            <tr v-for="dmg in jobDetail.damages" :key="dmg.id">
              <td>{{ formatDate(dmg.date) }}</td><td class="fw-bold">{{ dmg.damage_number }}</td><td>{{ dmg.reason }}</td><td class="text-end text-warning fw-bold">{{ fmt(dmg.quantity) }}</td>
              <td>{{ dmg.remarks || '-' }}</td>
            </tr>
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
          <strong>Total Balance:</strong> <span class="fw-bold text-primary">{{ fmt(selectedProductForJobs?.current_balance) }}</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-striped table-bordered small mb-0">
            <thead>
              <tr class="table-dark">
                <th>Job #</th>
                <th>Customer</th>
                <th class="text-end">Produced</th>
                <th class="text-end">Dispatched</th>
                <th class="text-end text-warning">Damaged</th>
                <th class="text-end text-danger">Waste</th>
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
                <td class="text-end text-warning">{{ fmt(job.total_damaged) }}</td>
                <td class="text-end text-danger-emphasis">{{ fmt(job.total_wastage) }}</td>
                <td class="text-end fw-bold text-primary">{{ fmt(job.remaining_balance) }}</td>
                <td class="text-center">
                  <button @click="showJobDetail(job); showProductJobsModal = false" class="btn btn-sm btn-outline-primary py-0 px-2">
                    <i class="bi bi-eye"></i> View Detail
                  </button>
                </td>
              </tr>
              <tr v-if="productJobsData.length === 0">
                <td colspan="8" class="text-center text-muted py-3">No active jobs contributing to this balance (might be opening balance stock).</td>
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
  props: { 
    user: { type: Object, default: null },
    canSeeAmounts: { type: Boolean, default: false }
  },
  data() {
    return {
      activeTab: 'stock',
      customers: [], products: [],
      stockData: [], stockFilters: { customer_id: '', date_from: '', date_to: '', item_search: '' },
      jobData: [], jobFilters: { customer_id: '', job_number: '', date_from: '', date_to: '' },
      auditData: [], auditFilters: { customer_id: '', transaction_type: '', date_from: '', date_to: '', item_search: '' },
      reversalData: [], reversalFilters: { customer_id: '', reversal_type: '', date_from: '', date_to: '', item_search: '' },
      showJobModal: false, jobDetail: null,
      showProductJobsModal: false,
      productJobsData: null,
      loadingProductJobs: false,
      selectedProductForJobs: null,
      searchTimeout: null,
      stockSearchTimeout: null,
      auditSearchTimeout: null,
      reversalSearchTimeout: null,
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg'
    };
  },
  computed: {
    hasData() {
      if (this.activeTab === 'stock') return this.stockData.length > 0;
      if (this.activeTab === 'job') return this.jobData.length > 0;
      if (this.activeTab === 'audit') return this.auditData.length > 0;
      if (this.activeTab === 'reversal') return this.reversalData.length > 0;
      return false;
    },
    printTitle() {
      const titles = { 
        stock: 'Finished Goods Stock Report', 
        job: 'Job-wise Report', 
        audit: 'Inventory Ledger Report',
        reversal: 'Finished Goods Reversals Report'
      };
      return titles[this.activeTab] || 'FG Report';
    },
    printFilterSummary() {
      const parts = [];
      let filters = {};
      if (this.activeTab === 'stock') filters = this.stockFilters;
      else if (this.activeTab === 'job') filters = this.jobFilters;
      else if (this.activeTab === 'audit') filters = this.auditFilters;
      else if (this.activeTab === 'reversal') filters = this.reversalFilters;

      if (filters.customer_id) {
        const c = this.customers.find(x => x.id == filters.customer_id);
        if (c) parts.push(`Customer: <strong>${c.name}</strong>`);
      }
      if (filters.date_from && filters.date_to) {
        parts.push(`Period: <strong>${this.formatDate(filters.date_from)} — ${this.formatDate(filters.date_to)}</strong>`);
      }
      if (filters.transaction_type) {
        parts.push(`Type: <strong>${filters.transaction_type}</strong>`);
      }
      if (filters.reversal_type) {
        parts.push(`Reversal Type: <strong>${filters.reversal_type}</strong>`);
      }
      if (filters.job_number) {
        parts.push(`Job: <strong>${filters.job_number}</strong>`);
      }
      if (filters.item_search) {
        parts.push(`Item Search: <strong>${filters.item_search}</strong>`);
      }
      return parts.length > 0 ? parts.join(' &nbsp;|&nbsp; ') : 'All Data';
    }
  },
  mounted() {
    if (localStorage.getItem('token')) axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    this.fetchFilters(); this.fetchStockReport();
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
        } else {
          this.companyLogo = window.location.origin + '/images/quality-cartons-logo.svg';
        }
      }).catch(error => { console.error('Error fetching company settings:', error); });
    },
    fetchFilters() {
      axios.get('/api/fg-reports/filters').then(r => { this.customers = r.data.customers; this.products = r.data.products; });
    },
    handleTabChange() {
      if (this.activeTab === 'stock') this.fetchStockReport();
      else if (this.activeTab === 'job') this.fetchJobReport();
      else if (this.activeTab === 'audit') this.fetchAuditReport();
      else if (this.activeTab === 'reversal') this.fetchReversalReport();
    },
    fetchStockReport() {
      const params = { ...this.stockFilters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-reports/stock', { params })
        .then(r => { this.stockData = r.data; })
        .catch(err => {
          if (axios.isCancel(err)) return;
          if (err.response?.data?.errors) {
            const msgs = Object.values(err.response.data.errors).flat().join('\n');
            this.$message.error(msgs);
          } else {
            this.$message.error(err.response?.data?.error || err.response?.data?.message || 'Error fetching stock report.');
          }
        });
    },
    fetchJobReport(page = 1) {
      const params = { page, ...this.jobFilters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-reports/job', { params })
        .then(r => { this.jobData = r.data.data || r.data; })
        .catch(err => {
          if (axios.isCancel(err)) return;
          if (err.response?.data?.errors) {
            const msgs = Object.values(err.response.data.errors).flat().join('\n');
            this.$message.error(msgs);
          } else {
            this.$message.error(err.response?.data?.error || err.response?.data?.message || 'Error fetching job report.');
          }
        });
    },
    debouncedJobFetch() { clearTimeout(this.searchTimeout); this.searchTimeout = setTimeout(() => this.fetchJobReport(), 400); },
    debouncedStockFetch() { clearTimeout(this.stockSearchTimeout); this.stockSearchTimeout = setTimeout(() => this.fetchStockReport(), 400); },
    debouncedAuditFetch() { clearTimeout(this.auditSearchTimeout); this.auditSearchTimeout = setTimeout(() => this.fetchAuditReport(), 400); },
    debouncedReversalFetch() { clearTimeout(this.reversalSearchTimeout); this.reversalSearchTimeout = setTimeout(() => this.fetchReversalReport(), 400); },
    showJobDetail(job) {
      axios.get('/api/fg-reports/job-detail', { params: { job_number: job.job_number, product_id: job.product_id } })
        .then(r => { this.jobDetail = r.data; this.showJobModal = true; })
        .catch(err => {
          if (axios.isCancel(err)) return;
          this.$message.error(err.response?.data?.error || err.response?.data?.message || 'Error fetching job details.');
        });
    },
    fetchAuditReport(page = 1) {
      const params = { page, ...this.auditFilters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-reports/audit', { params })
        .then(r => { this.auditData = r.data.data || r.data; })
        .catch(err => {
          if (axios.isCancel(err)) return;
          if (err.response?.data?.errors) {
            const msgs = Object.values(err.response.data.errors).flat().join('\n');
            this.$message.error(msgs);
          } else {
            this.$message.error(err.response?.data?.error || err.response?.data?.message || 'Error fetching ledger report.');
          }
        });
    },
    fetchReversalReport(page = 1) {
      const params = { page, ...this.reversalFilters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-reports/reversals', { params })
        .then(r => { this.reversalData = r.data.data || r.data; })
        .catch(err => {
          if (axios.isCancel(err)) return;
          if (err.response?.data?.errors) {
            const msgs = Object.values(err.response.data.errors).flat().join('\n');
            this.$message.error(msgs);
          } else {
            this.$message.error(err.response?.data?.error || err.response?.data?.message || 'Error fetching reversals report.');
          }
        });
    },
    typeBadge(type) {
      const map = { 
        opening: 'bg-info', 
        receipt: 'bg-success', 
        dispatch: 'bg-danger', 
        damage: 'bg-warning-subtle text-warning-emphasis border border-warning', 
        receipt_reversal: 'bg-danger-subtle text-danger-emphasis border border-danger', 
        dispatch_reversal: 'bg-success-subtle text-success-emphasis border border-success', 
        damage_reversal: 'bg-info-subtle text-info-emphasis border border-info', 
        adjustment: 'bg-warning' 
      };
      return map[type] || 'bg-secondary';
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
    fmt(v) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }); },
    fmtRate(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

    // ===== Export Functions =====
    printReport() {
      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        alert('Please allow pop-ups to print the report.');
        return;
      }
      
      const now = new Date();
      const timestamp = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

      printWindow.document.write(`
        <html>
          <head>
            <title>${this.printTitle} - Quality Cartons</title>
            <style>
              @page { size: A4 portrait; margin: 3mm 5mm 3mm 5mm; @bottom-center { content: "Page " counter(page) " of " counter(pages); font-size: 10px; color: #000; } }
              body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #000; }
              .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; color: #000; }
              .logo-section { flex-shrink: 0; }
              .logo { width: 1in; height: 1in; }
              .company-info { flex-grow: 1; text-align: center; color: #000; }
              .company-name { font-size: 28px; font-weight: bold; color: #000; font-family: 'Georgia', serif; }
              .company-address { font-size: 14px; color: #000; font-family: 'Georgia', serif; }
              table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; color: #000; }
              th, td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; color: #000; font-size: 12px; }
              th { background-color: #f8f9fa; color: #000; font-weight: bold; text-align: left; font-size: 13px; text-transform: uppercase; }
              .text-end { text-align: right !important; }
              .text-center { text-align: center !important; }
              .fw-bold { font-weight: bold !important; }
              .total-row { background-color: #e8e8e8; }
              .report-title { font-size: 20px; font-weight: bold; color: #000; margin-top: 10px; text-transform: uppercase; }
              .report-period { font-size: 14px; color: #000; margin-top: 5px; }
              .customer-heading { margin-top: 25px; margin-bottom: 5px; font-size: 16px; color: #000; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
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
                <div class="report-title">${this.printTitle}</div>
                <div class="report-period">As on: ${timestamp} | Filters: ${this.printFilterSummary}</div>
              </div>
            </div>
            ${this.generatePrintHTML()}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    printJobDetail() {
      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        alert('Please allow pop-ups to print the report.');
        return;
      }
      const now = new Date();
      const timestamp = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

      let html = `
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
          <div style="flex-shrink: 0;"><img src="${this.companyLogo}" alt="Logo" style="width: 1in; height: 1in;"></div>
          <div style="flex-grow: 1; text-align: center;">
            <div style="font-size: 26px; font-weight: bold; font-family: 'Georgia', serif;">${this.companyName}</div>
            <div style="font-size: 13px; font-family: 'Georgia', serif;">${this.companyAddress}</div>
            <div style="font-size: 18px; font-weight: bold; margin-top: 8px; text-transform: uppercase;">Job Detail Report</div>
            <div style="font-size: 12px; margin-top: 3px;">Printed on: ${timestamp}</div>
          </div>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
          <tr>
            <td style="border: none; padding: 4px 0; font-size: 13px;"><strong>Job #:</strong> ${this.jobDetail.job_number}</td>
            <td style="border: none; padding: 4px 0; font-size: 13px;"><strong>Customer:</strong> ${this.jobDetail.customer?.name || ''}</td>
            <td style="border: none; padding: 4px 0; font-size: 13px;"><strong>Product:</strong> ${this.jobDetail.product?.item_code || ''} - ${this.jobDetail.product?.item_name || ''}</td>
          </tr>
        </table>

        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
          <div style="flex: 1; border: 1px solid #000; padding: 8px; text-align: center; background-color: #f8f9fa; border-radius: 4px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #555;">Produced</div>
            <div style="font-size: 16px; font-weight: bold; color: green; margin-top: 3px;">${this.fmt(this.jobDetail.total_produced)}</div>
          </div>
          <div style="flex: 1; border: 1px solid #000; padding: 8px; text-align: center; background-color: #f8f9fa; border-radius: 4px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #555;">Dispatched</div>
            <div style="font-size: 16px; font-weight: bold; color: red; margin-top: 3px;">${this.fmt(this.jobDetail.total_dispatched)}</div>
          </div>
          <div style="flex: 1; border: 1px solid #000; padding: 8px; text-align: center; background-color: #f8f9fa; border-radius: 4px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #555;">Damaged</div>
            <div style="font-size: 16px; font-weight: bold; color: #d97706; margin-top: 3px;">${this.fmt(this.jobDetail.total_damaged)}</div>
          </div>
          <div style="flex: 1; border: 1px solid #000; padding: 8px; text-align: center; background-color: #f8f9fa; border-radius: 4px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #555;">Remaining</div>
            <div style="font-size: 16px; font-weight: bold; color: blue; margin-top: 3px;">${this.fmt(this.jobDetail.remaining_balance)}</div>
          </div>
        </div>

        <h4 style="margin-top: 15px; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 3px;">Production Entries</h4>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
          <thead>
            <tr style="background-color: #f3f4f6;">
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">Date</th>
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right;">Qty Produced</th>
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right;">Wastage</th>
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">Remarks</th>
            </tr>
          </thead>
          <tbody>
      `;

      this.jobDetail.receipts.forEach(r => {
        html += `
          <tr>
            <td style="border: 1px solid #000; padding: 6px; font-size: 11px;">${this.formatDate(r.date)}</td>
            <td style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right; font-weight: bold; color: green;">${this.fmt(r.quantity_produced)}</td>
            <td style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right;">${this.fmt(r.wastage)}</td>
            <td style="border: 1px solid #000; padding: 6px; font-size: 11px;">${r.remarks || '-'}</td>
          </tr>
        `;
      });

      html += `
          </tbody>
        </table>

        <h4 style="margin-top: 15px; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 3px;">Dispatch Entries</h4>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
          <thead>
            <tr style="background-color: #f3f4f6;">
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">Date</th>
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">DC #</th>
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right;">Qty</th>
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right;">Running Bal.</th>
              <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">Remarks</th>
            </tr>
          </thead>
          <tbody>
      `;

      if (this.jobDetail.dispatches.length === 0) {
        html += `<tr><td colspan="5" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11px; color: #777;">No dispatches yet.</td></tr>`;
      } else {
        this.jobDetail.dispatches.forEach(d => {
          html += `
            <tr>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px;">${this.formatDate(d.date)}</td>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px; font-weight: bold;">${d.dc_number}</td>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right; color: red;">${this.fmt(d.quantity_dispatched)}</td>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right; font-weight: bold;">${this.fmt(d.running_balance)}</td>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px;">${d.remarks || '-'}</td>
            </tr>
          `;
        });
      }

      html += `
          </tbody>
        </table>
      `;

      if (this.jobDetail.damages && this.jobDetail.damages.length > 0) {
        html += `
          <h4 style="margin-top: 15px; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 3px;">Damage Entries</h4>
          <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
              <tr style="background-color: #f3f4f6;">
                <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">Date</th>
                <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">DMG #</th>
                <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">Reason</th>
                <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right;">Qty</th>
                <th style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: left;">Remarks</th>
              </tr>
            </thead>
            <tbody>
        `;
        this.jobDetail.damages.forEach(dmg => {
          html += `
            <tr>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px;">${this.formatDate(dmg.date)}</td>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px; font-weight: bold;">${dmg.damage_number}</td>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px;">${dmg.reason}</td>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px; text-align: right; color: #d97706; font-weight: bold;">${this.fmt(dmg.quantity)}</td>
              <td style="border: 1px solid #000; padding: 6px; font-size: 11px;">${dmg.remarks || '-'}</td>
            </tr>
          `;
        });
        html += `
            </tbody>
          </table>
        `;
      }

      printWindow.document.write(`
        <html>
          <head>
            <title>Job ${this.jobDetail.job_number} Detail - Quality Cartons</title>
            <style>
              @page { size: A4 portrait; margin: 10mm; }
              body { font-family: Arial, sans-serif; margin: 0; padding: 10px; color: #000; }
              table { font-size: 11px; }
              th, td { border: 1px solid #000; }
            </style>
          </head>
          <body>
            ${html}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    generatePrintHTML() {
      let html = '';
      if (this.activeTab === 'stock') {
        this.stockData.forEach(group => {
          html += `<div class="customer-heading"><strong>Customer:</strong> ${group.customer}</div>`;
          html += `<table>
            <thead><tr><th class="text-center">Item Code</th><th class="text-center" style="width: 35%;">Item Name</th><th class="text-center">Opening</th><th class="text-center">Produced</th><th class="text-center">Dispatched</th><th class="text-center">Balance</th>${this.canSeeAmounts ? '<th class="text-center">Amount</th>' : ''}</tr></thead>
            <tbody>`;
          group.products.forEach(p => {
            html += `<tr>
              <td class="fw-bold">${p.item_code}</td><td>${p.item_name}</td>
              <td class="text-end">${this.fmt(p.opening_balance)}</td><td class="text-end">${this.fmt(p.total_produced)}</td>
              <td class="text-end">${this.fmt(p.total_dispatched)}</td><td class="text-end">${this.fmt(p.total_damaged)}</td><td class="text-end fw-bold">${this.fmt(p.current_balance)}</td>
              ${this.canSeeAmounts ? `<td class="text-end fw-bold">${this.fmt(p.amount)}</td>` : ''}
            </tr>`;
          });
          html += `<tr class="total-row"><td colspan="3" class="fw-bold">Total</td><td class="text-end fw-bold">${this.fmt(group.total_produced)}</td><td class="text-end fw-bold">${this.fmt(group.total_dispatched)}</td><td class="text-end fw-bold">${this.fmt(group.total_damaged)}</td><td class="text-end fw-bold">${this.fmt(group.total_balance)}</td>${this.canSeeAmounts ? `<td class="text-end fw-bold">${this.fmt(group.total_amount)}</td>` : ''}</tr>
            </tbody></table>`;
        });
      } else if (this.activeTab === 'job') {
        html += `<table>
          <thead><tr><th>Job #</th><th>Customer</th><th>Product</th><th class="text-end">Produced</th><th class="text-end">Dispatched</th><th class="text-end">Damaged</th><th class="text-end">Waste</th><th class="text-end">Remaining</th></tr></thead>
          <tbody>`;
        this.jobData.forEach(j => {
          html += `<tr>
            <td class="fw-bold">${j.job_number}</td><td>${j.customer?.name || ''}</td>
            <td>${j.product?.item_code || ''} - ${j.product?.item_name || ''}</td>
            <td class="text-end">${this.fmt(j.total_produced)}</td><td class="text-end">${this.fmt(j.total_dispatched)}</td>
            <td class="text-end">${this.fmt(j.total_damaged)}</td><td class="text-end">${this.fmt(j.total_wastage)}</td><td class="text-end fw-bold">${this.fmt(j.remaining_balance)}</td>
          </tr>`;
        });
        html += `</tbody></table>`;
      } else if (this.activeTab === 'audit') {
        html += `<table>
          <thead><tr><th>Date</th><th>Product</th><th>Job #</th><th>Type</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Balance</th></tr></thead>
          <tbody>`;
        this.auditData.forEach(a => {
          html += `<tr>
            <td>${this.formatDate(a.transaction_date)}</td><td>${a.product?.item_code || ''} - ${a.product?.item_name || ''}</td>
            <td>${a.job_number || '-'}</td><td>${a.transaction_type}</td>
            <td class="text-end">${a.quantity_in > 0 ? this.fmt(a.quantity_in) : '-'}</td>
            <td class="text-end">${a.quantity_out > 0 ? this.fmt(a.quantity_out) : '-'}</td>
            <td class="text-end fw-bold">${this.fmt(a.balance_after)}</td>
          </tr>`;
        });
        html += `</tbody></table>`;
      } else if (this.activeTab === 'reversal') {
        html += `<table>
          <thead><tr><th>Date</th><th>Document #</th><th>Product</th><th>Job #</th><th>Type</th><th class="text-end">Qty Restored</th><th>Remarks</th></tr></thead>
          <tbody>`;
        this.reversalData.forEach(a => {
          html += `<tr>
            <td>${this.formatDate(a.transaction_date)}</td>
            <td><strong>${a.document_number}</strong></td>
            <td>${a.product?.item_code || ''} - ${a.product?.item_name || ''}</td>
            <td>${a.job_number || '-'}</td>
            <td>${a.transaction_type}</td>
            <td class="text-end" style="font-weight: bold;">${a.quantity_in > 0 ? this.fmt(a.quantity_in) : this.fmt(a.quantity_out)}</td>
            <td>${a.remarks || '-'}</td>
          </tr>`;
        });
        html += `</tbody></table>`;
      }
      return html;
    },
    exportToPDF() {
      this.printReport();
    },
    exportToExcel() {
      let data = [];
      let sheetName = 'Report';
      let fileName = 'FG_Report';

      if (this.activeTab === 'stock') {
        sheetName = 'Stock_Report';
        fileName = 'FG_Stock_Report';
        this.stockData.forEach(group => {
          group.products.forEach(p => {
            data.push({
              'Customer': group.customer,
              'Item Code': p.item_code,
              'Item Name': p.item_name,
              'Opening Balance': Number(p.opening_balance),
              'Total Produced': Number(p.total_produced),
              'Total Dispatched': Number(p.total_dispatched),
              'Total Damaged': Number(p.total_damaged),
              'Current Balance': Number(p.current_balance),
              ...(this.canSeeAmounts ? { 'Amount': Number(p.amount) } : {})
            });
          });
        });
      } else if (this.activeTab === 'job') {
        sheetName = 'Job_Report';
        fileName = 'FG_Job_Report';
        this.jobData.forEach(j => {
          data.push({
            'Job #': j.job_number,
            'Customer': j.customer?.name || '',
            'Product': (j.product?.item_code || '') + ' - ' + (j.product?.item_name || ''),
            'Total Produced': Number(j.total_produced),
            'Total Dispatched': Number(j.total_dispatched),
            'Total Damaged': Number(j.total_damaged),
            'Total Waste': Number(j.total_wastage || 0),
            'Remaining': Number(j.remaining_balance)
          });
        });
      } else if (this.activeTab === 'audit') {
        sheetName = 'Ledger';
        fileName = 'FG_Inventory_Ledger';
        this.auditData.forEach(a => {
          data.push({
            'Date': this.formatDate(a.transaction_date),
            'Product': (a.product?.item_code || '') + ' - ' + (a.product?.item_name || ''),
            'Job #': a.job_number || '',
            'Type': a.transaction_type,
            'Qty In': Number(a.quantity_in),
            'Qty Out': Number(a.quantity_out),
            'Balance': Number(a.balance_after)
          });
        });
      } else if (this.activeTab === 'reversal') {
        sheetName = 'Reversals';
        fileName = 'FG_Reversals_Report';
        this.reversalData.forEach(a => {
          data.push({
            'Date': this.formatDate(a.transaction_date),
            'Document #': a.document_number,
            'Product': (a.product?.item_code || '') + ' - ' + (a.product?.item_name || ''),
            'Job #': a.job_number || '',
            'Type': a.transaction_type,
            'Qty Restored': a.quantity_in > 0 ? Number(a.quantity_in) : Number(a.quantity_out),
            'Remarks/Reason': a.remarks || ''
          });
        });
      }

      const worksheet = XLSX.utils.json_to_sheet(data);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
      XLSX.writeFile(workbook, `${fileName}_${new Date().toISOString().substr(0, 10)}.xlsx`);
      this.$message.success('Exported to Excel successfully!');
    }
  }
};
</script>

<style scoped>
.custom-dashboard-tabs :deep(.el-tabs__item) { font-weight: 600; font-size: 1rem; height: 50px; }
.custom-dashboard-tabs :deep(.el-tabs__active-bar) { background-color: #6366f1; height: 3px; }

[data-theme="dark"] .fg-report-wrapper .card-header {
  background: #334155 !important;
  color: #e2e8f0 !important;
}

[data-theme="dark"] .fg-report-wrapper .table,
[data-theme="dark"] .fg-report-wrapper .table th,
[data-theme="dark"] .fg-report-wrapper .table td {
  color: #e2e8f0 !important;
  border-color: #475569 !important;
}

[data-theme="dark"] .fg-report-wrapper .table-secondary,
[data-theme="dark"] .fg-report-wrapper .table-secondary td {
  background: #334155 !important;
}

[data-theme="dark"] .fg-report-wrapper .form-control,
[data-theme="dark"] .fg-report-wrapper .form-select {
  background: #1e293b !important;
  color: #e2e8f0 !important;
  border-color: #475569 !important;
}

[data-theme="dark"] .fg-report-wrapper .text-muted {
  color: #aebed3 !important;
}
</style>
