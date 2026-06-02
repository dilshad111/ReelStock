<template>
    <div class="cartage-rate-setup">
        <el-card class="box-card shadow-lg professional-card">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-tag-fill me-2 text-primary"></i>Rate Configuration</span>
                        <p class="text-muted mb-0 small">Setup standard cartage rates for customers and vehicles</p>
                    </div>
                    <div class="d-flex gap-2">
                        <el-button type="info" @click="printRates" plain>
                            <i class="bi bi-printer me-1"></i> Print
                        </el-button>
                        <el-button type="success" @click="exportToExcel" plain>
                            <i class="bi bi-file-earmark-excel me-1"></i> Excel
                        </el-button>
                        <el-button type="danger" @click="exportToPDF" plain>
                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                        </el-button>
                        <el-button type="primary" @click="openDialog()" class="add-btn shadow-sm ms-2">
                            <i class="bi bi-gear-fill me-2"></i> Configure Rate
                        </el-button>
                    </div>
                </div>
            </template>

            <!-- Filters Bar -->
            <div class="filters-bar mb-4 p-3 bg-light rounded border border-light-subtle">
                <div class="row g-3">
                    <div class="col-md-4">
                        <el-input 
                            v-model="searchFilters.customer" 
                            placeholder="Filter by Customer..." 
                            clearable
                        >
                            <template #prefix><i class="bi bi-search"></i></template>
                        </el-input>
                    </div>
                    <div class="col-md-4">
                        <el-input 
                            v-model="searchFilters.destination" 
                            placeholder="Filter by Destination..." 
                            clearable
                        >
                            <template #prefix><i class="bi bi-geo-alt"></i></template>
                        </el-input>
                    </div>
                    <div class="col-md-4">
                        <el-select v-model="searchFilters.vehicle_type" placeholder="Vehicle Type" clearable class="w-100">
                            <template #prefix><i class="bi bi-truck"></i></template>
                            <el-option label="Suzuki" value="Suzuki" />
                            <el-option label="Shehzore" value="Shehzore" />
                            <el-option label="Mazda" value="Mazda" />
                            <el-option label="1x17 Container" value="1x17 Container" />
                            <el-option label="1x20 Container" value="1x20 Container" />
                        </el-select>
                    </div>
                </div>
            </div>

            <el-table 
                :data="filteredRates" 
                style="width: 100%" 
                v-loading="loading"
                class="modern-table"
                :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '13px', textTransform: 'uppercase'}"
            >
                <el-table-column prop="shipping_address.customer.name" label="Customer" sortable min-width="180">
                    <template #default="scope">
                        <div class="fw-bold text-slate-700">{{ scope.row.shipping_address?.customer?.name }}</div>
                    </template>
                </el-table-column>
                <el-table-column prop="shipping_address.address_name" label="Shipping Destination" sortable min-width="200">
                    <template #default="scope">
                        <div class="small"><i class="bi bi-geo-alt me-1 opacity-50"></i>{{ scope.row.shipping_address?.address_name }}</div>
                    </template>
                </el-table-column>
                <el-table-column prop="vehicle_type" label="Transport Mode" sortable width="160">
                    <template #default="scope">
                        <el-tag size="small" effect="light" round class="fw-bold">{{ scope.row.vehicle_type }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="rate" label="Standard Rate" width="160" align="right">
                    <template #default="scope">
                        <div class="fw-800 text-emerald-600">Rs. {{ scope.row.rate.toLocaleString() }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="Actions" width="120" align="center">
                    <template #default="scope">
                        <el-button-group>
                            <el-button size="small" type="primary" plain @click="editRate(scope.row)" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </el-button>
                            <el-button size="small" type="danger" plain @click="deleteRate(scope.row)" title="Delete">
                                <i class="bi bi-trash"></i>
                            </el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-dialog v-model="dialogVisible" title="Setup Cartage Rate" width="550px" class="professional-dialog">
            <el-form :model="form" label-position="top" :rules="rules" ref="formRef">
                <el-form-item label="Select Client" prop="customer_id">
                    <el-select v-model="form.customer_id" placeholder="Choose client" class="w-100" @change="onCustomerChange" filterable>
                        <el-option v-for="c in customers" :key="c.id" :label="c.name" :value="c.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="Target Shipping Address" prop="shipping_address_id">
                    <el-select v-model="form.shipping_address_id" placeholder="Choose address" class="w-100" :disabled="!form.customer_id" filterable>
                        <el-option v-for="a in filteredAddresses" :key="a.id" :label="a.address_name" :value="a.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="Vehicle Categorization" prop="vehicle_type">
                    <el-select v-model="form.vehicle_type" placeholder="Select vehicle class" class="w-100">
                        <el-option label="Suzuki" value="Suzuki" />
                        <el-option label="Shehzore" value="Shehzore" />
                        <el-option label="Mazda" value="Mazda" />
                        <el-option label="1x17 Container" value="1x17 Container" />
                        <el-option label="1x20 Container" value="1x20 Container" />
                    </el-select>
                </el-form-item>
                <el-form-item label="Fixed Cartage Amount (Rs.)" prop="rate">
                    <el-input-number v-model="form.rate" :min="0" class="w-100" />
                </el-form-item>
            </el-form>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="dialogVisible = false">Cancel</el-button>
                    <el-button type="primary" @click="save" :loading="submitting">Apply Rate</el-button>
                </div>
            </template>
        </el-dialog>
    </div>

    <!-- Print Only Area -->
    <div id="print-area" class="d-none print-only">
        <div class="print-header text-center mb-4">
            <h2 class="fw-bold">Cartage Rate Configuration</h2>
            <p class="text-muted small">Generated on {{ new Date().toLocaleString() }}</p>
        </div>
        <table class="table table-bordered print-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Shipping Destination</th>
                    <th>Transport Mode</th>
                    <th class="text-end">Standard Rate (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="rate in filteredRates" :key="rate.id">
                    <td>{{ rate.shipping_address?.customer?.name }}</td>
                    <td>{{ rate.shipping_address?.address_name }}</td>
                    <td>{{ rate.vehicle_type }}</td>
                    <td class="text-end">{{ rate.rate.toLocaleString() }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Delete } from '@element-plus/icons-vue';
import * as XLSX from 'xlsx';

const rates = ref([]);
const customers = ref([]);
const loading = ref(false);
const submitting = ref(false);
const dialogVisible = ref(false);
const formRef = ref(null);

const form = ref({
    customer_id: null,
    shipping_address_id: null,
    vehicle_type: '',
    rate: 0
});

const searchFilters = reactive({
    customer: '',
    destination: '',
    vehicle_type: ''
});

const filteredRates = computed(() => {
    return rates.value.filter(rate => {
        const customerMatch = !searchFilters.customer || rate.shipping_address?.customer?.name.toLowerCase().includes(searchFilters.customer.toLowerCase());
        const destinationMatch = !searchFilters.destination || rate.shipping_address?.address_name.toLowerCase().includes(searchFilters.destination.toLowerCase());
        const vehicleMatch = !searchFilters.vehicle_type || rate.vehicle_type === searchFilters.vehicle_type;
        return customerMatch && destinationMatch && vehicleMatch;
    });
});

const rules = {
    shipping_address_id: [{ required: true, message: 'Address is required', trigger: 'change' }],
    vehicle_type: [{ required: true, message: 'Type is required', trigger: 'change' }],
    rate: [{ required: true, message: 'Rate is required', trigger: 'blur' }]
};

const filteredAddresses = computed(() => {
    if (!form.value.customer_id) return [];
    const customer = customers.value.find(c => c.id === form.value.customer_id);
    return customer ? customer.shipping_addresses : [];
});

const fetchRates = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/cartage-rates');
        rates.value = response.data;
    } finally {
        loading.value = false;
    }
};

const fetchCustomers = async () => {
    const response = await axios.get('/api/customers');
    customers.value = response.data;
};

const openDialog = () => {
    form.value = { customer_id: null, shipping_address_id: null, vehicle_type: '', rate: 0 };
    dialogVisible.value = true;
};

const editRate = (rate) => {
    form.value = {
        customer_id: rate.shipping_address?.customer_id,
        shipping_address_id: rate.shipping_address_id,
        vehicle_type: rate.vehicle_type,
        rate: rate.rate
    };
    dialogVisible.value = true;
};

const onCustomerChange = () => {
    form.value.shipping_address_id = null;
};

const save = async () => {
    if (!formRef.value) return;
    await formRef.value.validate(async (valid) => {
        if (!valid) return;
        submitting.value = true;
        try {
            await axios.post('/api/cartage-rates', form.value);
            ElMessage.success('Configuration saved');
            dialogVisible.value = false;
            fetchRates();
        } catch (error) {
            ElMessage.error('Error saving rate');
        } finally {
            submitting.value = false;
        }
    });
};

const deleteRate = async (rate) => {
    try {
        await ElMessageBox.confirm('Delete this rate setting?', 'Warning', { type: 'warning' });
        await axios.delete(`/api/cartage-rates/${rate.id}`);
        ElMessage.success('Deleted');
        fetchRates();
    } catch (e) {}
};

const printRates = () => {
    window.print();
};

const exportToExcel = () => {
    const data = filteredRates.value.map(r => ({
        Customer: r.shipping_address?.customer?.name,
        Destination: r.shipping_address?.address_name,
        Vehicle: r.vehicle_type,
        Rate: r.rate
    }));
    
    const worksheet = XLSX.utils.json_to_sheet(data);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Rates");
    XLSX.writeFile(workbook, "Cartage_Rates.xlsx");
    ElMessage.success('Excel file exported');
};

const exportToPDF = () => {
    // For now, use print to PDF. In a real app we might call a backend PDF route
    // but browser print handles it beautifully if styled correctly.
    window.print();
};

onMounted(() => {
    fetchRates();
    fetchCustomers();
});
</script>

<style scoped>
.cartage-rate-setup {
    padding: 30px;
    background-color: #f1f5f9;
    min-height: calc(100vh - 120px);
    font-family: 'Montserrat', sans-serif;
}
.professional-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}
.fw-800 { font-weight: 800; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }
.text-emerald-600 { color: #059669; }

.modern-table :deep(.el-table__row) {
    transition: background-color 0.2s;
}
.modern-table :deep(.el-table__row:hover) {
    background-color: #f8fafc !important;
}
.modern-table :deep(td) {
    padding: 15px 0;
}

.professional-dialog :deep(.el-dialog) {
    border-radius: 16px;
}
.professional-dialog :deep(.el-dialog__header) {
    padding: 24px;
    margin-right: 0;
    border-bottom: 1px solid #f1f5f9;
}
.professional-dialog :deep(.el-dialog__title) {
    font-weight: 800;
    color: #1e293b;
    font-size: 20px;
}
.professional-dialog :deep(.el-form-item__label) {
    font-weight: 700;
    color: #475569;
    padding-bottom: 8px !important;
}
.professional-dialog :deep(.el-form-item) {
    margin-bottom: 20px;
}
.professional-dialog :deep(.el-input__wrapper),
.professional-dialog :deep(.el-select__wrapper),
.professional-dialog :deep(.el-input-number) {
    min-height: 52px;
    height: 52px;
    border-radius: 10px;
}
.filters-bar :deep(.el-input__wrapper),
.filters-bar :deep(.el-select__wrapper) {
    min-height: 52px;
    height: 52px;
    border-radius: 10px;
}

.filters-bar {
    border-radius: 12px;
}

[data-theme="dark"] .cartage-rate-setup {
    background-color: #0f172a !important;
}

[data-theme="dark"] .cartage-rate-setup .professional-card,
[data-theme="dark"] .cartage-rate-setup :deep(.el-card__header),
[data-theme="dark"] .cartage-rate-setup :deep(.el-card__body) {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .cartage-rate-setup .filters-bar {
    background: #334155 !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .cartage-rate-setup .text-slate-800,
[data-theme="dark"] .cartage-rate-setup .text-slate-700,
[data-theme="dark"] .cartage-rate-setup .text-muted {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .cartage-rate-setup .modern-table :deep(.el-table th),
[data-theme="dark"] .cartage-rate-setup .modern-table :deep(.el-table td) {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .cartage-rate-setup .modern-table :deep(.el-table__row:hover) {
    background-color: #334155 !important;
}

.print-only {
    display: none;
}

@media print {
    body * {
        visibility: hidden;
    }
    #print-area, #print-area * {
        visibility: visible;
    }
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
    }
    .cartage-rate-setup {
        padding: 0;
        background: none;
    }
}
</style>
