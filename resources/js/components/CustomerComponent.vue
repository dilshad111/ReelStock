<template>
    <div class="customer-management">
        <el-card class="box-card shadow-lg professional-card">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-people-fill me-2 text-primary"></i>Customer Management</span>
                        <p class="text-muted mb-0 small">Manage your client base and shipping locations</p>
                    </div>
                    <el-button type="primary" size="large" @click="openCustomerDialog()" class="add-btn shadow-sm">
                        <i class="bi bi-plus-lg me-2"></i> Add Customer
                    </el-button>
                </div>
            </template>

            <el-table 
                :data="customers" 
                style="width: 100%" 
                v-loading="loading" 
                class="modern-table"
                :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '13px', textTransform: 'uppercase'}"
            >
                <el-table-column prop="name" label="Customer Name" sortable min-width="180">
                    <template #default="scope">
                        <div class="fw-bold text-slate-700">{{ scope.row.name }}</div>
                    </template>
                </el-table-column>
                <el-table-column prop="email" label="Contact Details" min-width="180">
                    <template #default="scope">
                        <div class="d-flex flex-column">
                            <span v-if="scope.row.email" class="small"><i class="bi bi-envelope me-1 opacity-50"></i>{{ scope.row.email }}</span>
                            <span v-if="scope.row.phone" class="small"><i class="bi bi-telephone me-1 opacity-50"></i>{{ scope.row.phone }}</span>
                            <span v-if="!scope.row.email && !scope.row.phone" class="text-muted small">No contact info</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="Shipping Locations" min-width="250">
                    <template #default="scope">
                        <div class="location-badges">
                            <el-tag v-for="addr in scope.row.shipping_addresses" :key="addr.id" size="small" class="custom-tag" effect="plain" round>
                                {{ addr.address_name }}
                            </el-tag>
                            <span v-if="!scope.row.shipping_addresses.length" class="text-muted x-small italic">No addresses defined</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="Actions" width="120" align="center">
                    <template #default="scope">
                        <el-button-group>
                            <el-button size="small" @click="openCustomerDialog(scope.row)" type="primary" plain title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </el-button>
                            <el-button size="small" type="danger" @click="deleteCustomer(scope.row)" plain title="Delete">
                                <i class="bi bi-trash"></i>
                            </el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- Professional Customer Form Dialog -->
        <el-dialog v-model="customerDialogVisible" :title="customerForm.id ? 'Edit Customer' : 'Add Customer'" width="650px" class="professional-dialog">
            <el-form :model="customerForm" label-position="top" :rules="customerRules" ref="customerFormRef">
                <div class="row">
                    <div class="col-md-6">
                        <el-form-item label="Customer Name" prop="name">
                            <el-input v-model="customerForm.name" placeholder="Enter name" />
                        </el-form-item>
                    </div>
                    <div class="col-md-6">
                        <el-form-item label="Email Address" prop="email">
                            <el-input v-model="customerForm.email" placeholder="email@example.com" />
                        </el-form-item>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <el-form-item label="Phone Number" prop="phone">
                            <el-input v-model="customerForm.phone" placeholder="Contact number" />
                        </el-form-item>
                    </div>
                </div>
                <el-form-item label="Main Office Address" prop="address">
                    <el-input type="textarea" :rows="2" v-model="customerForm.address" placeholder="Enter main office address" />
                </el-form-item>

                <el-divider content-position="left">
                    <span class="fw-bold"><i class="bi bi-geo-alt-fill me-1 text-primary"></i> Shipping Addresses</span>
                </el-divider>

                <div v-for="(addr, index) in customerForm.shipping_addresses" :key="index" class="address-row animate__animated animate__fadeIn">
                    <div class="d-flex gap-2 align-items-center">
                        <el-input v-model="addr.address_name" placeholder="Name" size="small" style="flex: 0 0 150px" />
                        <el-input v-model="addr.full_address" placeholder="Complete physical address" size="small" style="flex: 1" />
                        <el-button type="danger" circle size="small" @click="removeAddressField(index)" v-if="customerForm.shipping_addresses.length > 1">
                            <i class="bi bi-trash"></i>
                        </el-button>
                    </div>
                </div>

                <div class="text-center mt-2">
                    <el-button type="primary" plain circle @click="addAddressField">
                        <i class="bi bi-plus-lg"></i>
                    </el-button>
                    <div class="small text-muted mt-1">Add Shipping Address</div>
                </div>
            </el-form>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="customerDialogVisible = false">Cancel</el-button>
                    <el-button type="primary" @click="saveCustomer" :loading="submitting" class="save-btn">Save Changes</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Edit, Delete } from '@element-plus/icons-vue';

