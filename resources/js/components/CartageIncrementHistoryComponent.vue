<template>
    <div class="cartage-history-page">
        <el-card class="box-card shadow-lg professional-card mb-4 no-print" v-loading="loading">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-clock-history me-2 text-primary"></i>Increment History</span>
                        <p class="text-muted mb-0 small">Audit log of all bulk cartage rate updates</p>
                    </div>
                </div>
            </template>

            <el-table 
                :data="history" 
                style="width: 100%" 
                class="modern-table"
                :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '12px', textTransform: 'uppercase'}"
            >
                <el-table-column label="Implementation Date" width="180" sortable>
                    <template #default="scope">
                        <div class="fw-bold"><i class="bi bi-calendar-check me-2"></i>{{ formatDate(scope.row.effective_date) }}</div>
                        <div class="x-small text-muted">Applied: {{ new Date(scope.row.created_at).toLocaleString() }}</div>
                    </template>
                </el-table-column>

                <el-table-column prop="vehicle_type" label="Classification" min-width="150">
                    <template #default="scope">
                        <el-tag effect="plain" class="fw-bold">{{ scope.row.vehicle_type }}</el-tag>
                    </template>
                </el-table-column>

                <el-table-column label="Increment Detail" min-width="200">
                    <template #default="scope">
                        <div v-if="scope.row.increment_type === 'percentage'">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">+{{ scope.row.increment_value }}% Increase</span>
                        </div>
                        <div v-else-if="scope.row.increment_type === 'fixed'">
                            <span class="badge bg-success-subtle text-success border border-success-subtle">+Rs. {{ scope.row.increment_value }} Fixed</span>
                        </div>
                        <div class="text-muted x-small mt-1">{{ scope.row.details.length }} addresses updated</div>
                    </template>
                </el-table-column>

                <el-table-column label="Actions" width="280" align="center">
                    <template #default="scope">
                        <div class="d-flex justify-content-center gap-1">
                            <el-button size="small" type="primary" @click="viewDetails(scope.row)" plain>
                                <i class="bi bi-eye"></i> View
                            </el-button>
                            <el-button size="small" type="info" @click="printHistory(scope.row)" plain>
                                <i class="bi bi-printer"></i> Print
                            </el-button>
                            <el-button size="small" type="danger" @click="deleteHistory(scope.row)" plain>
                                <i class="bi bi-trash"></i> Delete
                            </el-button>
                        </div>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- Detail Dialog -->
        <el-dialog v-model="dialogVisible" :title="'Rate List Detail - ' + activeLog?.vehicle_type" width="800px" class="professional-dialog">
            <div v-if="activeLog" class="p-2">
                <div class="d-flex justify-content-between mb-4 bg-light p-3 rounded border">
                    <div>
                        <p class="mb-0 text-muted small uppercase fw-bold">Classification</p>
                        <p class="mb-0 fw-bold fs-5">{{ activeLog.vehicle_type }}</p>
                    </div>
                    <div>
                        <p class="mb-0 text-muted small uppercase fw-bold">Effective Date</p>
                        <p class="mb-0 fw-bold fs-5 text-primary">{{ formatDate(activeLog.effective_date) }}</p>
                    </div>
                    <div class="text-end">
                        <p class="mb-0 text-muted small uppercase fw-bold">Increment</p>
                        <p class="mb-0 fw-bold fs-5 text-success">
                            {{ activeLog.increment_type === 'percentage' ? '+' + activeLog.increment_value + '%' : '+Rs. ' + activeLog.increment_value }}
                        </p>
                    </div>
                </div>

                <el-table :data="activeLog.details" max-height="400" border>
                    <el-table-column label="Client / Destination">
                        <template #default="scope">
                            <div class="fw-bold">{{ scope.row.shipping_address?.customer?.name }}</div>
                            <div class="small text-muted">{{ scope.row.shipping_address?.address_name }}</div>
                        </template>
                    </el-table-column>
                    <el-table-column prop="old_rate" label="Old Rate" width="120" align="right">
                        <template #default="scope">Rs. {{ scope.row.old_rate.toLocaleString() }}</template>
                    </el-table-column>
                    <el-table-column prop="amount_increase" label="Increment" width="120" align="right">
                        <template #default="scope" class="text-success">+{{ scope.row.amount_increase.toLocaleString() }}</template>
                    </el-table-column>
                    <el-table-column prop="new_rate" label="New Rate" width="120" align="right">
                        <template #default="scope" class="fw-bold">Rs. {{ scope.row.new_rate.toLocaleString() }}</template>
                    </el-table-column>
                </el-table>
            </div>
            <template #footer>
                <el-button @click="dialogVisible = false">Close</el-button>
                <el-button type="primary" @click="printHistory(activeLog)">Print This List</el-button>
            </template>
        </el-dialog>

        <!-- Hidden Print Template -->
        <div id="print-content" class="print-container d-none" v-if="activeLog">
            <div class="print-header-section text-center mb-5 pb-3 border-bottom-black">
                <!-- Logo -->
                <div class="mb-3">
                    <img v-if="settings.company_logo" :src="'/storage/' + settings.company_logo" class="print-logo-centered" @error="$event.target.style.display='none'">
                </div>
                
                <!-- Company Name -->
                <h1 class="company-name-print-header">{{ settings.company_name || 'QUALITY CARTONS (PVT) LTD.' }}</h1>
                
                <!-- Report Title -->
                <div class="report-title-wrapper my-3">
                    <h2 class="report-main-title">CARTAGE / FREIGHT RATE LIST</h2>
                </div>
                
                <!-- Meta Info Row -->
                <div class="d-flex justify-content-center gap-5 mt-2 text-black fw-bold">
                    <span>DATE: {{ formatDateForPrint(new Date()) }}</span>
                    <span class="text-uppercase">EFFECTIVE: {{ formatDateForPrint(activeLog.effective_date) }}</span>
                    <span class="text-uppercase">VEHICLE: {{ activeLog.vehicle_type }}</span>
                </div>
            </div>

            <!-- Summary Table (Only Groups) -->
            <table class="print-table-executive summary-table table table-bordered w-100 mb-5">
                <thead>
                    <tr>
                        <th style="width: 60px;" class="text-center py-3 fs-6 text-black">S.NO</th>
                        <th class="py-3 fs-6 text-black text-center">DESTINATION</th>
                        <th class="text-center py-3 fs-6 text-black" style="width: 140px;">EXISTING RATES</th>
                        <th class="text-center py-3 fs-6 text-black" style="width: 140px;">RATE INCREASED</th>
                        <th class="text-center py-3 fs-6 text-black" style="width: 150px;">NEW RATES (RS.)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(items, rate, index) in groupedDetails" :key="rate">
                        <td class="text-center fw-bold">{{ index + 1 }}</td>
                        <td class="fw-bold text-uppercase fs-6 text-center">{{ getGroupName(rate, items) }}</td>
                        <td class="text-center fs-6">Rs. {{ parseFloat(items[0].old_rate).toLocaleString() }}</td>
                        <td class="text-center fs-6">Rs. {{ (parseFloat(items[0].new_rate) - parseFloat(items[0].old_rate)).toLocaleString() }}</td>
                        <td class="text-center fw-800 fs-5">Rs. {{ parseFloat(rate).toLocaleString() }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Note Section -->
            <div class="print-notes mb-5 p-3 border rounded bg-light">
                <p class="mb-1 fw-bold text-muted small"><i class="bi bi-info-circle me-1"></i>NOTES & TERMS:</p>
                <ul class="mb-0 small text-muted ps-3">
                    <li>The above mentioned rates are inclusive of all taxes unless specified otherwise.</li>
                    <li>These rates are applicable for <strong>{{ activeLog.vehicle_type }}</strong> only.</li>
                </ul>
            </div>


            <div class="print-footer-signature mt-auto pt-5">
                <div class="d-flex justify-content-between text-center">
                    <div class="signature-block">
                        <div class="hand-signature-line"></div>
                        <p class="fw-bold text-black mb-0">Prepared By</p>
                        <p class="text-black small">Accounts Dept</p>
                    </div>
                    <div class="signature-block">
                        <div class="hand-signature-line"></div>
                        <p class="fw-bold text-black mb-0">Approved By</p>
                        <p class="text-black small">Manager Operations</p>
                    </div>
                    <div class="signature-block">
                        <div class="hand-signature-line"></div>
                        <p class="fw-bold text-black mb-0">Transportation</p>
                        <p class="fw-bold text-black mb-0">Approval</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const loading = ref(false);
const history = ref([]);
const dialogVisible = ref(false);
const activeLog = ref(null);
const settings = ref({});

const groupedDetails = computed(() => {
    if (!activeLog.value) return {};
    const groups = {};
    activeLog.value.details.forEach(item => {
        const rate = item.new_rate;
        if (!groups[rate]) groups[rate] = [];
        groups[rate].push(item);
    });
    return groups;
});

const getGroupName = (rate, items) => {
    // 1. Try to identify by address name from detail
    if (items && items.length > 0) {
        const addr = items[0].shipping_address?.address_name?.toUpperCase() || '';
        if (addr.includes('LOCAL')) return 'LOCAL / KORANGI';
        if (addr.includes('SITE')) return 'SITE AREA';
        if (addr.includes('MUSHARAF')) return 'MUSHARAF COLONY';
        if (addr.includes('SHANGRILA')) return 'SHANGRILA';
        if (addr.includes('ARCHROMA')) return 'ARCHROMA / SUGAR GODOWN';
        if (addr.includes('SUGAR')) return 'ARCHROMA / SUGAR GODOWN';
    }

    // 2. Try to identify by original known rates
    if (items && items.length > 0) {
        const oldR = parseFloat(items[0].old_rate);
        const knownOriginals = {
            3200: 'LOCAL / KORANGI',
            6000: 'SITE AREA / MUSHARAF COLONY',
            7500: 'SHANGRILA',
            5000: 'ARCHROMA / SUGAR GODOWN'
        };
        if (knownOriginals[oldR]) return knownOriginals[oldR];
    }

    return 'DESTINATION GROUP';
};

const fetchHistory = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/cartage-rates/increment-history');
        history.value = response.data;
    } finally {
        loading.value = false;
    }
};

