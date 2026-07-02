<template>
  <div class="container-fluid px-0 rm-item-master">
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
      <div>
        <h2 class="dashboard-title"><i class="bi bi-box-seam-fill text-indigo"></i> Raw Material Product Master</h2>
        <p class="text-muted small mb-0">Corrugated carton materials, consumables, reorder controls, and supplier references.</p>
      </div>
      <button @click="openCreateDialog" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-circle"></i> Add Raw Material
      </button>
    </div>

    <div v-if="dialogVisible" class="card mb-4 shadow-sm border-0 animated fadeIn">
      <div class="card-header bg-transparent border-0 pt-3 pb-0">
        <h5 class="fw-bold text-slate-800">
          <i class="bi bi-pencil-square text-indigo me-1"></i> {{ editing ? 'Edit' : 'Add' }} Raw Material
        </h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm" novalidate>
          <div class="row g-3">
            <div class="col-lg-4 col-md-6">
              <label class="form-label fw-semibold text-slate-700">RM Name <span class="text-danger">*</span></label>
              <input v-model.trim="form.name" type="text" class="form-control" placeholder="Enter material name" required />
            </div>

            <div class="col-lg-4 col-md-6">
              <div class="d-flex justify-content-between align-items-center">
                <label class="form-label fw-semibold text-slate-700 mb-1">Category <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-link btn-sm p-0" @click="addCategory">+ Add</button>
              </div>
              <select v-model="form.rm_category_id" class="form-control" required @change="onFormCategoryChange">
                <option value="">Select Category</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </div>

            <div class="col-lg-4 col-md-6">
              <div class="d-flex justify-content-between align-items-center">
                <label class="form-label fw-semibold text-slate-700 mb-1">Subcategory</label>
                <button type="button" class="btn btn-link btn-sm p-0" @click="addSubcategory">+ Add</button>
              </div>
              <select v-model="form.rm_subcategory_id" class="form-control" :disabled="!form.rm_category_id">
                <option value="">Select Subcategory</option>
                <option v-for="subcategory in formSubcategories" :key="subcategory.id" :value="subcategory.id">{{ subcategory.name }}</option>
              </select>
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Unit of Measure <span class="text-danger">*</span></label>
              <select v-model="form.unit_type" class="form-control" required>
                <option value="">Select Unit</option>
                <option v-for="uom in uomOptions" :key="uom" :value="uom">{{ uom }}</option>
              </select>
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Material Type <span class="text-danger">*</span></label>
              <select v-model="form.material_type" class="form-control" required>
                <option value="Direct Material">Direct Material</option>
                <option value="Indirect Material">Indirect Material</option>
                <option value="Consumable">Consumable</option>
              </select>
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Preferred Supplier</label>
              <v-select
                v-model="form.preferred_supplier_id"
                :options="suppliers"
                label="name"
                :reduce="supplier => supplier.id"
                placeholder="Select Supplier"
                :clearable="true"
              ></v-select>
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Cost Rate <span class="text-danger">*</span></label>
              <input v-model.number="form.cost_price" type="number" step="0.01" min="0" class="form-control" required />
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Opening Stock</label>
              <input v-model.number="form.opening_stock" type="number" min="0" class="form-control" :disabled="editing" />
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Reorder Level</label>
              <input v-model.number="form.reorder_level" type="number" min="0" class="form-control" />
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Minimum Stock</label>
              <input v-model.number="form.minimum_stock" type="number" min="0" class="form-control" />
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Maximum Stock</label>
              <input v-model.number="form.maximum_stock" type="number" min="0" class="form-control" />
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">GST/Tax Code</label>
              <input v-model.trim="form.gst_tax_code" type="text" maxlength="50" class="form-control" placeholder="e.g. GST-17" />
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-slate-700">Status <span class="text-danger">*</span></label>
              <select v-model="form.status" class="form-control" required>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold text-slate-700">Remarks</label>
              <textarea v-model="form.remarks" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
            </div>

            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold text-slate-700 mb-0">Supplier-wise Rates</label>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="addSupplierRateRow">
                  <i class="bi bi-plus-circle me-1"></i> Add Rate
                </button>
              </div>
              <div class="table-responsive border rounded">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr class="text-uppercase small text-muted">
                      <th>Supplier</th>
                      <th style="width: 150px;">Rate</th>
                      <th style="width: 170px;">Effective From</th>
                      <th style="width: 130px;">Status</th>
                      <th style="width: 70px;"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!form.supplier_rates.length">
                      <td colspan="5" class="text-center text-muted py-3">No supplier-specific rates added.</td>
                    </tr>
                    <tr v-for="(rate, index) in form.supplier_rates" :key="index">
                      <td>
                        <select v-model="rate.supplier_id" class="form-control form-control-sm">
                          <option value="">Select Supplier</option>
                          <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                        </select>
                      </td>
                      <td>
                        <input v-model.number="rate.rate" type="number" min="0" step="0.01" class="form-control form-control-sm" />
                      </td>
                      <td>
                        <input v-model="rate.effective_from" type="date" class="form-control form-control-sm" />
                      </td>
                      <td>
                        <select v-model="rate.is_active" class="form-control form-control-sm">
                          <option :value="true">Active</option>
                          <option :value="false">Inactive</option>
                        </select>
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeSupplierRateRow(index)">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <small class="text-muted">GRN auto-rate will pick supplier mapping first, then default cost rate.</small>
            </div>
          </div>

          <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-success shadow-sm" :disabled="submitting">
              <i class="bi bi-save me-1"></i> {{ editing ? 'Update Item' : 'Create Item' }}
            </button>
            <button type="button" @click="dialogVisible = false" class="btn btn-secondary shadow-sm">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
      <div class="card-body">
        <div class="row mb-3 g-2 align-items-end">
          <div class="col-xl-3 col-md-4">
            <label class="small text-muted fw-bold">Search</label>
            <input v-model="filters.search" @input="fetchItems" type="text" class="form-control form-control-sm" placeholder="Search by RM code or name...">
          </div>
          <div class="col-xl-2 col-md-4">
            <label class="small text-muted fw-bold">Category</label>
            <select v-model="filters.rm_category_id" @change="onFilterCategoryChange" class="form-control form-control-sm">
              <option value="">All Categories</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </div>
          <div class="col-xl-2 col-md-4">
            <label class="small text-muted fw-bold">Subcategory</label>
            <select v-model="filters.rm_subcategory_id" @change="fetchItems" class="form-control form-control-sm" :disabled="!filters.rm_category_id">
              <option value="">All Subcategories</option>
              <option v-for="subcategory in filterSubcategories" :key="subcategory.id" :value="subcategory.id">{{ subcategory.name }}</option>
            </select>
          </div>
          <div class="col-xl-2 col-md-4">
            <label class="small text-muted fw-bold">Supplier</label>
            <v-select
              v-model="filters.preferred_supplier_id"
              :options="suppliers"
              label="name"
              :reduce="supplier => supplier.id"
              placeholder="All Suppliers"
              :clearable="true"
              class="v-select-sm"
              @option:selected="fetchItems"
              @option:deselected="fetchItems"
            ></v-select>
          </div>
          <div class="col-xl-1 col-md-4">
            <label class="small text-muted fw-bold">Status</label>
            <select v-model="filters.status" @change="fetchItems" class="form-control form-control-sm">
              <option value="">All</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="col-xl-1 col-md-4">
            <label class="small text-muted fw-bold">Stock</label>
            <select v-model="filters.stock_status" @change="fetchItems" class="form-control form-control-sm">
              <option value="">All</option>
              <option value="in_stock">In Stock</option>
              <option value="reorder_required">Reorder</option>
              <option value="out_of_stock">Out</option>
            </select>
          </div>
          <div class="col-xl-1 col-md-4">
            <button @click="resetFilters" class="btn btn-sm btn-clear-filters w-100">Clear</button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-sm text-nowrap align-middle">
            <thead>
              <tr class="text-uppercase small text-muted">
                <th>S.No.</th>
                <th>RM Code</th>
                <th>RM Name</th>
                <th>Category</th>
                <th>Subcategory</th>
                <th>UOM</th>
                <th>Type</th>
                <th>Preferred Supplier</th>
                <th class="text-end">Stock</th>
                <th class="text-end">Reorder</th>
                <th class="text-end">Min</th>
                <th class="text-end">Max</th>
                <th class="text-end">Cost Rate</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td><span class="badge bg-light text-slate-800 border font-monospace fw-bold">{{ item.code }}</span></td>
                <td class="fw-semibold text-slate-700">{{ item.name }}</td>
                <td>{{ item.category?.name || '-' }}</td>
                <td>{{ item.subcategory?.name || '-' }}</td>
                <td>{{ item.unit_type }}</td>
                <td>{{ item.material_type || '-' }}</td>
                <td>{{ item.preferred_supplier?.name || '-' }}</td>
                <td class="text-end">
                  <span :class="stockClass(item.stock_status)">{{ formatQty(item.current_balance ?? item.opening_stock) }}</span>
                </td>
                <td class="text-end">{{ formatQty(item.reorder_level) }}</td>
                <td class="text-end">{{ formatQty(item.minimum_stock ?? item.min_stock_alert) }}</td>
                <td class="text-end">{{ formatQty(item.maximum_stock) }}</td>
                <td class="text-end">Rs. {{ formatAmount(item.cost_price) }}</td>
                <td class="text-center">
                  <span :class="['badge py-1 px-2.5', item.status === 'Active' ? 'bg-success-soft text-success border border-success-subtle' : 'bg-danger-soft text-danger border border-danger-subtle']">
                    {{ item.status }}
                  </span>
                  <div class="small mt-1" :class="stockClass(item.stock_status)">{{ stockStatusLabel(item.stock_status) }}</div>
                </td>
                <td class="text-center">
                  <button @click="editItem(item)" class="btn btn-sm btn-outline-primary border-0 me-1" title="Edit">
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <button @click="deleteItem(item)" class="btn btn-sm btn-outline-danger border-0" title="Delete">
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="15" class="text-center py-4 text-muted">No raw materials found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const requiredUoms = ['Kg', 'Ton', 'Roll', 'Liter', 'Piece', 'Meter', 'Sqr. Meter'];

