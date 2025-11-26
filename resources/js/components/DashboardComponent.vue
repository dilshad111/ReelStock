<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="bi bi-speedometer2"></i> Paper Reel Stock Dashboard</h2>
      <div class="d-flex gap-2">
        <select v-model="timeRange" @change="fetchDashboard" class="form-select">
          <option value="7">Last 7 days</option>
          <option value="30">Last 30 days</option>
          <option value="90">Last 90 days</option>
        </select>
        <button @click="fetchDashboard" class="btn btn-primary">Refresh</button>
      </div>
    </div>
    <div class="alert alert-info mb-4" v-if="user">
      <strong>{{ greeting }}, {{ user.name }}!</strong> Welcome to the Paper Reel Stock Dashboard.
    </div>

    <!-- Loading State -->
    <div v-if="!dashboard.last_updated" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading dashboard data...</p>
    </div>

    <!-- Dashboard Content -->
    <div v-else>
    <div v-if="dashboard.low_stock_alerts && dashboard.low_stock_alerts.length" class="alert alert-danger mb-4">
      <h5><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</h5>
      <ul class="mb-0">
        <li v-for="alert in dashboard.low_stock_alerts" :key="alert.quality">
          {{ alert.quality }}: Only {{ alert.count }} reels remaining
        </li>
      </ul>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4" v-if="canViewDashboard && dashboard.total_reels_in_stock !== undefined">
      <div class="col-md-3">
        <div class="card text-white bg-primary">
          <div class="card-body text-center">
            <i class="bi bi-boxes display-6 mb-2"></i>
            <h5 class="card-title mb-2">Total Reels in Stock</h5>
            <h3 class="mb-0">{{ formattedTotalReels }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3" v-if="canSeeAmounts">
        <div class="card text-white bg-success">
          <div class="card-body text-center">
            <i class="bi bi-box-seam display-6 mb-2"></i>
            <h5 class="card-title mb-2">Total Weight in Stock</h5>
            <h3 class="mb-0">{{ formattedTotalWeight }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3" v-if="canSeeAmounts">
        <div class="card text-white bg-warning">
          <div class="card-body text-center">
            <i class="bi bi-graph-up display-6 mb-2"></i>
            <h5 class="card-title mb-2">Consumption Efficiency</h5>
            <h3 class="mb-0">{{ formattedEfficiency }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-info">
          <div class="card-body text-center">
            <i class="bi bi-exclamation-triangle display-6 mb-2"></i>
            <h5 class="card-title mb-2">Low Stock Alerts</h5>
            <h3 class="mb-0">{{ formattedLowStockAlerts }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Stock in Hand by Quality</h5>
          </div>
          <div class="card-body">
            <canvas id="stockChart" width="400" height="300"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Stock Distribution (Pie Chart)</h5>
          </div>
          <div class="card-body">
            <canvas id="stockPieChart" width="400" height="300"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Consumption by Quality (Last {{ timeRange }} days)</h5>
          </div>
          <div class="card-body">
            <canvas id="consumptionChart" width="400" height="300"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Monthly Consumption Trend</h5>
          </div>
          <div class="card-body">
            <canvas id="trendChart" width="400" height="300"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Stock Movement (Last {{ timeRange }} days)</h5>
          </div>
          <div class="card-body">
            <canvas id="movementChart" width="400" height="300"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Consumption Efficiency</h5>
          </div>
          <div class="card-body d-flex justify-content-center">
            <canvas id="efficiencyGauge" width="300" height="300"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Tables Section -->
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Recent Supplier Updates</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Supplier</th>
                    <th>Quality</th>
                    <th>Date</th>
                    <th>Weight</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="update in dashboard.supplier_updates" :key="update.name + update.receiving_date">
                    <td>{{ update.name }}</td>
                    <td>{{ update.quality }}</td>
                    <td>{{ formatDate(update.receiving_date) }}</td>
                    <td>{{ update.original_weight }} kg</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Low Stock Alerts</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Quality</th>
                    <th>Remaining Count</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="alert in dashboard.low_stock_alerts" :key="alert.quality" class="table-danger">
                    <td>{{ alert.quality }}</td>
                    <td>{{ alert.count }}</td>
                    <td><span class="badge bg-danger">{{ alert.status }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-muted mt-4">
      <small>Last updated: {{ dashboard.last_updated ? formatDateTime(dashboard.last_updated) : 'Never' }}</small>
    </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    user: {
      type: Object,
      default: null
    },
    canViewDashboard: {
      type: Boolean,
      default: true
    },
    canSeeAmounts: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      dashboard: {},
      timeRange: 30,
      charts: {}
    };
  },
  computed: {
    totalBalanceWeight() {
      return this.dashboard.total_weight_in_stock || 0;
    },
    formattedTotalReels() {
      const count = Number(this.dashboard.total_reels_in_stock) || 0;
      return `${count.toLocaleString('en-US')} Reels`;
    },
    formattedTotalWeight() {
      const weight = Math.round(this.totalBalanceWeight);
      return `${weight.toLocaleString('en-US')} Kg`;
    },
    formattedEfficiency() {
      const efficiency = Number(this.dashboard.efficiency_percentage);
      if (Number.isFinite(efficiency)) {
        return `${efficiency.toFixed(1)}%`;
      }
      return '0.0%';
    },
    formattedLowStockAlerts() {
      const count = this.dashboard.low_stock_alerts ? this.dashboard.low_stock_alerts.length : 0;
      return count.toLocaleString('en-US');
    },
    greeting() {
      const hour = new Date().getHours();
      if (hour < 12) return 'Good Morning';
      if (hour < 17) return 'Good Afternoon';
      return 'Good Evening';
    }
  },
  mounted() {
    if (this.user) {
      this.setAuthAndFetch();
    }
  },
  watch: {
    user(newVal) {
      if (newVal) {
        this.setAuthAndFetch();
      }
    }
  },
  methods: {
    setAuthAndFetch() {
      if (localStorage.getItem('token')) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
      }
      this.fetchDashboard();
    },
    fetchDashboard() {
      // Fetch dashboard data from backend
      axios.get('/api/dashboard').then(response => {
        this.dashboard = response.data;
        this.$nextTick(() => {
          this.renderCharts();
        });
      }).catch(error => {
        console.error('Error fetching dashboard:', error);
      });
    },
    renderCharts() {
      this.destroyCharts();
      this.renderStockChart();
      this.renderStockPieChart();
      this.renderConsumptionChart();
      this.renderTrendChart();
      this.renderMovementChart();
      this.renderEfficiencyGauge();
    },
    destroyCharts() {
      Object.values(this.charts).forEach(chart => {
        if (chart) chart.destroy();
      });
      this.charts = {};
    },
    renderStockChart() {
      const ctx = document.getElementById('stockChart');
      if (!ctx) return;

      if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
      }

      try {
        const qualities = this.dashboard.stock_by_quality || [];
        this.charts.stockChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: qualities.map(q => q.quality),
            datasets: [{
              label: 'Reels Count',
              data: qualities.map(q => q.count),
              backgroundColor: 'rgba(54, 162, 235, 0.6)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1
            }, {
              label: 'Total Weight (kg)',
              data: qualities.map(q => q.weight ?? 0),
              backgroundColor: 'rgba(255, 99, 132, 0.6)',
              borderColor: 'rgba(255, 99, 132, 1)',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      } catch (error) {
        console.error('Error creating stock chart:', error);
      }
    },
    renderStockPieChart() {
      const ctx = document.getElementById('stockPieChart');
      if (!ctx) return;

      if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
      }

      try {
        const qualities = this.dashboard.stock_by_quality || [];
        this.charts.stockPieChart = new Chart(ctx, {
          type: 'pie',
          data: {
            labels: qualities.map(q => q.quality),
            datasets: [{
              data: qualities.map(q => q.weight ?? 0),
              backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
              ]
            }]
          },
          options: {
            responsive: true
          }
        });
      } catch (error) {
        console.error('Error creating stock pie chart:', error);
      }
    },
    renderConsumptionChart() {
      const ctx = document.getElementById('consumptionChart');
      if (!ctx) return;

      if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
      }

      try {
        const consumptionData = this.dashboard.consumption_data || [];
        const dates = [...new Set(consumptionData.map(d => d.date))].sort();
        const qualities = [...new Set(consumptionData.map(d => d.quality))];

        const datasets = qualities.map((quality, index) => ({
          label: quality,
          data: dates.map(date => {
            const entry = consumptionData.find(d => d.date === date && d.quality === quality);
            return entry ? entry.total_consumed : 0;
          }),
          borderColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'][index % 4],
          backgroundColor: ['rgba(255, 99, 132, 0.1)', 'rgba(54, 162, 235, 0.1)', 'rgba(255, 206, 86, 0.1)', 'rgba(75, 192, 192, 0.1)'][index % 4],
          tension: 0.1
        }));

        this.charts.consumptionChart = new Chart(ctx, {
          type: 'line',
          data: { labels: dates, datasets },
          options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
          }
        });
      } catch (error) {
        console.error('Error creating consumption chart:', error);
      }
    },
    renderTrendChart() {
      const ctx = document.getElementById('trendChart');
      if (!ctx) return;

      if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
      }

      try {
        const monthlyData = this.dashboard.monthly_consumption || [];
        this.charts.trendChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: monthlyData.map(d => d.month),
            datasets: [{
              label: 'Monthly Consumption',
              data: monthlyData.map(d => d.total_consumed),
              borderColor: '#36A2EB',
              backgroundColor: 'rgba(54, 162, 235, 0.1)',
              tension: 0.1
            }]
          },
          options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
          }
        });
      } catch (error) {
        console.error('Error creating trend chart:', error);
      }
    },
    renderMovementChart() {
      const ctx = document.getElementById('movementChart');
      if (!ctx) return;

      if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
      }

      try {
        const movementData = this.dashboard.stock_movement || [];
        const dates = [...new Set(movementData.map(d => d.date))].sort();

        this.charts.movementChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: dates,
            datasets: [{
              label: 'Stock Movement',
              data: dates.map(date => {
                const entry = movementData.find(d => d.date === date);
                return entry ? entry.added_weight : 0;
              }),
              backgroundColor: (ctx) => {
                const value = ctx.parsed.y;
                return value > 0 ? 'rgba(75, 192, 192, 0.6)' : 'rgba(255, 99, 132, 0.6)';
              }
            }]
          },
          options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
          }
        });
      } catch (error) {
        console.error('Error creating movement chart:', error);
      }
    },
    renderEfficiencyGauge() {
      const ctx = document.getElementById('efficiencyGauge');
      if (!ctx) return;

      if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
      }

      try {
        const efficiency = this.dashboard.efficiency_percentage || 0;
        this.charts.efficiencyGauge = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: ['Efficiency', 'Remaining'],
            datasets: [{
              data: [efficiency, 100 - efficiency],
              backgroundColor: ['#4BC0C0', '#E7E9ED']
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: (ctx) => `${ctx.label}: ${ctx.parsed}%`
                }
              }
            }
          }
        });
      } catch (error) {
        console.error('Error creating efficiency gauge:', error);
      }
    },
    formatDate(dateString) {
      if (!dateString) {
        return '';
      }
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) {
        return '';
      }
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    },
    formatDateTime(dateString) {
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) {
        return '-';
      }
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    }
  },
  beforeUnmount() {
    this.destroyCharts();
  }
};
</script>

<style scoped>
.card {
  margin-bottom: 1rem;
}
</style>