const viewDetails = (log) => {
    activeLog.value = log;
    dialogVisible.value = true;
};

const printHistory = (log) => {
    activeLog.value = log;
    setTimeout(() => {
        window.print();
    }, 100);
};

const deleteHistory = (log) => {
    ElMessageBox.confirm(
        'Are you sure you want to delete this history record? This will not affect current rates but will remove the audit log.',
        'Warning',
        {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning',
        }
    ).then(async () => {
        try {
            await axios.delete(`/api/cartage-rates/increment-history/${log.id}`);
            ElMessage.success('History record deleted');
            fetchHistory();
        } catch (error) {
            ElMessage.error('Failed to delete record');
        }
    });
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatDateForPrint = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB'); // dd/mm/yyyy
};

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/setup/settings');
        const settingsMap = {};
        response.data.forEach(s => settingsMap[s.key] = s.value);
        settings.value = settingsMap;
    } catch (e) {}
};

onMounted(() => {
    fetchHistory();
    fetchSettings();
});
</script>

<style scoped>
.cartage-history-page {
    padding: 30px;
    background-color: #f1f5f9;
    min-height: calc(100vh - 120px);
}
.professional-card {
    border: none;
    border-radius: 16px;
}
.fw-800 { font-weight: 800; }
.print-table {
    border-collapse: collapse !important;
    font-size: 11px;
}