const items = ref([]);
const categories = ref([]);
const suppliers = ref([]);
const uoms = ref([]);
const loading = ref(false);
const submitting = ref(false);
const dialogVisible = ref(false);
const editing = ref(false);

const filters = reactive({
    search: '',
    status: '',
    rm_category_id: '',
    rm_subcategory_id: '',
    preferred_supplier_id: '',
    stock_status: ''
});

const blankForm = () => ({
    id: null,
    name: '',
    paper_quality_id: null,
    rm_category_id: '',
    rm_subcategory_id: '',
    unit_type: 'Kg',
    material_type: 'Direct Material',
    preferred_supplier_id: '',
    cost_price: 0,
    opening_stock: 0,
    min_stock_alert: 0,
    reorder_level: 0,
    minimum_stock: 0,
    maximum_stock: 0,
    gst_tax_code: '',
    status: 'Active',
    remarks: '',
    supplier_rates: []
});

const form = reactive(blankForm());

const uomOptions = computed(() => {
    const existing = uoms.value.map(uom => uom.name);
    return [...new Set([...requiredUoms, ...existing])];
});

const formSubcategories = computed(() => {
    const category = categories.value.find(item => Number(item.id) === Number(form.rm_category_id));
    return category?.subcategories || [];
});

const filterSubcategories = computed(() => {
    const category = categories.value.find(item => Number(item.id) === Number(filters.rm_category_id));
    return category?.subcategories || [];
});

