<template>
    <div class="rm-reports">
        <el-card class="box-card shadow-sm">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i> Raw Material Inventory Reports
                    </span>
                    <el-button type="info" @click="exportToExcel" plain>
                        <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
                    </el-button>
                </div>
            </template>

            <el-tabs v-model="activeTab" class="report-tabs">
                <!-- Current Inventory Report -->
                <el-tab-pane label="Current Inventory" name="inventory">
                    <div class="p-3">
                        <el-table :data="inventoryData" border stripe style="width: 100%" v-loading="loading">
                            <el-table-column prop="code" label="Item Code" width="120" />
                            <el-table-column prop="name" label="Item Name" min-width="200" />
                            <el-table-column prop="unit" label="Unit" width="80" align="center" />
                            <el-table-column prop="balance" label="In Stock" width="120" align="right">
                                <template #default="scope">
                                    <span :class="scope.row.balance < scope.row.min_stock ? 'text-danger fw-bold' : ''">
                                        {{ formatQty(scope.row.balance) }}
                                    </span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="cost_price" label="Rate" width="110" align="right">
                                <template #default="scope">Rs. {{ formatAmount(scope.row.cost_price) }}</template>
                            </el-table-column>
                            <el-table-column prop="valuation" label="Valuation" width="140" align="right">
                                <template #default="scope">
                                    <span class="fw-bold">Rs. {{ formatAmount(scope.row.valuation) }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="min_stock" label="Min Level" width="110" align="right" />
                            <el-table-column prop="status" label="Status" width="100" align="center">
                                <template #default="scope">
                                    <el-tag :type="scope.row.status === 'Active' ? 'success' : 'info'" size="small">{{ scope.row.status }}</el-tag>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </el-tab-pane>

                <!-- Stock Ledger Report -->
                <el-tab-pane label="Stock Ledger" name="ledger">
                    <div class="p-3">
                        <el-row :gutter="20" class="mb-4 align-items-end">
                            <el-col :span="8">
                                <label class="small fw-bold text-muted mb-1 d-block">Select Item</label>
                                <el-select v-model="ledgerParams.rm_item_id" placeholder="Select Item" class="w-100" filterable>
                                    <el-option v-for="i in rmItems" :key="i.id" :label="i.name" :value="i.id" />
                                </el-select>
                            </el-col>
                            <el-col :span="10">
                                <label class="small fw-bold text-muted mb-1 d-block">Date Range</label>
                                <el-date-picker
                                    v-model="ledgerParams.date_range"
                                    type="daterange"
                                    range-separator="To"
                                    start-placeholder="Start date"
                                    end-placeholder="End date"
                                    format="DD/MM/YYYY"
                                    value-format="YYYY-MM-DD"
                                    class="w-100"
                                />
                            </el-col>
                            <el-col :span="4">
                                <el-button type="primary" @click="fetchLedger" class="w-100">View Ledger</el-button>
                            </el-col>
                        </el-row>

                        <div v-if="ledgerResult" class="ledger-content">
                            <div class="opening-balance-box p-2 bg-light border rounded mb-3 d-flex justify-content-between">
                                <span class="fw-bold">Opening Balance:</span>
                                <span class="fw-bold">{{ formatQty(ledgerResult.opening_balance) }}</span>
                            </div>
                            
                            <el-table :data="ledgerResult.ledger" border stripe size="small">
                                <el-table-column prop="transaction_date" label="Date" width="120">
                                    <template #default="scope">{{ formatDate(scope.row.transaction_date) }}</template>
                                </el-table-column>
                                <el-table-column prop="transaction_type" label="Type" width="120">
                                    <template #default="scope">
                                        <el-tag :type="getLedgerTypeTag(scope.row.transaction_type)" size="small" effect="plain" class="text-uppercase">
                                            {{ scope.row.transaction_type }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="reference_id" label="Ref #" width="100" />
                                <el-table-column prop="quantity_in" label="Qty In" width="120" align="right" class-name="text-success fw-bold">
                                    <template #default="scope">{{ scope.row.quantity_in > 0 ? formatQty(scope.row.quantity_in) : '-' }}</template>
                                </el-table-column>
                                <el-table-column prop="quantity_out" label="Qty Out" width="120" align="right" class-name="text-danger fw-bold">
                                    <template #default="scope">{{ scope.row.quantity_out > 0 ? formatQty(scope.row.quantity_out) : '-' }}</template>
                                </el-table-column>
                                <el-table-column prop="balance_after" label="Balance" width="140" align="right" class-name="fw-bold bg-light" />
                            </el-table>
                        </div>
                    </div>
                </el-tab-pane>

                <!-- Receiving Report -->
                <el-tab-pane label="Receiving Report" name="receiving">
                    <div class="p-3">
                        <el-row :gutter="20" class="mb-4">
                            <el-col :span="12">
                                <el-date-picker
                                    v-model="receivingParams.date_range"
                                    type="daterange"
                                    range-separator="To"
                                    start-placeholder="Start date"
                                    end-placeholder="End date"
                                    format="DD/MM/YYYY"
                                    value-format="YYYY-MM-DD"
                                    class="w-100"
                                    @change="fetchReceiving"
                                />
                            </el-col>
                        </el-row>
                        <el-table :data="receivingData" border stripe v-loading="loading">
                            <el-table-column prop="receipt.grn_no" label="GRN #" width="120" />
                            <el-table-column prop="receipt.date" label="Date" width="110">
                                <template #default="scope">{{ formatDate(scope.row.receipt?.date) }}</template>
                            </el-table-column>
                            <el-table-column prop="receipt.supplier.name" label="Supplier" min-width="180" />
                            <el-table-column prop="item.name" label="Material" min-width="180" />
                            <el-table-column prop="quantity" label="Qty Received" width="130" align="right">
                                <template #default="scope">{{ formatQty(scope.row.quantity) }} {{ scope.row.unit }}</template>
                            </el-table-column>
                            <el-table-column prop="rate" label="Rate" width="110" align="right" />
                            <el-table-column prop="total_amount" label="Total Rs." width="130" align="right">
                                <template #default="scope" class="fw-bold">{{ formatAmount(scope.row.total_amount) }}</template>
                            </el-table-column>
                        </el-table>
                    </div>
                </el-tab-pane>

                <!-- Consumption Report -->
                <el-tab-pane label="Consumption Report" name="consumption">
                    <div class="p-3">
                        <el-row :gutter="20" class="mb-4">
                            <el-col :span="12">
                                <el-date-picker
                                    v-model="consumptionParams.date_range"
                                    type="daterange"
                                    range-separator="To"
                                    start-placeholder="Start date"
                                    end-placeholder="End date"
                                    format="DD/MM/YYYY"
                                    value-format="YYYY-MM-DD"
                                    class="w-100"
                                    @change="fetchConsumption"
                                />
                            </el-col>
                        </el-row>
                        <el-table :data="consumptionData" border stripe v-loading="loading">
                            <el-table-column prop="consumption.voucher_no" label="Voucher #" width="120" />
                            <el-table-column prop="consumption.date" label="Date" width="110">
                                <template #default="scope">{{ formatDate(scope.row.consumption?.date) }}</template>
                            </el-table-column>
                            <el-table-column prop="consumption.department" label="Department" width="130" />
                            <el-table-column prop="item.name" label="Material" min-width="180" />
                            <el-table-column prop="quantity" label="Qty Consumed" width="130" align="right">
                                <template #default="scope">
                                    <span class="text-danger fw-bold">{{ formatQty(scope.row.quantity) }} {{ scope.row.item?.unit_type }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="consumption.issued_to" label="Issued To" width="150" />
                        </el-table>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';
import * as XLSX from 'xlsx';

const activeTab = ref('inventory');
const loading = ref(false);
const rmItems = ref([]);

const inventoryData = ref([]);
const ledgerResult = ref(null);
const receivingData = ref([]);
const consumptionData = ref([]);

const ledgerParams = reactive({
    rm_item_id: null,
    date_range: [new Date().toISOString().substr(0, 7) + '-01', new Date().toISOString().substr(0, 10)]
});

const receivingParams = reactive({
    date_range: [new Date().toISOString().substr(0, 7) + '-01', new Date().toISOString().substr(0, 10)]
});

const consumptionParams = reactive({
    date_range: [new Date().toISOString().substr(0, 7) + '-01', new Date().toISOString().substr(0, 10)]
});

const fetchInventory = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-reports/inventory');
        inventoryData.value = res.data;
    } finally {
        loading.value = false;
    }
};

const fetchLedger = async () => {
    if (!ledgerParams.rm_item_id || !ledgerParams.date_range) {
        ElMessage.warning('Please select item and date range');
        return;
    }
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-reports/ledger', {
            params: {
                rm_item_id: ledgerParams.rm_item_id,
                date_from: ledgerParams.date_range[0],
                date_to: ledgerParams.date_range[1]
            }
        });
        ledgerResult.value = res.data;
    } finally {
        loading.value = false;
    }
};

