<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="page-title"><i class="bi bi-arrow-up-circle-fill text-primary"></i> FG Dispatch Entry</h2>
      <button @click="showForm = true; editing = false; resetForm()" class="btn btn-purple shadow-sm px-4 py-2">
        <i class="bi bi-plus-circle-fill me-1"></i> Add Dispatch
      </button>
    </div>

    <div v-if="showForm" class="row mb-5 fade-in">
      <div :class="jobInfo.job_number ? 'col-md-9' : 'col-md-12'">
        <div class="card shadow border-0">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ editing ? 'Edit' : 'Add' }} Dispatch Details</h6>
            <button @click="showForm = false" class="btn-close btn-close-white btn-sm"></button>
          </div>
          <div class="card-body p-3">
            <form @submit.prevent="saveDispatch" novalidate>
              <div class="row g-3">
                <!-- Column 1: Core Identifiers -->
                <div class="col-md-3">
                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Job # (Primary) <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input v-model="form.job_number" type="text" class="form-control form-control-sm border-primary" 
                             placeholder="Enter Job #..." @keyup.enter="fetchJobData">
                      <button class="btn btn-sm btn-primary px-2" type="button" @click="fetchJobData" :disabled="loadingJob">
                        <i class="bi bi-search" v-if="!loadingJob"></i>
                        <span v-else class="spinner-border spinner-border-sm" style="width: 12px; height: 12px;"></span>
                      </button>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Dispatch Date <span class="text-danger">*</span></label>
                    <input v-model="form.date" type="date" class="form-control form-control-sm" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">DC # <span class="text-danger">*</span></label>
                    <input v-model="form.dc_number" type="text" class="form-control form-control-sm" required placeholder="DC No.">
                  </div>
                </div>

                <!-- Column 2: Data Display (Expanded) -->
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Customer</label>
                    <div class="form-control form-control-sm bg-light border-0 text-truncate fw-bold" :title="jobInfo.customer?.name">
                      {{ jobInfo.customer ? jobInfo.customer.name : '---' }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Product Name</label>
                    <div class="form-control form-control-sm bg-light border-0 text-dark small text-nowrap overflow-hidden" style="text-overflow: ellipsis;" :title="jobInfo.product?.item_name">
                      <span class="fw-bold">{{ jobInfo.product?.item_code }}</span> - {{ jobInfo.product?.item_name || '---' }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1 text-primary">Qty to Dispatch <span class="text-danger">*</span></label>
                    <input v-model="form.quantity_dispatched" type="number" step="0.01" class="form-control form-control-sm border-primary fw-bold text-primary" required>
                  </div>
                </div>

                <!-- Column 3: Summary & Actions (Shrunk) -->
                <div class="col-md-3 border-start ps-3">
                  <div class="d-flex flex-column h-100 justify-content-between">
                    <div>
                      <label class="form-label fw-bold text-muted x-small uppercase mb-1">Job Balance</label>
                      <div class="balance-display py-2 px-3 rounded text-center mb-2" :class="jobInfo.balance > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'">
                        <span class="h3 fw-bold mb-0 d-block line-height-1">{{ formatNumber(jobInfo.balance) }}</span>
                        <div class="x-small opacity-75">Available Qty</div>
                      </div>
                      <div v-if="jobInfo.balance !== null && form.quantity_dispatched > jobInfo.balance" class="text-danger x-small fw-bold animate-pulse">
                        <i class="bi bi-exclamation-triangle-fill"></i> Exceeds balance!
                      </div>
                    </div>
                    
                    <div class="d-flex flex-column gap-2 mt-2">
                      <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 shadow-sm" :disabled="!jobInfo.product || (jobInfo.balance !== null && form.quantity_dispatched > jobInfo.balance)">
                        <i class="bi bi-check-circle-fill me-1"></i> Save Dispatch
                      </button>
                      <button @click="showForm = false" type="button" class="btn btn-outline-secondary btn-sm w-100 py-1">Cancel</button>
                    </div>
                  </div>
                </div>

                <!-- Footer Row: Remarks -->
                <div class="col-md-12 mt-1">
                  <div class="border-top pt-2">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Remarks / Notes</label>
                    <textarea v-model="form.remarks" class="form-control form-control-sm bg-light border-0" rows="1" placeholder="Add any notes here..."></textarea>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Right Panel: Job History (Shrunk) -->
      <div class="col-md-3" v-if="jobInfo.job_number">
        <div class="card shadow border-0 h-100 dispatch-history-card">
          <div class="card-header dispatch-header text-white py-2 px-3">
            <h6 class="mb-0 d-flex align-items-center gap-2 fw-bold" style="font-size: 0.85rem;">
              <i class="bi bi-clock-history fs-5"></i> 
              Dispatch History: Job # {{ jobInfo.job_number }}
            </h6>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0 custom-history-table">
                <thead>
                  <tr class="bg-light text-muted uppercase x-small">
                    <th class="ps-3 py-2">Date</th>
                    <th class="py-2 text-center">DC #</th>
                    <th class="text-end pe-3 py-2">Qty</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in jobInfo.history" :key="h.id" class="align-middle">
                    <td class="ps-3 py-1 text-muted small">{{ formatDate(h.date) }}</td>
                    <td class="fw-bold text-dark text-center small">{{ h.dc_number }}</td>
                    <td class="text-end pe-3 fw-bold text-dark small">{{ formatNumber(h.quantity_dispatched) }}</td>
                  </tr>
                  <tr v-if="!jobInfo.history || jobInfo.history.length === 0">
                    <td colspan="3" class="text-center text-muted py-4">
                      <div class="small opacity-50">No history found.</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer p-0 border-0 overflow-hidden rounded-bottom">
            <div class="d-flex justify-content-between align-items-center p-2 bg-white border-top border-bottom">
              <span class="fw-bold text-muted uppercase x-small ps-1">Total Dispatched</span>
              <span class="h5 fw-bold mb-0 text-dark pe-1">{{ formatNumber(jobInfo.total_dispatched) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center p-2 bg-cyan-light">
              <span class="fw-bold text-dark uppercase x-small ps-1">Total Produced</span>
              <span class="h5 fw-bold mb-0 text-dark pe-1">{{ formatNumber(jobInfo.total_produced) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
      <div class="card-body p-3">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-person"></i> Customer</label>
            <select v-model="filters.customer_id" @change="fetchDispatches" class="form-select border-0 bg-light">
              <option value="">All Customers</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-hash"></i> Job #</label>
            <input v-model="filters.job_number" @input="debouncedFetch" type="text" class="form-control border-0 bg-light" placeholder="Search...">
          </div>
          <div class="col-md-2">
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-file-earmark-text"></i> DC #</label>
            <input v-model="filters.dc_number" @input="debouncedFetch" type="text" class="form-control border-0 bg-light" placeholder="Search...">
          </div>
          <div class="col-md-4">
            <div class="row g-2">
              <div class="col-6">
                <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-calendar"></i> From</label>
                <input v-model="filters.date_from" type="date" class="form-control border-0 bg-light" @change="fetchDispatches">
              </div>
              <div class="col-6">
                <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-calendar"></i> To</label>
                <input v-model="filters.date_to" type="date" class="form-control border-0 bg-light" @change="fetchDispatches">
              </div>
            </div>
          </div>
          <div class="col-md-1">
            <button @click="clearFilters" class="btn btn-outline-danger border-0 w-100" title="Clear Filters"><i class="bi bi-eraser-fill"></i></button>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 custom-main-table">
          <thead class="table-dark">
            <tr>
              <th class="ps-4">S.No.</th>
              <th>Date</th>
              <th>Customer</th>
              <th>Product</th>
              <th class="text-center">Job #</th>
              <th class="text-center">DC #</th>
              <th class="text-end">Qty Dispatched</th>
              <th class="text-center pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(d, index) in dispatches" :key="d.id">
              <td class="ps-4 text-muted small">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
              <td class="fw-bold">{{ formatDate(d.date) }}</td>
              <td><span class="text-dark">{{ d.customer?.name }}</span></td>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-bold">{{ d.product?.item_name }}</span>
                  <span class="badge bg-light text-muted border-0 p-0 text-start">Code: {{ d.product?.item_code }}</span>
                </div>
              </td>
              <td class="text-center"><span class="badge bg-primary-subtle text-primary px-3">{{ d.job_number || '-' }}</span></td>
              <td class="text-center fw-bold text-dark">{{ d.dc_number }}</td>
              <td class="text-end fw-bold text-danger h5 mb-0">{{ formatNumber(d.quantity_dispatched) }}</td>
              <td class="text-center pe-4">
                <div class="btn-group shadow-sm rounded">
                  <button @click="editDispatch(d)" class="btn btn-sm btn-white text-warning border-end" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                  <button @click="deleteDispatch(d)" class="btn btn-sm btn-white text-danger" title="Delete"><i class="bi bi-trash-fill"></i></button>
                </div>
              </td>
            </tr>
            <tr v-if="dispatches.length === 0">
              <td colspan="8" class="text-center py-5">
                <div class="py-4">
                  <i class="bi bi-inbox fs-1 text-muted opacity-25"></i>
                  <h5 class="text-muted mt-3">No dispatch entries found matching your criteria.</h5>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4" v-if="pagination.total > 0">
      <div class="text-muted small">Showing {{ dispatches.length }} of {{ pagination.total }} entries</div>
      <nav v-if="pagination.last_page > 1">
        <ul class="pagination pagination-sm shadow-sm mb-0">
          <li class="page-item" :class="{ disabled: pagination.current_page == 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">Previous</a>
          </li>
          <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page == pagination.current_page }">
            <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page == pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">Next</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  props: { user: { type: Object, default: null } },
  data() {
    return {
      dispatches: [], 
      customers: [], 
      loadingJob: false,
      showForm: false, 
      editing: false,
      jobInfo: { job_number: '', customer: null, product: null, balance: null, total_produced: 0, total_dispatched: 0, history: [] },
      form: { id: null, date: new Date().toISOString().substr(0, 10), customer_id: '', product_id: '', job_number: '', dc_number: '', quantity_dispatched: '', remarks: '' },
      filters: { customer_id: '', job_number: '', dc_number: '', date_from: '', date_to: '' },
      searchTimeout: null,
      pagination: { current_page: 1, last_page: 1, per_page: 50, total: 0 }
    };
  },
  computed: {
    pages() { const c = this.pagination.current_page, l = this.pagination.last_page; let s = Math.max(1, c - 2), e = Math.min(l, c + 2), p = []; for (let i = s; i <= e; i++) p.push(i); return p; }
  },
  mounted() {
    if (localStorage.getItem('token')) axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    this.fetchCustomers(); this.fetchDispatches();
  },
  methods: {
    fetchCustomers() { axios.get('/api/customers').then(r => { this.customers = r.data; }); },
    
    fetchJobData() {
      if (!this.form.job_number) return;
      this.loadingJob = true;
      axios.get(`/api/fg-dispatches/job-details/${this.form.job_number}`)
        .then(r => {
          this.jobInfo = r.data;
          this.form.customer_id = r.data.customer.id;
          this.form.product_id = r.data.product.id;
          this.$message.success('Job details fetched.');
        })
        .catch(err => {
          this.jobInfo = { job_number: '', customer: null, product: null, balance: null, history: [] };
          this.$message.error(err.response?.data?.error || 'Job not found.');
        })
        .finally(() => { this.loadingJob = false; });
    },

    fetchDispatches(page = 1) {
      const params = { page, ...this.filters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-dispatches', { params }).then(r => {
        this.dispatches = r.data.data;
        this.pagination = { current_page: r.data.current_page, last_page: r.data.last_page, per_page: r.data.per_page, total: r.data.total };
      });
    },
    debouncedFetch() { clearTimeout(this.searchTimeout); this.searchTimeout = setTimeout(() => this.fetchDispatches(), 400); },
    goToPage(p) { if (p >= 1 && p <= this.pagination.last_page) this.fetchDispatches(p); },
    clearFilters() { this.filters = { customer_id: '', job_number: '', dc_number: '', date_from: '', date_to: '' }; this.fetchDispatches(); },
    
    resetForm() { 
      this.form = { id: null, date: new Date().toISOString().substr(0, 10), customer_id: '', product_id: '', job_number: '', dc_number: '', quantity_dispatched: '', remarks: '' }; 
      this.jobInfo = { job_number: '', customer: null, product: null, balance: null, history: [] };
    },

    saveDispatch() {
      if (!this.form.customer_id || !this.form.product_id || !this.form.dc_number || !this.form.quantity_dispatched) { 
        this.$message.error('Fill all required fields.'); return; 
      }
      const action = this.editing ? axios.put(`/api/fg-dispatches/${this.form.id}`, this.form) : axios.post('/api/fg-dispatches', this.form);
      action.then(() => { 
        this.$message.success(this.editing ? 'Updated successfully!' : 'Dispatch saved successfully!'); 
        this.showForm = false; 
        this.fetchDispatches(); 
      })
      .catch(err => { this.$message.error(err.response?.data?.error || 'Operation failed.'); });
    },

    editDispatch(d) {
      this.form = { 
        id: d.id, 
        date: d.date?.split('T')[0], 
        customer_id: d.customer_id, 
        product_id: d.product_id, 
        job_number: d.job_number, 
        dc_number: d.dc_number, 
        quantity_dispatched: d.quantity_dispatched, 
        remarks: d.remarks 
      };
      this.fetchJobData();
      this.editing = true; 
      this.showForm = true;
    },

    deleteDispatch(d) {
      if (!confirm('Are you sure you want to delete this dispatch entry?')) return;
      axios.delete(`/api/fg-dispatches/${d.id}`).then(() => { this.$message.success('Deleted.'); this.fetchDispatches(); })
        .catch(err => { this.$message.error(err.response?.data?.error || 'Cannot delete.'); });
    },
    formatDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-GB'); },
    formatNumber(v) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }); }
  }
};
</script>

<style scoped>
.page-title { font-weight: 800; color: #2d3436; letter-spacing: -0.5px; }
.uppercase { text-transform: uppercase; letter-spacing: 1px; }
.card { border-radius: 12px; transition: transform 0.2s; }
.btn-purple { background: #7048e8; color: white; border: none; font-weight: 600; border-radius: 8px; }
.btn-purple:hover { background: #5f3dc4; color: white; transform: translateY(-1px); }
.btn-white { background: white; border: 1px solid #eee; }
.btn-white:hover { background: #f8f9fa; }

.dispatch-header { background: #00BCD4; border-radius: 12px 12px 0 0 !important; }
.bg-cyan-light { background-color: #E0F7FA; }
.balance-display { background: #f1f3f5; border: 1px solid rgba(0,0,0,0.05); }

.custom-history-table thead th { background: transparent; border-bottom: 2px solid #f1f3f5; }
.custom-history-table tbody tr:last-child td { border-bottom: none; }

.custom-main-table thead th { font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem 0.5rem; }
.custom-main-table tbody td { padding: 1rem 0.5rem; }

.fade-in { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.25rem rgba(112, 72, 232, 0.15); border-color: #7048e8; }
</style>
