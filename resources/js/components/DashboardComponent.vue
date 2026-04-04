<template>
  <div class="container-fluid dashboard-container">
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
      <h2 class="dashboard-title"><i class="bi bi-graph-up-arrow"></i> Reel Analytics Dashboard</h2>
      <div class="d-flex gap-2 align-items-center">
        <select v-model="timeRange" @change="fetchActiveDashboard" class="form-select range-select shadow-sm ms-2">
          <option value="7">Last 7 Days</option>
          <option value="30">Last 30 Days</option>
          <option value="90">Last 90 Days</option>
          <option value="180">Last 6 Months</option>
        </select>
        <button @click="fetchActiveDashboard" class="btn btn-primary shadow-sm"><i class="bi bi-arrow-clockwise"></i> Sync Data</button>
      </div>
    </div>

    <!-- TABS -->
    <el-tabs v-model="activeTab" class="custom-dashboard-tabs" @tab-change="handleTabChange">
      <el-tab-pane label="Operational Dashboard" name="operational">
        <!-- OPERATIONAL DASHBOARD CONTENT -->
        <div v-if="loadingOperational" class="text-center py-5">
          <div class="spinner-grow text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
          <p class="mt-3 text-muted">Analyzing reel movements...</p>
        </div>

        <div v-else-if="dashboard">
          <div class="row mb-4 g-3">
            <div class="col-md-3" v-for="(kpi, key) in operationalKpis" :key="key">
              <div :class="['card action-widget border-start border-4 shadow-sm h-100', kpi.borderColor]">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 :class="[kpi.textColor, 'fw-bold mb-1']">{{ kpi.label }}</h6>
                      <h3 class="mb-0">{{ kpi.value }}</h3>
                    </div>
                    <div :class="['widget-icon', kpi.bgSoft]"><i :class="['bi', kpi.icon, kpi.textColor]"></i></div>
                  </div>
                  <p class="text-muted small mt-2 mb-0">{{ kpi.subtext }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="section-container mb-5">
            <h4 class="section-title"><i class="bi bi-geo-alt"></i> Stock by Location</h4>
            <div class="row g-4">
              <div class="col-md-6" v-for="(loc, name) in dashboard.location_breakdown" :key="name">
                <div class="card location-card border-0 shadow-sm bg-white overflow-hidden">
                    <div class="card-body p-0">
                        <div class="d-flex">
                            <div :class="[name === 'godown' ? 'bg-primary' : 'bg-indigo', 'text-white d-flex align-items-center justify-content-center px-4']" style="width: 80px;">
                                <i :class="['bi', name === 'godown' ? 'bi-building' : 'bi-tools', 'fs-2']"></i>
                            </div>
                            <div class="p-3 flex-grow-1">
                                <h5 class="fw-bold mb-1 text-uppercase">{{ name }}</h5>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div><h3 class="mb-0">{{ loc.count }} <small class="fs-6 text-muted">Reels</small></h3></div>
                                    <div class="text-end"><h3 :class="['mb-0', name === 'godown' ? 'text-primary' : 'text-indigo']">{{ formatNumber(loc.weight) }} <small class="fs-6 text-muted">kg</small></h3></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-5">
            <div class="col-md-8">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0">Current Stock by Quality (kg)</h6></div>
                <div class="card-body pt-0"><canvas id="stockByQualityChart" height="300"></canvas></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0">Stock Distribution</h6></div>
                <div class="card-body pt-0 d-flex flex-column align-items-center justify-content-center">
                  <canvas id="stockStatusPieChart" height="200"></canvas>
                  <div class="mt-3 text-center">
                    <h2 class="mb-0 text-primary">{{ formatNumber(dashboard.stock_overview.total_weight) }}</h2>
                    <span class="text-muted">Total Available kg</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </el-tab-pane>

      <el-tab-pane label="Management Dashboard" name="management" v-if="canViewManagement">
        <!-- MANAGEMENT DASHBOARD CONTENT -->
        <div v-if="loadingManagement" class="text-center py-5">
          <div class="spinner-grow text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
          <p class="mt-3 text-muted">Calculating financial metrics...</p>
        </div>

        <div v-else-if="managementData">
          <!-- TOP KPI CARDS (FINANCIAL) -->
          <div class="row mb-4 g-3">
             <div class="col-md-3">
              <div class="card action-widget border-start border-primary border-4 shadow-sm h-100 financial-card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-primary fw-bold mb-1">Stock Portfolio Value</h6>
                      <h3 class="mb-0">₨ {{ formatNumber(managementData.kpis.total_stock_amount) }}</h3>
                    </div>
                    <div class="widget-icon bg-primary-soft"><i class="bi bi-wallet2 text-primary"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card action-widget border-start border-success border-4 shadow-sm h-100 financial-card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-success fw-bold mb-1">Consumption (Month)</h6>
                      <h3 class="mb-0">₨ {{ formatNumber(managementData.kpis.monthly_consumption_amount) }}</h3>
                    </div>
                    <div class="widget-icon bg-success-soft"><i class="bi bi-graph-down-arrow text-success"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card action-widget border-start border-info border-4 shadow-sm h-100 financial-card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-info fw-bold mb-1">Receiving (Month)</h6>
                      <h3 class="mb-0">₨ {{ formatNumber(managementData.kpis.monthly_received_amount) }}</h3>
                    </div>
                    <div class="widget-icon bg-info-soft"><i class="bi bi-cash-stack text-info"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card action-widget border-start border-warning border-4 shadow-sm h-100 financial-card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-warning fw-bold mb-1">Avg Rate / Kg</h6>
                      <h3 class="mb-0">₨ {{ formatNumber(managementData.kpis.avg_rate_per_kg) }}</h3>
                    </div>
                    <div class="widget-icon bg-warning-soft"><i class="bi bi-tags text-warning"></i></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 12 MONTHS HISTORY BAR GRAPHS -->
          <div class="row g-4 mb-5">
            <div class="col-md-6">
              <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                  <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-bar-chart-fill"></i> Monthly Consumption Value (Last 12 Months)</h6>
                </div>
                <div class="card-body pt-0">
                  <canvas id="mgmtConsumption12Chart" height="220"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                  <h6 class="fw-bold mb-0 text-success"><i class="bi bi-bar-chart-fill"></i> Monthly Material Receipt Value (Last 12 Months)</h6>
                </div>
                <div class="card-body pt-0">
                  <canvas id="mgmtReceived12Chart" height="220"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- MANAGEMENT VISUALS -->
          <div class="row g-4 mb-5">
            <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0">Investment by Quality (₨)</h6></div>
                <div class="card-body"><canvas id="mgmtAmountByQualityChart" height="300"></canvas></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0">Portfolio by Supplier (₨)</h6></div>
                <div class="card-body"><canvas id="mgmtAmountBySupplierChart" height="300"></canvas></div>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-5">
            <div class="col-md-12">
               <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0">Daily Value Movement Trend (Last {{ timeRange }} Days)</h6></div>
                <div class="card-body"><canvas id="mgmtConsumptionTrendChart" height="150"></canvas></div>
              </div>
            </div>
          </div>

          <!-- TABLE AND ASSETS -->
          <div class="row g-4 mb-5">
            <div class="col-md-8">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0">Quality-wise Value Profile</h6></div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mt-2">
                        <thead class="table-light"><tr><th>Quality</th><th class="text-end">Weight (kg)</th><th class="text-end">Total Amount (₨)</th><th class="text-end">Avg Rate</th></tr></thead>
                        <tbody>
                          <tr v-for="item in managementData.stock.by_quality" :key="item.name">
                            <td>{{ item.name }}</td>
                            <td class="text-end fw-bold">{{ formatNumber(item.weight) }}</td>
                            <td class="text-end text-primary fw-bold">₨ {{ formatNumber(item.amount) }}</td>
                            <td class="text-end small text-muted">₨ {{ formatNumber(item.amount / item.weight) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card shadow-sm border-0 h-100 bg-gradient-premium text-white">
                <div class="card-body d-flex flex-column justify-content-center text-center p-4">
                  <div class="mb-4"><i class="bi bi-award fs-1 opacity-50"></i></div>
                  <h1 class="display-6 fw-bold mb-1">₨ {{ formatNumber(managementData.kpis.total_stock_amount) }}</h1>
                  <p class="text-uppercase tracking-wider small opacity-75">Net Inventory Asset Value</p>
                  <div class="mt-4 pt-4 border-top border-white border-opacity-25">
                    <div class="d-flex justify-content-between mb-2"><span>Current Month Inward</span><span>₨ {{ formatNumber(managementData.kpis.monthly_received_amount) }}</span></div>
                    <div class="d-flex justify-content-between"><span>Current Month Consumption</span><span>₨ {{ formatNumber(managementData.kpis.monthly_consumption_amount) }}</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: ['user', 'canViewManagement'],
  data() {
    return {
      activeTab: 'operational',
      dashboard: null,
      managementData: null,
      timeRange: 30,
      loadingOperational: true,
      loadingManagement: false,
      charts: {}
    };
  },
  computed: {
    operationalKpis() {
      if (!this.dashboard) return [];
      return [
        { label: 'Reels in Stock', value: this.dashboard.kpis.reels_in_stock, icon: 'bi-box-seam', borderColor: 'border-primary', textColor: 'text-primary', bgSoft: 'bg-primary-soft', subtext: 'Total available units' },
        { label: 'Weight in Stock (kg)', value: this.formatNumber(this.dashboard.kpis.weight_in_stock), icon: 'bi-database-check', borderColor: 'border-success', textColor: 'text-success', bgSoft: 'bg-success-soft', subtext: 'Total physical stock' },
        { label: 'Reels Used (Month)', value: this.dashboard.kpis.reels_used_month, icon: 'bi-arrow-up-right-circle', borderColor: 'border-info', textColor: 'text-info', bgSoft: 'bg-info-soft', subtext: 'Total reels issued' },
        { label: 'Weight Used (Month)', value: this.formatNumber(this.dashboard.kpis.weight_used_month), icon: 'bi-graph-up', borderColor: 'border-warning', textColor: 'text-warning', bgSoft: 'bg-warning-soft', subtext: 'Monthly net consumption' }
      ];
    }
  },
  mounted() {
    this.fetchActiveDashboard();
  },
  methods: {
    handleTabChange() { this.fetchActiveDashboard(); },
    fetchActiveDashboard() {
      if (this.activeTab === 'operational') this.fetchOperationalDashboard();
      else this.fetchManagementDashboard();
    },
    fetchOperationalDashboard() {
      this.loadingOperational = true;
      axios.get(`/api/dashboard?range=${this.timeRange}`).then(response => {
        this.dashboard = response.data;
        this.loadingOperational = false;
        this.$nextTick(() => this.renderOperationalCharts());
      }).catch(error => { console.error(error); this.loadingOperational = false; });
    },
    fetchManagementDashboard() {
      this.loadingManagement = true;
      axios.get(`/api/management-dashboard?range=${this.timeRange}`).then(response => {
        this.managementData = response.data;
        this.loadingManagement = false;
        this.$nextTick(() => this.renderManagementCharts());
      }).catch(error => { console.error(error); this.loadingManagement = false; });
    },
    renderOperationalCharts() {
      this.destroyCharts();
      const d = this.dashboard; if (!d) return;
      this.charts.stockByQuality = new Chart(document.getElementById('stockByQualityChart'), {
        type: 'bar',
        data: {
          labels: d.stock_overview.by_quality.map(i => i.name),
          datasets: [{ label: 'Reels', data: d.stock_overview.by_quality.map(i => i.count), backgroundColor: 'rgba(99, 102, 241, 0.5)', yAxisID: 'yCount' }, 
                     { label: 'Weight (kg)', data: d.stock_overview.by_quality.map(i => i.weight), backgroundColor: 'rgba(16, 185, 129, 0.5)', yAxisID: 'yWeight' }]
        },
        options: { scales: { yWeight: { position: 'right' }, yCount: { position: 'left' } } }
      });
      this.charts.stockStatusPie = new Chart(document.getElementById('stockStatusPieChart'), {
        type: 'doughnut',
        data: { labels: d.stock_overview.status_distribution.map(i => i.label), datasets: [{ data: d.stock_overview.status_distribution.map(i => i.value), backgroundColor: ['#6366f1', '#f59e0b'] }] },
        options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
      });
    },
    renderManagementCharts() {
      this.destroyCharts();
      const m = this.managementData; if (!m) return;

      // New 12 Months Consumption Bar
      this.charts.mgmtCons12 = new Chart(document.getElementById('mgmtConsumption12Chart'), {
        type: 'bar',
        data: {
          labels: m.consumption.monthly_12.map(i => i.month),
          datasets: [{ label: 'Consumption Amount (₨)', data: m.consumption.monthly_12.map(i => i.amount), backgroundColor: '#6366f1', borderRadius: 5 }]
        }
      });

      // New 12 Months Received Bar
      this.charts.mgmtRec12 = new Chart(document.getElementById('mgmtReceived12Chart'), {
        type: 'bar',
        data: {
          labels: m.receiving.monthly_12.map(i => i.month),
          datasets: [{ label: 'Receipt Amount (₨)', data: m.receiving.monthly_12.map(i => i.amount), backgroundColor: '#10b981', borderRadius: 5 }]
        }
      });

      this.charts.mgmtQuality = new Chart(document.getElementById('mgmtAmountByQualityChart'), {
        type: 'bar',
        data: { labels: m.stock.by_quality.map(i => i.name), datasets: [{ label: 'Value (₨)', data: m.stock.by_quality.map(i => i.amount), backgroundColor: 'rgba(99, 102, 241, 0.7)' }] },
        options: { indexAxis: 'y' }
      });

      this.charts.mgmtSupplier = new Chart(document.getElementById('mgmtAmountBySupplierChart'), {
        type: 'bar',
        data: { labels: m.stock.by_supplier.map(i => i.name), datasets: [{ label: 'Value (₨)', data: m.stock.by_supplier.map(i => i.amount), backgroundColor: 'rgba(16, 185, 129, 0.7)' }] }
      });

      this.charts.mgmtTrend = new Chart(document.getElementById('mgmtConsumptionTrendChart'), {
        type: 'line',
        data: {
          labels: m.consumption.over_time.map(i => i.date),
          datasets: [
            { label: 'Outward (₨)', data: m.consumption.over_time.map(i => i.amount), borderColor: '#6366f1', fill: false, tension: 0.3 },
            { label: 'Inward (₨)', data: m.receiving.over_time.map(i => i.amount), borderColor: '#10b981', fill: false, tension: 0.3 }
          ]
        }
      });
    },
    destroyCharts() { Object.values(this.charts).forEach(c => c.destroy()); this.charts = {}; },
    formatNumber(val) { return Number(val || 0).toLocaleString(undefined, { maximumFractionDigits: 0 }); }
  },
  beforeUnmount() { this.destroyCharts(); }
};
</script>

<style scoped>
.dashboard-container { background-color: #f8fafc; min-height: 100vh; }
.dashboard-title { color: #1e293b; font-weight: 700; margin-bottom: 0; }
.range-select { width: 180px; border-radius: 8px; }
.section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; }
.card { border-radius: 12px; border: none; transition: all 0.3s; }
.action-widget:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
.widget-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.bg-primary-soft { background-color: #e0e7ff; }
.bg-success-soft { background-color: #dcfce7; }
.bg-info-soft { background-color: #e0f2fe; }
.bg-warning-soft { background-color: #fef3c7; }
.bg-indigo { background-color: #6366f1; }
.bg-gradient-premium { background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); }
.custom-dashboard-tabs :deep(.el-tabs__item) { font-weight: 600; font-size: 1rem; height: 50px; }
.custom-dashboard-tabs :deep(.el-tabs__active-bar) { background-color: #6366f1; height: 3px; }
</style>