const customers = ref([]);
const loading = ref(false);
const submitting = ref(false);
const customerDialogVisible = ref(false);
const customerFormRef = ref(null);

const customerForm = ref({
    id: null,
    name: '',
    email: '',
    phone: '',
    address: '',
    shipping_addresses: []
});

const customerRules = {
    name: [{ required: true, message: 'Customer name is required', trigger: 'blur' }]
};

const fetchCustomers = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/customers');
        customers.value = response.data;
    } catch (error) {
        ElMessage.error('Failed to load customers');
    } finally {
        loading.value = false;
    }
};

const openCustomerDialog = (customer = null) => {
    if (customer) {
        // Clone and ensure shipping_addresses is an array
        customerForm.value = JSON.parse(JSON.stringify(customer));
        if (!customerForm.value.shipping_addresses || customerForm.value.shipping_addresses.length === 0) {
            customerForm.value.shipping_addresses = [{ address_name: '', full_address: '' }];
        }
    } else {
        customerForm.value = {
            id: null,
            name: '',
            email: '',
            phone: '',
            address: '',
            shipping_addresses: [{ address_name: '', full_address: '' }]
        };
    }
    customerDialogVisible.value = true;
};

const addAddressField = () => {
    customerForm.value.shipping_addresses.push({ address_name: '', full_address: '' });
};

const removeAddressField = (index) => {
    customerForm.value.shipping_addresses.splice(index, 1);
};

const saveCustomer = async () => {
    if (!customerFormRef.value) return;
    
    await customerFormRef.value.validate(async (valid) => {
        if (!valid) return;

        // Validation for shipping addresses
        const invalidAddr = customerForm.value.shipping_addresses.some(a => !a.address_name || !a.full_address);
        if (invalidAddr) {
            ElMessage.warning('Please complete all shipping address fields or remove empty ones.');
            return;
        }

        submitting.value = true;
        try {
            if (customerForm.value.id) {
                await axios.put(`/api/customers/${customerForm.value.id}`, customerForm.value);
                ElMessage.success('Customer updated successfully');
            } else {
                await axios.post('/api/customers', customerForm.value);
                ElMessage.success('Customer created successfully');
            }
            customerDialogVisible.value = false;
            fetchCustomers();
        } catch (error) {
            ElMessage.error(error.response?.data?.message || 'Error processing request');
        } finally {
            submitting.value = false;
        }
    });
};

const deleteCustomer = async (customer) => {
    try {
        await ElMessageBox.confirm('Permanent delete customer and all associated data?', 'Warning', {
            type: 'warning',
            confirmButtonText: 'Delete',
            confirmButtonClass: 'el-button--danger'
        });
        await axios.delete(`/api/customers/${customer.id}`);
        ElMessage.success('Customer deleted');
        fetchCustomers();
    } catch (e) {}
};

onMounted(fetchCustomers);
</script>

<style scoped>
.customer-management {
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
.header-title h5 {
    margin-bottom: 2px;
}
.fw-800 { font-weight: 800; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }

.modern-table :deep(.el-table__row) {
    transition: background-color 0.2s;
}
.modern-table :deep(.el-table__row:hover) {
    background-color: #f8fafc !important;
}
.modern-table :deep(td) {
    padding: 12px 0;
}

.custom-tag {
    border-color: #e2e8f0;
    color: #475569;
    background-color: #ffffff;
    font-weight: 600;
    margin: 2px;
}

.location-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.address-row {
    background: #f8fafc;
    padding: 15px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 12px;
    transition: all 0.3s;
}
.address-row:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
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
    margin-bottom: 8px;
}
.save-btn {
    border-radius: 8px;
    font-weight: 700;
    padding: 12px 32px;
}
.italic { font-style: italic; }
.x-small { font-size: 11px; }
</style>
