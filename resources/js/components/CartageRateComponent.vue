<template>
    <div class="cartage-rate-setup">
        <el-card class="box-card shadow-lg professional-card">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-tag-fill me-2 text-primary"></i>Rate Configuration</span>
                        <p class="text-muted mb-0 small">Setup standard cartage rates for customers and vehicles</p>
                    </div>
                    <el-button type="primary" size="large" @click="openDialog()" class="add-btn shadow-sm">
                        <i class="bi bi-gear-fill me-2"></i> Configure Rate
                    </el-button>
                </div>
            </template>

            <el-table 
                :data="rates" 
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Delete } from '@element-plus/icons-vue';

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
</style>
