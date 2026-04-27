<template>
  <div class="container-fluid dashboard-container">
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
      <h2 class="dashboard-title"><i class="bi bi-box-seam-fill"></i> Finished Goods Dashboard</h2>
      <button @click="fetchDashboard" class="btn btn-primary shadow-sm"><i class="bi bi-arrow-clockwise"></i> Sync Data</button>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-grow text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
      <p class="mt-3 text-muted">Loading finished goods analytics...</p>
    </div>

    <div v-else-if="data">
      <!-- KPI Cards -->
      <div class="row mb-4 g-3">
        <div class="col-md-3" v-for="(kpi, key) in kpis" :key="key">
          <div :class="['card action-widget border-start border-4 shadow-sm h-100', kpi.borderColor]">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 :class="[kpi.textColor, 'fw-bold mb-1']">{{ kpi.label }}</h6>
                  <h3 class="mb-0 fw-bold text-slate-800">{{ kpi.value }}</h3>
                </div>
                <div :class="['widget-icon', kpi.bgSoft]"><i :class="['bi', kpi.icon, kpi.textColor]"></i></div>
              </div>
              <p class="text-muted small mt-2 mb-0">{{ kpi.subtext }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="row g-4 mb-5">
        <div class="col-md-7">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-bar-chart-line"></i> Monthly Production vs Dispatch (Last 6 Months)</h6></div>
            <div class="card-body pt-0"><canvas id="fgMonthlyTrendChart" height="250"></canvas></div>
          </div>
        </div>
        <div class="col-md-5">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-trophy"></i> Top 10 Products by Stock</h6></div>
            <div class="card-body pt-0"><canvas id="fgTopProductsChart" height="250"></canvas></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  props: { user: { type: Object, default: null } },
  data() { return { data: null, loading: true, charts: {} }; },
  computed: {
    kpis() {
      if (!this.data) return [];
      const k = this.data.kpis;
      return [
        { label: 'Total Products', value: k.total_products, icon: 'bi-box-seam', borderColor: 'border-primary', textColor: 'text-primary', bgSoft: 'bg-primary-soft', subtext: 'Registered products' },
        { label: 'Total FG Stock', value: this.fmt(k.total_stock), icon: 'bi-boxes', borderColor: 'border-success', textColor: 'text-success', bgSoft: 'bg-success-soft', subtext: 'Current inventory balance' },
        { label: 'Monthly Produced', value: this.fmt(k.monthly_produced), icon: 'bi-arrow-down-circle', borderColor: 'border-info', textColor: 'text-info', bgSoft: 'bg-info-soft', subtext: 'This month production' },
        { label: 'Monthly Dispatched', value: this.fmt(k.monthly_dispatched), icon: 'bi-arrow-up-circle', borderColor: 'border-warning', textColor: 'text-warning', bgSoft: 'bg-warning-soft', subtext: 'This month dispatch' }
      ];
    }
  },
  mounted() {
    if (localStorage.getItem('token')) axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    this.fetchDashboard();
  },
  methods: {
    fetchDashboard() {
      this.loading = true;
      axios.get('/api/fg-dashboard').then(r => {
        this.data = r.data; this.loading = false;
        this.$nextTick(() => this.renderCharts());
      }).catch(() => { this.loading = false; });
    },
    renderCharts() {
      this.destroyCharts();
      if (!this.data) return;
      const trend = this.data.monthly_trend;

      this.charts.monthly = new Chart(document.getElementById('fgMonthlyTrendChart'), {
        type: 'bar',
        data: {
          labels: trend.map(t => t.month),
          datasets: [
            { label: 'Produced', data: trend.map(t => t.produced), backgroundColor: 'rgba(16, 185, 129, 0.7)', borderRadius: 4 },
            { label: 'Dispatched', data: trend.map(t => t.dispatched), backgroundColor: 'rgba(239, 68, 68, 0.7)', borderRadius: 4 }
          ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { x: { grid: { display: false } } } }
      });

      const top = this.data.top_products;
      this.charts.topProducts = new Chart(document.getElementById('fgTopProductsChart'), {
        type: 'bar',
        data: {
          labels: top.map(p => p.item_name.length > 15 ? p.item_name.substring(0, 15) + '...' : p.item_name),
          datasets: [{ label: 'Balance', data: top.map(p => p.balance), backgroundColor: 'rgba(99, 102, 241, 0.7)', borderRadius: 4 }]
        },
        options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
      });
    },
    destroyCharts() { Object.values(this.charts).forEach(c => c.destroy()); this.charts = {}; },
    fmt(v) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 0 }); }
  },
  beforeUnmount() { this.destroyCharts(); }
};
</script>

<style scoped>
.dashboard-container { background-color: #f8fafc; min-height: 100vh; }
.dashboard-title { color: #1e293b; font-weight: 700; margin-bottom: 0; }
.card { border-radius: 12px; border: none; transition: all 0.3s; }
.action-widget:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
.widget-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.bg-primary-soft { background-color: #e0e7ff; }
.bg-success-soft { background-color: #dcfce7; }
.bg-info-soft { background-color: #e0f2fe; }
.bg-warning-soft { background-color: #fef3c7; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }
</style>
