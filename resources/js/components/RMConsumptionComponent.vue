<template>
  <div class="container-fluid px-0">
    <!-- Premium Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
      <div class="header-title">
        <h2 class="dashboard-title"><i class="bi bi-upload text-indigo me-2"></i>Raw Material Consumption / Issue</h2>
        <p class="text-muted mb-0 small">Record material requisitions and consumption logs against departments or job cards</p>
      </div>
      <button @click="openCreateDialog" class="btn btn-danger shadow-sm">
        <i class="bi bi-plus-circle"></i> New Consumption Voucher
      </button>
    </div>

    <!-- Create/Edit Form (Standard Card matching Finished Goods style) -->
    <div v-if="dialogVisible" class="card mb-4 shadow-sm border-0 animated fadeIn">
      <div class="card-header bg-transparent border-0 pt-3 pb-0">
        <h5 class="fw-bold text-slate-800"><i class="bi bi-pencil-square text-indigo me-1"></i> Record Material Consumption</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm" novalidate>
          <div class="row">
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Voucher Number <span class="text-danger">*</span></label>
                <input v-model="form.voucher_no" type="text" class="form-control" placeholder="e.g. VOU-0001" required />
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Consumption Date <span class="text-danger">*</span></label>
                <input v-model="form.date" type="date" class="form-control" required />
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Department <span class="text-danger">*</span></label>
                <select v-model="form.department" class="form-control" required>
                  <option value="">Select Department</option>
                  <option value="Production">Production</option>
                  <option value="Maintenance">Maintenance</option>
                  <option value="Packing">Packing</option>
                  <option value="QC">QC</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Issued To / Worker</label>
                <input v-model="form.issued_to" type="text" class="form-control" placeholder="Person name" />
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <v-select
                  v-model="form.job_card_id"
                  :options="activeJobCards"
                  :get-option-label="jc => jc.job_card_no + ' - ' + jc.product?.item_name"
                  :reduce="jc => jc.id"
                  placeholder="Link with Job Card"
                  :clearable="true"
                ></v-select>
              </div>
            </div>
          </div>

          <div class="items-container mt-3 p-3 bg-light rounded border border-light-subtle">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="fw-bold text-slate-700"><i class="bi bi-list-task me-1"></i> ITEMS TO CONSUME</span>
              <button type="button" @click="addItemRow" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Item
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0 bg-white rounded border border-light-subtle">
                <thead>
                  <tr class="text-uppercase small text-muted">
                    <th>Item <span class="text-danger">*</span></th>
                    <th style="width: 150px;" class="text-end">Available Stock</th>
                    <th style="width: 150px;">Qty to Issue <span class="text-danger">*</span></th>
                    <th style="width: 100px;">Unit</th>
                    <th class="text-center" style="width: 50px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, index) in form.items" :key="index">
                    <td>
                      <v-select
                        v-model="row.rm_item_id"
                        :options="rmItems"
                        :get-option-label="i => i.name + ' (Current: ' + formatQty(i.balance) + ' ' + i.unit + ')'"
                        :reduce="i => i.id"
                        placeholder="Search Item..."
                        :clearable="false"
                        class="v-select-sm"
                        @option:selected="onItemChange(row)"
                      ></v-select>
                    </td>
                    <td class="text-end fw-semibold">
                      <span :class="row.available > 0 ? 'text-success' : 'text-danger'">
                        {{ formatQty(row.available) }}
                      </span>
                    </td>
                    <td>
                      <input v-model.number="row.quantity" type="number" step="0.01" min="0.01" :max="row.available" class="form-control form-control-sm" required />
                    </td>
                    <td>
                      <input v-model="row.unit" type="text" class="form-control form-control-sm" readonly />
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
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label fw-semibold text-slate-700">Remarks / Purpose</label>
            <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Explain the purpose of consumption..."></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger shadow-sm" :disabled="submitting">
              <i class="bi bi-upload me-1"></i> Record Consumption
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
            <label class="small text-muted fw-bold">Department</label>
            <select v-model="filters.department" @change="fetchHistory" class="form-control form-control-sm">
              <option value="">All Departments</option>
              <option value="Production">Production</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Packing">Packing</option>
              <option value="QC">QC</option>
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
                <th>Voucher #</th>
                <th>Date</th>
                <th>Department</th>
                <th>Issued To</th>
                <th>Consumed Items</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in history" :key="row.id">
                <td><span class="badge bg-light text-danger border font-monospace fw-bold">{{ row.voucher_no }}</span></td>
                <td>{{ formatDate(row.date) }}</td>
                <td class="fw-semibold text-slate-700">{{ row.department }}</td>
                <td>{{ row.issued_to || 'N/A' }}</td>
                <td>
                  <div v-for="item in row.items" :key="item.id" class="small text-slate-600">
                    • {{ item.item?.name }}: <strong>{{ formatQty(item.quantity) }} {{ item.unit }}</strong>
                  </div>
                </td>
                <td class="text-wrap small text-muted" style="max-width: 250px;">{{ row.notes || 'N/A' }}</td>
              </tr>
              <tr v-if="history.length === 0 && !loading">
                <td colspan="6" class="text-center py-4 text-muted">No consumption records found.</td>
              </tr>
              <tr v-if="loading">
                <td colspan="6" class="text-center py-4 text-muted">
                  <div class="spinner-border spinner-border-sm text-indigo me-2" role="status"></div>
                  Loading consumption history...
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
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const history = ref([]);
const rmItems = ref([]);
const activeJobCards = ref([]);
const loading = ref(false);
const submitting = ref(false);
const dialogVisible = ref(false);

