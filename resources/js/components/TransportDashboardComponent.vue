<template>
    <div class="transport-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-slate-800 mb-0">Transport Analytics</h2>
                <p class="text-muted small">Comprehensive logistics and cartage performance tracking</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <el-radio-group v-model="timeRange" @change="fetchData" size="small" class="custom-radio-group">
                    <el-radio-button label="7">7D</el-radio-button>
                    <el-radio-button label="30">30D</el-radio-button>
                    <el-radio-button label="90">90D</el-radio-button>
                </el-radio-group>
                <el-button type="primary" :loading="loading" @click="fetchData" circle class="shadow-sm">
                    <i class="bi bi-arrow-clockwise"></i>
                </el-button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card kpi-primary h-100 shadow-sm">
                    <div class="stat-content">
                        <p class="stat-label">Total Bills</p>
                        <h3 class="stat-value">{{ kpis.total_bills }}</h3>
                    </div>
                    <div class="stat-icon-box">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card kpi-success h-100 shadow-sm">
                    <div class="stat-content">
                        <p class="stat-label">Total Cartage</p>
                        <h3 class="stat-value">₨ {{ formatAmount(kpis.total_amount) }}</h3>
                    </div>
                    <div class="stat-icon-box">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card kpi-warning h-100 shadow-sm">
                    <div class="stat-content">
                        <p class="stat-label">Pending Approval</p>
                        <h3 class="stat-value">{{ kpis.pending_bills }}</h3>
                    </div>
                    <div class="stat-icon-box">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card kpi-info h-100 shadow-sm">
                    <div class="stat-content">
                        <p class="stat-label">Approved Bills</p>
                        <h3 class="stat-value">{{ kpis.approved_bills }}</h3>
                    </div>
                    <div class="stat-icon-box">
                        <i class="bi bi-check2-all"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Billing Trend Chart -->
            <div class="col-md-8">
                <el-card class="glass-card shadow-sm h-100">
                    <template #header>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i> Billing & Volume Trend</span>
                            <el-tag size="small" type="primary" effect="plain">Last {{ timeRange }} Days</el-tag>
                        </div>
                    </template>
                    <div class="chart-container">
                        <canvas id="billingTrendChart"></canvas>
                    </div>
                </el-card>
            </div>

            <!-- Transporter Distribution -->
            <div class="col-md-4">
                <el-card class="glass-card shadow-sm h-100">
                    <template #header>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="bi bi-pie-chart me-2 text-success"></i> Transporter Share</span>
                            <i class="bi bi-info-circle text-muted small" title="Distribution by cartage value"></i>
                        </div>
                    </template>
                    <div class="chart-container share-chart-wrapper">
                        <canvas id="transporterShareChart"></canvas>
                    </div>
                </el-card>
            </div>
        </div>

        <div class="row g-4">
            <!-- Destinations Table -->
            <div class="col-md-7">
                <el-card class="glass-card shadow-sm">
                    <template #header>
                        <span class="fw-bold"><i class="bi bi-geo-alt me-2 text-danger"></i> Top Delivery Destinations</span>
                    </template>
                    <el-table :data="destinations" style="width: 100%" size="small" class="professional-table">
                        <el-table-column prop="name" label="Destination" />
                        <el-table-column prop="count" label="Trips" width="80" align="center" />
                        <el-table-column label="Amount" align="right" width="120">
                            <template #default="scope">
                                ₨ {{ formatAmount(scope.row.amount) }}
                            </template>
                        </el-table-column>
                        <el-table-column label="Share" width="150">
                            <template #default="scope">
                                <div class="d-flex align-items-center gap-2">
                                    <el-progress :percentage="calculatePercentage(scope.row.amount)" :show-text="false" :stroke-width="6" color="#ef4444" class="flex-grow-1" />
                                    <span class="small text-muted">{{ calculatePercentage(scope.row.amount) }}%</span>
                                </div>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
            </div>

            <!-- Vehicle Stats -->
            <div class="col-md-5">
                <el-card class="glass-card shadow-sm">
                    <template #header>
                        <span class="fw-bold"><i class="bi bi-truck-flatbed me-2 text-info"></i> Vehicle Utilization</span>
                    </template>
                    <el-table :data="vehicles" style="width: 100%" size="small" class="professional-table">
                        <el-table-column prop="number" label="Vehicle #" />
                        <el-table-column prop="count" label="Usage" width="80" align="center" />
                        <el-table-column label="Billing Amount" align="right">
                            <template #default="scope">
                                <span class="fw-bold text-slate-700">₨ {{ formatAmount(scope.row.amount) }}</span>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
            </div>
        </div>

        <div class="mt-4 text-center text-muted small p-3 bg-light rounded-3">
            <i class="bi bi-info-circle me-1"></i> Data automatically refreshed based on the selected period. Last Sync: {{ lastUpdated }}
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';

