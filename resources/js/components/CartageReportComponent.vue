<template>
    <div class="cartage-report">
        <el-card class="box-card shadow-lg professional-card mb-4">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-file-earmark-bar-graph-fill me-2 text-primary"></i>Cartage Performance Report</span>
                        <p class="text-muted mb-0 small">Analyze transport history and costs with multi-dimensional filters</p>
                    </div>
                    <div class="d-flex gap-2">
                    <div class="d-flex gap-2">
                        <el-button type="success" @click="exportToExcel" :disabled="!reportData.length">
                            <i class="bi bi-file-earmark-excel me-2"></i> Export Excel
                        </el-button>
                        <el-button type="danger" @click="exportToPDF" :disabled="!reportData.length">
                            <i class="bi bi-file-earmark-pdf me-2"></i> PDF
                        </el-button>
                        <el-button type="primary" @click="printReport" :disabled="!reportData.length">
                            <i class="bi bi-printer me-2"></i> Print Report
                        </el-button>
                    </div>
                    </div>
                </div>
            </template>

            <!-- Filters Section -->
            <div class="filter-section p-4 bg-light rounded border mb-4">
                <el-form label-position="top" :model="filters">
                    <div class="row w-100 g-3">
                        <div class="col-md-2">
                            <el-form-item label="From Date" class="w-100">
                                <el-date-picker
                                    v-model="filters.start_date"
                                    type="date"
                                    placeholder="Start Date"
                                    class="w-100"
                                    format="DD/MM/YYYY"
                                    value-format="YYYY-MM-DD"
                                />
                            </el-form-item>
                        </div>
                        <div class="col-md-2">
                            <el-form-item label="To Date" class="w-100">
                                <el-date-picker
                                    v-model="filters.end_date"
                                    type="date"
                                    placeholder="End Date"
                                    class="w-100"
                                    format="DD/MM/YYYY"
                                    value-format="YYYY-MM-DD"
                                />
                            </el-form-item>
                        </div>
                        <div class="col-md-2">
                            <el-form-item label="Customer" class="w-100">
                                <el-select v-model="filters.customer_id" placeholder="All Customers" clearable filterable class="w-100">
                                    <el-option v-for="c in filterOptions.customers" :key="c.id" :label="c.name" :value="c.id" />
                                </el-select>
                            </el-form-item>
                        </div>
                        <div class="col-md-2">
                            <el-form-item label="Transporter" class="w-100">
                                <el-select v-model="filters.transporter_id" placeholder="All Transporters" clearable filterable class="w-100">
                                    <el-option v-for="t in filterOptions.transporters" :key="t.id" :label="t.name" :value="t.id" />
                                </el-select>
                            </el-form-item>
                        </div>
                        <div class="col-md-2">
                            <el-form-item label="Vehicle" class="w-100">
                                <el-select v-model="filters.vehicle_number" placeholder="All Vehicles" clearable filterable class="w-100">
                                    <el-option v-for="v in filterOptions.vehicles" :key="v.id" :label="v.vehicle_number" :value="v.vehicle_number" />
                                </el-select>
                            </el-form-item>
                        </div>
                        <div class="col-md-2 d-flex align-items-end" style="padding-bottom: 18px;">
                            <el-button type="primary" @click="fetchReport" :loading="loading" class="w-100 shadow-sm">
                                <i class="bi bi-search me-2"></i> Generate
                            </el-button>
                        </div>
                    </div>
                </el-form>
            </div>

            <!-- Report Table -->
            <el-table 
                :data="reportData" 
                style="width: 100%" 
                v-loading="loading"
                class="modern-table shadow-sm"
                :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '12px', textTransform: 'uppercase'}"
                show-summary
                :summary-method="getSummaries"
            >
                <el-table-column prop="entry_date" label="Date" width="120" sortable>
                    <template #default="scope">{{ formatDate(scope.row.entry_date) }}</template>
                </el-table-column>
                <el-table-column prop="bill.id" label="Bill #" width="120">
                    <template #default="scope">
                        <el-tag size="small" effect="plain" class="fw-bold">{{ formatBillId(scope.row.bill?.id) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="customer.name" label="Customer" min-width="180" sortable />
                <el-table-column prop="shipping_address.address_name" label="Destination" min-width="180" />
                <el-table-column prop="bill.transporter.name" label="Transporter" min-width="150" />
                <el-table-column prop="vehicle_number" label="Vehicle #" width="130" sortable />
                <el-table-column prop="dc_number" label="DC #" width="100" />
                <el-table-column prop="amount" label="Amount (Rs.)" width="140" align="right" sortable>
                    <template #default="scope">
                        <span class="fw-800 text-slate-800">{{ scope.row.amount.toLocaleString() }}</span>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- Print-only area (hidden) -->
        <div id="report-print-area" v-if="printing" class="report-print-container">
            <div class="print-header text-center mb-4">
                <h2 class="fw-bold text-uppercase">Cartage Performance Report</h2>
                <p class="text-muted">Generated on {{ new Date().toLocaleString() }}</p>
                <div class="filter-summary border-bottom pb-2 mb-3">
                    <span v-if="filters.start_date" class="me-3">Period: <strong>{{ formatDate(filters.start_date) }} - {{ formatDate(filters.end_date) }}</strong></span>
                    <span v-if="filters.customer_id" class="me-3">Customer: <strong>{{ getFilterLabel('customers', filters.customer_id) }}</strong></span>
                    <span v-if="filters.transporter_id">Transporter: <strong>{{ getFilterLabel('transporters', filters.transporter_id) }}</strong></span>
                </div>
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bill #</th>
                        <th>Customer</th>
                        <th>Destination</th>
                        <th>Transporter</th>
                        <th>Vehicle #</th>
                        <th>DC #</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in reportData" :key="row.id">
                        <td>{{ formatDate(row.entry_date) }}</td>
                        <td>{{ formatBillId(row.bill?.id) }}</td>
                        <td>{{ row.customer?.name }}</td>
                        <td>{{ row.shipping_address?.address_name }}</td>
                        <td>{{ row.bill?.transporter?.name }}</td>
                        <td>{{ row.vehicle_number }}</td>
                        <td>{{ row.dc_number }}</td>
                        <td class="text-end fw-bold">{{ row.amount.toLocaleString() }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-end fw-bold">TOTAL AMOUNT (Rs.):</td>
                        <td class="text-end fw-bold fs-5">{{ totalAmount.toLocaleString() }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';
import * as XLSX from 'xlsx';

const loading = ref(false);
const printing = ref(false);
const reportData = ref([]);
const filterOptions = reactive({
    customers: [],
    transporters: [],
    vehicles: []
});

const now = new Date();
const year = now.getFullYear();
const month = String(now.getMonth() + 1).padStart(2, '0');
const day = String(now.getDate()).padStart(2, '0');

const filters = reactive({
    start_date: `${year}-${month}-01`,
    end_date: `${year}-${month}-${day}`,
    customer_id: null,
    transporter_id: null,
    vehicle_number: null
});

const totalAmount = computed(() => {
    return reportData.value.reduce((sum, item) => sum + parseFloat(item.amount), 0);
});

const fetchFilterOptions = async () => {
    try {
        const res = await axios.get('/api/reports/cartage/filters');
        filterOptions.customers = res.data.customers;
        filterOptions.transporters = res.data.transporters;
        filterOptions.vehicles = res.data.vehicles;
    } catch (e) {
        ElMessage.error('Failed to load filters');
    }
};

const fetchReport = async () => {
    loading.value = true;
    try {
        const params = {
            start_date: filters.start_date,
            end_date: filters.end_date,
            customer_id: filters.customer_id,
            transporter_id: filters.transporter_id,
            vehicle_number: filters.vehicle_number
        };
        const res = await axios.get('/api/reports/cartage', { params });
        reportData.value = res.data;
        if (reportData.value.length === 0) ElMessage.warning('No records found for these filters');
    } catch (e) {
        ElMessage.error('Error fetching report');
    } finally {
        loading.value = false;
    }
};

const formatBillId = (id) => {
    if (!id) return '';
    return 'CTG' + String(id).padStart(4, '0');
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB');
};

const getFilterLabel = (key, id) => {
    const item = filterOptions[key].find(i => i.id === id);
    return item ? item.name : 'All';
};

const getSummaries = (param) => {
    const { columns, data } = param;
    const sums = [];
    columns.forEach((column, index) => {
        if (index === 0) {
            sums[index] = `TOTAL (${data.length} Trips)`;
            return;
        }
        if (column.property === 'amount') {
            const values = data.map(item => Number(item[column.property]));
            const total = values.reduce((prev, curr) => {
                const value = Number(curr);
                if (!isNaN(value)) return prev + curr;
                else return prev;
            }, 0);
            sums[index] = 'Rs. ' + total.toLocaleString();
        } else {
            sums[index] = '';
        }
    });
    return sums;
};

const printReport = () => {
    printing.value = true;
    setTimeout(() => {
        window.print();
        printing.value = false;
    }, 500);
};

const exportToExcel = () => {
    const data = reportData.value.map(row => ({
        Date: formatDate(row.entry_date),
        'Bill #': formatBillId(row.bill?.id),
        Customer: row.customer?.name,
        Destination: row.shipping_address?.address_name,
        Transporter: row.bill?.transporter?.name,
        'Vehicle #': row.vehicle_number,
        'DC #': row.dc_number,
        'Amount (Rs.)': row.amount
    }));
    
    const worksheet = XLSX.utils.json_to_sheet(data);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Cartage_Report");
    XLSX.writeFile(workbook, `Cartage_Report_${filters.start_date}_to_${filters.end_date}.xlsx`);
    ElMessage.success('Report exported to Excel');
};

const exportToPDF = () => {
    printReport();
};

onMounted(() => {
    fetchFilterOptions();
});
</script>

<style scoped>
.cartage-report {
    padding: 30px;
    background-color: #f1f5f9;
    min-height: calc(100vh - 120px);
}
.professional-card {
    border: none;
    border-radius: 16px;
}
.fw-800 { font-weight: 800; }
.text-slate-800 { color: #1e293b; }

.filter-section {
    background: #ffffff !important;
}

:deep(.el-form-item__label) {
    font-weight: 700;
    color: #475569;
}

/* Report Table */
.modern-table :deep(.el-table__row) {
    transition: background-color 0.2s;
}
.modern-table :deep(.el-table__row:hover) {
    background-color: #f8fafc !important;
}
.modern-table :deep(td) {
    padding: 12px 0;
}

/* Summary Row Styling */
.modern-table :deep(.el-table__footer-wrapper tbody td) {
    background-color: #f8fafc !important; /* Professional Light Background */
    border-top: 2px solid #e2e8f0;
    border-bottom: 2px solid #e2e8f0;
}

.modern-table :deep(.el-table__footer-wrapper .cell) {
    color: #1e293b !important; /* Dark Slate for standard cells */
    font-weight: 800 !important;
    font-size: 15px !important;
    text-transform: uppercase;
    white-space: nowrap !important; /* Ensure single row */
}

.modern-table :deep(.el-table__footer-wrapper tr td:last-child .cell) {
    color: #000000 !important; /* BOLD BLACK as requested */
    font-size: 18px !important;
}

.modern-table :deep(.el-table__footer-wrapper tr td:first-child .cell) {
    color: #475569 !important; /* Medium slate for label */
    letter-spacing: 0.5px;
}

/* Date Picker adjustments for right-side icon */
:deep(.dark-date-picker .el-input__wrapper) {
    flex-direction: row-reverse;
}
:deep(.dark-date-picker .el-input__prefix) {
    margin-left: 8px;
    margin-right: 0;
}

/* Print Styles */
.report-print-container {
    padding: 20px;
    background: #fff;
    color: #000;
}
.report-table {
    width: 100%;
    border-collapse: collapse;
}
.report-table th, .report-table td {
    border: 1px solid #000;
    padding: 8px;
    font-size: 11px;
}
.report-table th {
    background: #f0f0f0;
    text-transform: uppercase;
}

/* Increase DatePicker Cell Size */
:deep(.el-picker-panel__content) {
    width: 320px !important;
}
:deep(.el-date-table td) {
    padding: 8px 0 !important;
}
:deep(.el-date-table td .el-date-table-cell) {
    height: 45px !important;
    line-height: 45px !important;
    width: 45px !important;
    font-size: 14px !important;
}
:deep(.el-date-picker__header) {
    margin: 15px 15px 10px !important;
}

@media print {
    .cartage-report > .professional-card { display: none !important; }
    .report-print-container { display: block !important; }
}
</style>
