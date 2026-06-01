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
                <el-tab-pane label="Current Inventory" name="inventory">
                    <div class="p-3">
                        <report-filter-row
                            :categories="categories"
                            :subcategories="inventorySubcategories"
                            :suppliers="suppliers"
                            :filters="inventoryParams"
                            show-stock
                            show-status
                            @category-change="onInventoryCategoryChange"
                            @run="fetchInventory"
                        />
                        <el-table :data="inventoryData" border stripe style="width: 100%" v-loading="loading">
                            <el-table-column prop="code" label="RM Code" width="120" />
                            <el-table-column prop="name" label="RM Name" min-width="190" />
                            <el-table-column prop="category" label="Category" min-width="160" />
                            <el-table-column prop="subcategory" label="Subcategory" min-width="150" />
                            <el-table-column prop="preferred_supplier" label="Supplier" min-width="150" />
                            <el-table-column prop="unit" label="Unit" width="80" align="center" />
                            <el-table-column prop="balance" label="In Stock" width="120" align="right">
                                <template #default="scope">
                                    <span :class="scope.row.stock_status !== 'in_stock' ? 'text-danger fw-bold' : 'fw-semibold'">
                                        {{ formatQty(scope.row.balance) }}
                                    </span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="reorder_level" label="Reorder" width="110" align="right" />
                            <el-table-column prop="cost_price" label="Rate" width="110" align="right">
                                <template #default="scope">Rs. {{ formatAmount(scope.row.cost_price) }}</template>
                            </el-table-column>
                            <el-table-column prop="valuation" label="Valuation" width="140" align="right">
                                <template #default="scope">
                                    <span class="fw-bold">Rs. {{ formatAmount(scope.row.valuation) }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="stock_status" label="Stock Status" width="140" align="center">
                                <template #default="scope">
                                    <el-tag :type="stockStatusTag(scope.row.stock_status)" size="small">{{ stockStatusLabel(scope.row.stock_status) }}</el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column prop="status" label="Status" width="100" align="center">
                                <template #default="scope">
                                    <el-tag :type="scope.row.status === 'Active' ? 'success' : 'info'" size="small">{{ scope.row.status }}</el-tag>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </el-tab-pane>

                <el-tab-pane label="Stock Ledger" name="ledger">
                    <div class="p-3">
                        <el-row :gutter="20" class="mb-4 align-items-end">
                            <el-col :span="8">
                                <label class="small fw-bold text-muted mb-1 d-block">Select Item</label>
                                <el-select v-model="ledgerParams.rm_item_id" placeholder="Select Item" class="w-100" filterable>
                                    <el-option v-for="i in rmItems" :key="i.id" :label="`${i.code} - ${i.name}`" :value="i.id" />
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

                <el-tab-pane label="Receiving Report" name="receiving">
                    <div class="p-3">
                        <report-filter-row
                            :categories="categories"
                            :subcategories="receivingSubcategories"
                            :suppliers="suppliers"
                            :filters="receivingParams"
                            show-date
                            show-receipt-supplier
                            @category-change="onReceivingCategoryChange"
                            @run="fetchReceiving"
                        />
                        <el-table :data="receivingData" border stripe v-loading="loading">
                            <el-table-column prop="receipt.grn_no" label="GRN #" width="120" />
                            <el-table-column prop="receipt.date" label="Date" width="110">
                                <template #default="scope">{{ formatDate(scope.row.receipt?.date) }}</template>
                            </el-table-column>
                            <el-table-column prop="receipt.supplier.name" label="Supplier" min-width="160" />
                            <el-table-column prop="item.category.name" label="Category" min-width="150" />
                            <el-table-column prop="item.subcategory.name" label="Subcategory" min-width="140" />
                            <el-table-column prop="item.name" label="Material" min-width="180" />
                            <el-table-column prop="quantity" label="Qty Received" width="130" align="right">
                                <template #default="scope">{{ formatQty(scope.row.quantity) }} {{ scope.row.unit }}</template>
                            </el-table-column>
                            <el-table-column prop="rate" label="Rate" width="110" align="right" />
                            <el-table-column prop="total_amount" label="Total Rs." width="130" align="right">
                                <template #default="scope">{{ formatAmount(scope.row.total_amount) }}</template>
                            </el-table-column>
                        </el-table>
                    </div>
                </el-tab-pane>

                <el-tab-pane label="Consumption Report" name="consumption">
                    <div class="p-3">
                        <report-filter-row
                            :categories="categories"
                            :subcategories="consumptionSubcategories"
                            :suppliers="suppliers"
                            :filters="consumptionParams"
                            show-date
                            @category-change="onConsumptionCategoryChange"
                            @run="fetchConsumption"
                        />
                        <el-table :data="consumptionData" border stripe v-loading="loading">
                            <el-table-column prop="consumption.voucher_no" label="Voucher #" width="120" />
                            <el-table-column prop="consumption.date" label="Date" width="110">
                                <template #default="scope">{{ formatDate(scope.row.consumption?.date) }}</template>
                            </el-table-column>
                            <el-table-column prop="consumption.department" label="Department" width="130" />
                            <el-table-column prop="item.category.name" label="Category" min-width="150" />
                            <el-table-column prop="item.subcategory.name" label="Subcategory" min-width="140" />
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

                <el-tab-pane label="Consumption Analysis" name="analysis">
                    <div class="p-3">
                        <report-filter-row
                            :categories="categories"
                            :subcategories="analysisSubcategories"
                            :suppliers="suppliers"
                            :filters="analysisParams"
                            show-date
                            @category-change="onAnalysisCategoryChange"
                            @run="fetchConsumptionAnalysis"
                        />
                        <el-table :data="analysisData" border stripe v-loading="loading">
                            <el-table-column prop="category" label="Category" min-width="180" />
                            <el-table-column prop="subcategory" label="Subcategory" min-width="180" />
                            <el-table-column prop="consumed_qty" label="Consumed Qty" width="150" align="right">
                                <template #default="scope">{{ formatQty(scope.row.consumed_qty) }}</template>
                            </el-table-column>
                            <el-table-column prop="consumed_value" label="Consumed Value" width="160" align="right">
                                <template #default="scope">Rs. {{ formatAmount(scope.row.consumed_value) }}</template>
                            </el-table-column>
                        </el-table>
                    </div>
                </el-tab-pane>

                <el-tab-pane label="Reorder Requirement" name="reorder">
                    <div class="p-3">
                        <report-filter-row
                            :categories="categories"
                            :subcategories="reorderSubcategories"
                            :suppliers="suppliers"
                            :filters="reorderParams"
                            show-status
                            @category-change="onReorderCategoryChange"
                            @run="fetchReorder"
                        />
                        <el-table :data="reorderData" border stripe v-loading="loading">
                            <el-table-column prop="code" label="RM Code" width="120" />
                            <el-table-column prop="name" label="RM Name" min-width="200" />
                            <el-table-column prop="category" label="Category" min-width="160" />
                            <el-table-column prop="subcategory" label="Subcategory" min-width="150" />
                            <el-table-column prop="preferred_supplier" label="Supplier" min-width="150" />
                            <el-table-column prop="balance" label="Current Stock" width="130" align="right" />
                            <el-table-column prop="reorder_level" label="Reorder Level" width="130" align="right" />
                            <el-table-column prop="min_stock" label="Minimum Stock" width="130" align="right" />
                        </el-table>
                    </div>
                </el-tab-pane>

                <el-tab-pane label="Cost by Category" name="cost">
                    <div class="p-3">
                        <report-filter-row
                            :categories="categories"
                            :subcategories="costSubcategories"
                            :suppliers="suppliers"
                            :filters="costParams"
                            show-stock
                            show-status
                            @category-change="onCostCategoryChange"
                            @run="fetchCostByCategory"
                        />
                        <el-table :data="costByCategoryData" border stripe v-loading="loading">
                            <el-table-column prop="category" label="Category" min-width="220" />
                            <el-table-column prop="items_count" label="Items" width="110" align="center" />
                            <el-table-column prop="stock_qty" label="Stock Qty" width="140" align="right">
                                <template #default="scope">{{ formatQty(scope.row.stock_qty) }}</template>
                            </el-table-column>
                            <el-table-column prop="stock_value" label="Stock Value" width="170" align="right">
                                <template #default="scope"><span class="fw-bold">Rs. {{ formatAmount(scope.row.stock_value) }}</span></template>
                            </el-table-column>
                        </el-table>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, defineComponent, h, resolveDynamicComponent } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';