const loading = ref(false);
const timeRange = ref('30');
const kpis = ref({
    total_bills: 0,
    total_amount: 0,
    pending_bills: 0,
    approved_bills: 0
});
const transporters = ref([]);
const vehicles = ref([]);
const destinations = ref([]);
const lastUpdated = ref('');
const charts = {};

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/transport-dashboard', {
            params: { range: timeRange.value }
        });
        kpis.value = res.data.kpis;
        transporters.value = res.data.transporters;
        vehicles.value = res.data.vehicles;
        destinations.value = res.data.destinations;
        lastUpdated.value = new Date(res.data.last_updated).toLocaleTimeString();
        
        await nextTick();
        renderCharts(res.data);
    } catch (error) {
        console.error(error);
        ElMessage.error('Error fetching transport analytics');
    } finally {
        loading.value = false;
    }
};

const renderCharts = (data) => {
    // Destroy existing charts
    Object.values(charts).forEach(chart => chart.destroy());

    // 1. Billing Trend Chart
    const trendCtx = document.getElementById('billingTrendChart');
    if (trendCtx) {
        const trendData = data.trends.over_time;
        const labels = Object.keys(trendData);
        charts.trend = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Billing Amount (₨)',
                        data: labels.map(l => trendData[l].amount),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        fill: true,
                        tension: 0.45,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Bill Count',
                        data: labels.map(l => trendData[l].count),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        type: 'bar',
                        barThickness: 12,
                        borderRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 10, usePointStyle: true, font: { size: 12, weight: '600' } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 13, weight: 'bold' }
                    }
                },
                scales: {
                    y: { 
                        type: 'linear', 
                        display: true, 
                        position: 'left',
                        grid: { borderDash: [5, 5], color: '#f1f5f9' },
                        ticks: { font: { weight: '600' }, color: '#64748b' }
                    },
                    y1: { 
                        type: 'linear', 
                        display: true, 
                        position: 'right', 
                        grid: { drawOnChartArea: false },
                        ticks: { font: { weight: '600' }, color: '#10b981' }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { weight: '600' }, color: '#64748b' }
                    }
                }
            }
        });
    }

    // 2. Transporter Share Chart
    const shareCtx = document.getElementById('transporterShareChart');
    if (shareCtx) {
        charts.share = new Chart(shareCtx, {
            type: 'doughnut',
            data: {
                labels: data.transporters.map(t => t.name),
                datasets: [{
                    data: data.transporters.map(t => t.amount),
                    backgroundColor: [
                        '#6366f1', // Indigo
                        '#10b981', // Emerald
                        '#f59e0b', // Amber
                        '#ef4444', // Red
                        '#8b5cf6', // Violet
                        '#06b6d4', // Cyan
                        '#f43f5e'  // Rose
                    ],
                    hoverOffset: 12,
                    borderRadius: 8,
                    spacing: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            boxWidth: 10, 
                            usePointStyle: true, 
                            padding: 15,
                            font: { size: 11, weight: '600' }
                        } 
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => {
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return ` ₨ ${formatAmount(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: (chart) => {
                    const { ctx, chartArea: { top, bottom, left, right, width, height } } = chart;
                    ctx.save();
                    const total = chart.config.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    
                    // Total Label
                    ctx.font = '600 12px Montserrat';
                    ctx.fillStyle = '#94a3b8';
                    ctx.textAlign = 'center';
                    ctx.fillText('TOTAL VALUE', width / 2 + left, height / 2 + top - 10);
                    
                    // Amount
                    ctx.font = 'bold 18px Montserrat';
                    ctx.fillStyle = '#1e293b';
                    ctx.fillText(`₨ ${formatAmount(total)}`, width / 2 + left, height / 2 + top + 12);
                    ctx.restore();
                }
            }]
        });
    }
};

const formatAmount = (val) => {
    return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};

const calculatePercentage = (amount) => {
    if (kpis.value.total_amount === 0) return 0;
    return Math.round((amount / kpis.value.total_amount) * 100);
};

onMounted(() => {
    fetchData();
});

onUnmounted(() => {
    Object.values(charts).forEach(chart => chart.destroy());
});
</script>

<style scoped>
.transport-dashboard {
    padding: 20px;
    background-color: #f8fafc;
    min-height: calc(100vh - 150px);
}

.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }

/* KPI Cards Custom Styles */
.stat-card {
    background: white;
    border-radius: 20px;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-label {
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 26px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 0;
}

.stat-icon-box {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

/* KPI Colors */
.kpi-primary { border-bottom: 4px solid #6366f1; }
.kpi-primary .stat-icon-box { background: rgba(99, 102, 241, 0.1); color: #6366f1; }

.kpi-success { border-bottom: 4px solid #10b981; }
.kpi-success .stat-icon-box { background: rgba(16, 185, 129, 0.1); color: #10b981; }

.kpi-warning { border-bottom: 4px solid #f59e0b; }
.kpi-warning .stat-icon-box { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

.kpi-info { border-bottom: 4px solid #0ea5e9; }
.kpi-info .stat-icon-box { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; }

/* Glass Cards */
.glass-card {
    border: none;
    border-radius: 24px;
}

.chart-container {
    height: 320px;
    width: 100%;
}

.share-chart-wrapper {
    padding: 10px;
}

.professional-table :deep(.el-table__header th) {
    background-color: #f8fafc;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 12px;
}

.professional-table :deep(.el-table__row) {
    transition: background-color 0.2s;
}

.custom-radio-group :deep(.el-radio-button__inner) {
    padding: 8px 16px;
}

:deep(.el-card__header) {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
}
</style>
