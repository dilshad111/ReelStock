<template>
  <div class="container-fluid dashboard-container">
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
      <h2 class="dashboard-title"><i class="bi bi-graph-up-arrow"></i> Reel Analytics Dashboard</h2>
      <div class="d-flex gap-2">
        <select v-model="timeRange" @change="fetchDashboard" class="form-select range-select shadow-sm">
          <option value="7">Last 7 Days</option>
          <option value="30">Last 30 Days</option>
          <option value="90">Last 90 Days</option>
          <option value="180">Last 6 Months</option>
        </select>
        <button @click="fetchDashboard" class="btn btn-primary shadow-sm"><i class="bi bi-arrow-clockwise"></i> Sync Data</button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-grow text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Analyzing reel movements...</p>
    </div>

    <div v-else-if="dashboard">
      <!-- TOP KPI CARDS -->
      <div class="row mb-4 g-3">
        <div class="col-md-3">
          <div class="card action-widget border-start border-primary border-4 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="text-primary fw-bold mb-1">Reels in Stock</h6>
                  <h3 class="mb-0">{{ dashboard.kpis.reels_in_stock }}</h3>
                </div>
                <div class="widget-icon bg-primary-soft"><i class="bi bi-box-seam text-primary"></i></div>
              </div>
              <p class="text-muted small mt-2 mb-0">Total available units</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card action-widget border-start border-success border-4 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="text-success fw-bold mb-1">Weight in Stock (kg)</h6>
                  <h3 class="mb-0">{{ formatNumber(dashboard.kpis.weight_in_stock) }}</h3>
                </div>
                <div class="widget-icon bg-success-soft"><i class="bi bi-database-check text-success"></i></div>
              </div>
              <p class="text-muted small mt-2 mb-0">Total physical stock</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card action-widget border-start border-info border-4 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="text-info fw-bold mb-1">Reels Used (Month)</h6>
                  <h3 class="mb-0">{{ dashboard.kpis.reels_used_month }}</h3>
                </div>
                <div class="widget-icon bg-info-soft"><i class="bi bi-arrow-up-right-circle text-info"></i></div>
              </div>
              <p class="text-muted small mt-2 mb-0">Total reels issued</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card action-widget border-start border-warning border-4 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="text-warning fw-bold mb-1">Weight Used (Month)</h6>
                  <h3 class="mb-0">{{ formatNumber(dashboard.kpis.weight_used_month) }}</h3>
                </div>
                <div class="widget-icon bg-warning-soft"><i class="bi bi-graph-up text-warning"></i></div>
              </div>
              <p class="text-muted small mt-2 mb-0">Monthly net consumption</p>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION: STOCK BY LOCATION -->
      <div class="section-container mb-5">
        <h4 class="section-title"><i class="bi bi-geo-alt"></i> Stock by Location</h4>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="card location-card border-0 shadow-sm bg-white overflow-hidden">
                <div class="card-body p-0">
                    <div class="d-flex">
                        <div class="bg-primary text-white d-flex align-items-center justify-content-center px-4" style="width: 80px;">
                            <i class="bi bi-building fs-2"></i>
                        </div>
                        <div class="p-3 flex-grow-1">
                            <h5 class="fw-bold mb-1">GoDown</h5>
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <h3 class="mb-0">{{ dashboard.location_breakdown.godown.count }} <small class="fs-6 text-muted">Reels</small></h3>
                                </div>
                                <div class="text-end">
                                    <h3 class="mb-0 text-primary">{{ formatNumber(dashboard.location_breakdown.godown.weight) }} <small class="fs-6 text-muted">kg</small></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card location-card border-0 shadow-sm bg-white overflow-hidden">
                <div class="card-body p-0">
                    <div class="d-flex">
                        <div class="bg-indigo text-white d-flex align-items-center justify-content-center px-4" style="width: 80px;">
                            <i class="bi bi-tools fs-2"></i>
                        </div>
                        <div class="p-3 flex-grow-1">
                            <h5 class="fw-bold mb-1">Factory</h5>
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <h3 class="mb-0">{{ dashboard.location_breakdown.factory.count }} <small class="fs-6 text-muted">Reels</small></h3>
                                </div>
                                <div class="text-end">
                                    <h3 class="mb-0 text-indigo">{{ formatNumber(dashboard.location_breakdown.factory.weight) }} <small class="fs-6 text-muted">kg</small></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 1: STOCK OVERVIEW -->
      <div class="section-container mb-5">
        <h4 class="section-title"><i class="bi bi-box-seam"></i> Godown Stock Overview</h4>
        <div class="row g-4">
          <div class="col-md-8">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="fw-bold mb-0">Current Stock by Quality (kg)</h6>
              </div>
              <div class="card-body pt-0">
                <canvas id="stockByQualityChart" height="280"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="fw-bold mb-0">Full vs Partial Distribution</h6>
                </div>
                <div class="card-body pt-0 d-flex flex-column align-items-center justify-content-center">
                    <canvas id="stockStatusPieChart" height="200"></canvas>
                    <div class="mt-3 text-center">
                        <h2 class="mb-0 text-primary">{{ formatNumber(dashboard.stock_overview.total_weight) }}</h2>
                        <span class="text-muted">Total Available Weight (kg)</span>
                    </div>
                </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card shadow-sm border-0">
              <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="fw-bold mb-0">Current Stock by Size / GSM (kg)</h6>
              </div>
              <div class="card-body pt-0">
                <canvas id="stockBySizeGsmChart" height="250"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card shadow-sm border-0">
              <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="fw-bold mb-0">Supplier-wise Current Stock (kg)</h6>
              </div>
              <div class="card-body pt-0">
                <canvas id="stockBySupplierChart" height="250"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 2 & 3: INWARD & CONSUMPTION -->
      <div class="row mb-5 g-4">
        <div class="col-md-6">
          <div class="section-container">
            <h4 class="section-title text-success"><i class="bi bi-arrow-down-left-circle"></i> Inward (Receiving) Analysis</h4>
            <div class="card shadow-sm border-0">
              <div class="card-body">
                <canvas id="receivingOverTimeChart" height="200"></canvas>
                <div class="row mt-4">
                    <div class="col-6">
                        <h6 class="text-muted small">Received by Supplier</h6>
                        <canvas id="receivingBySupplierChart" height="150"></canvas>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted small">Received by Quality</h6>
                        <canvas id="receivingByQualityChart" height="150"></canvas>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="section-container">
            <h4 class="section-title text-primary"><i class="bi bi-arrow-up-right-circle"></i> Issue (Consumption) Analysis</h4>
            <div class="card shadow-sm border-0">
              <div class="card-body">
                <canvas id="consumptionOverTimeChart" height="200"></canvas>
                <div class="mt-4">
                    <h6 class="text-muted small">Issue vs Return (kg)</h6>
                    <canvas id="issueVsReturnStackedChart" height="150"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 4 & 5: PARTIALS & SUPPLIER RETURNS -->
      <div class="row mb-5 g-4">
          <div class="col-md-12">
            <div class="section-container">
                <h4 class="section-title text-warning"><i class="bi bi-arrow-repeat"></i> Partial Reel & Supplier Returns</h4>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-transparent border-0 pt-3 pb-0"><h6 class="fw-bold">Partial Returns Trend</h6></div>
                            <div class="card-body">
                                <canvas id="partialReturnsTrendChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-transparent border-0 pt-3 pb-0"><h6 class="fw-bold">Supplier Return Rejection Rate (%)</h6></div>
                            <div class="card-body">
                                <canvas id="supplierRejectionRateChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-transparent border-0 pt-3 pb-0"><h6 class="fw-bold">Supplier Return Reasons</h6></div>
                            <div class="card-body">
                                <canvas id="returnReasonsPieChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
      </div>

      <!-- SECTION 6: QUALITY CONTROL -->
      <div class="section-container mb-5">
        <h4 class="section-title text-purple"><i class="bi bi-patch-check"></i> Quality Control & Traceability</h4>
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="fw-bold text-muted small px-3">Quality-wise Issue vs Return Weight</h6>
                        <canvas id="qualityIssueVsReturnChart" height="150"></canvas>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded shadow-inner">
                            <h6 class="fw-bold mb-3">Top Remaining Balances</h6>
                            <div v-for="reel in dashboard.partial_reel_tracking.top_remaining" :key="reel.reel_no" class="d-flex justify-content-between mb-2 pb-1 border-bottom border-white shadow-sm px-2 py-1 bg-white rounded-pill">
                                <span class="small fw-bold">{{ reel.reel_no }}</span>
                                <span class="small text-primary fw-bold">{{ formatNumber(reel.weight) }} kg</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      dashboard: null,
      timeRange: 30,
      loading: true,
      charts: {}
    };
  },
  mounted() {
    this.fetchDashboard();
  },
  methods: {
    fetchDashboard() {
      this.loading = true;
      axios.get(`/api/dashboard?range=${this.timeRange}`).then(response => {
        this.dashboard = response.data;
        this.loading = false;
        this.$nextTick(() => {
          this.renderAllCharts();
        });
      }).catch(error => {
        console.error('Error fetching dashboard:', error);
        this.loading = false;
      });
    },
    renderAllCharts() {
      // Destroy existing charts to prevent memory leaks/overlap
      Object.values(this.charts).forEach(c => c.destroy());
      this.charts = {};

      const d = this.dashboard;

      // --- SECTION 1 ---
      // Stock by Quality
      this.charts.stockByQuality = new Chart(document.getElementById('stockByQualityChart'), {
        type: 'bar',
        data: {
          labels: d.stock_overview.by_quality.map(i => i.name),
          datasets: [{
            label: 'Reel Count',
            data: d.stock_overview.by_quality.map(i => i.count),
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            borderColor: '#6366f1',
            borderWidth: 1,
            yAxisID: 'yCount'
          }, {
            label: 'Total Weight (kg)',
            data: d.stock_overview.by_quality.map(i => i.weight),
            backgroundColor: 'rgba(16, 185, 129, 0.5)',
            borderColor: '#10b981',
            borderWidth: 1,
            yAxisID: 'yWeight'
          }]
        },
        options: {
            scales: {
                yWeight: { position: 'right', title: { display: true, text: 'Weight (kg)' } },
                yCount: { position: 'left', title: { display: true, text: 'Reels' } }
            }
        }
      });

      // Stock Status Pie
      this.charts.stockStatusPie = new Chart(document.getElementById('stockStatusPieChart'), {
        type: 'doughnut',
        data: {
          labels: d.stock_overview.status_distribution.map(i => i.label),
          datasets: [{
            data: d.stock_overview.status_distribution.map(i => i.value),
            backgroundColor: ['#6366f1', '#f59e0b']
          }]
        },
        options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
      });

      // Stock by Size/GSM
      this.charts.stockBySizeGsm = new Chart(document.getElementById('stockBySizeGsmChart'), {
        type: 'bar',
        data: {
          labels: d.stock_overview.by_size_gsm.map(i => i.label),
          datasets: [{
            label: 'Weight (kg)',
            data: d.stock_overview.by_size_gsm.map(i => i.weight),
            backgroundColor: 'rgba(239, 68, 68, 0.7)'
          }]
        }
      });

      // Stock by Supplier
      this.charts.stockBySupplier = new Chart(document.getElementById('stockBySupplierChart'), {
        type: 'bar',
        data: {
          labels: d.stock_overview.by_supplier.map(i => i.supplier),
          datasets: [{
            label: 'Weight (kg)',
            data: d.stock_overview.by_supplier.map(i => i.weight),
            backgroundColor: 'rgba(59, 130, 246, 0.7)'
          }]
        }
      });

      // --- SECTION 2 ---
      // Receiving Over Time
      this.charts.receivingTrend = new Chart(document.getElementById('receivingOverTimeChart'), {
        type: 'line',
        data: {
          labels: Object.keys(d.receiving_analysis.over_time),
          datasets: [{
            label: 'Received Weight (kg)',
            data: Object.values(d.receiving_analysis.over_time),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.3
          }]
        }
      });

      this.charts.receivingSupplier = new Chart(document.getElementById('receivingBySupplierChart'), {
        type: 'pie',
        data: {
          labels: Object.keys(d.receiving_analysis.by_supplier),
          datasets: [{ data: Object.values(d.receiving_analysis.by_supplier), backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'] }]
        },
        options: { plugins: { legend: { display: false } } }
      });

      this.charts.receivingQuality = new Chart(document.getElementById('receivingByQualityChart'), {
        type: 'pie',
        data: {
          labels: Object.keys(d.receiving_analysis.by_quality),
          datasets: [{ data: Object.values(d.receiving_analysis.by_quality), backgroundColor: ['#ef4444', '#8b5cf6', '#3b82f6', '#10b981', '#f59e0b'] }]
        },
        options: { plugins: { legend: { display: false } } }
      });

      // --- SECTION 3 ---
      // Consumption Trend
      this.charts.consumptionTrend = new Chart(document.getElementById('consumptionOverTimeChart'), {
        type: 'line',
        data: {
          labels: Object.keys(d.consumption_analysis.over_time),
          datasets: [{
            label: 'Issued Weight (kg)',
            data: Object.values(d.consumption_analysis.over_time),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.3
          }]
        }
      });

      // Issue vs Return Stacked
      const issueReturnDates = Object.keys(d.consumption_analysis.issue_vs_return);
      this.charts.issueReturnStacked = new Chart(document.getElementById('issueVsReturnStackedChart'), {
        type: 'bar',
        data: {
          labels: issueReturnDates,
          datasets: [
            { label: 'Issued', data: issueReturnDates.map(k => d.consumption_analysis.issue_vs_return[k].issued), backgroundColor: '#6366f1' },
            { label: 'Returned', data: issueReturnDates.map(k => d.consumption_analysis.issue_vs_return[k].returned), backgroundColor: '#f1f5f9' }
          ]
        },
        options: { scales: { x: { stacked: true }, y: { stacked: true } } }
      });

      // --- SECTION 4 & 5 ---
      this.charts.partialTrend = new Chart(document.getElementById('partialReturnsTrendChart'), {
        type: 'line',
        data: {
          labels: Object.keys(d.partial_reel_tracking.returns_over_time),
          datasets: [{ label: 'Partial Returns', data: Object.values(d.partial_reel_tracking.returns_over_time), borderColor: '#f59e0b', tension: 0.4 }]
        }
      });

      this.charts.rejectionRate = new Chart(document.getElementById('supplierRejectionRateChart'), {
        type: 'bar',
        data: {
          labels: Object.keys(d.supplier_return_tracking.rejection_rate),
          datasets: [{ label: 'Rate (%)', data: Object.values(d.supplier_return_tracking.rejection_rate), backgroundColor: '#ef4444' }]
        },
        options: { indexAxis: 'y' }
      });

      this.charts.returnReasons = new Chart(document.getElementById('returnReasonsPieChart'), {
        type: 'pie',
        data: {
          labels: Object.keys(d.supplier_return_tracking.reasons),
          datasets: [{ data: Object.values(d.supplier_return_tracking.reasons), backgroundColor: ['#ef4444', '#f59e0b', '#64748b'] }]
        }
      });

      // --- SECTION 6 ---
      const qNames = Object.keys(d.quality_control.issue_vs_return);
      this.charts.qualityTrace = new Chart(document.getElementById('qualityIssueVsReturnChart'), {
        type: 'bar',
        data: {
          labels: qNames,
          datasets: [
            { label: 'Issued', data: qNames.map(n => d.quality_control.issue_vs_return[n].issued), backgroundColor: '#6366f1' },
            { label: 'Returned', data: qNames.map(n => d.quality_control.issue_vs_return[n].returned), backgroundColor: '#cbd5e1' }
          ]
        },
        options: { scales: { x: { stacked: true }, y: { stacked: true } } }
      });
    },
    formatNumber(val) {
      return Number(val || 0).toLocaleString(undefined, { maximumFractionDigits: 1 });
    }
  },
  beforeUnmount() {
    Object.values(this.charts).forEach(c => c.destroy());
  }
};
</script>

<style scoped>
.dashboard-container {
    background-color: #f8fafc;
    min-height: 100vh;
}
.dashboard-title {
    color: #1e293b;
    font-weight: 700;
}
.range-select {
    width: 180px;
    border-radius: 8px;
}
.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #334155;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
}
.card {
    border-radius: 12px;
    border: none;
}
.action-widget {
    transition: transform 0.2s;
}
.action-widget:hover {
    transform: translateY(-4px);
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
.bg-danger-soft { background-color: #fee2e2; }
.bg-warning-soft { background-color: #fef3c7; }
.bg-info-soft { background-color: #e0f2fe; }
.bg-dark-soft { background-color: #f1f5f9; }
.bg-indigo { background-color: #6366f1; }
.text-indigo { color: #6366f1; }
.text-purple { color: #8b5cf6; }
</style>