import * as XLSX from 'xlsx';

const activeTab = ref('inventory');
const loading = ref(false);
const rmItems = ref([]);
const categories = ref([]);
const suppliers = ref([]);

const inventoryData = ref([]);
const ledgerResult = ref(null);
const receivingData = ref([]);
const consumptionData = ref([]);
const analysisData = ref([]);
const reorderData = ref([]);
const costByCategoryData = ref([]);

const monthStart = () => new Date().toISOString().substr(0, 7) + '-01';
const today = () => new Date().toISOString().substr(0, 10);

const categoryFilters = () => ({
    rm_category_id: '',
    rm_subcategory_id: '',
    preferred_supplier_id: '',
    stock_status: '',
    status: ''
});

const inventoryParams = reactive(categoryFilters());
const reorderParams = reactive(categoryFilters());
const costParams = reactive(categoryFilters());

const ledgerParams = reactive({
    rm_item_id: null,
    date_range: [monthStart(), today()]
});

const receivingParams = reactive({
    ...categoryFilters(),
    supplier_id: '',
    date_range: [monthStart(), today()]
});

const consumptionParams = reactive({
    ...categoryFilters(),
    date_range: [monthStart(), today()]
});

const analysisParams = reactive({
    ...categoryFilters(),
    date_range: [monthStart(), today()]
});

