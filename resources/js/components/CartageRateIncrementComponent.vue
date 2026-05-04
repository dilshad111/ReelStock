<template>
    <div class="cartage-increment-page">
        <el-card class="box-card shadow-lg professional-card mb-4 no-print" v-loading="loading">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Cartage Rate Increment</span>
                        <p class="text-muted mb-0 small">Bulk update cartage rates by classification</p>
                    </div>
                    <div class="d-flex gap-2 no-print">
                        <el-button type="info" @click="printRates" plain>
                            <i class="bi bi-printer me-1"></i> Print
                        </el-button>
                        <el-button type="success" @click="exportToExcel" plain>
                            <i class="bi bi-file-earmark-excel me-1"></i> Excel
                        </el-button>
                        <el-button type="danger" @click="exportToPDF" plain>
                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                        </el-button>
                        <el-button type="primary" @click="saveAll" :loading="saving" class="add-btn shadow-sm">
                            <i class="bi bi-save-fill me-2"></i> Save & Implement
                        </el-button>
                    </div>
                </div>
            </template>

            <!-- Header Filters / Configuration -->
            <div class="filters-section p-4 bg-light rounded border border-light-subtle mb-4">
                <div class="row g-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Classification</label>
                        <el-select v-model="filters.classification" placeholder="Select Classification" class="w-100" @change="fetchRatesForClassification" filterable>
                            <el-option v-for="item in classifications" :key="item" :label="item" :value="item" />
                        </el-select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Increment Type</label>
                        <div class="d-flex h-10 align-items-center">
                            <el-radio-group v-model="incrementType" @change="handleTypeChange">
                                <el-radio label="percentage">Percentage (%)</el-radio>
                                <el-radio label="fixed">Fixed Amount</el-radio>
                            </el-radio-group>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Effective Date</label>
                        <el-date-picker v-model="effectiveDate" type="date" placeholder="Implementation Date" class="w-100" format="YYYY-MM-DD" value-format="YYYY-MM-DD" />
                    </div>
                    <div class="col-md-2" v-if="incrementType === 'percentage'">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Bulk % Increase</label>
                        <el-input-number v-model="bulkValue" :precision="2" :step="0.1" @change="applyBulkValue" class="w-100" />
                    </div>
                    <div class="col-md-2" v-else>
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Bulk Amount Increase</label>
                        <el-input-number v-model="bulkValue" :precision="0" :step="100" @change="applyBulkValue" class="w-100" />
                    </div>
                    <div class="col-md-2">
                         <el-button type="warning" @click="resetIncrements" class="w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </el-button>
                    </div>
                </div>
            </div>

            <!-- Rates Table -->
            <el-table 
                :data="tableData" 
                style="width: 100%" 
                class="modern-table"
                :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '12px', textTransform: 'uppercase'}"
            >
                <el-table-column label="Shipping Address" min-width="250">
                    <template #default="scope">
                        <div class="fw-bold text-slate-800">{{ scope.row.customer_name }}</div>
                        <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ scope.row.address_name }}</div>
                    </template>
                </el-table-column>
                
                <el-table-column prop="existing_rate" label="Existing Cartage" width="150" align="right">
                    <template #default="scope">
                        <div class="fw-bold">Rs. {{ scope.row.existing_rate.toLocaleString() }}</div>
                    </template>
                </el-table-column>

                <el-table-column v-if="incrementType === 'percentage'" label="% Increase" width="150" align="center">
                    <template #default="scope">
                        <el-input-number v-model="scope.row.percentage" :precision="2" :step="0.1" size="small" @change="calculateFromPercentage(scope.row)" />
                    </template>
                </el-table-column>

                <el-table-column label="Amount Increase" width="180" align="center">
                    <template #default="scope">
                        <el-input-number v-model="scope.row.amount_increase" :precision="0" :step="50" size="small" @change="calculateFromAmount(scope.row)" />
                    </template>
                </el-table-column>

                <el-table-column label="New Cartage" width="160" align="right">
                    <template #default="scope">
                        <div class="fw-800 text-primary">Rs. {{ scope.row.new_rate.toLocaleString() }}</div>
                    </template>
                </el-table-column>

                <el-table-column label="Difference" width="140" align="right">
                    <template #default="scope">
                        <div :class="scope.row.amount_increase > 0 ? 'text-success fw-bold' : 'text-muted'">
                            +{{ scope.row.amount_increase.toLocaleString() }}
                        </div>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>


        <!-- Print Template (Hidden) -->
        <div id="print-content" class="print-container d-none">
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
                    <span class="text-uppercase">EFFECTIVE: {{ formatDateForPrint(effectiveDate || new Date()) }}</span>
                    <span class="text-uppercase">VEHICLE: {{ filters.classification }}</span>
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
                    <tr v-for="(items, rate, index) in groupedTableData" :key="rate">
                        <td class="text-center fw-bold">{{ index + 1 }}</td>
                        <td class="fw-bold text-uppercase fs-6 text-center">{{ getGroupName(rate, items) }}</td>
                        <td class="text-center fs-6">Rs. {{ parseFloat(items[0].existing_rate).toLocaleString() }}</td>
                        <td class="text-center fs-6">Rs. {{ parseFloat(items[0].amount_increase).toLocaleString() }}</td>
                        <td class="text-center fw-800 fs-5">Rs. {{ parseFloat(rate).toLocaleString() }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Note Section -->
            <div class="print-notes mb-5 p-3 border rounded bg-light">
                <p class="mb-1 fw-bold text-muted small"><i class="bi bi-info-circle me-1"></i>NOTES & TERMS:</p>
                <ul class="mb-0 small text-muted ps-3">
                    <li>The above mentioned rates are inclusive of all taxes unless specified otherwise.</li>
                    <li>These rates are applicable for <strong>{{ filters.classification }}</strong> only.</li>
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
import { ref, reactive, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import * as XLSX from 'xlsx';

const loading = ref(false);
const saving = ref(false);
const classifications = ref([]);
const filters = reactive({
    classification: '1x17 Container'
});
const incrementType = ref('percentage');
const effectiveDate = ref(new Date().toISOString().split('T')[0]);
const bulkValue = ref(0);
const tableData = ref([]);
const settings = ref({});

const groupedTableData = computed(() => {
    const groups = {};
    tableData.value.forEach(item => {
        const rate = item.new_rate;
        if (!groups[rate]) groups[rate] = [];
        groups[rate].push(item);
    });
    return groups;
});

const getGroupName = (rate, items) => {
    // 1. Try to identify by address name
    if (items && items.length > 0) {
        const addr = items[0].address_name?.toUpperCase() || '';
        if (addr.includes('LOCAL')) return 'LOCAL / KORANGI';
        if (addr.includes('SITE')) return 'SITE AREA';
        if (addr.includes('MUSHARAF')) return 'MUSHARAF COLONY';
        if (addr.includes('SHANGRILA')) return 'SHANGRILA';
        if (addr.includes('ARCHROMA')) return 'ARCHROMA / SUGAR GODOWN';
        if (addr.includes('SUGAR')) return 'ARCHROMA / SUGAR GODOWN';
    }

    // 2. Try to identify by original known rates
    if (items && items.length > 0) {
        const oldR = parseFloat(items[0].existing_rate);
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


const fetchClassifications = async () => {
    try {
        const response = await axios.get('/api/cartage-rates/classifications');
        classifications.value = response.data;
    } catch (error) {
        console.error('Error fetching classifications:', error);
    }
};

const fetchRatesForClassification = async () => {
    if (!filters.classification) return;
    loading.value = true;
    try {
        // We need all shipping addresses and their current rates for this classification
        // First get all shipping addresses
        const addrResponse = await axios.get('/api/customers');
        const addresses = [];
        addrResponse.data.forEach(customer => {
            customer.shipping_addresses.forEach(addr => {
                addresses.push({
                    shipping_address_id: addr.id,
                    address_name: addr.address_name,
                    customer_name: customer.name,
                    existing_rate: 0,
                    percentage: 0,
                    amount_increase: 0,
                    new_rate: 0
                });
            });
        });

        // Then get rates for this classification
        const ratesResponse = await axios.get('/api/cartage-rates');
        const rates = ratesResponse.data.filter(r => r.vehicle_type === filters.classification);

        // Map rates to addresses
        addresses.forEach(addr => {
            const rateObj = rates.find(r => r.shipping_address_id === addr.shipping_address_id);
            if (rateObj) {
                addr.existing_rate = parseFloat(rateObj.rate);
            }
            addr.new_rate = addr.existing_rate;
        });

        tableData.value = addresses.filter(addr => addr.existing_rate > 0);
        applyBulkValue(); // Re-apply current bulk value if any
    } catch (error) {
        ElMessage.error('Error loading data');
    } finally {
        loading.value = false;
    }
};

const handleTypeChange = () => {
    bulkValue.value = 0;
    resetIncrements();
};

const applyBulkValue = () => {
    tableData.value.forEach(row => {
        if (incrementType.value === 'percentage') {
            row.percentage = bulkValue.value;
            calculateFromPercentage(row);
        } else {
            row.amount_increase = bulkValue.value;
            calculateFromAmount(row);
        }
    });
};

const calculateFromPercentage = (row) => {
    row.amount_increase = Math.round((row.existing_rate * row.percentage) / 100);
    row.new_rate = row.existing_rate + row.amount_increase;
};

const calculateFromAmount = (row) => {
    row.new_rate = row.existing_rate + row.amount_increase;
    if (row.existing_rate > 0) {
        row.percentage = (row.amount_increase / row.existing_rate) * 100;
    } else {
        row.percentage = 0;
    }
};

const resetIncrements = () => {
    bulkValue.value = 0;
    tableData.value.forEach(row => {
        row.percentage = 0;
        row.amount_increase = 0;
        row.new_rate = row.existing_rate;
    });
};


const saveAll = async () => {
    if (!filters.classification) {
        ElMessage.warning('Please select a classification');
        return;
    }

    const payload = {
        vehicle_type: filters.classification,
        effective_date: effectiveDate.value,
        increment_type: incrementType.value,
        increment_value: bulkValue.value,
        rates: tableData.value.filter(r => r.amount_increase !== 0 || r.existing_rate > 0).map(r => ({
            shipping_address_id: r.shipping_address_id,
            old_rate: r.existing_rate,
            new_rate: r.new_rate
        }))
    };

    if (payload.rates.length === 0) {
        ElMessage.warning('No changes to save');
        return;
    }

    try {
        await ElMessageBox.confirm(`Implement these rate changes for ${filters.classification} effective from ${effectiveDate.value}?`, 'Confirm Implementation', {
            type: 'warning',
            confirmButtonText: 'Yes, Implement',
            cancelButtonText: 'Cancel'
        });

        saving.value = true;
        await axios.post('/api/cartage-rates/bulk-update', payload);
        ElMessage.success('Rates updated successfully');
        fetchRatesForClassification();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error('Failed to update rates');
        }
    } finally {
        saving.value = false;
    }
};

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/setup/settings');
        const settingsMap = {};
        response.data.forEach(s => settingsMap[s.key] = s.value);
        settings.value = settingsMap;
    } catch (error) {
        console.error('Error fetching settings:', error);
    }
};

const printRates = () => {
    window.print();
};

const exportToExcel = () => {
    const data = tableData.value.map(r => ({
        'Customer': r.customer_name,
        'Shipping Address': r.address_name,
        'Classification': filters.classification,
        'Old Rate': r.existing_rate,
        'Increment': r.amount_increase,
        'New Rate': r.new_rate,
        'Effective Date': effectiveDate.value
    }));
    
    const worksheet = XLSX.utils.json_to_sheet(data);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Rate_Increment");
    XLSX.writeFile(workbook, `Cartage_Increment_${filters.classification}_${effectiveDate.value}.xlsx`);
    ElMessage.success('Excel file exported');
};

const exportToPDF = () => {
    window.print();
};

onMounted(() => {
    fetchClassifications();
    fetchRatesForClassification();
    fetchSettings();
});
</script>

<style scoped>
.cartage-increment-page {
    padding: 30px;
    background-color: #f1f5f9;
    min-height: calc(100vh - 120px);
    font-family: 'Inter', -apple-system, sans-serif;
}

.professional-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}

.fw-800 { font-weight: 800; }
.text-slate-800 { color: #1e293b; }
.text-primary { color: #3b82f6; }

.modern-table :deep(.el-table__header) {
    border-bottom: 1px solid #e2e8f0;
}

.modern-table :deep(td) {
    padding: 12px 0;
}

.signature-line {
    width: 150px;
    height: 1px;
    background-color: #000;
}

.print-table {
    border-collapse: collapse !important;
    font-size: 11px;
}

.print-table th, .print-table td {
    padding: 6px 10px !important;
    border: 1px solid #cbd5e1 !important;
    vertical-align: middle;
}

.print-table th {
    background-color: #f1f5f9 !important;
    color: #475569 !important;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 0.05em;
}

.rate-group-container {
    page-break-inside: avoid;
}

.group-header {
    border-left: 5px solid #3b82f6;
    background: #1e293b !important;
    color: white !important;
    font-size: 13px;
    letter-spacing: 0.5px;
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
        padding: 0; 
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
