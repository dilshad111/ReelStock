<template>
    <div class="rm-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
            <h2 class="dashboard-title"><i class="bi bi-graph-up-arrow"></i> Raw Material Analytics</h2>
            <el-button type="primary" @click="fetchData" :loading="loading"><i class="bi bi-arrow-clockwise me-1"></i> Sync Data</el-button>
        </div>

        <!-- KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <el-card shadow="hover" class="kpi-card border-primary border-start border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-primary fw-bold small mb-1">STOCK VALUATION</div>
                            <h3 class="fw-bold mb-0">Rs. {{ formatAmount(stats.total_valuation) }}</h3>
                        </div>
                        <div class="kpi-icon bg-primary-light text-primary"><i class="bi bi-currency-dollar"></i></div>
                    </div>
                </el-card>
            </div>
            <div class="col-md-3">
                <el-card shadow="hover" class="kpi-card border-danger border-start border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-danger fw-bold small mb-1">LOW STOCK ALERTS</div>
                            <h3 class="fw-bold mb-0">{{ stats.low_stock_count }}</h3>
                        </div>
                        <div class="kpi-icon bg-danger-light text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                    </div>
                </el-card>
            </div>
            <div class="col-md-3">
                <el-card shadow="hover" class="kpi-card border-success border-start border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-success fw-bold small mb-1">MONTHLY CONSUMPTION</div>
                            <h3 class="fw-bold mb-0">{{ formatQty(stats.monthly_consumption) }} KG</h3>
                        </div>
                        <div class="kpi-icon bg-success-light text-success"><i class="bi bi-graph-down"></i></div>
                    </div>
                </el-card>
            </div>
            <div class="col-md-3">
                <el-card shadow="hover" class="kpi-card border-info border-start border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-info fw-bold small mb-1">MONTHLY RECEIVING</div>
                            <h3 class="fw-bold mb-0">{{ formatQty(stats.monthly_receiving) }} KG</h3>
                        </div>
                        <div class="kpi-icon bg-info-light text-info"><i class="bi bi-truck"></i></div>
                    </div>
                </el-card>
            </div>
        </div>

        <!-- Charts Row -->
        <el-row :gutter="20">
            <el-col :span="14">
                <el-card shadow="hover" class="chart-card">
                    <template #header>
                        <div class="fw-bold"><i class="bi bi-bar-chart-line me-2"></i> Consumption Trend (Last 6 Months)</div>
                    </template>
                    <canvas id="consumptionTrendChart" height="250"></canvas>
                </el-card>
            </el-col>
            <el-col :span="10">
                <el-card shadow="hover" class="chart-card">
                    <template #header>
                        <div class="fw-bold"><i class="bi bi-pie-chart me-2"></i> Top Consumed Materials</div>
                    </template>
                    <canvas id="topConsumedChart" height="250"></canvas>
                </el-card>
            </el-col>
        </el-row>

        <!-- Recent Transactions or other info can go here -->
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';

const stats = ref({
    total_valuation: 0,
    low_stock_count: 0,
    monthly_consumption: 0,
    monthly_receiving: 0
});

const loading = ref(false);
const charts = {};

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/rm-dashboard');
        stats.value = res.data.stats;
        
        // Render Charts
        renderTrendChart(res.data.consumption_trend);
        renderTopConsumedChart(res.data.top_consumed);
        
    } catch (error) {
        ElMessage.error('Failed to load dashboard data');
    } finally {
        loading.value = false;
    }
};

const renderTrendChart = (data) => {
    if (charts.trend) charts.trend.destroy();
    
    const ctx = document.getElementById('consumptionTrendChart');
    if (!ctx) return;

    charts.trend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.month),
            datasets: [{
                label: 'Consumption (KG)',
                data: data.map(d => d.quantity),
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
};

const renderTopConsumedChart = (data) => {
    if (charts.top) charts.top.destroy();
    
    const ctx = document.getElementById('topConsumedChart');
    if (!ctx) return;

    charts.top = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.item.name),
            datasets: [{
                data: data.map(d => d.total_qty),
                backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#3b82f6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } }
            }
        }
    });
};

const formatAmount = (val) => Number(val).toLocaleString(undefined, { maximumFractionDigits: 0 });
const formatQty = (val) => Number(val).toLocaleString();

onMounted(() => {
    fetchData();
});

onBeforeUnmount(() => {
    Object.values(charts).forEach(c => c.destroy());
});
</script>

<style scoped>
.rm-dashboard { padding: 5px; }
.dashboard-title { color: #1e293b; font-weight: 700; margin-bottom: 0; }
.kpi-card { border-radius: 12px; }
.kpi-card.h-100 { height: 100% !important; display: flex; flex-direction: column; justify-content: center; }
.kpi-card.h-100 :deep(.el-card__body) { flex: 1; display: flex; align-items: center; }
.kpi-icon { width: 48px; height: 48px; min-width: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.bg-primary-light { background-color: #eff6ff; }
.bg-danger-light { background-color: #fef2f2; }
.bg-success-light { background-color: #f0fdf4; }
.bg-info-light { background-color: #ecfeff; }
.chart-card { border-radius: 12px; margin-top: 20px; }
</style>