const subcategoriesFor = (categoryId) => {
    const category = categories.value.find(item => Number(item.id) === Number(categoryId));
    return category?.subcategories || [];
};

const inventorySubcategories = computed(() => subcategoriesFor(inventoryParams.rm_category_id));
const receivingSubcategories = computed(() => subcategoriesFor(receivingParams.rm_category_id));
const consumptionSubcategories = computed(() => subcategoriesFor(consumptionParams.rm_category_id));
const analysisSubcategories = computed(() => subcategoriesFor(analysisParams.rm_category_id));
const reorderSubcategories = computed(() => subcategoriesFor(reorderParams.rm_category_id));
const costSubcategories = computed(() => subcategoriesFor(costParams.rm_category_id));

const buildParams = (params) => {
    const requestParams = { ...params };
    if (requestParams.date_range) {
        requestParams.date_from = requestParams.date_range[0];
        requestParams.date_to = requestParams.date_range[1];
        delete requestParams.date_range;
    }
    Object.keys(requestParams).forEach(key => {
        if (requestParams[key] === '' || requestParams[key] === null || requestParams[key] === undefined) {
            delete requestParams[key];
        }
    });
    return requestParams;
};

const fetchInventory = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-reports/inventory', { params: buildParams(inventoryParams) });
        inventoryData.value = res.data;
    } catch (error) {
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load inventory report' : 'Failed to load inventory report');
        inventoryData.value = [];
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
    } catch (error) {
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load stock ledger' : 'Failed to load stock ledger');
        ledgerResult.value = null;
    } finally {
        loading.value = false;
    }
};