const filters = reactive({
    department: '',
    date_from: '',
    date_to: ''
});

const form = reactive({
    voucher_no: '',
    job_card_id: '',
    date: new Date().toISOString().substr(0, 10),
    department: '',
    issued_to: '',
    notes: '',
    items: []
});

const fetchHistory = async () => {
    loading.value = true;
    try {
        const params = {
            department: filters.department || null,
            date_from: filters.date_from || null,
            date_to: filters.date_to || null
        };
        const res = await axios.get('/api/rm-consumptions', { params });
        history.value = res.data.data;
    } catch (error) {
        if (axios.isCancel(error)) return;
        ElMessage.error('Failed to fetch consumption history');
    } finally {
        loading.value = false;
    }
};

const fetchRmItems = async () => {
    const res = await axios.get('/api/rm-reports/inventory');
    rmItems.value = res.data;
};

const fetchActiveJobCards = async () => {
    const res = await axios.get('/api/job-cards/active-list');
    activeJobCards.value = res.data;
};

const resetFilters = () => {
    filters.department = '';
    filters.date_from = '';
    filters.date_to = '';
    fetchHistory();
};

const openCreateDialog = () => {
    Object.assign(form, {
        voucher_no: '',
        job_card_id: '',
        date: new Date().toISOString().substr(0, 10),
        department: '',
        issued_to: '',
        notes: '',
        items: []
    });
    addItemRow();
    dialogVisible.value = true;
};

const addItemRow = () => {
    form.items.push({
        rm_item_id: '',
        quantity: 0,
        unit: 'KG',
        available: 0
    });
};

const removeItemRow = (index) => {
    form.items.splice(index, 1);
    if (form.items.length === 0) addItemRow();
};

const onItemChange = (row) => {
    const item = rmItems.value.find(i => i.id === row.rm_item_id);
    if (item) {
        row.unit = item.unit;
        row.available = Number(item.balance);
    }
};

const submitForm = async () => {
    if (!form.voucher_no) {
        ElMessage.error('Voucher number is required');
        return;
    }
    if (!form.date) {
        ElMessage.error('Date is required');
        return;
    }
    if (!form.department) {
        ElMessage.error('Department selection is required');
        return;
    }
    if (form.items.some(i => !i.rm_item_id || i.quantity <= 0)) {
        ElMessage.warning('Please complete all item entries with valid quantities');
        return;
    }
    if (form.items.some(i => i.quantity > i.available)) {
        ElMessage.error('Some items have quantity exceeding available stock!');
        return;
    }

    submitting.value = true;
    try {
        await axios.post('/api/rm-consumptions', form);
        ElMessage.success('Consumption Voucher saved successfully');
        dialogVisible.value = false;
        fetchHistory();
        fetchRmItems(); // Refresh stock info
    } catch (error) {
        ElMessage.error(error.response?.data?.error || 'Failed to save consumption');
    } finally {
        submitting.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
};

const formatQty = (val) => Number(val).toLocaleString();

onMounted(() => {
    fetchHistory();
    fetchRmItems();
    fetchActiveJobCards();
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
