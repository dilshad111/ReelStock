<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-arrow-down-circle-fill"></i> FG Production Entry</h2>
      <button @click="showForm = true; editing = false; resetForm()" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Receipt</button>
    </div>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>{{ editing ? 'Edit' : 'Add' }} Production Entry</h5>
        <form @submit.prevent="saveReceipt" novalidate>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label>Date <span class="text-danger">*</span></label>
                <input v-model="form.date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Customer <span class="text-danger">*</span></label>
                <select v-model="form.customer_id" @change="onCustomerChange" class="form-control" required>
                  <option value="">Select Customer</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Product <span class="text-danger">*</span></label>
                <select v-model="form.product_id" @change="onProductChange" class="form-control" required>
                  <option value="">Select Product</option>
                  <option v-for="p in filteredProducts" :key="p.id" :value="p.id">{{ p.item_code }} - {{ p.item_name }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label>Job # <span class="text-danger">*</span></label>
                <input v-model="form.job_number" type="text" class="form-control" required placeholder="e.g. JOB-2026-001">
              </div>
              <div class="mb-3">
                <label>Production Date <span class="text-danger">*</span></label>
                <input v-model="form.production_date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Quantity Produced <span class="text-danger">*</span></label>
                <input v-model="form.quantity_produced" type="number" step="0.01" class="form-control" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label>Carton Price</label>
                <input v-model="form.carton_price" type="number" step="0.01" class="form-control">
              </div>
              <div class="mb-3">
                <label>Wastage</label>
                <input v-model="form.wastage" type="number" step="0.01" class="form-control">
              </div>
              <div class="mb-3">
                <label>Remarks</label>
                <textarea v-model="form.remarks" class="form-control" rows="2"></textarea>
              </div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">{{ editing ? 'Update' : 'Save' }}</button>
            <button @click="showForm = false" type="button" class="btn btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3 g-2 align-items-end">
      <div class="col-md-2">
        <label class="small text-muted">Customer</label>
        <select v-model="filters.customer_id" @change="fetchReceipts" class="form-control form-control-sm">
          <option value="">All</option>
          <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="small text-muted">Job #</label>
        <input v-model="filters.job_number" @input="debouncedFetch" type="text" class="form-control form-control-sm" placeholder="Search...">
      </div>
      <div class="col-md-2">
        <label class="small text-muted">From</label>
        <input v-model="filters.date_from" type="date" class="form-control form-control-sm" @change="fetchReceipts">
      </div>
      <div class="col-md-2">
        <label class="small text-muted">To</label>
        <input v-model="filters.date_to" type="date" class="form-control form-control-sm" @change="fetchReceipts">
      </div>
      <div class="col-md-1">
        <label class="small text-muted">Show</label>
        <select v-model="filters.per_page" @change="fetchReceipts(1)" class="form-control form-control-sm">
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="200">200</option>
          <option value="500">500</option>
        </select>
      </div>
      <div class="col-md-1">
        <button @click="clearFilters" class="btn btn-sm btn-outline-secondary w-100">Clear</button>
      </div>
    </div>

    <table class="table table-striped table-sm text-nowrap small table-sticky-header">
      <thead>
        <tr>
          <th>S.No.</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Product</th>
          <th>Job #</th>
          <th>Prod. Date</th>
          <th class="text-end">Qty Produced</th>
          <th class="text-end">Price</th>
          <th class="text-end">Wastage</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(r, index) in receipts" :key="r.id">
          <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
          <td>{{ formatDate(r.date) }}</td>
          <td>{{ r.customer?.name }}</td>
          <td>{{ r.product?.item_code }} - {{ r.product?.item_name }}</td>
          <td class="fw-bold text-primary">{{ r.job_number }}</td>
          <td>{{ formatDate(r.production_date) }}</td>
          <td class="text-end fw-bold text-success">{{ formatNumber(r.quantity_produced) }}</td>
          <td class="text-end fw-bold text-dark">{{ formatNumber(r.carton_price) }}</td>
          <td class="text-end">{{ formatNumber(r.wastage) }}</td>
          <td>
            <button @click="showDetail(r)" class="btn btn-sm btn-info me-1" title="View"><i class="bi bi-eye"></i></button>
            <button @click="editReceipt(r)" class="btn btn-sm btn-warning me-1" title="Edit"><i class="bi bi-pencil"></i></button>
            <button @click="deleteReceipt(r)" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
          </td>
        </tr>
        <tr v-if="receipts.length === 0"><td colspan="10" class="text-center text-muted py-4">No receipts found.</td></tr>
      </tbody>
      <tfoot v-if="receipts.length > 0">
        <tr class="table-info fw-bold">
          <td colspan="6" class="text-end">Page Totals:</td>
          <td class="text-end text-success">{{ formatNumber(pageTotals.quantity_produced) }}</td>
          <td></td>
          <td class="text-end">{{ formatNumber(pageTotals.wastage) }}</td>
          <td></td>
        </tr>
        <tr class="table-secondary fw-bold" v-if="grandTotals.quantity_produced > 0">
          <td colspan="6" class="text-end">Grand Totals:</td>
          <td class="text-end text-success">{{ formatNumber(grandTotals.quantity_produced) }}</td>
          <td></td>
          <td class="text-end">{{ formatNumber(grandTotals.wastage) }}</td>
          <td></td>
        </tr>
      </tfoot>
    </table>

    <div class="d-flex justify-content-center mt-3" v-if="pagination.last_page > 1">
      <nav><ul class="pagination">
        <li class="page-item" :class="{ disabled: pagination.current_page == 1 }"><a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">Previous</a></li>
        <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page == pagination.current_page }"><a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a></li>
        <li class="page-item" :class="{ disabled: pagination.current_page == pagination.last_page }"><a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">Next</a></li>
      </ul></nav>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedEntry" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-success text-white py-2">
            <h5 class="modal-title small fw-bold">Production Entry Details: {{ selectedEntry.job_number }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="selectedEntry = null"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Entry Date</label>
                  <span class="fw-bold">{{ formatDate(selectedEntry.date) }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Customer</label>
                  <span class="fw-bold">{{ selectedEntry.customer?.name }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Product</label>
                  <span class="fw-bold">{{ selectedEntry.product?.item_code }} - {{ selectedEntry.product?.item_name }}</span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Job Number</label>
                  <span class="badge bg-primary px-3 fs-6">{{ selectedEntry.job_number }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Production Date</label>
                  <span class="fw-bold">{{ formatDate(selectedEntry.production_date) }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Quantity Produced</label>
                  <span class="fw-bold fs-4 text-success">{{ formatNumber(selectedEntry.quantity_produced) }}</span>
                </div>
              </div>
              <div class="col-md-4 border-top pt-3">
                <label class="small text-muted fw-bold d-block">Carton Price</label>
                <span class="fw-bold">{{ formatNumber(selectedEntry.carton_price) }}</span>
              </div>
              <div class="col-md-4 border-top pt-3">
                <label class="small text-muted fw-bold d-block">Wastage</label>
                <span class="fw-bold">{{ formatNumber(selectedEntry.wastage) }}</span>
              </div>
              <div class="col-md-4 border-top pt-3">
                <label class="small text-muted fw-bold d-block">Amount</label>
                <span class="fw-bold text-dark">{{ formatNumber(selectedEntry.quantity_produced * selectedEntry.carton_price) }}</span>
              </div>
              <div class="col-md-12 border-top pt-3">
                <label class="small text-muted fw-bold d-block">Remarks</label>
                <p class="mb-0 text-muted">{{ selectedEntry.remarks || 'No remarks provided.' }}</p>
              </div>
              <div class="col-md-12">
                <label class="small text-muted fw-bold d-block">Created By</label>
                <span class="small text-muted">{{ selectedEntry.creator?.name || 'Unknown' }}</span>
              </div>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" @click="selectedEntry = null">Close</button>
            <button type="button" class="btn btn-warning btn-sm" @click="editReceipt(selectedEntry); selectedEntry = null">Edit Entry</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  props: { user: { type: Object, default: null } },
  data() {
    const today = new Date().toISOString().substr(0, 10);
    return {
      receipts: [], customers: [], allProducts: [], filteredProducts: [],
      showForm: false, editing: false,
      form: { id: null, date: today, customer_id: '', product_id: '', job_number: '', production_date: today, quantity_produced: '', carton_price: '', wastage: 0, remarks: '' },
      filters: { customer_id: '', job_number: '', date_from: '', date_to: '', per_page: 50 },
      searchTimeout: null,
      pagination: { current_page: 1, last_page: 1, per_page: 50, total: 0 },
      grandTotals: { quantity_produced: 0, wastage: 0 },
      selectedEntry: null
    };
  },
  computed: {
    pages() { const c = this.pagination.current_page, l = this.pagination.last_page; let s = Math.max(1, c - 2), e = Math.min(l, c + 2), p = []; for (let i = s; i <= e; i++) p.push(i); return p; },
    pageTotals() {
      return this.receipts.reduce((acc, r) => {
        acc.quantity_produced += Number(r.quantity_produced || 0);
        acc.wastage += Number(r.wastage || 0);
        return acc;
      }, { quantity_produced: 0, wastage: 0 });
    }
  },
  mounted() {
    if (localStorage.getItem('token')) axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    this.fetchCustomers(); this.fetchReceipts();
  },
  methods: {
    fetchCustomers() { axios.get('/api/customers').then(r => { this.customers = r.data; }); },
    onCustomerChange() {
      this.form.product_id = '';
      if (this.form.customer_id) {
        axios.get(`/api/products/by-customer/${this.form.customer_id}`).then(r => { this.filteredProducts = r.data; });
      } else { this.filteredProducts = []; }
    },
    onProductChange() {
      if (this.form.product_id) {
        const product = this.filteredProducts.find(p => p.id === this.form.product_id);
        if (product) {
          this.form.carton_price = product.rate;
        }
      } else {
        this.form.carton_price = '';
      }
    },
    fetchReceipts(page = 1) {
      const params = { page, ...this.filters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-receipts', { params }).then(r => {
        const data = r.data.pagination || r.data;
        this.receipts = data.data;
        this.pagination = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
        if (r.data.totals) {
          this.grandTotals = {
            quantity_produced: r.data.totals.total_quantity_produced,
            wastage: r.data.totals.total_wastage
          };
        }
      });
    },
    showDetail(r) {
      this.selectedEntry = r;
    },
    debouncedFetch() { clearTimeout(this.searchTimeout); this.searchTimeout = setTimeout(() => this.fetchReceipts(), 400); },
    goToPage(p) { if (p >= 1 && p <= this.pagination.last_page) this.fetchReceipts(p); },
    clearFilters() { this.filters = { customer_id: '', job_number: '', date_from: '', date_to: '', per_page: 50 }; this.fetchReceipts(); },
    resetForm() { const t = new Date().toISOString().substr(0, 10); this.form = { id: null, date: t, customer_id: '', product_id: '', job_number: '', production_date: t, quantity_produced: '', carton_price: '', wastage: 0, remarks: '' }; this.filteredProducts = []; },
    saveReceipt() {
      if (!this.form.customer_id || !this.form.product_id || !this.form.job_number || !this.form.quantity_produced) { this.$message.error('Fill required fields.'); return; }
      const action = this.editing ? axios.put(`/api/fg-receipts/${this.form.id}`, this.form) : axios.post('/api/fg-receipts', this.form);
      action.then(() => { this.$message.success(this.editing ? 'Updated!' : 'Saved!'); this.showForm = false; this.fetchReceipts(); })
        .catch(err => { this.$message.error(err.response?.data?.error || 'Failed.'); });
    },
    editReceipt(r) {
      this.form = { id: r.id, date: r.date?.split('T')[0], customer_id: r.customer_id, product_id: r.product_id, job_number: r.job_number, production_date: r.production_date?.split('T')[0], quantity_produced: r.quantity_produced, carton_price: r.carton_price, wastage: r.wastage, remarks: r.remarks };
      this.onCustomerChange();
      this.editing = true; this.showForm = true;
    },
    deleteReceipt(r) {
      if (!confirm('Delete this receipt?')) return;
      axios.delete(`/api/fg-receipts/${r.id}`).then(() => { this.$message.success('Deleted.'); this.fetchReceipts(); })
        .catch(err => { this.$message.error(err.response?.data?.error || 'Cannot delete.'); });
    },
    formatDate(d) { if (!d) return '-'; const dt = new Date(d); return dt.toLocaleDateString('en-GB'); },
    formatNumber(v) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }); }
  }
};
</script>
