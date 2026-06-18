<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-box-seam-fill"></i> Product Master</h2>
      <button @click="showForm = true; editing = false; resetForm()" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Product
      </button>
    </div>

    <!-- Form -->
    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>{{ editing ? 'Edit' : 'Add' }} Product</h5>
        <form @submit.prevent="saveProduct" novalidate>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label>Customer <span class="text-danger">*</span></label>
                <select v-model="form.customer_id" class="form-control" required>
                  <option value="">Select Customer</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Item Code <span class="text-danger">*</span></label>
                <input v-model="form.item_code" type="text" class="form-control" required placeholder="e.g. PROD-001">
              </div>
              <div class="mb-3">
                <label>Item Name <span class="text-danger">*</span></label>
                <input v-model="form.item_name" type="text" class="form-control" required placeholder="e.g. Corrugated Box 12x8">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label>Rate</label>
                <input v-model="form.rate" type="number" step="0.01" class="form-control" placeholder="0.00">
              </div>
              <div class="mb-3">
                <label>Opening Balance</label>
                <input v-model="form.opening_balance" type="number" class="form-control" placeholder="0">
              </div>
              <div class="mb-3">
                <label>Dispatch Policy <span class="text-danger">*</span></label>
                <select v-model="form.dispatch_policy" class="form-control" required>
                  <option value="customer_restricted">Customer Restricted</option>
                  <option value="shared_product">Shared Product</option>
                </select>
                <small class="text-muted">
                  Shared products can be dispatched to other customers; restricted products stay locked to the assigned customer.
                </small>
              </div>
            </div>
          </div>
          <div v-if="form.dispatch_policy === 'shared_product'" class="shared-link-panel mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <h6 class="mb-0 fw-bold">Linked Customer Items</h6>
                <small class="text-muted">Define each customer item identity and rate for this same shared stock.</small>
              </div>
              <button type="button" class="btn btn-sm btn-primary" @click="addCustomerLink">
                <i class="bi bi-plus-circle"></i> Add Link
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0 shared-link-table">
                <thead>
                  <tr>
                    <th style="width: 24%;">Customer</th>
                    <th>Customer Item</th>
                    <th style="width: 14%;" class="text-end">Rate</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 52px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(link, index) in form.customer_links" :key="index">
                    <td>
                      <select v-model="link.customer_id" class="form-control form-control-sm" required @change="onLinkCustomerChange(link)">
                        <option value="">Select</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                      </select>
                    </td>
                    <td>
                      <select
                        v-model="link.selected_product_id"
                        class="form-control form-control-sm"
                        :disabled="!link.customer_id"
                        @change="onLinkItemChange(link)"
                      >
                        <option value="">{{ link.customer_id ? 'Select customer item' : 'Select customer first' }}</option>
                        <option v-for="item in linkedCustomerItems(link.customer_id)" :key="item.id" :value="item.id">
                          {{ item.item_code }} - {{ item.item_name }}
                        </option>
                      </select>
                    </td>
                    <td><input v-model="link.customer_rate" type="number" step="0.01" class="form-control form-control-sm text-end" placeholder="0.00"></td>
                    <td>
                      <select v-model="link.status" class="form-control form-control-sm">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                      </select>
                    </td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-outline-danger" @click="removeCustomerLink(index)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="form.customer_links.length === 0">
                    <td colspan="5" class="text-center text-muted py-3">No linked customer items added.</td>
                  </tr>
                </tbody>
              </table>
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
      <div class="col-md-3">
        <label class="small text-muted">Customer</label>
        <select v-model="filterCustomerId" @change="fetchProducts" class="form-control form-control-sm">
          <option value="">All Customers</option>
          <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="small text-muted">Search</label>
        <input v-model="searchQuery" @input="debouncedFetch" type="text" class="form-control form-control-sm" placeholder="Code or Name...">
      </div>
      <div class="col-md-2">
        <button @click="filterCustomerId = ''; searchQuery = ''; fetchProducts()" class="btn btn-sm btn-clear-filters w-100">Clear</button>
      </div>
    </div>

    <!-- Table -->
    <table class="table table-striped table-sm text-nowrap small table-sticky-header">
      <thead>
        <tr>
          <th>S.No.</th>
          <th>Customer</th>
          <th>Item Code</th>
          <th>Item Name</th>
          <th class="text-end">Rate</th>
          <th class="text-end">Opening Bal.</th>
          <th class="text-end">Current Stock</th>
          <th>Dispatch Policy</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(p, index) in products" :key="p.id">
          <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
          <td>{{ p.customer?.name }}</td>
          <td class="fw-bold">{{ p.item_code }}</td>
          <td>{{ p.item_name }}</td>
          <td class="text-end">{{ p.rate ? formatRate(p.rate) : '-' }}</td>
          <td class="text-end">{{ formatNumber(p.opening_balance) }}</td>
          <td class="text-end fw-bold" :class="p.current_balance > 0 ? 'text-success' : 'text-danger'">{{ formatNumber(p.current_balance) }}</td>
          <td>
            <span :class="['policy-badge', p.dispatch_policy === 'shared_product' ? 'is-shared' : 'is-restricted']">
              {{ policyLabel(p.dispatch_policy) }}
            </span>
          </td>
          <td>
            <button @click="editProduct(p)" class="btn btn-sm btn-warning me-1">Edit</button>
            <button @click="deleteProduct(p)" class="btn btn-sm btn-danger">Delete</button>
          </td>
        </tr>
        <tr v-if="products.length === 0">
          <td colspan="9" class="text-center text-muted py-4">No products found.</td>
        </tr>
      </tbody>
    </table>

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
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: { user: { type: Object, default: null } },
  data() {
    return {
      products: [],
      customers: [],
      showForm: false,
      editing: false,
      form: { id: null, customer_id: '', item_code: '', item_name: '', rate: '', opening_balance: 0, dispatch_policy: 'customer_restricted', customer_links: [] },
      customerItemOptions: {},
      filterCustomerId: '',
      searchQuery: '',
      searchTimeout: null,
      pagination: { current_page: 1, last_page: 1, per_page: 50, total: 0 }
    };
  },
  computed: {
    pages() {
      const c = this.pagination.current_page, l = this.pagination.last_page;
      let s = Math.max(1, c - 2), e = Math.min(l, c + 2), p = [];
      for (let i = s; i <= e; i++) p.push(i);
      return p;
    }
  },
  mounted() {
    if (localStorage.getItem('token')) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    }
    this.fetchCustomers();
    this.fetchProducts();
  },
  methods: {
    fetchCustomers() {
      axios.get('/api/customers').then(r => { this.customers = r.data; });
    },
    fetchProducts(page = 1) {
      const params = { page };
      if (this.filterCustomerId) params.customer_id = this.filterCustomerId;
      if (this.searchQuery) params.search = this.searchQuery;
      axios.get('/api/products', { params }).then(r => {
        this.products = r.data.data;
        this.pagination = { current_page: r.data.current_page, last_page: r.data.last_page, per_page: r.data.per_page, total: r.data.total };
      });
    },
    debouncedFetch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => this.fetchProducts(), 400);
    },
    goToPage(page) { if (page >= 1 && page <= this.pagination.last_page) this.fetchProducts(page); },
    emptyForm() {
      return { id: null, customer_id: '', item_code: '', item_name: '', rate: '', opening_balance: 0, dispatch_policy: 'customer_restricted', customer_links: [] };
    },
    resetForm() { this.form = this.emptyForm(); },
    addCustomerLink() {
      this.form.customer_links.push({ id: null, customer_id: '', selected_product_id: '', customer_item_code: '', customer_item_name: '', customer_rate: '', status: 'Active', is_default: false });
    },
    removeCustomerLink(index) {
      this.form.customer_links.splice(index, 1);
    },
    linkedCustomerItems(customerId) {
      return this.customerItemOptions[customerId] || [];
    },
    fetchCustomerItems(customerId) {
      if (!customerId || this.customerItemOptions[customerId]) return Promise.resolve();

      return axios.get(`/api/products/customer-items/${customerId}`).then(r => {
        this.customerItemOptions = { ...this.customerItemOptions, [customerId]: r.data || [] };
      });
    },
    onLinkCustomerChange(link) {
      link.selected_product_id = '';
      link.customer_item_code = '';
      link.customer_item_name = '';
      link.customer_rate = '';
      this.fetchCustomerItems(link.customer_id);
    },
    onLinkItemChange(link) {
      const item = this.linkedCustomerItems(link.customer_id)
        .find(row => Number(row.id) === Number(link.selected_product_id));

      if (!item) return;

      link.customer_item_code = item.item_code;
      link.customer_item_name = item.item_name;
      link.customer_rate = item.rate || '';
    },
    preloadCustomerLinkItems() {
      const customerIds = [...new Set(this.form.customer_links.map(link => link.customer_id).filter(Boolean))];
      Promise.all(customerIds.map(id => this.fetchCustomerItems(id))).then(() => {
        this.form.customer_links.forEach(link => {
          if (link.selected_product_id) return;
          const match = this.linkedCustomerItems(link.customer_id).find(item =>
            item.item_code === link.customer_item_code && item.item_name === link.customer_item_name
          );
          if (match) {
            link.selected_product_id = match.id;
          }
        });
      });
    },
    saveProduct() {
      if (!this.form.customer_id || !this.form.item_code || !this.form.item_name) {
        this.$message.error('Please fill in all required fields.');
        return;
      }
      if (this.form.dispatch_policy === 'shared_product' && this.form.customer_links.length === 0) {
        this.$message.error('Add at least one linked customer item for a shared product.');
        return;
      }
      const action = this.editing
        ? axios.put(`/api/products/${this.form.id}`, this.form)
        : axios.post('/api/products', this.form);
      action.then(() => {
        this.$message.success(this.editing ? 'Product updated!' : 'Product created!');
        this.showForm = false;
        this.fetchProducts();
      }).catch(err => {
        this.$message.error(err.response?.data?.error || 'Failed to save product.');
      });
    },
    editProduct(p) {
      this.form = {
        id: p.id,
        customer_id: p.customer_id,
        item_code: p.item_code,
        item_name: p.item_name,
        rate: p.rate || '',
        opening_balance: Math.floor(p.opening_balance),
        dispatch_policy: p.dispatch_policy || 'customer_restricted',
        customer_links: (p.customer_links || []).map(link => ({
          id: link.id,
          customer_id: link.customer_id,
          selected_product_id: '',
          customer_item_code: link.customer_item_code,
          customer_item_name: link.customer_item_name,
          customer_rate: link.customer_rate,
          status: link.status || 'Active',
          is_default: !!link.is_default,
        }))
      };
      this.editing = true;
      this.showForm = true;
      this.preloadCustomerLinkItems();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    deleteProduct(p) {
      if (!confirm(`Delete product "${p.item_name}"?`)) return;
      axios.delete(`/api/products/${p.id}`).then(() => {
        this.$message.success('Product deleted.');
        this.fetchProducts();
      }).catch(err => {
        this.$message.error(err.response?.data?.error || 'Cannot delete product.');
      });
    },
    formatNumber(v) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 0 }); },
    formatRate(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    policyLabel(policy) {
      return policy === 'shared_product' ? 'Shared Product' : 'Customer Restricted';
    }
  }
};
</script>