const fetchReceiving = async () => {
    loading.value = true;
    try {
        const params = {};
        if (receivingParams.date_range) {
            params.date_from = receivingParams.date_range[0];
            params.date_to = receivingParams.date_range[1];
        }
        const res = await axios.get('/api/rm-reports/receiving', { params });
        receivingData.value = res.data;
    } finally {
        loading.value = false;
    }
};

const fetchConsumption = async () => {
    loading.value = true;
    try {
        const params = {};
        if (consumptionParams.date_range) {
            params.date_from = consumptionParams.date_range[0];
            params.date_to = consumptionParams.date_range[1];
        }
        const res = await axios.get('/api/rm-reports/consumption', { params });
        consumptionData.value = res.data;
    } finally {
        loading.value = false;
    }
};

const fetchRmItems = async () => {
    const res = await axios.get('/api/rm-items');
    rmItems.value = res.data;
};

const getLedgerTypeTag = (type) => {
    const map = {
        'receipt': 'success',
        'consumption': 'danger',
        'opening': 'info',
        'adjustment': 'warning'
    };
    return map[type] || 'info';
};

const exportToExcel = () => {
    let data = [];
    let filename = '';

    if (activeTab.value === 'inventory') {
        data = inventoryData.value;
        filename = 'RM_Current_Inventory.xlsx';
    } else if (activeTab.value === 'receiving') {
        data = receivingData.value.map(r => ({
            'GRN #': r.receipt?.grn_no,
            'Date': r.receipt?.date,
            'Supplier': r.receipt?.supplier?.name,
            'Item': r.item?.name,
            'Qty': r.quantity,
            'Unit': r.unit,
            'Rate': r.rate,
            'Total': r.total_amount
        }));
        filename = 'RM_Receiving_Report.xlsx';
    } else if (activeTab.value === 'consumption') {
        data = consumptionData.value.map(c => ({
            'Voucher #': c.consumption?.voucher_no,
            'Date': c.consumption?.date,
            'Department': c.consumption?.department,
            'Item': c.item?.name,
            'Qty': c.quantity,
            'Issued To': c.consumption?.issued_to
        }));
        filename = 'RM_Consumption_Report.xlsx';
    }

    if (data.length === 0) return ElMessage.warning('No data to export');

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Report");
    XLSX.writeFile(wb, filename);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
};

const formatAmount = (val) => Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatQty = (val) => Number(val).toLocaleString();

watch(activeTab, (tab) => {
    if (tab === 'inventory') fetchInventory();
    if (tab === 'receiving') fetchReceiving();
    if (tab === 'consumption') fetchConsumption();
});

onMounted(() => {
    fetchInventory();
    fetchRmItems();
});
</script>

<style scoped>
.report-tabs :deep(.el-tabs__item) { font-weight: 600; }
.opening-balance-box { border: 1px solid #e2e8f0; }
</style>
