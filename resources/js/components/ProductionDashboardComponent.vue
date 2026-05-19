<template>
    <div class="production-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-slate-800"><i class="bi bi-cpu me-2"></i> Production Intelligence</h2>
            <el-button type="primary" @click="fetchData" :loading="loading" circle>
                <i class="bi bi-arrow-clockwise"></i>
            </el-button>
        </div>

        <div v-if="loading" class="text-center py-5">
            <el-skeleton :rows="10" animated />
        </div>

        <div v-else-if="data">
            <!-- KPI Row -->
            <el-row :gutter="20" class="mb-4">
                <el-col :span="6">
                    <div class="kpi-card border-start border-primary border-4 shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-primary fw-bold mb-1">Active Job Cards</h6>
                                    <h3 class="mb-0 fw-bold">{{ data.kpis.active_job_cards }}</h3>
                                </div>
                                <div class="widget-icon bg-primary-soft"><i class="bi bi-card-checklist text-primary"></i></div>
                            </div>
                            <p class="text-muted small mt-2 mb-0">Currently in shop floor</p>
                        </div>
                    </div>
                </el-col>
                <el-col :span="6">
                    <div class="kpi-card border-start border-success border-4 shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-success fw-bold mb-1">Planned Qty</h6>
                                    <h3 class="mb-0 fw-bold">{{ formatQty(data.kpis.total_planned_qty) }}</h3>
                                </div>
                                <div class="widget-icon bg-success-soft"><i class="bi bi-bullseye text-success"></i></div>
                            </div>
                            <p class="text-muted small mt-2 mb-0">Workload on active jobs</p>
                        </div>
                    </div>
                </el-col>
                <el-col :span="6">
                    <div class="kpi-card border-start border-info border-4 shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-info fw-bold mb-1">Final Output (MTD)</h6>
                                    <h3 class="mb-0 fw-bold">{{ formatQty(data.kpis.produced_this_month) }}</h3>
                                </div>
                                <div class="widget-icon bg-info-soft"><i class="bi bi-box-seam text-info"></i></div>
                            </div>
                            <p class="text-muted small mt-2 mb-0">Completed units this month</p>
                        </div>
                    </div>
                </el-col>
                <el-col :span="6">
                    <div class="kpi-card border-start border-danger border-4 shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-danger fw-bold mb-1">Avg Wastage</h6>
                                    <h3 class="mb-0 fw-bold">{{ data.kpis.avg_wastage.toFixed(2) }}%</h3>
                                </div>
                                <div class="widget-icon bg-danger-soft"><i class="bi bi-trash-fill text-danger"></i></div>
                            </div>
                            <p class="text-muted small mt-2 mb-0">Production loss metric</p>
                        </div>
                    </div>
                </el-col>
            </el-row>

            <el-row :gutter="20" class="mb-4">
                <!-- Daily Trend -->
                <el-col :span="16">
                    <el-card class="chart-card shadow-sm border-0 h-100">
                        <template #header>
                            <div class="fw-bold"><i class="bi bi-graph-up me-2"></i> Daily Production Trend (Last 30 Days)</div>
                        </template>
                        <div style="height: 300px;">
                            <canvas id="prodDailyTrendChart"></canvas>
                        </div>
                    </el-card>
                </el-col>
                <!-- Status Mix -->
                <el-col :span="8">
                    <el-card class="chart-card shadow-sm border-0 h-100">
                        <template #header>
                            <div class="fw-bold"><i class="bi bi-pie-chart me-2"></i> Job Card Status Mix</div>
                        </template>
                        <div style="height: 300px;" class="d-flex align-items-center justify-content-center">
                            <canvas id="prodStatusMixChart"></canvas>
                        </div>
                    </el-card>
                </el-col>
            </el-row>

            <el-row :gutter="20" class="mb-4">
                <!-- Step Output -->
                <el-col :span="12">
                    <el-card class="chart-card shadow-sm border-0 h-100">
                        <template #header>
                            <div class="fw-bold"><i class="bi bi-bar-chart-steps me-2"></i> Process Step Output (Monthly)</div>
                        </template>
                        <div style="height: 300px;">
                            <canvas id="prodStepOutputChart"></canvas>
                        </div>
                    </el-card>
                </el-col>
                <!-- Material Shortage -->
                <el-col :span="12">
                    <el-card class="shadow-sm border-0 h-100">
                        <template #header>
                            <div class="fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Material Coverage Alerts</div>
                        </template>
                        <el-table :data="data.material_shortage" size="small" height="300">
                            <el-table-column prop="job_card_no" label="JC #" width="120" />
                            <el-table-column prop="material_name" label="Material" show-overflow-tooltip />
                            <el-table-column label="Shortage" align="right">
                                <template #default="scope">
                                    <span class="text-danger fw-bold">
                                        {{ formatQty((scope.row.required_qty - scope.row.consumed_qty) - scope.row.available_stock) }}
                                    </span>
                                </template>
                            </el-table-column>
                            <el-table-column label="Stock" align="right" width="100">
                                <template #default="scope">
                                    <el-tag type="danger" size="small" effect="plain">{{ formatQty(scope.row.available_stock) }}</el-tag>
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-card>
                </el-col>
            </el-row>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const data = ref(null);
const loading = ref(false);
const charts = {};

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/job-cards/dashboard');
        data.value = res.data;
        nextTick(() => {
            renderCharts();
        });
    } catch (error) {
        console.error('Failed to fetch dashboard data', error);
    } finally {
        loading.value = false;
    }
};

const renderCharts = () => {
    destroyCharts();
    const d = data.value;
    if (!d) return;

    // 1. Daily Trend
    charts.dailyTrend = new Chart(document.getElementById('prodDailyTrendChart'), {
        type: 'line',
        data: {
            labels: d.daily_trend.map(i => i.date),
            datasets: [
                {
                    label: 'Quantity Produced',
                    data: d.daily_trend.map(i => i.total_qty),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Wastage',
                    data: d.daily_trend.map(i => i.total_wastage),
                    borderColor: '#ef4444',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true }
            }
        }
    });

    // 2. Status Mix
    charts.statusMix = new Chart(document.getElementById('prodStatusMixChart'), {
        type: 'doughnut',
        data: {
            labels: d.status_distribution.map(i => i.status),
            datasets: [{
                data: d.status_distribution.map(i => i.count),
                backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } }
            }
        }
    });

    // 3. Step Output
    charts.stepOutput = new Chart(document.getElementById('prodStepOutputChart'), {
        type: 'bar',
        data: {
            labels: d.step_output.map(i => i.step_name),
            datasets: [{
                label: 'Units Processed',
                data: d.step_output.map(i => i.total_qty),
                backgroundColor: '#6366f1',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true }
            }
        }
    });
};

const destroyCharts = () => {
    Object.values(charts).forEach(c => c.destroy());
};

const formatQty = (val) => Number(val || 0).toLocaleString();

onMounted(() => {
    fetchData();
});
</script>

<style scoped>
.production-dashboard {
    padding: 10px;
    background-color: #f8fafc;
}
.kpi-card {
    background: white;
    border-radius: 12px;
    border: none;
}
.widget-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.bg-primary-soft { background-color: #e0e7ff; }
.bg-success-soft { background-color: #dcfce7; }
.bg-info-soft { background-color: #e0f2fe; }
.bg-danger-soft { background-color: #fee2e2; }
.text-slate-800 { color: #1e293b; }
.chart-card {
    border-radius: 12px;
}
</style>
