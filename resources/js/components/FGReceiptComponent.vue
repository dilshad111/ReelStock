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
      <div class="col-md-2">
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
            <button @click="editReceipt(r)" class="btn btn-sm btn-warning me-1">Edit</button>
            <button @click="deleteReceipt(r)" class="btn btn-sm btn-danger">Delete</button>
          </td>
        </tr>
        <tr v-if="receipts.length === 0"><td colspan="10" class="text-center text-muted py-4">No receipts found.</td></tr>
      </tbody>
    </table>

    <div class="d-flex justify-content-center mt-3" v-if="pagination.last_page > 1">
      <nav><ul class="pagination">
        <li class="page-item" :class="{ disabled: pagination.current_page == 1 }"><a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">Previous</a></li>
        <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page == pagination.current_page }"><a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a></li>
        <li class="page-item" :class="{ disabled: pagination.current_page == pagination.last_page }"><a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">Next</a></li>
      </ul></nav>
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
      filters: { customer_id: '', job_number: '', date_from: '', date_to: '' },
      searchTimeout: null,
      pagination: { current_page: 1, last_page: 1, per_page: 50, total: 0 }
    };
  },
  computed: {
    pages() { const c = this.pagination.current_page, l = this.pagination.last_page; let s = Math.max(1, c - 2), e = Math.min(l, c + 2), p = []; for (let i = s; i <= e; i++) p.push(i); return p; }
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
        this.receipts = r.data.data;
        this.pagination = { current_page: r.data.current_page, last_page: r.data.last_page, per_page: r.data.per_page, total: r.data.total };
      });
    },
    debouncedFetch() { clearTimeout(this.searchTimeout); this.searchTimeout = setTimeout(() => this.fetchReceipts(), 400); },
    goToPage(p) { if (p >= 1 && p <= this.pagination.last_page) this.fetchReceipts(p); },
    clearFilters() { this.filters = { customer_id: '', job_number: '', date_from: '', date_to: '' }; this.fetchReceipts(); },
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
