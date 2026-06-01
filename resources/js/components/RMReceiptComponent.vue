<template>
  <div class="container-fluid px-0">
    <!-- Premium Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
      <div class="header-title">
        <h2 class="dashboard-title"><i class="bi bi-download text-indigo me-2"></i>Raw Material Receiving (GRN)</h2>
        <p class="text-muted mb-0 small">Log and track inbound raw material shipments and supplier invoices</p>
      </div>
      <button @click="openCreateDialog" class="btn btn-success shadow-sm">
        <i class="bi bi-plus-circle"></i> New GRN Receipt
      </button>
    </div>

    <!-- Create/Edit Form (Standard Card matching Finished Goods style) -->
    <div v-if="dialogVisible" class="card mb-4 shadow-sm border-0 animated fadeIn">
      <div class="card-header bg-transparent border-0 pt-3 pb-0">
        <h5 class="fw-bold text-slate-800"><i class="bi bi-pencil-square text-indigo me-1"></i> New Raw Material GRN</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm" novalidate>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">GRN Number <span class="text-danger">*</span></label>
                <input v-model="form.grn_no" type="text" class="form-control bg-light font-monospace fw-bold text-indigo" placeholder="Auto Generating..." readonly required />
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Supplier <span class="text-danger">*</span></label>
                <select v-model="form.supplier_id" class="form-control" required @change="onSupplierChange">
                  <option value="">Select Supplier</option>
                  <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Receiving Date <span class="text-danger">*</span></label>
                <input v-model="form.date" type="date" class="form-control" required @change="onSupplierChange" />
              </div>
            </div>
          </div>

          <div class="items-container mt-3 p-3 bg-light rounded border border-light-subtle">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="fw-bold text-slate-700"><i class="bi bi-list-task me-1"></i> RECEIPT ITEMS</span>
              <button type="button" @click="addItemRow" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Item
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0 bg-white rounded border border-light-subtle">
                <thead>
                  <tr class="text-uppercase small text-muted">
                    <th>Item <span class="text-danger">*</span></th>
                    <th style="width: 140px;">Quantity <span class="text-danger">*</span></th>
                    <th style="width: 100px;">Unit</th>
                    <th style="width: 150px;">Rate (Rs.) <span class="text-danger">*</span></th>
                    <th class="text-end" style="width: 180px;">Total</th>
                    <th class="text-center" style="width: 50px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, index) in form.items" :key="index">
                    <td>
                      <select v-model="row.rm_item_id" @change="onItemChange(row)" class="form-control form-control-sm" required>
                        <option value="">Select Item</option>
                        <option v-for="i in rmItems" :key="i.id" :value="i.id">{{ i.name }}</option>
                      </select>
                    </td>
                    <td>
                      <input v-model.number="row.quantity" type="number" step="0.01" min="0.01" class="form-control form-control-sm" @input="calculateRowTotal(row)" required />
                    </td>
                    <td>
                      <input v-model="row.unit" type="text" class="form-control form-control-sm" readonly />
                    </td>
                    <td>
                      <input v-model.number="row.rate" type="number" step="0.01" min="0" class="form-control form-control-sm" @input="calculateRowTotal(row)" required />
                      <small class="text-muted">{{ row.rate_source === 'supplier_mapping' ? 'Mapped Rate' : 'Default Rate' }}</small>
                    </td>
                    <td class="text-end fw-semibold text-slate-800">
                      Rs. {{ formatAmount(row.total_amount) }}
                    </td>
                    <td class="text-center">
                      <button type="button" @click="removeItemRow(index)" class="btn btn-sm btn-outline-danger border-0">
                        <i class="bi bi-trash-fill"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="text-end mt-3">
              <span class="fs-5 fw-bold text-success">GRAND TOTAL: Rs. {{ formatAmount(grandTotal) }}</span>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label fw-semibold text-slate-700">Remarks</label>
            <textarea v-model="form.remarks" class="form-control" rows="2" placeholder="Any additional notes or invoice references..."></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success shadow-sm" :disabled="submitting">
              <i class="bi bi-save me-1"></i> Save GRN Record
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
        <div class="row mb-4 g-2 align-items-end">
          <div class="col-md-3">
            <label class="small text-muted fw-bold">Supplier</label>
            <select v-model="filters.supplier_id" @change="fetchHistory" class="form-control form-control-sm">
              <option value="">All Suppliers</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="small text-muted fw-bold">Date Range</label>
            <div class="d-flex gap-2">
              <input v-model="filters.date_from" type="date" class="form-control form-control-sm" @change="fetchHistory" />
              <span class="align-self-center text-muted">to</span>
              <input v-model="filters.date_to" type="date" class="form-control form-control-sm" @change="fetchHistory" />
            </div>
          </div>
          <div class="col-md-2">
            <button @click="resetFilters" class="btn btn-sm btn-clear-filters w-100">Clear Filters</button>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-striped table-sm text-nowrap align-middle mb-0">
            <thead>
              <tr class="text-uppercase small text-muted">
                <th>GRN #</th>
                <th>Date</th>
                <th>Supplier</th>
                <th>Items</th>
                <th class="text-end">Total Amount</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in history" :key="row.id">
                <td><span class="badge bg-light text-primary border font-monospace fw-bold">{{ row.grn_no }}</span></td>
                <td>{{ formatDate(row.date) }}</td>
                <td class="fw-semibold text-slate-700">{{ row.supplier?.name }}</td>
                <td>
                  <div v-for="item in row.items" :key="item.id" class="small text-slate-600">
                    • {{ item.item?.name }}: <strong>{{ formatQty(item.quantity) }} {{ item.unit }}</strong>
                  </div>
                </td>
                <td class="text-end fw-bold text-slate-700">Rs. {{ formatAmount(calculateTotal(row.items)) }}</td>
                <td class="text-center">
                  <button @click="viewDetails(row)" class="btn btn-sm btn-outline-info border-0" title="View Details">
                    <i class="bi bi-eye-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="history.length === 0 && !loading">
                <td colspan="6" class="text-center py-4 text-muted">No GRN records found.</td>
              </tr>
              <tr v-if="loading">
                <td colspan="6" class="text-center py-4 text-muted">
                  <div class="spinner-border spinner-border-sm text-indigo me-2" role="status"></div>
                  Loading GRN history...
                </td>
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