const fetchReceiving = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-reports/receiving', { params: buildParams(receivingParams) });
        receivingData.value = res.data;
    } catch (error) {
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load receiving report' : 'Failed to load receiving report');
        receivingData.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchConsumption = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-reports/consumption', { params: buildParams(consumptionParams) });
        consumptionData.value = res.data;
    } catch (error) {
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load consumption report' : 'Failed to load consumption report');
        consumptionData.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchConsumptionAnalysis = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-reports/consumption-analysis', { params: buildParams(analysisParams) });
        analysisData.value = res.data;
    } catch (error) {
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load consumption analysis' : 'Failed to load consumption analysis');
        analysisData.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchReorder = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-reports/reorder-requirement', { params: buildParams(reorderParams) });
        reorderData.value = res.data;
    } catch (error) {
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load reorder report' : 'Failed to load reorder report');
        reorderData.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchCostByCategory = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-reports/cost-by-category', { params: buildParams(costParams) });
        costByCategoryData.value = res.data;
    } catch (error) {
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load category cost report' : 'Failed to load category cost report');
        costByCategoryData.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchRmItems = async () => {
    try {
        const res = await axios.get('/api/rm-items');
        rmItems.value = res.data;
    } catch (error) {
        rmItems.value = [];
    }
};

const fetchLookups = async () => {
    try {
        const [categoryRes, supplierRes] = await Promise.all([
            axios.get('/api/rm-categories', { params: { status: 'Active' } }),
            axios.get('/api/suppliers')
        ]);
        categories.value = categoryRes.data;
        suppliers.value = Array.isArray(supplierRes.data) ? supplierRes.data : (supplierRes.data.data || []);
    } catch (error) {
        categories.value = [];
        suppliers.value = [];
        ElMessage.error(error.response?.status === 401 ? 'Please login again to load RM report filters' : 'Failed to load RM report filters');
    }
};

const resetSubcategory = (params) => {
    params.rm_subcategory_id = '';
};

const onInventoryCategoryChange = () => { resetSubcategory(inventoryParams); fetchInventory(); };
const onReceivingCategoryChange = () => { resetSubcategory(receivingParams); fetchReceiving(); };
const onConsumptionCategoryChange = () => { resetSubcategory(consumptionParams); fetchConsumption(); };
const onAnalysisCategoryChange = () => { resetSubcategory(analysisParams); fetchConsumptionAnalysis(); };
const onReorderCategoryChange = () => { resetSubcategory(reorderParams); fetchReorder(); };
const onCostCategoryChange = () => { resetSubcategory(costParams); fetchCostByCategory(); };

const getLedgerTypeTag = (type) => {
    const map = {
        receipt: 'success',
        consumption: 'danger',
        opening: 'info',
        adjustment: 'warning'
    };
    return map[type] || 'info';
};

const stockStatusLabel = (status) => ({
    in_stock: 'In Stock',
    reorder_required: 'Reorder Required',
    out_of_stock: 'Out of Stock'
}[status] || '-');

const stockStatusTag = (status) => ({
    in_stock: 'success',
    reorder_required: 'warning',
    out_of_stock: 'danger'
}[status] || 'info');

const exportToExcel = () => {
    let data = [];
    let filename = '';

    if (activeTab.value === 'inventory') {
        data = inventoryData.value;
        filename = 'RM_Current_Inventory.xlsx';
    } else if (activeTab.value === 'receiving') {
        data = receivingData.value.map(r => ({
            'GRN #': r.receipt?.grn_no,
            Date: r.receipt?.date,
            Supplier: r.receipt?.supplier?.name,
            Category: r.item?.category?.name,
            Subcategory: r.item?.subcategory?.name,
            Item: r.item?.name,
            Qty: r.quantity,
            Unit: r.unit,
            Rate: r.rate,
            Total: r.total_amount
        }));
        filename = 'RM_Receiving_Report.xlsx';
    } else if (activeTab.value === 'consumption') {
        data = consumptionData.value.map(c => ({
            'Voucher #': c.consumption?.voucher_no,
            Date: c.consumption?.date,
            Department: c.consumption?.department,
            Category: c.item?.category?.name,
            Subcategory: c.item?.subcategory?.name,
            Item: c.item?.name,
            Qty: c.quantity,
            'Issued To': c.consumption?.issued_to
        }));
        filename = 'RM_Consumption_Report.xlsx';
    } else if (activeTab.value === 'analysis') {
        data = analysisData.value;
        filename = 'RM_Consumption_Analysis.xlsx';
    } else if (activeTab.value === 'reorder') {
        data = reorderData.value;
        filename = 'RM_Reorder_Requirement.xlsx';
    } else if (activeTab.value === 'cost') {
        data = costByCategoryData.value;
        filename = 'RM_Cost_By_Category.xlsx';
    }

    if (data.length === 0) return ElMessage.warning('No data to export');

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(wb, filename);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
};

const formatAmount = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatQty = (val) => Number(val || 0).toLocaleString();

const ReportFilterRow = defineComponent({
    props: {
        categories: { type: Array, default: () => [] },
        subcategories: { type: Array, default: () => [] },
        suppliers: { type: Array, default: () => [] },
        filters: { type: Object, required: true },
        showDate: { type: Boolean, default: false },
        showStock: { type: Boolean, default: false },
        showStatus: { type: Boolean, default: false },
        showReceiptSupplier: { type: Boolean, default: false }
    },
    emits: ['category-change', 'run'],
    setup(props, { emit }) {
        return () => h('div', { class: 'report-filter-grid mb-4' }, [
            h('div', [
                h('label', { class: 'small fw-bold text-muted mb-1 d-block' }, 'Category'),
                h('select', {
                    class: 'form-control form-control-sm',
                    value: props.filters.rm_category_id,
                    onChange: (event) => {
                        props.filters.rm_category_id = event.target.value;
                        emit('category-change');
                    }
                }, [
                    h('option', { value: '' }, 'All Categories'),
                    ...props.categories.map(category => h('option', { value: category.id }, category.name))
                ])
            ]),
            h('div', [
                h('label', { class: 'small fw-bold text-muted mb-1 d-block' }, 'Subcategory'),
                h('select', {
                    class: 'form-control form-control-sm',
                    value: props.filters.rm_subcategory_id,
                    disabled: !props.filters.rm_category_id,
                    onChange: (event) => {
                        props.filters.rm_subcategory_id = event.target.value;
                        emit('run');
                    }
                }, [
                    h('option', { value: '' }, 'All Subcategories'),
                    ...props.subcategories.map(subcategory => h('option', { value: subcategory.id }, subcategory.name))
                ])
            ]),
            props.showReceiptSupplier
                ? h('div', [
                    h('label', { class: 'small fw-bold text-muted mb-1 d-block' }, 'Receipt Supplier'),
                    h('select', {
                        class: 'form-control form-control-sm',
                        value: props.filters.supplier_id,
                        onChange: (event) => {
                            props.filters.supplier_id = event.target.value;
                            emit('run');
                        }
                    }, [
                        h('option', { value: '' }, 'All Suppliers'),
                        ...props.suppliers.map(supplier => h('option', { value: supplier.id }, supplier.name))
                    ])
                ])
                : h('div', [
                    h('label', { class: 'small fw-bold text-muted mb-1 d-block' }, 'Preferred Supplier'),
                    h('select', {
                        class: 'form-control form-control-sm',
                        value: props.filters.preferred_supplier_id,
                        onChange: (event) => {
                            props.filters.preferred_supplier_id = event.target.value;
                            emit('run');
                        }
                    }, [
                        h('option', { value: '' }, 'All Suppliers'),
                        ...props.suppliers.map(supplier => h('option', { value: supplier.id }, supplier.name))
                    ])
                ]),
            props.showStock ? h('div', [
                h('label', { class: 'small fw-bold text-muted mb-1 d-block' }, 'Stock Status'),
                h('select', {
                    class: 'form-control form-control-sm',
                    value: props.filters.stock_status,
                    onChange: (event) => {
                        props.filters.stock_status = event.target.value;
                        emit('run');
                    }
                }, [
                    h('option', { value: '' }, 'All Stock'),
                    h('option', { value: 'in_stock' }, 'In Stock'),
                    h('option', { value: 'reorder_required' }, 'Reorder Required'),
                    h('option', { value: 'out_of_stock' }, 'Out of Stock')
                ])
            ]) : null,
            props.showStatus ? h('div', [
                h('label', { class: 'small fw-bold text-muted mb-1 d-block' }, 'Status'),
                h('select', {
                    class: 'form-control form-control-sm',
                    value: props.filters.status,
                    onChange: (event) => {
                        props.filters.status = event.target.value;
                        emit('run');
                    }
                }, [
                    h('option', { value: '' }, 'All'),
                    h('option', { value: 'Active' }, 'Active'),
                    h('option', { value: 'Inactive' }, 'Inactive')
                ])
            ]) : null,
            props.showDate ? h('div', { class: 'date-filter' }, [
                h('label', { class: 'small fw-bold text-muted mb-1 d-block' }, 'Date Range'),
                h(resolveDynamicComponent('el-date-picker'), {
                    modelValue: props.filters.date_range,
                    'onUpdate:modelValue': value => { props.filters.date_range = value; },
                    type: 'daterange',
                    rangeSeparator: 'To',
                    startPlaceholder: 'Start date',
                    endPlaceholder: 'End date',
                    format: 'DD/MM/YYYY',
                    valueFormat: 'YYYY-MM-DD',
                    class: 'w-100',
                    onChange: () => emit('run')
                })
            ]) : null,
            h('div', { class: 'd-flex align-items-end' }, [
                h('button', { class: 'btn btn-primary btn-sm w-100', onClick: () => emit('run') }, 'Apply')
            ])
        ].filter(Boolean));
    }
});

watch(activeTab, (tab) => {
    if (tab === 'inventory') fetchInventory();
    if (tab === 'receiving') fetchReceiving();
    if (tab === 'consumption') fetchConsumption();
    if (tab === 'analysis') fetchConsumptionAnalysis();
    if (tab === 'reorder') fetchReorder();
    if (tab === 'cost') fetchCostByCategory();
});

onMounted(async () => {
    await Promise.allSettled([fetchLookups(), fetchRmItems()]);
    fetchInventory();
});
</script>

<style scoped>
.report-tabs :deep(.el-tabs__item) {
    font-weight: 600;
}
.opening-balance-box {
    border: 1px solid #e2e8f0;
}
.report-filter-grid {
    align-items: end;
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(6, minmax(0, 1fr));
}
.date-filter {
    grid-column: span 2;
}
@media (max-width: 1200px) {
    .report-filter-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .report-filter-grid {
        grid-template-columns: 1fr;
    }
    .date-filter {
        grid-column: span 1;
    }
}
</style>