const fetchItems = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-items', { params: filters });
        items.value = res.data;
    } catch (error) {
        ElMessage.error('Failed to fetch raw material items');
    } finally {
        loading.value = false;
    }
};

const fetchCategories = async () => {
    try {
        const res = await axios.get('/api/rm-categories', { params: { status: 'Active' } });
        categories.value = res.data;
    } catch (error) {
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load RM categories' : 'Failed to load RM categories');
        categories.value = [];
    }
};

const fetchSuppliers = async () => {
    try {
        const res = await axios.get('/api/suppliers');
        suppliers.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
    } catch (error) {
        suppliers.value = [];
    }
};

const fetchUoms = async () => {
    try {
        const res = await axios.get('/api/unit-of-measures');
        uoms.value = res.data;
    } catch (error) {
        console.error('Failed to fetch UoMs:', error);
    }
};

const resetFilters = () => {
    Object.assign(filters, {
        search: '',
        status: '',
        rm_category_id: '',
        rm_subcategory_id: '',
        preferred_supplier_id: '',
        stock_status: ''
    });
    fetchItems();
};

const onFormCategoryChange = () => {
    form.rm_subcategory_id = '';
};

const onFilterCategoryChange = () => {
    filters.rm_subcategory_id = '';
    fetchItems();
};

