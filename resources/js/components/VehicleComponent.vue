<template>
    <div class="vehicle-management">
        <el-card class="box-card shadow-lg professional-card">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-car-front-fill me-2 text-primary"></i>Vehicle Registry</span>
                        <p class="text-muted mb-0 small">Manage your transport fleet and classification</p>
                    </div>
                    <el-button type="primary" size="large" @click="openDialog()" class="add-btn shadow-sm">
                        <i class="bi bi-plus-lg me-2"></i> Add Vehicle
                    </el-button>
                </div>
            </template>

            <el-table 
                :data="vehicles" 
                style="width: 100%" 
                v-loading="loading"
                class="modern-table"
                :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '13px', textTransform: 'uppercase'}"
            >
                <el-table-column prop="vehicle_number" label="Registration #" sortable min-width="150">
                    <template #default="scope">
                        <div class="fw-bold text-slate-700">{{ scope.row.vehicle_number }}</div>
                    </template>
                </el-table-column>
                <el-table-column prop="vehicle_type" label="Classification" width="180">
                    <template #default="scope">
                        <el-tag :type="getTagType(scope.row.vehicle_type)" effect="light" round class="fw-bold">{{ scope.row.vehicle_type }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="transporter.name" label="Transporter" sortable min-width="200">
                    <template #default="scope">
                        <div class="small"><i class="bi bi-truck me-2 opacity-50"></i>{{ scope.row.transporter?.name || 'Unassigned' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="Actions" width="120" align="center">
                    <template #default="scope">
                        <el-button-group>
                            <el-button size="small" @click="openDialog(scope.row)" type="primary" plain title="Edit"><i class="bi bi-pencil-square"></i></el-button>
                            <el-button size="small" type="danger" @click="deleteVehicle(scope.row)" plain title="Delete"><i class="bi bi-trash"></i></el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-dialog v-model="dialogVisible" :title="form.id ? 'Edit Vehicle' : 'Add Vehicle'" width="500px" class="professional-dialog">
            <el-form :model="form" label-position="top" :rules="rules" ref="formRef">
                <el-form-item label="Vehicle Registration Number" prop="vehicle_number">
                    <el-input v-model="form.vehicle_number" placeholder="e.g. LS-5432" />
                </el-form-item>
                <el-form-item label="Vehicle Classification" prop="vehicle_type">
                    <el-select v-model="form.vehicle_type" placeholder="Select classification" class="w-100">
                        <el-option label="Suzuki" value="Suzuki" />
                        <el-option label="Shehzore" value="Shehzore" />
                        <el-option label="Mazda" value="Mazda" />
                        <el-option label="1x17 Container" value="1x17 Container" />
                        <el-option label="1x20 Container" value="1x20 Container" />
                    </el-select>
                </el-form-item>
                <el-form-item label="Transporter Assignment" prop="transporter_id">
                    <el-select v-model="form.transporter_id" placeholder="Assign to transporter" class="w-100" filterable>
                        <el-option v-for="t in transporters" :key="t.id" :label="t.name" :value="t.id" />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="dialogVisible = false">Cancel</el-button>
                    <el-button type="primary" @click="save" :loading="submitting">Save Vehicle</el-button>
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

const vehicles = ref([]);
const transporters = ref([]);
const loading = ref(false);
const submitting = ref(false);
const dialogVisible = ref(false);
const formRef = ref(null);

const form = ref({
    id: null,
    vehicle_number: '',
    vehicle_type: '',
    transporter_id: null
});

const rules = {
    vehicle_number: [{ required: true, message: 'Vehicle number is required', trigger: 'blur' }],
    vehicle_type: [{ required: true, message: 'Type is required', trigger: 'change' }],
    transporter_id: [{ required: true, message: 'Transporter is required', trigger: 'change' }]
};

const fetchVehicles = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/vehicles');
        vehicles.value = response.data;
    } finally {
        loading.value = false;
    }
};

const fetchTransporters = async () => {
    const response = await axios.get('/api/transporters');
    transporters.value = response.data;
};

const openDialog = (vehicle = null) => {
    if (vehicle) {
        form.value = { ...vehicle };
    } else {
        form.value = { id: null, vehicle_number: '', vehicle_type: '', transporter_id: null };
    }
    dialogVisible.value = true;
};

const getTagType = (type) => {
    if (type.includes('Container')) return 'warning';
    if (type === 'Mazda') return 'success';
    return 'info';
};

const save = async () => {
    if (!formRef.value) return;
    await formRef.value.validate(async (valid) => {
        if (!valid) return;
        submitting.value = true;
        try {
            if (form.value.id) {
                await axios.put(`/api/vehicles/${form.value.id}`, form.value);
                ElMessage.success('Vehicle Updated');
            } else {
                await axios.post('/api/vehicles', form.value);
                ElMessage.success('Vehicle Added');
            }
            dialogVisible.value = false;
            fetchVehicles();
        } catch (error) {
            ElMessage.error('Error saving data');
        } finally {
            submitting.value = false;
        }
    });
};

const deleteVehicle = async (vehicle) => {
    try {
        await ElMessageBox.confirm('Delete this vehicle?', 'Warning', { type: 'warning' });
        await axios.delete(`/api/vehicles/${vehicle.id}`);
        ElMessage.success('Deleted');
        fetchVehicles();
    } catch (e) {}
};

onMounted(() => {
    fetchVehicles();
    fetchTransporters();
});
</script>

<style scoped>
.vehicle-management {
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
