<template>
    <div class="vehicle-type-setup">
        <el-card class="box-card shadow-lg professional-card">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-tags-fill me-2 text-primary"></i>Vehicle Classifications</span>
                        <p class="text-muted mb-0 small">Manage transport categories and modes</p>
                    </div>
                    <div>
                        <el-button type="primary" @click="openDialog()" class="add-btn shadow-sm">
                            <i class="bi bi-plus-lg me-2"></i> Add Classification
                        </el-button>
                    </div>
                </div>
            </template>

            <el-table 
                :data="types" 
                style="width: 100%" 
                v-loading="loading"
                class="modern-table"
                :header-cell-style="tableHeaderStyle"
            >
                <el-table-column prop="id" label="ID" width="80" align="center" />
                <el-table-column prop="name" label="Classification Name" sortable>
                    <template #default="scope">
                        <div class="fw-bold text-slate-700">{{ scope.row.name }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="Actions" width="150" align="center">
                    <template #default="scope">
                        <el-button-group>
                            <el-button size="small" type="primary" plain @click="editType(scope.row)" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </el-button>
                            <el-button size="small" type="danger" plain @click="deleteType(scope.row)" title="Delete">
                                <i class="bi bi-trash"></i>
                            </el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-dialog v-model="dialogVisible" :title="isEditing ? 'Edit Classification' : 'Add New Classification'" width="450px" class="professional-dialog">
            <el-form :model="form" label-position="top" :rules="rules" ref="formRef" @submit.prevent="save">
                <el-form-item label="Classification Name" prop="name">
                    <el-input v-model="form.name" placeholder="e.g. 1x17 Container, Suzuki, etc." />
                </el-form-item>
            </el-form>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="dialogVisible = false">Cancel</el-button>
                    <el-button type="primary" @click="save" :loading="submitting">Save Classification</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const types = ref([]);
const loading = ref(false);
const submitting = ref(false);
const dialogVisible = ref(false);
const isEditing = ref(false);
const formRef = ref(null);

const form = ref({
    id: null,
    name: ''
});

const rules = {
    name: [{ required: true, message: 'Name is required', trigger: 'blur' }]
};

const tableHeaderStyle = computed(() => {
    const dark = document?.documentElement?.getAttribute('data-theme') === 'dark';
    return {
        backgroundColor: dark ? '#1e2a44' : '#f8fafc',
        color: dark ? '#dbe7ff' : '#475569',
        fontWeight: '800',
        fontSize: '13px',
        textTransform: 'uppercase'
    };
});

const fetchTypes = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/vehicle-types');
        types.value = response.data;
    } finally {
        loading.value = false;
    }
};

const openDialog = () => {
    isEditing.value = false;
    form.value = { id: null, name: '' };
    dialogVisible.value = true;
};

const editType = (type) => {
    isEditing.value = true;
    form.value = { ...type };
    dialogVisible.value = true;
};

const save = async () => {
    if (!formRef.value) return;
    await formRef.value.validate(async (valid) => {
        if (!valid) return;
        submitting.value = true;
        try {
            if (isEditing.value) {
                await axios.put(`/api/vehicle-types/${form.value.id}`, form.value);
                ElMessage.success('Classification updated');
            } else {
                await axios.post('/api/vehicle-types', form.value);
                ElMessage.success('Classification created');
            }
            dialogVisible.value = false;
            fetchTypes();
        } catch (error) {
            ElMessage.error(error.response?.data?.message || 'Error saving classification');
        } finally {
            submitting.value = false;
        }
    });
};

const deleteType = async (type) => {
    try {
        await ElMessageBox.confirm('Delete this classification?', 'Warning', { type: 'warning' });
        await axios.delete(`/api/vehicle-types/${type.id}`);
        ElMessage.success('Deleted');
        fetchTypes();
    } catch (e) {}
};

onMounted(() => {
    fetchTypes();
});
</script>

<style scoped>
.vehicle-type-setup {
    padding: 30px;
    background: var(--surface-page, #f1f5f9);
    min-height: calc(100vh - 120px);
}
.professional-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    background: var(--surface-card, #ffffff);
}
.fw-800 { font-weight: 800; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }

.modern-table :deep(td) {
    padding: 15px 0;
}

.professional-dialog :deep(.el-form-item) {
    margin-bottom: 22px;
}

.professional-dialog :deep(.el-input__wrapper) {
    min-height: 52px;
    height: 52px;
    border-radius: 10px;
}

.professional-dialog :deep(.el-input__inner) {
    height: 50px;
    line-height: 50px;
    font-size: 1.05rem;
}

[data-theme="dark"] .vehicle-type-setup {
    --surface-page: #0f1a2f;
    --surface-card: #182540;
}

[data-theme="dark"] .vehicle-type-setup :deep(.el-card) {
    background: #182540;
    border: 1px solid #2b3f67;
}

[data-theme="dark"] .vehicle-type-setup :deep(.el-card__header) {
    background: #13203a;
    border-bottom: 1px solid #2b3f67;
}

[data-theme="dark"] .vehicle-type-setup :deep(.el-table),
[data-theme="dark"] .vehicle-type-setup :deep(.el-table__inner-wrapper),
[data-theme="dark"] .vehicle-type-setup :deep(.el-table tr),
[data-theme="dark"] .vehicle-type-setup :deep(.el-table th.el-table__cell),
[data-theme="dark"] .vehicle-type-setup :deep(.el-table td.el-table__cell) {
    background: #182540 !important;
    color: #dbe7ff !important;
    border-color: #2b3f67 !important;
}

[data-theme="dark"] .vehicle-type-setup :deep(.el-table__row:hover > td.el-table__cell) {
    background: #22365a !important;
}

[data-theme="dark"] .vehicle-type-setup :deep(.el-dialog) {
    background: #182540;
    border: 1px solid #2b3f67;
}

[data-theme="dark"] .vehicle-type-setup :deep(.el-dialog__title),
[data-theme="dark"] .vehicle-type-setup :deep(.el-form-item__label),
[data-theme="dark"] .vehicle-type-setup :deep(.el-input__inner) {
    color: #dbe7ff !important;
}
</style>