const blankSupplierRateRow = () => ({
    supplier_id: '',
    rate: 0,
    effective_from: '',
    is_active: true
});

const addSupplierRateRow = () => {
    form.supplier_rates.push(blankSupplierRateRow());
};

const removeSupplierRateRow = (index) => {
    form.supplier_rates.splice(index, 1);
};

const openCreateDialog = () => {
    editing.value = false;
    Object.assign(form, blankForm());
    dialogVisible.value = true;
};

const editItem = (item) => {
    editing.value = true;
    Object.assign(form, blankForm(), {
        ...item,
        rm_category_id: item.rm_category_id || item.category?.id || '',
        rm_subcategory_id: item.rm_subcategory_id || item.subcategory?.id || '',
        preferred_supplier_id: item.preferred_supplier_id || item.preferred_supplier?.id || '',
        cost_price: Number(item.cost_price || 0),
        opening_stock: Number(item.opening_stock || 0),
        min_stock_alert: Number(item.min_stock_alert || item.minimum_stock || 0),
        reorder_level: Number(item.reorder_level || 0),
        minimum_stock: Number(item.minimum_stock || item.min_stock_alert || 0),
        maximum_stock: Number(item.maximum_stock || 0),
        supplier_rates: (item.supplier_rates || []).map(rate => ({
            supplier_id: rate.supplier_id || '',
            rate: Number(rate.rate || 0),
            effective_from: rate.effective_from || '',
            is_active: rate.is_active !== false
        }))
    });
    dialogVisible.value = true;
};

const submitForm = async () => {
    if (!form.name) {
        ElMessage.error('RM name is required');
        return;
    }
    if (!form.rm_category_id) {
        ElMessage.error('Category is required');
        return;
    }
    if (!form.unit_type) {
        ElMessage.error('Unit of measure is required');
        return;
    }
    if (Number(form.cost_price) < 0) {
        ElMessage.error('Cost rate cannot be negative');
        return;
    }
    if (Number(form.maximum_stock) > 0 && Number(form.maximum_stock) < Number(form.minimum_stock)) {
        ElMessage.error('Maximum stock must be greater than or equal to minimum stock');
        return;
    }

    const payload = {
        ...form,
        rm_subcategory_id: form.rm_subcategory_id || null,
        preferred_supplier_id: form.preferred_supplier_id || null,
        min_stock_alert: Number(form.minimum_stock || 0),
        reorder_level: Number(form.reorder_level || 0),
        minimum_stock: Number(form.minimum_stock || 0),
        maximum_stock: Number(form.maximum_stock || 0),
        supplier_rates: (form.supplier_rates || [])
            .filter(rate => rate.supplier_id && Number(rate.rate) >= 0)
            .map(rate => ({
                supplier_id: rate.supplier_id,
                rate: Number(rate.rate || 0),
                effective_from: rate.effective_from || null,
                is_active: rate.is_active !== false
            }))
    };

    submitting.value = true;
    try {
        if (editing.value) {
            await axios.put(`/api/rm-items/${form.id}`, payload);
            ElMessage.success('Raw material updated successfully');
        } else {
            await axios.post('/api/rm-items', payload);
            ElMessage.success('Raw material created successfully');
        }
        dialogVisible.value = false;
        fetchItems();
    } catch (error) {
        const errors = error.response?.data?.errors;
        const firstError = errors ? Object.values(errors).flat()[0] : null;
        ElMessage.error(firstError || error.response?.data?.message || 'Failed to save item');
    } finally {
        submitting.value = false;
    }
};