<style scoped>
.policy-badge {
  border-radius: 999px;
  display: inline-flex;
  font-size: 0.72rem;
  font-weight: 800;
  line-height: 1;
  padding: 0.38rem 0.65rem;
  white-space: nowrap;
}
.policy-badge.is-shared {
  background: #dcfce7;
  border: 1px solid #86efac;
  color: #166534;
}
.policy-badge.is-restricted {
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  color: #3730a3;
}
:global([data-theme="dark"]) .policy-badge.is-shared,
:global(body.dark-mode) .policy-badge.is-shared {
  background: rgba(34, 197, 94, 0.16);
  border-color: rgba(134, 239, 172, 0.35);
  color: #bbf7d0;
}
:global([data-theme="dark"]) .policy-badge.is-restricted,
:global(body.dark-mode) .policy-badge.is-restricted {
  background: rgba(99, 102, 241, 0.18);
  border-color: rgba(165, 180, 252, 0.35);
  color: #c7d2fe;
}
.shared-link-panel {
  background: #f8fafc;
  border: 1px solid #dbe3ef;
  border-radius: 10px;
  padding: 14px;
}
.shared-link-table thead th {
  color: #475569;
  font-size: 0.72rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
:global([data-theme="dark"]) .shared-link-panel,
:global(body.dark-mode) .shared-link-panel {
  background: #18263a;
  border-color: #3b4f6b;
}
:global([data-theme="dark"]) .shared-link-table thead th,
:global(body.dark-mode) .shared-link-table thead th {
  color: #bfd0e5;
}
</style>