.print-table th, .print-table td {
    padding: 6px 10px !important;
    border: 1px solid #cbd5e1 !important;
}

.print-table th {
    background-color: #f1f5f9 !important;
    color: #475569 !important;
    text-transform: uppercase;
    font-size: 10px;
}

.rate-group-container {
    page-break-inside: avoid;
}

.print-header-layout {
    width: 100%;
}

.border-bottom-black {
    border-bottom: 2px solid black !important;
}

.print-logo {
    max-height: 90px;
    width: auto;
}

.print-header-section {
    border-bottom: 3px solid black !important;
}

.company-name-print-header {
    color: black !important;
    font-size: 38px !important;
    font-weight: 900 !important;
    margin: 0;
    font-family: 'Inter', sans-serif;
    line-height: 1.2;
}

.report-main-title {
    color: black !important;
    font-size: 26px !important;
    font-weight: 800 !important;
    border-top: 2px solid black;
    border-bottom: 2px solid black;
    display: inline-block;
    padding: 10px 40px;
    margin: 0;
}

.print-logo-centered {
    max-height: 90px;
    width: auto;
    display: block;
    margin: 0 auto;
}

.fs-5 { font-size: 1.25rem !important; }

.print-table-executive {
    border: 3px solid black !important;
}

.print-table-executive th {
    background-color: #f8f9fa !important;
    color: black !important;
    border: 2px solid black !important;
    font-weight: bold !important;
    text-transform: uppercase;
}

.print-table-executive td {
    border: 1px solid black !important;
    color: black !important;
    padding: 12px 15px !important;
    font-weight: 600;
}

.hand-signature-line {
    width: 240px;
    height: 2px;
    background: black !important;
    margin: 0 auto 10px;
    border: none;
}

.signature-block { width: 32%; }

.print-notes {
    border: 2px solid black !important;
    color: black !important;
}

[data-theme="dark"] .cartage-history-page {
    background-color: #0f172a !important;
}

[data-theme="dark"] .cartage-history-page .professional-card,
[data-theme="dark"] .cartage-history-page :deep(.el-card__header),
[data-theme="dark"] .cartage-history-page :deep(.el-card__body),
[data-theme="dark"] .cartage-history-page :deep(.el-dialog__body) {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .cartage-history-page .text-slate-800,
[data-theme="dark"] .cartage-history-page .text-muted {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .cartage-history-page :deep(.el-table th),
[data-theme="dark"] .cartage-history-page :deep(.el-table td) {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

@media print {
    /* Hide everything by default */
    body * {
        visibility: hidden;
    }
    
    /* Only show the print content */
    #print-content, #print-content * {
        visibility: visible;
    }
    
    #print-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: flex !important;
        flex-direction: column;
        min-height: 100vh;
        padding: 0; /* Let margins be handled by @page */
        background: white !important;
        z-index: 9999;
    }

    /* Force hide UI elements that might bleed through */
    .no-print, .el-table__header-wrapper, .el-table__body-wrapper, .el-card, .container-fluid {
        display: none !important;
    }

    @page {
        size: portrait;
        margin: 2cm;
    }
}
</style>