const addCategory = async () => {
    try {
        const { value } = await ElMessageBox.prompt('Category name', 'Add Raw Material Category', {
            confirmButtonText: 'Create',
            cancelButtonText: 'Cancel',
            inputPattern: /\S+/,
            inputErrorMessage: 'Category name is required'
        });

        const res = await axios.post('/api/rm-categories', { name: value, status: 'Active' });
        await fetchCategories();
        form.rm_category_id = res.data.id;
        form.rm_subcategory_id = '';
        ElMessage.success('Category added');
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || 'Failed to add category');
        }
    }
};

const addSubcategory = async () => {
    if (!form.rm_category_id) {
        ElMessage.warning('Select a category before adding a subcategory');
        return;
    }

    try {
        const { value } = await ElMessageBox.prompt('Subcategory name', 'Add Raw Material Subcategory', {
            confirmButtonText: 'Create',
            cancelButtonText: 'Cancel',
            inputPattern: /\S+/,
            inputErrorMessage: 'Subcategory name is required'
        });

        const res = await axios.post('/api/rm-subcategories', {
            rm_category_id: form.rm_category_id,
            name: value,
            status: 'Active'
        });
        await fetchCategories();
        form.rm_subcategory_id = res.data.id;
        ElMessage.success('Subcategory added');
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || 'Failed to add subcategory');
        }
    }
};

const deleteItem = async (item) => {
    try {
        await ElMessageBox.confirm(
            `Are you sure you want to delete "${item.name}"? This will only work if there is no transaction history.`,
            'Warning',
            { type: 'warning' }
        );
        await axios.delete(`/api/rm-items/${item.id}`);
        ElMessage.success('Item deleted successfully');
        fetchItems();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.error || 'Failed to delete item');
        }
    }
};

const stockStatusLabel = (status) => ({
    in_stock: 'In Stock',
    reorder_required: 'Reorder',
    out_of_stock: 'Out of Stock'
}[status] || '-');

const stockClass = (status) => ({
    in_stock: 'text-success fw-bold',
    reorder_required: 'text-warning fw-bold',
    out_of_stock: 'text-danger fw-bold'
}[status] || '');

const formatAmount = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatQty = (val) => Number(val || 0).toLocaleString();

onMounted(async () => {
    await Promise.allSettled([fetchCategories(), fetchSuppliers(), fetchUoms()]);
    fetchItems();
});
</script>

<style scoped>
.dashboard-title {
  color: #1e293b;
  font-weight: 700;
  margin-bottom: 0;
}
.text-indigo {
  color: #4f46e5;
}
.bg-success-soft {
  background-color: #dcfce7;
  color: #15803d;
}
.bg-danger-soft {
  background-color: #fee2e2;
  color: #b91c1c;
}
.text-slate-800 {
  color: #1e293b;
}
.text-slate-700 {
  color: #334155;
}
.card {
  border-radius: 12px;
  border: none;
  background-color: #ffffff;
}
.table th {
  font-weight: 700;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
}
.table td {
  padding: 10px 8px;
}
.animated {
  animation-duration: 0.3s;
  animation-fill-mode: both;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.fadeIn {
  animation-name: fadeIn;
}
:global([data-theme="dark"]) .rm-item-master .card,
:global(body.dark-mode) .rm-item-master .card {
  background: #111827;
  color: #f8fafc;
  border: 1px solid #334155 !important;
}
:global([data-theme="dark"]) .rm-item-master .dashboard-title,
:global([data-theme="dark"]) .rm-item-master .text-slate-700,
:global([data-theme="dark"]) .rm-item-master .text-slate-800,
:global(body.dark-mode) .rm-item-master .dashboard-title,
:global(body.dark-mode) .rm-item-master .text-slate-700,
:global(body.dark-mode) .rm-item-master .text-slate-800 {
  color: #f8fafc !important;
}
:global([data-theme="dark"]) .rm-item-master .form-control,
:global(body.dark-mode) .rm-item-master .form-control {
  background: #1e293b;
  border-color: #475569;
  color: #f8fafc;
}
</style>