const history = ref([]);
const suppliers = ref([]);
const rmItems = ref([]);
const loading = ref(false);
const submitting = ref(false);
const dialogVisible = ref(false);

const filters = reactive({
    supplier_id: '',
    date_from: '',
    date_to: ''
});

const form = reactive({
    grn_no: '',
    supplier_id: '',
    date: new Date().toISOString().substr(0, 10),
    remarks: '',
    items: []
});

const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (Number(item.total_amount) || 0), 0);
});

const fetchHistory = async () => {
    loading.value = true;
    try {
        const params = {
            supplier_id: filters.supplier_id || null,
            date_from: filters.date_from || null,
            date_to: filters.date_to || null
        };
        const res = await axios.get('/api/rm-receipts', { params });
        history.value = res.data.data;
    } catch (error) {
        if (axios.isCancel(error)) return;
        ElMessage.error('Failed to fetch GRN history');
    } finally {
        loading.value = false;
    }
};

const fetchSuppliers = async () => {
    const res = await axios.get('/api/suppliers');
    suppliers.value = res.data;
};

const fetchRmItems = async () => {
    const res = await axios.get('/api/rm-items', { params: { status: 'Active' } });
    rmItems.value = res.data;
};

const resetFilters = () => {
    filters.supplier_id = '';
    filters.date_from = '';
    filters.date_to = '';
    fetchHistory();
};

const openCreateDialog = async () => {
    Object.assign(form, {
        grn_no: 'Generating...',
        supplier_id: '',
        date: new Date().toISOString().substr(0, 10),
        remarks: '',
        items: []
    });
    addItemRow();
    dialogVisible.value = true;

    try {
        const res = await axios.get('/api/rm-receipts/next-grn');
        form.grn_no = res.data.next_grn_no;
    } catch (error) {
        console.error('Failed to auto-generate GRN number:', error);
        form.grn_no = '';
    }
};

const addItemRow = () => {
    form.items.push({
        rm_item_id: '',
        quantity: 0,
        unit: 'KG',
        rate: 0,
        total_amount: 0,
        rate_source: 'item_default'
    });
};

const removeItemRow = (index) => {
    form.items.splice(index, 1);
    if (form.items.length === 0) addItemRow();
};

const onItemChange = async (row) => {
    const item = rmItems.value.find(i => i.id === row.rm_item_id);
    if (item) {
        row.unit = item.unit_type;
        row.rate = Number(item.cost_price);
        row.rate_source = 'item_default';
        await applyResolvedRate(row);
        calculateRowTotal(row);
    }
};

const applyResolvedRate = async (row) => {
    if (!form.supplier_id || !row.rm_item_id) {
        return;
    }

    try {
        const res = await axios.post('/api/rm-item-supplier-rates/resolve', {
            supplier_id: form.supplier_id,
            rm_item_id: row.rm_item_id,
            date: form.date
        });
        row.rate = Number(res.data.rate || 0);
        row.rate_source = res.data.source || 'item_default';
        calculateRowTotal(row);
    } catch (error) {
        row.rate_source = 'item_default';
    }
};

const onSupplierChange = async () => {
    await Promise.all(form.items.map(row => applyResolvedRate(row)));
};

const calculateRowTotal = (row) => {
    row.total_amount = (Number(row.quantity) || 0) * (Number(row.rate) || 0);
};

const submitForm = async () => {
    if (!form.grn_no) {
        ElMessage.error('GRN number is required');
        return;
    }
    if (!form.supplier_id) {
        ElMessage.error('Supplier selection is required');
        return;
    }
    if (!form.date) {
        ElMessage.error('Receiving date is required');
        return;
    }
    if (form.items.some(i => !i.rm_item_id || i.quantity <= 0)) {
        ElMessage.warning('Please complete all item entries with valid quantities');
        return;
    }

    submitting.value = true;
    try {
        await axios.post('/api/rm-receipts', form);
        ElMessage.success('GRN Receipt saved successfully');
        dialogVisible.value = false;
        fetchHistory();
    } catch (error) {
        ElMessage.error(error.response?.data?.error || 'Failed to save receipt');
    } finally {
        submitting.value = false;
    }
};

const calculateTotal = (items) => {
    return items.reduce((sum, item) => sum + (Number(item.total_amount) || 0), 0);
};

const viewDetails = (receipt) => {
    // Custom logic if needed
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
};

const formatAmount = (val) => Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatQty = (val) => Number(val).toLocaleString();

onMounted(() => {
    fetchHistory();
    fetchSuppliers();
    fetchRmItems();
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
  padding: 12px 8px;
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
