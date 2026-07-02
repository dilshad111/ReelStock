<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-exclamation-triangle-fill text-warning"></i> FG Damage / Wastage Entry</h2>
      <button @click="showForm = true; resetForm()" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Damage Log
      </button>
    </div>

    <!-- Form Section -->
    <div v-if="showForm" class="card mb-3 shadow-sm border-0">
      <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 small fw-bold"><i class="bi bi-pencil-square me-2"></i>Log Damaged Cartons</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="saveDamage" novalidate>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                <input v-model="form.date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Customer <span class="text-danger">*</span></label>
                <v-select
                  v-model="form.customer_id"
                  :options="customers"
                  label="name"
                  :reduce="c => c.id"
                  placeholder="Search customer..."
                  :clearable="false"
                  @option:selected="onCustomerChange"
                ></v-select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Product <span class="text-danger">*</span></label>
                <v-select
                  v-model="form.product_id"
                  :options="filteredProducts"
                  :get-option-label="p => p.item_code + ' - ' + p.item_name"
                  :reduce="p => p.id"
                  placeholder="Search product..."
                  :clearable="false"
                  @option:selected="onProductOrWarehouseChange"
                ></v-select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label fw-bold">Warehouse <span class="text-danger">*</span></label>
                <select v-model="form.warehouse_id" @change="onProductOrWarehouseChange" class="form-control" required>
                  <option value="">Select Warehouse</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.code }} - {{ w.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Job Number <span class="text-muted small">(Optional)</span></label>
                <input v-model="form.job_number" type="text" class="form-control" placeholder="e.g. JOB-2026-001">
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Damage Reason <span class="text-danger">*</span></label>
                <select v-model="form.reason" class="form-control" required>
                  <option value="">Select Reason</option>
                  <option v-for="r in reasons" :key="r" :value="r">{{ r }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                <input v-model.number="form.quantity" type="number" step="0.01" min="0.01" class="form-control" required placeholder="0.00">
                <div v-if="stockChecked" class="mt-1 small fw-semibold text-slate-700">
                  Available Warehouse Stock: <span class="text-primary">{{ fmt(availableStock) }}</span>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Approved By <span class="text-muted small">(Optional)</span></label>
                <select v-model="form.approved_by" class="form-control">
                  <option value="">Select Approver</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Remarks</label>
                <textarea v-model="form.remarks" class="form-control" rows="2" placeholder="Describe damage details..."></textarea>
              </div>
            </div>
          </div>
          <div class="d-flex gap-2 justify-content-end">
            <button type="submit" class="btn btn-success px-4" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
              Log Damage
            </button>
            <button @click="showForm = false" type="button" class="btn btn-secondary px-4">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-3 g-2 align-items-end">
      <div class="col-md-3">
        <label class="small text-muted fw-bold">Customer</label>
        <v-select
          v-model="filters.customer_id"
          :options="customers"
          label="name"
          :reduce="c => c.id"
          placeholder="All Customers"
          :clearable="true"
          class="v-select-sm"
          @option:selected="fetchDamages"
          @option:deselected="fetchDamages"
        ></v-select>
      </div>
      <div class="col-md-2">
        <label class="small text-muted fw-bold">Warehouse</label>
        <select v-model="filters.warehouse_id" @change="fetchDamages" class="form-control form-control-sm">
          <option value="">All Warehouses</option>
          <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.code }}</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="small text-muted fw-bold">Status</label>
        <select v-model="filters.status" @change="fetchDamages" class="form-control form-control-sm">
          <option value="">All</option>
          <option value="posted">Posted</option>
          <option value="reversed">Reversed</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="small text-muted fw-bold">From</label>
        <input v-model="filters.date_from" type="date" class="form-control form-control-sm" @change="fetchDamages">
      </div>
      <div class="col-md-2">
        <label class="small text-muted fw-bold">To</label>
        <input v-model="filters.date_to" type="date" class="form-control form-control-sm" @change="fetchDamages">
      </div>
      <div class="col-md-1">
        <button @click="clearFilters" class="btn btn-sm btn-clear-filters w-100">Clear</button>
      </div>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
      <table class="table table-striped table-sm text-nowrap small table-sticky-header">
        <thead>
          <tr>
            <th>S.No.</th>
            <th>Date</th>
            <th>DMG Number</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Warehouse</th>
            <th>Job #</th>
            <th>Reason</th>
            <th class="text-end">Quantity</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(d, index) in damages" :key="d.id">
            <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
            <td>{{ formatDate(d.date) }}</td>
            <td class="fw-bold">{{ d.damage_number }}</td>
            <td>{{ d.customer?.name }}</td>
            <td>{{ d.product?.item_code }} - {{ d.product?.item_name }}</td>
            <td><span class="badge bg-secondary">{{ d.warehouse?.code }}</span></td>
            <td>{{ d.job_number || '-' }}</td>
            <td>{{ d.reason }}</td>
            <td class="text-end fw-bold text-danger">{{ fmt(d.quantity) }}</td>
            <td>
              <span class="badge" :class="d.status === 'posted' ? 'bg-success' : 'bg-danger'">
                {{ d.status }}
              </span>
            </td>
            <td>
              <button @click="showDetail(d)" class="btn btn-sm btn-info me-1" title="View"><i class="bi bi-eye"></i></button>
              <button v-if="d.status === 'posted'" @click="initReversal(d)" class="btn btn-sm btn-danger" title="Reverse"><i class="bi bi-arrow-counterclockwise"></i> Reverse</button>
            </td>
          </tr>
          <tr v-if="damages.length === 0">
            <td colspan="11" class="text-center text-muted py-4">No damage records found.</td>
          </tr>
        </tbody>
        <tfoot v-if="damages.length > 0">
          <tr class="table-info fw-bold">
            <td colspan="8" class="text-end">Page Total:</td>
            <td class="text-end text-danger">{{ fmt(pageTotalQty) }}</td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3" v-if="pagination.last_page > 1">
      <nav>
        <ul class="pagination">
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

    <!-- Reversal Modal -->
    <div v-if="reversalTarget" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-danger text-white py-2">
            <h5 class="modal-title small fw-bold">Reverse Damage Entry: {{ reversalTarget.damage_number }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="reversalTarget = null"></button>
          </div>
          <div class="modal-body p-4">
            <p class="text-slate-800 small mb-3">
              This action will create a signed reversal ledger entry (+{{ fmt(reversalTarget.quantity) }} units) to restore inventory.
            </p>
            <div class="mb-3">
              <label class="form-label fw-bold small">Reason for Reversal <span class="text-danger">*</span></label>
              <textarea v-model="reversalReason" class="form-control" rows="2" placeholder="e.g. Logged incorrect quantity by mistake..."></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end">
              <button @click="confirmReversal" class="btn btn-danger btn-sm px-4" :disabled="reversing">
                <span v-if="reversing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                Confirm Reversal
              </button>
              <button @click="reversalTarget = null" class="btn btn-secondary btn-sm px-4">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedEntry" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-info text-white py-2">
            <h5 class="modal-title small fw-bold">Damage Log Details: {{ selectedEntry.damage_number }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="selectedEntry = null"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Log Date</label>
                  <span class="fw-bold text-slate-800">{{ formatDate(selectedEntry.date) }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Customer</label>
                  <span class="fw-bold text-slate-800">{{ selectedEntry.customer?.name }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Product</label>
                  <span class="fw-bold text-slate-800">{{ selectedEntry.product?.item_code }} - {{ selectedEntry.product?.item_name }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Warehouse</label>
                  <span class="fw-bold text-slate-800">{{ selectedEntry.warehouse?.code }} - {{ selectedEntry.warehouse?.name }}</span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Job Number</label>
                  <span class="fw-bold text-slate-800">{{ selectedEntry.job_number || '-' }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Quantity</label>
                  <span class="fw-bold text-danger fs-5">{{ fmt(selectedEntry.quantity) }}</span>
                </div>
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Reason</label>
                  <span class="badge bg-warning text-dark fs-6">{{ selectedEntry.reason }}</span>
                </div>
                <div class="mb-3" v-if="selectedEntry.approved_by">
                  <label class="small text-muted fw-bold d-block">Approved By</label>
                  <span class="fw-bold text-slate-800">{{ selectedEntry.approver?.name }}</span>
                </div>
              </div>
              <div class="col-12" v-if="selectedEntry.remarks">
                <div class="mb-3">
                  <label class="small text-muted fw-bold d-block">Remarks</label>
                  <p class="mb-0 text-slate-700 bg-light p-2 rounded border">{{ selectedEntry.remarks }}</p>
                </div>
              </div>
              <div class="col-12">
                <hr class="my-2">
                <div class="d-flex justify-content-between text-muted small">
                  <span>Logged by: {{ selectedEntry.creator?.name || 'Unknown' }} on {{ formatDate(selectedEntry.created_at) }}</span>
                  <span v-if="selectedEntry.status === 'reversed'" class="text-danger fw-bold">
                    Reversed by: {{ selectedEntry.reverser?.name }} on {{ formatDate(selectedEntry.reversed_at) }} (Reason: {{ selectedEntry.reversal_reason }})
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    user: { type: Object, default: null }
  },
  data() {
    return {
      showForm: false,
      saving: false,
      reversing: false,
      stockChecked: false,
      availableStock: 0.0,
      
      customers: [],
      products: [],
      warehouses: [],
      users: [],
      
      reasons: [
        'Water Damage',
        'Compression',
        'Printing Defect',
        'Pest Damage',
        'Expired',
        'Handling Damage',
        'Other'
      ],
      
      form: {
        date: new Date().toISOString().split('T')[0],
        customer_id: '',
        product_id: '',
        warehouse_id: '',
        job_number: '',
        quantity: '',
        reason: '',
        remarks: '',
        approved_by: ''
      },
      
      damages: [],
      filters: {
        customer_id: '',
        warehouse_id: '',
        status: '',
        date_from: '',
        date_to: ''
      },
      
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 50
      },
      
      reversalTarget: null,
      reversalReason: '',
      selectedEntry: null
    };
  },
  computed: {
    filteredProducts() {
      if (!this.form.customer_id) return [];
      return this.products.filter(p => p.customer_id == this.form.customer_id);
    },
    pageTotalQty() {
      return this.damages.reduce((sum, d) => sum + Number(d.quantity || 0), 0);
    },
    pages() {
      const p = [];
      for (let i = 1; i <= this.pagination.last_page; i++) p.push(i);
      return p;
    }
  },
  mounted() {
    if (localStorage.getItem('token')) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    }
    this.fetchFilters();
    this.fetchUsers();
    this.fetchDamages();
  },
  methods: {
    fetchFilters() {
      axios.get('/api/fg-reports/filters').then(r => {
        this.customers = r.data.customers;
        this.products = r.data.products;
        this.warehouses = r.data.warehouses;
      }).catch(() => {});
    },
    fetchUsers() {
      axios.get('/api/users').then(r => {
        this.users = r.data;
      }).catch(() => {});
    },
    fetchDamages(page = 1) {
      const params = { page, ...this.filters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-damages', { params }).then(r => {
        this.damages = r.data.pagination.data || [];
        this.pagination.current_page = r.data.pagination.current_page;
        this.pagination.last_page = r.data.pagination.last_page;
        this.pagination.per_page = r.data.pagination.per_page;
      }).catch(() => {
        this.$message.error('Failed to load damage logs.');
      });
    },
    onCustomerChange() {
      this.form.product_id = '';
      this.stockChecked = false;
      this.availableStock = 0.0;
    },
    onProductOrWarehouseChange() {
      if (this.form.product_id && this.form.warehouse_id) {
        axios.get(`/api/fg-damages/available-stock/${this.form.product_id}/${this.form.warehouse_id}`).then(r => {
          this.availableStock = r.data.available_stock;
          this.stockChecked = true;
        }).catch(() => {
          this.stockChecked = false;
        });
      } else {
        this.stockChecked = false;
        this.availableStock = 0.0;
      }
    },
    saveDamage() {
      // Basic validations
      if (!this.form.date || !this.form.customer_id || !this.form.product_id || !this.form.warehouse_id || !this.form.reason || !this.form.quantity) {
        this.$message.warning('Please fill in all required fields.');
        return;
      }
      if (this.form.quantity <= 0) {
        this.$message.warning('Quantity must be greater than zero.');
        return;
      }
      
      const warehouse = this.warehouses.find(w => w.id == this.form.warehouse_id);
      if (warehouse && !warehouse.allow_negative_stock && this.form.quantity > this.availableStock) {
        this.$message.warning('Damage quantity cannot exceed available warehouse stock.');
        return;
      }

      this.saving = true;
      axios.post('/api/fg-damages', this.form).then(() => {
        this.$message.success('Damage entry logged successfully.');
        this.showForm = false;
        this.resetForm();
        this.fetchDamages();
      }).catch(err => {
        const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to log damage.';
        this.$message.error(msg);
      }).finally(() => {
        this.saving = false;
      });
    },
    initReversal(row) {
      this.reversalTarget = row;
      this.reversalReason = '';
    },
    confirmReversal() {
      if (!this.reversalReason) {
        this.$message.warning('Please provide a reason for the reversal.');
        return;
      }

      this.reversing = true;
      axios.post(`/api/fg-damages/${this.reversalTarget.id}/reverse`, { reason: this.reversalReason }).then(() => {
        this.$message.success('Damage entry reversed successfully.');
        this.reversalTarget = null;
        this.fetchDamages();
      }).catch(err => {
        const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to reverse damage.';
        this.$message.error(msg);
      }).finally(() => {
        this.reversing = false;
      });
    },
    showDetail(row) {
      this.selectedEntry = row;
    },
    resetForm() {
      this.form = {
        date: new Date().toISOString().split('T')[0],
        customer_id: '',
        product_id: '',
        warehouse_id: '',
        job_number: '',
        quantity: '',
        reason: '',
        remarks: '',
        approved_by: ''
      };
      this.stockChecked = false;
      this.availableStock = 0.0;
    },
    clearFilters() {
      this.filters = { customer_id: '', warehouse_id: '', status: '', date_from: '', date_to: '' };
      this.fetchDamages();
    },
    goToPage(p) {
      this.fetchDamages(p);
    },
    formatDate(d) {
      if (!d) return '-';
      const date = new Date(d);
      return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    },
    fmt(v) {
      return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
    }
  }
};
</script>

<style scoped>
.form-label {
  color: #1e293b;
}
.table-sticky-header {
  max-height: 500px;
  overflow-y: auto;
}
</style>
