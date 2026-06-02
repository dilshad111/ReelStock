<template>
    <div class="fuel-cost-report cartage-report">
        <el-card class="box-card shadow-lg professional-card mb-4">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="header-title">
                        <span class="fs-4 fw-800 text-slate-800">
                            <i class="bi bi-fuel-pump-fill me-2 text-primary"></i>Fuel Cost & Profitability Report
                        </span>
                        <p class="text-muted mb-0 small">Vehicle mileage, fuel consumption, and trip profitability analysis</p>
                    </div>
                    <div class="d-flex gap-2">
                        <el-button type="success" @click="exportToExcel" :disabled="!reportData.length">
                            <i class="bi bi-file-earmark-excel me-2"></i> Export Excel
                        </el-button>
                        <el-button type="primary" @click="printReport" :disabled="!reportData.length">
                            <i class="bi bi-printer me-2"></i> Print / PDF
                        </el-button>
                    </div>
                </div>
            </template>

            <div class="filter-section p-4 bg-light rounded border mb-4">
                <el-form label-position="top" :model="filters">
                    <div class="row w-100 g-3">
                        <div class="col-xl-2 col-md-4">
                            <el-form-item label="From Date" class="w-100" :class="{ 'is-error': errors.start_date }">
                                <el-date-picker v-model="filters.start_date" type="date" placeholder="From Date" class="w-100" format="DD/MM/YYYY" value-format="YYYY-MM-DD" />
                                <div v-if="errors.start_date" class="validation-message">{{ errors.start_date }}</div>
                            </el-form-item>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <el-form-item label="To Date" class="w-100" :class="{ 'is-error': errors.end_date }">
                                <el-date-picker v-model="filters.end_date" type="date" placeholder="To Date" class="w-100" format="DD/MM/YYYY" value-format="YYYY-MM-DD" />
                                <div v-if="errors.end_date" class="validation-message">{{ errors.end_date }}</div>
                            </el-form-item>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <el-form-item label="Vehicle Number" class="w-100">
                                <el-select v-model="filters.vehicle_number" placeholder="All Vehicles" clearable filterable class="w-100">
                                    <el-option v-for="v in filterOptions.vehicles" :key="v.id" :label="formatVehicleOption(v)" :value="v.vehicle_number" />
                                </el-select>
                            </el-form-item>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <el-form-item label="Customer" class="w-100">
                                <el-select v-model="filters.customer_id" placeholder="All Customers" clearable filterable class="w-100" @change="filters.shipping_address_id = null">
                                    <el-option v-for="c in filterOptions.customers" :key="c.id" :label="c.name" :value="c.id" />
                                </el-select>
                            </el-form-item>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <el-form-item label="Location" class="w-100">
                                <el-select v-model="filters.shipping_address_id" placeholder="All Locations" clearable filterable class="w-100">
                                    <el-option v-for="l in filteredLocations" :key="l.id" :label="formatLocationOption(l)" :value="l.id" />
                                </el-select>
                            </el-form-item>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <el-form-item label="Fuel Rate (PKR/L)" class="w-100" :class="{ 'is-error': errors.fuel_rate }">
                                <el-input v-model.number="filters.fuel_rate" type="number" min="0.01" step="0.01" placeholder="e.g. 285" />
                                <div v-if="errors.fuel_rate" class="validation-message">{{ errors.fuel_rate }}</div>
                            </el-form-item>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <el-button class="btn-clear-filters" @click="clearFilters">
                                <i class="bi bi-x-circle me-2"></i> Clear
                            </el-button>
                            <el-button type="primary" @click="fetchReport" :loading="loading" class="shadow-sm generate-btn">
                                <i class="bi bi-search me-2"></i> Generate Report
                            </el-button>
                        </div>
                    </div>
                </el-form>
            </div>

            <el-table
                :data="reportData"
                style="width: 100%"
                v-loading="loading"
                class="modern-table shadow-sm"
                :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '12px', textTransform: 'uppercase'}"
                show-summary
                :summary-method="getSummaries"
            >
                <el-table-column prop="trip_date" label="Trip Date" width="115" sortable>
                    <template #default="scope">{{ formatDate(scope.row.trip_date) }}</template>
                </el-table-column>
                <el-table-column prop="vehicle_number" label="Vehicle #" width="125" sortable />
                <el-table-column label="Customer" min-width="170">
                    <template #default="scope">{{ scope.row.customer?.name || '-' }}</template>
                </el-table-column>
                <el-table-column label="Location" min-width="190">
                    <template #default="scope">{{ scope.row.shipping_address?.address_name || '-' }}</template>
                </el-table-column>
                <el-table-column prop="round_trip_distance_km" label="Distance (KM)" width="135" align="right">
                    <template #default="scope">{{ number(scope.row.round_trip_distance_km) }}</template>
                </el-table-column>
                <el-table-column prop="freight_amount" label="Freight (PKR)" width="140" align="right">
                    <template #default="scope"><span class="fw-800">{{ money(scope.row.freight_amount) }}</span></template>
                </el-table-column>
                <el-table-column prop="vehicle_mileage" label="Mileage (KM/L)" width="140" align="right">
                    <template #default="scope">{{ scope.row.vehicle_mileage > 0 ? number(scope.row.vehicle_mileage) : 'Missing' }}</template>
                </el-table-column>
                <el-table-column prop="fuel_consumption_liters" label="Fuel (L)" width="120" align="right">
                    <template #default="scope">{{ number(scope.row.fuel_consumption_liters) }}</template>
                </el-table-column>
                <el-table-column prop="fuel_cost_amount" label="Fuel Cost" width="135" align="right">
                    <template #default="scope">{{ money(scope.row.fuel_cost_amount) }}</template>
                </el-table-column>
                <el-table-column prop="profit" label="Profit" width="135" align="right">
                    <template #default="scope">
                        <span :class="scope.row.profit >= 0 ? 'text-success fw-800' : 'text-danger fw-800'">{{ money(scope.row.profit) }}</span>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <div id="fuel-report-print-area" v-if="printing" class="report-print-container">
            <div class="print-header text-center mb-3">
                <h2 class="fw-bold text-uppercase">Fuel Cost & Profitability Report</h2>
                <div class="filter-summary border-bottom pb-2 mb-3">
                    <span>Period: <strong>{{ formatDate(filters.start_date) }} - {{ formatDate(filters.end_date) }}</strong></span>
                    <span class="ms-3">Fuel Rate: <strong>PKR {{ money(filters.fuel_rate) }}/L</strong></span>
                </div>
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Trip Date</th>
                        <th>Vehicle</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th class="text-end">Distance</th>
                        <th class="text-end">Freight</th>
                        <th class="text-end">Mileage</th>
                        <th class="text-end">Fuel L</th>
                        <th class="text-end">Fuel Cost</th>
                        <th class="text-end">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in reportData" :key="row.id">
                        <td>{{ formatDate(row.trip_date) }}</td>
                        <td>{{ row.vehicle_number }}</td>
                        <td>{{ row.customer?.name }}</td>
                        <td>{{ row.shipping_address?.address_name }}</td>
                        <td class="text-end">{{ number(row.round_trip_distance_km) }}</td>
                        <td class="text-end">{{ money(row.freight_amount) }}</td>
                        <td class="text-end">{{ number(row.vehicle_mileage) }}</td>
                        <td class="text-end">{{ number(row.fuel_consumption_liters) }}</td>
                        <td class="text-end">{{ money(row.fuel_cost_amount) }}</td>
                        <td class="text-end fw-bold">{{ money(row.profit) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold">TOTAL</td>
                        <td class="text-end fw-bold">{{ number(totals.distance) }}</td>
                        <td class="text-end fw-bold">{{ money(totals.freight) }}</td>
                        <td></td>
                        <td class="text-end fw-bold">{{ number(totals.fuel) }}</td>
                        <td class="text-end fw-bold">{{ money(totals.fuelCost) }}</td>
                        <td class="text-end fw-bold">{{ money(totals.profit) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';
import * as XLSX from 'xlsx';

const loading = ref(false);
const printing = ref(false);
const reportData = ref([]);
const filterOptions = reactive({
    customers: [],
    vehicles: [],
    locations: []
});

const today = new Date();
const y = today.getFullYear();
const m = String(today.getMonth() + 1).padStart(2, '0');
const d = String(today.getDate()).padStart(2, '0');

const filters = reactive({
    start_date: `${y}-${m}-01`,
    end_date: `${y}-${m}-${d}`,
    vehicle_number: null,
    customer_id: null,
    shipping_address_id: null,
    fuel_rate: null
});

const errors = reactive({});

const filteredLocations = computed(() => {
    if (!filters.customer_id) return filterOptions.locations;
    return filterOptions.locations.filter(location => location.customer_id === filters.customer_id);
});

const totals = computed(() => {
    return reportData.value.reduce((sum, row) => {
        sum.distance += Number(row.round_trip_distance_km) || 0;
        sum.freight += Number(row.freight_amount) || 0;
        sum.fuel += Number(row.fuel_consumption_liters) || 0;
        sum.fuelCost += Number(row.fuel_cost_amount) || 0;
        sum.profit += Number(row.profit) || 0;
        return sum;
    }, { distance: 0, freight: 0, fuel: 0, fuelCost: 0, profit: 0 });
});

const validateFilters = () => {
    Object.keys(errors).forEach(key => delete errors[key]);
    if (!filters.start_date) errors.start_date = 'From Date is required.';
    if (!filters.end_date) errors.end_date = 'To Date is required.';
    if (filters.start_date && filters.end_date && filters.start_date > filters.end_date) {
        errors.start_date = 'From Date cannot be greater than To Date.';
    }
    if (!filters.fuel_rate || Number(filters.fuel_rate) <= 0) {
        errors.fuel_rate = 'Fuel rate must be greater than zero.';
    }
    return Object.keys(errors).length === 0;
};

const fetchFilterOptions = async () => {
    try {
        const res = await axios.get('/api/reports/cartage/filters');
        filterOptions.customers = res.data.customers || [];
        filterOptions.vehicles = res.data.vehicles || [];
        filterOptions.locations = res.data.locations || [];
    } catch (e) {
        ElMessage.error('Failed to load filters');
    }
};

const fetchReport = async () => {
    if (!validateFilters()) {
        ElMessage.error('Please correct report filters before generating.');
        return;
    }
    loading.value = true;
    try {
        const res = await axios.get('/api/reports/cartage/fuel-cost', { params: filters });
        reportData.value = res.data;
        if (!reportData.value.length) ElMessage.warning('No trips found for selected filters.');
    } catch (e) {
        ElMessage.error(e.response?.data?.message || 'Error fetching fuel cost report');
    } finally {
        loading.value = false;
    }
};

const clearFilters = () => {
    filters.start_date = `${y}-${m}-01`;
    filters.end_date = `${y}-${m}-${d}`;
    filters.vehicle_number = null;
    filters.customer_id = null;
    filters.shipping_address_id = null;
    filters.fuel_rate = null;
    reportData.value = [];
    Object.keys(errors).forEach(key => delete errors[key]);
};

const getSummaries = ({ columns }) => {
    return columns.map((column, index) => {
        if (index === 0) return `TOTAL (${reportData.value.length} Trips)`;
        if (column.property === 'round_trip_distance_km') return number(totals.value.distance);
        if (column.property === 'freight_amount') return money(totals.value.freight);
        if (column.property === 'fuel_consumption_liters') return number(totals.value.fuel);
        if (column.property === 'fuel_cost_amount') return money(totals.value.fuelCost);
        if (column.property === 'profit') return money(totals.value.profit);
        return '';
    });
};

const exportToExcel = () => {
    const rows = reportData.value.map(row => ({
        'Trip Date': formatDate(row.trip_date),
        'Vehicle Number': row.vehicle_number,
        Customer: row.customer?.name,
        Location: row.shipping_address?.address_name,
        'Round Trip Distance (KM)': row.round_trip_distance_km,
        'Freight Amount': row.freight_amount,
        'Vehicle Mileage (KM/L)': row.vehicle_mileage,
        'Fuel Consumption (Liters)': row.fuel_consumption_liters,
        'Fuel Cost Amount': row.fuel_cost_amount,
        Profit: row.profit
    }));
    rows.push({
        'Trip Date': 'TOTAL',
        'Round Trip Distance (KM)': totals.value.distance,
        'Freight Amount': totals.value.freight,
        'Fuel Consumption (Liters)': totals.value.fuel,
        'Fuel Cost Amount': totals.value.fuelCost,
        Profit: totals.value.profit
    });
    const worksheet = XLSX.utils.json_to_sheet(rows);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Fuel_Cost_Report');
    XLSX.writeFile(workbook, `Fuel_Cost_Report_${filters.start_date}_to_${filters.end_date}.xlsx`);
    ElMessage.success('Report exported to Excel');
};

const printReport = () => {
    printing.value = true;
    setTimeout(() => {
        window.print();
        printing.value = false;
    }, 300);
};

const formatVehicleOption = vehicle => {
    const mileage = vehicle.mileage ? ` - ${Number(vehicle.mileage).toFixed(2)} KM/L` : '';
    return `${vehicle.vehicle_number}${mileage}`;
};

const formatLocationOption = location => {
    const customer = location.customer?.name ? `${location.customer.name} - ` : '';
    const distance = location.round_trip_distance_km ? ` (${Number(location.round_trip_distance_km).toFixed(2)} KM)` : '';
    return `${customer}${location.address_name}${distance}`;
};

const formatDate = value => {
    if (!value) return '';
    return new Date(value).toLocaleDateString('en-GB');
};

const number = value => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const money = value => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

onMounted(fetchFilterOptions);
</script>

<style scoped>
.fuel-cost-report {
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
.filter-section { background: #ffffff !important; }
.generate-btn { min-width: 180px; }

.validation-message {
    color: #dc3545;
    font-size: .78rem;
    font-weight: 700;
    margin-top: .35rem;
}

:deep(.is-error .el-input__wrapper),
:deep(.is-error .el-select__wrapper) {
    box-shadow: 0 0 0 1px #dc3545 inset !important;
}

[data-theme="dark"] .fuel-cost-report {
    background-color: #0f172a !important;
}

[data-theme="dark"] .fuel-cost-report .professional-card,
[data-theme="dark"] .fuel-cost-report :deep(.el-card__header),
[data-theme="dark"] .fuel-cost-report :deep(.el-card__body) {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .fuel-cost-report .filter-section {
    background: #1e293b !important;
    border-color: #334155 !important;
}

[data-theme="dark"] .fuel-cost-report .text-slate-800,
[data-theme="dark"] .fuel-cost-report .text-muted {
    color: #e2e8f0 !important;
}

@media print {
    .fuel-cost-report > .professional-card {
        display: none !important;
    }

    #fuel-report-print-area {
        display: block !important;
        visibility: visible !important;
        position: absolute;
        inset: 0;
        background: white;
        color: black;
        padding: 10mm;
        font-size: 10px;
    }

    #fuel-report-print-area * {
        visibility: visible !important;
        color: black !important;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
    }

    .report-table th,
    .report-table td {
        border: 1px solid #111;
        padding: 4px 5px;
    }

    .report-table th {
        background: #e5e7eb !important;
        font-weight: 800;
    }
}
</style>
