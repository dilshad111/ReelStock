<template>
  <div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
      <h2 class="dashboard-title"><i class="bi bi-box-seam-fill text-indigo"></i> Raw Material Product Master</h2>
      <button @click="openCreateDialog" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-circle"></i> Add Raw Material
      </button>
    </div>

    <!-- Upsert Form (Standard Card matching Finished Goods style) -->
    <div v-if="dialogVisible" class="card mb-4 shadow-sm border-0 animated fadeIn">
      <div class="card-header bg-transparent border-0 pt-3 pb-0">
        <h5 class="fw-bold text-slate-800"><i class="bi bi-pencil-square text-indigo me-1"></i> {{ editing ? 'Edit' : 'Add' }} Raw Material</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm" novalidate>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Item Name <span class="text-danger">*</span></label>
                <input v-model="form.name" type="text" class="form-control" placeholder="Enter item name (e.g. Kraft Paper)" required />
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold text-slate-700">Unit Type <span class="text-danger">*</span></label>
                    <select v-model="form.unit_type" class="form-control" required>
                      <option value="">Select Unit</option>
                      <option v-for="u in uoms" :key="u.id" :value="u.name">{{ u.name }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold text-slate-700">Cost Price (Rs.) <span class="text-danger">*</span></label>
                    <input v-model.number="form.cost_price" type="number" step="0.01" min="0" class="form-control" required />
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold text-slate-700">Opening Stock</label>
                    <input v-model.number="form.opening_stock" type="number" min="0" class="form-control" :disabled="editing" />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold text-slate-700">Min. Stock Alert</label>
                    <input v-model.number="form.min_stock_alert" type="number" min="0" class="form-control" />
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold text-slate-700">Status <span class="text-danger">*</span></label>
                    <select v-model="form.status" class="form-control" required>
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold text-slate-700">Remarks</label>
            <textarea v-model="form.remarks" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success shadow-sm" :disabled="submitting">
              <i class="bi bi-save me-1"></i> {{ editing ? 'Update Item' : 'Create Item' }}
            </button>
            <button type="button" @click="dialogVisible = false" class="btn btn-secondary shadow-sm">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Filters & Table Section -->
    <div class="card shadow-sm border-0 mb-5">
      <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3 g-2 align-items-end">
          <div class="col-md-4">
            <label class="small text-muted fw-bold">Search</label>
            <input v-model="filters.search" @input="fetchItems" type="text" class="form-control form-control-sm" placeholder="Search by name or code...">
          </div>
          <div class="col-md-3">
            <label class="small text-muted fw-bold">Status</label>
            <select v-model="filters.status" @change="fetchItems" class="form-control form-control-sm">
              <option value="">All Statuses</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-2">
            <button @click="resetFilters" class="btn btn-sm btn-clear-filters w-100">Clear</button>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-striped table-sm text-nowrap align-middle">
            <thead>
              <tr class="text-uppercase small text-muted">
                <th>S.No.</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Unit</th>
                <th class="text-end">Cost/Price</th>
                <th class="text-end">Opening Stock</th>
                <th class="text-end">Min. Alert</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td><span class="badge bg-light text-slate-800 border font-monospace fw-bold">{{ item.code }}</span></td>
                <td class="fw-semibold text-slate-700">{{ item.name }}</td>
                <td>{{ item.unit_type }}</td>
                <td class="text-end">Rs. {{ formatAmount(item.cost_price) }}</td>
                <td class="text-end">{{ formatQty(item.opening_stock) }}</td>
                <td class="text-end">
                  <span :class="{'text-danger fw-bold': item.min_stock_alert > 0}">{{ formatQty(item.min_stock_alert) }}</span>
                </td>
                <td class="text-center">
                  <span :class="['badge py-1 px-2.5', item.status === 'Active' ? 'bg-success-soft text-success border border-success-subtle' : 'bg-danger-soft text-danger border border-danger-subtle']">
                    {{ item.status }}
                  </span>
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
                <td colspan="9" class="text-center py-4 text-muted">No raw materials found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const items = ref([]);
const qualities = ref([]);
const uoms = ref([]);
const loading = ref(false);
const submitting = ref(false);
const dialogVisible = ref(false);
const editing = ref(false);

const filters = reactive({
    search: '',
    status: ''
});

const form = reactive({
    id: null,
    name: '',
    paper_quality_id: null,
    unit_type: 'KG',
    cost_price: 0,
    opening_stock: 0,
    min_stock_alert: 0,
    status: 'Active',
    remarks: ''
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

const fetchQualities = async () => {
    try {
        const res = await axios.get('/api/paper-qualities');
        qualities.value = res.data;
    } catch (error) {}
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    fetchItems();
};

const openCreateDialog = () => {
    editing.value = false;
    Object.assign(form, {
        id: null,
        name: '',
        paper_quality_id: null,
        unit_type: 'KG',
        cost_price: 0,
        opening_stock: 0,
        min_stock_alert: 0,
        status: 'Active',
        remarks: ''
    });
    dialogVisible.value = true;
};

const editItem = (item) => {
    editing.value = true;
    Object.assign(form, {
        ...item,
        cost_price: Number(item.cost_price),
        opening_stock: Number(item.opening_stock),
        min_stock_alert: Number(item.min_stock_alert)
    });
    dialogVisible.value = true;
};

const submitForm = async () => {
    if (!form.name) {
        ElMessage.error('Item name is required');
        return;
    }
    if (!form.unit_type) {
        ElMessage.error('Unit type is required');
        return;
    }
    if (form.cost_price < 0) {
        ElMessage.error('Cost price cannot be negative');
        return;
    }

    submitting.value = true;
    try {
        if (editing.value) {
            await axios.put(`/api/rm-items/${form.id}`, form);
            ElMessage.success('Raw material updated successfully');
        } else {
            await axios.post('/api/rm-items', form);
            ElMessage.success('Raw material created successfully');
        }
        dialogVisible.value = false;
        fetchItems();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Failed to save item');
    } finally {
        submitting.value = false;
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

const fetchUoms = async () => {
    try {
        const res = await axios.get('/api/unit-of-measures');
        uoms.value = res.data;
    } catch (error) {
        console.error('Failed to fetch UoMs:', error);
    }
};

const formatAmount = (val) => Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatQty = (val) => Number(val).toLocaleString();

onMounted(() => {
    fetchItems();
    fetchQualities();
    fetchUoms();
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
</style>
