<template>
    <div class="transporter-management">
        <el-card class="box-card shadow-lg professional-card">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-truck me-2 text-primary"></i>Transporter Management</span>
                        <p class="text-muted mb-0 small">Manage logistics partners and contact information</p>
                    </div>
                    <el-button type="primary" size="large" @click="openDialog()" class="add-btn shadow-sm">
                        <i class="bi bi-plus-lg me-2"></i> Add Transporter
                    </el-button>
                </div>
            </template>

            <el-table 
                :data="transporters" 
                style="width: 100%" 
                v-loading="loading"
                class="modern-table"
                :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '13px', textTransform: 'uppercase'}"
            >
                <el-table-column prop="name" label="Transporter Name" sortable min-width="200">
                    <template #default="scope">
                        <div class="d-flex align-items-center">
                            <el-avatar v-if="scope.row.logo_url" :src="scope.row.logo_url" :size="32" class="me-2" />
                            <div class="fw-bold text-slate-700">{{ scope.row.name }}</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column prop="phone" label="Contact info" width="180">
                    <template #default="scope">
                        <div v-if="scope.row.phone" class="small"><i class="bi bi-telephone me-2 opacity-50"></i>{{ scope.row.phone }}</div>
                        <div v-else class="text-muted small">No phone</div>
                    </template>
                </el-table-column>
                <el-table-column prop="address" label="Office Address" min-width="250">
                    <template #default="scope">
                        <div class="small text-muted text-truncate" :title="scope.row.address">{{ scope.row.address || 'Not defined' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="Actions" width="120" align="center">
                    <template #default="scope">
                        <el-button-group>
                            <el-button size="small" @click="openDialog(scope.row)" type="primary" plain title="Edit"><i class="bi bi-pencil-square"></i></el-button>
                            <el-button size="small" type="danger" @click="deleteTransporter(scope.row)" plain title="Delete"><i class="bi bi-trash"></i></el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-dialog v-model="dialogVisible" :title="form.id ? 'Edit Transporter' : 'Add Transporter'" width="500px" class="professional-dialog">
            <el-form :model="form" label-position="top" :rules="rules" ref="formRef">
                <el-form-item label="Transporter Name" prop="name">
                    <input v-model="form.name" type="text" class="form-control fg-like-input" placeholder="Enter company name" />
                </el-form-item>
                <el-form-item label="Contact Phone" prop="phone">
                    <input v-model="form.phone" type="text" class="form-control fg-like-input" placeholder="Contact number" />
                </el-form-item>
                <el-form-item label="Head Office Address" prop="address">
                    <textarea v-model="form.address" rows="4" class="form-control fg-like-textarea" placeholder="Enter full address"></textarea>
                </el-form-item>
                <el-form-item label="Transporter Logo" prop="logo">
                    <div class="d-flex align-items-center gap-3">
                        <el-upload
                            class="logo-uploader"
                            action="#"
                            :auto-upload="false"
                            :show-file-list="false"
                            :on-change="handleLogoChange"
                            accept="image/*"
                        >
                            <img v-if="logoPreview" :src="logoPreview" class="logo-preview-img" />
                            <el-icon v-else class="logo-uploader-icon"><i class="bi bi-plus-lg"></i></el-icon>
                        </el-upload>
                        <div class="small text-muted" v-if="!logoPreview">Upload company logo (PNG/JPG)</div>
                        <el-button v-else type="danger" size="small" link @click="removeLogo">Remove Logo</el-button>
                    </div>
                </el-form-item>
            </el-form>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="dialogVisible = false">Cancel</el-button>
                    <el-button type="primary" @click="save" :loading="submitting">Save Details</el-button>
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

const transporters = ref([]);
const loading = ref(false);
const submitting = ref(false);
const dialogVisible = ref(false);
const formRef = ref(null);

const form = ref({
    id: null,
    name: '',
    phone: '',
    address: '',
    logo: null
});

const logoPreview = ref(null);
const logoFile = ref(null);

const handleLogoChange = (file) => {
    logoFile.value = file.raw;
    logoPreview.value = URL.createObjectURL(file.raw);
};

const removeLogo = () => {
    logoFile.value = null;
    logoPreview.value = null;
    form.value.logo = null;
};

const rules = {
    name: [{ required: true, message: 'Transporter name is required', trigger: 'blur' }]
};

const fetchTransporters = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/transporters');
        transporters.value = response.data;
    } finally {
        loading.value = false;
    }
};

const openDialog = (transporter = null) => {
    if (transporter) {
        form.value = { ...transporter };
        logoPreview.value = transporter.logo_url;
        logoFile.value = null;
    } else {
        form.value = { id: null, name: '', phone: '', address: '', logo: null };
        logoPreview.value = null;
        logoFile.value = null;
    }
    dialogVisible.value = true;
};

const save = async () => {
    if (!formRef.value) return;
    await formRef.value.validate(async (valid) => {
        if (!valid) return;
        submitting.value = true;

        const formData = new FormData();
        formData.append('name', form.value.name);
        if (form.value.phone) formData.append('phone', form.value.phone);
        if (form.value.address) formData.append('address', form.value.address);
        if (logoFile.value) {
            formData.append('logo', logoFile.value);
        } else if (form.value.id && !logoPreview.value) {
            // Logic to signal removal if needed, or just let update handle it
        }

        try {
            if (form.value.id) {
                // Laravel hack for multipart PUT: use POST with _method=PUT
                formData.append('_method', 'PUT');
                await axios.post(`/api/transporters/${form.value.id}`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                ElMessage.success('Updated');
            } else {
                await axios.post('/api/transporters', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                ElMessage.success('Transporter added');
            }
            dialogVisible.value = false;
            fetchTransporters();
        } catch (error) {
            ElMessage.error('Error saving data');
        } finally {
            submitting.value = false;
        }
    });
};

const deleteTransporter = async (transporter) => {
    try {
        await ElMessageBox.confirm('Delete this transporter?', 'Warning', { type: 'warning' });
        await axios.delete(`/api/transporters/${transporter.id}`);
        ElMessage.success('Deleted');
        fetchTransporters();
    } catch (e) {}
};

onMounted(fetchTransporters);
</script>

<style scoped>
.transporter-management {
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
.professional-dialog :deep(.el-form-item) {
    margin-bottom: 22px;
}
.professional-dialog :deep(.el-input__wrapper) {
    min-height: 44px;
    border-radius: 12px;
}
.professional-dialog :deep(.el-input__inner) {
    font-size: 1rem;
    line-height: 1.35;
}
.professional-dialog :deep(.el-textarea__inner) {
    min-height: 132px !important;
    border-radius: 12px;
    font-size: 1rem;
    line-height: 1.45;
    padding-top: 12px;
}

.fg-like-input {
    height: 52px !important;
    min-height: 52px !important;
    border-radius: 10px !important;
    font-size: 1.1rem !important;
    padding: 0 16px !important;
}
.fg-like-input::placeholder {
    font-size: 1rem !important;
    color: #8fa1bd !important;
    opacity: 1 !important;
}
.fg-like-textarea {
    min-height: 120px !important;
    border-radius: 10px !important;
    font-size: 1.1rem !important;
    line-height: 1.45 !important;
    padding: 14px 16px !important;
    resize: vertical;
}
.fg-like-textarea::placeholder {
    font-size: 1rem !important;
    color: #8fa1bd !important;
    opacity: 1 !important;
}

.logo-uploader {
    border: 1px dashed #d9d9d9;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    width: 80px;
    height: 80px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #f8fafc;
}
.logo-uploader:hover {
    border-color: #409eff;
}
.logo-uploader-icon {
    font-size: 20px;
    color: #8c939d;
}
.logo-preview-img {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

[data-theme="dark"] .professional-dialog :deep(.el-input__wrapper),
[data-theme="dark"] .professional-dialog :deep(.el-textarea__inner) {
    border-color: #3a5078;
}

[data-theme="dark"] .fg-like-input,
[data-theme="dark"] .fg-like-textarea {
    background-color: #1e293b !important;
    border-color: #475569 !important;
    color: #e2e8f0 !important;
}
</style>
