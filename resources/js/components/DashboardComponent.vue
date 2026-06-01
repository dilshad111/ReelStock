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
          <div class="row mb-4 g-3 mt-1">
            <div class="col-md-3" v-for="(kpi, key) in operationalKpis" :key="key">
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

          <div class="section-container mb-4">
            <h6 class="section-title text-muted text-uppercase tracking-wider small fw-bold mb-3"><i class="bi bi-geo-alt"></i> Stock by Location</h6>
            <div class="row g-3">
              <div class="col-md-6" v-for="(loc, name) in dashboard.location_breakdown" :key="name">
                <div class="card location-card border-0 shadow-sm bg-white overflow-hidden h-100">
                    <div class="card-body p-0">
                        <div class="d-flex h-100">
                            <div :class="[name === 'godown' ? 'bg-primary' : 'bg-indigo', 'text-white d-flex align-items-center justify-content-center']" style="width: 70px; min-height: 80px;">
                                <i :class="['bi', name === 'godown' ? 'bi-building' : 'bi-tools', 'fs-3']"></i>
                            </div>
                            <div class="p-3 flex-grow-1 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold mb-0 text-uppercase text-slate-800">{{ name }}</h5>
                                    <p class="mb-0 text-muted fs-5 fw-bold">{{ loc.count }} <small class="fs-6 fw-normal text-muted">Reels</small></p>
                                </div>
                                <div class="text-end">
                                    <h3 :class="['mb-0 fw-bold', name === 'godown' ? 'text-primary' : 'text-indigo']">{{ formatNumber(loc.weight) }} <small class="fs-6 fw-normal text-muted">kg</small></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-5">
            <div class="col-md-7">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-water"></i> Inventory Flow Trend (Inward vs Outward)</h6>
                    <el-tag size="small" type="primary" effect="plain">Last {{ timeRange }} Days</el-tag>
                </div>
                <div class="card-body pt-0">
                  <canvas id="inventoryFlowChart" height="220"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-5">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-rulers"></i> Consumption by Reel Size (Volume)</h6></div>
                <div class="card-body pt-0">
                  <canvas id="usageBySizeChart" height="220"></canvas>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-5">
            <div class="col-12">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                  <h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-bar-chart-line"></i> Last 12 Months Reel Receipt & Usage</h6>
                  <el-tag size="small" type="success" effect="plain">Tonnage</el-tag>
                </div>
                <div class="card-body pt-0">
                  <canvas id="receiptUsage12MonthChart" height="110"></canvas>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-5">
            <div class="col-md-8">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-bar-chart"></i> Current Stock by Quality (Volume & Count)</h6>
                    <el-tag size="small" type="info" effect="plain">Weight vs Units</el-tag>
                </div>
                <div class="card-body pt-0"><canvas id="stockByQualityChart" height="280"></canvas></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0 text-slate-700">Stock Mix Profile</h6></div>
                <div class="card-body pt-0 d-flex flex-column align-items-center justify-content-center">
                  <canvas id="stockStatusPieChart" height="220"></canvas>
                  <div class="mt-3 text-center">
                    <h2 class="mb-0 text-primary fw-bold">{{ formatNumber(dashboard.stock_overview.total_weight) }}</h2>
                    <span class="text-muted small text-uppercase tracking-wider">Total kg Available</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-5">
            <div class="col-md-5">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-award"></i> Top 10 Heaviest Reels in Stock</h6></div>
                <div class="card-body pt-0">
                  <canvas id="topReelsChart" height="300"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-7">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between">
                  <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-arrow-return-left"></i> Supplier Returns & Rejections</h6>
                  <span class="badge bg-danger-soft text-danger">Last {{ timeRange }} Days</span>
                </div>
                <div class="card-body pt-0">
                    <div class="row align-items-center">
                        <div class="col-md-8 border-end">
                            <canvas id="returnsTrendChart" height="220"></canvas>
                        </div>
                        <div class="col-md-4 text-center">
                            <p class="small text-muted mb-2 text-uppercase fw-bold">Reason Breakdown</p>
                            <canvas id="returnReasonsChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </el-tab-pane>

      <el-tab-pane label="Live Stock (High Density)" name="livestock">
        <div v-if="loadingOperational" class="text-center py-5">
          <div class="spinner-grow text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
          <p class="mt-3 text-muted">Scanning godown...</p>
        </div>
        <div v-else-if="dashboard">
          <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
              <h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-list-task"></i> Comprehensive Stock List</h6>
              <div class="d-flex gap-2">
                <input v-model="stockSearch" type="text" class="form-control form-control-sm" style="width: 250px;" placeholder="Filter by Reel #, Quality, or Supplier...">
              </div>
            </div>
            <div class="card-body pt-0">
              <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-sm table-hover text-nowrap align-middle high-density-table">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th>Reel No.</th>
                      <th>Paper Quality</th>
                      <th>Supplier</th>
                      <th class="text-center">Size</th>
                      <th class="text-center">Original Wt</th>
                      <th class="text-center">Balance Wt</th>
                      <th class="text-center">Status</th>
                      <th class="text-center">Location</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="reel in filteredStockList" :key="reel.id">
                      <td class="fw-bold text-primary">{{ reel.reel_no }}</td>
                      <td>{{ reel.paper_quality?.quality }}</td>
                      <td class="small">{{ reel.supplier?.name }}</td>
                      <td class="text-center">{{ reel.reel_size }}"</td>
                      <td class="text-center text-muted small">{{ formatNumber(reel.original_weight) }}</td>
                      <td class="text-center fw-bold">{{ formatNumber(reel.balance_weight || reel.original_weight) }}</td>
                      <td class="text-center">
                        <span :class="['badge rounded-pill', reel.balance_weight < reel.original_weight ? 'bg-warning-soft text-warning' : 'bg-success-soft text-success']">
                          {{ reel.balance_weight < reel.original_weight ? 'Partial' : 'Full' }}
                        </span>
                      </td>
                      <td class="text-center">
                        <span :class="['badge rounded-pill', getReelLocation(reel) === 'Factory' ? 'bg-indigo-soft text-indigo' : 'bg-info-soft text-info']">
                          {{ getReelLocation(reel) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
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

          <!-- 12 MONTHS HISTORY BAR GRAPHS (Dual Axis: Amount & Weight) -->
          <div class="row g-4 mb-5">
            <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                  <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-cart-check-fill me-1"></i> Monthly Purchase Analysis (Last 12 Months)</h6>
                  <el-tag size="small" type="success" effect="plain">Volume vs Value</el-tag>
                </div>
                <div class="card-body pt-0">
                  <canvas id="mgmtReceived12Chart" height="240"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                  <h6 class="fw-bold mb-0 text-indigo"><i class="bi bi-box-arrow-up me-1"></i> Monthly Consumption Analysis (Last 12 Months)</h6>
                  <el-tag size="small" type="primary" effect="plain">Volume vs Value</el-tag>
                </div>
                <div class="card-body pt-0">
                  <canvas id="mgmtConsumption12Chart" height="240"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- TOP INWARD ANALYSIS (By Quality & Supplier) -->
          <div class="row g-4 mb-5">
            <div class="col-md-4">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0 text-slate-700">Inward by Quality (Monthly)</h6></div>
                <div class="card-body d-flex align-items-center justify-content-center">
                  <canvas id="mgmtInwardByQualityChart" height="280"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-8">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h6 class="fw-bold mb-0 text-slate-700">Inward by Supplier (Monthly Volume)</h6></div>
                <div class="card-body">
                  <canvas id="mgmtInwardBySupplierChart" height="280"></canvas>
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
import { mapState, mapActions, mapWritableState } from 'pinia';
import { useDashboardStore } from '../stores/useDashboardStore';

export default {
  props: ['user', 'canViewManagement', 'initialTab'],
  data() {
    return {
      activeTab: this.initialTab || 'operational',
      charts: {},
      stockSearch: ''
    };
  },
  computed: {
    ...mapState(useDashboardStore, {
      dashboard: 'operationalData',
      managementData: 'managementData',
      loadingOperational: state => state.loading.operational,
      loadingManagement: state => state.loading.management,
    }),
    ...mapWritableState(useDashboardStore, ['timeRange']),
    filteredStockList() {
      if (!this.dashboard?.full_stock) return [];
      if (!this.stockSearch) return this.dashboard.full_stock;
      
      const search = this.stockSearch.toLowerCase();
      return this.dashboard.full_stock.filter(reel => 
        reel.reel_no.toLowerCase().includes(search) ||
        (reel.paper_quality?.quality && reel.paper_quality.quality.toLowerCase().includes(search)) ||
        (reel.supplier?.name && reel.supplier.name.toLowerCase().includes(search))
      );
    },
    getReelLocation() {
      return (reel) => {
        const latestReturn = reel.returns?.[0]; // Assumes returns are ordered descending in backend or frontend
        return latestReturn ? latestReturn.return_location : 'GoDown';
      };
    },
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
    this.setupRealtimeListener();
  },
  methods: {
    ...mapActions(useDashboardStore, [
      'fetchOperationalDashboard',
      'fetchManagementDashboard',
      'setupRealtimeListener'
    ]),
    handleTabChange() { this.fetchActiveDashboard(); },
    fetchActiveDashboard() {
      if (this.activeTab === 'operational') {
        this.fetchOperationalDashboard().then(() => {
          this.$nextTick(() => this.renderOperationalCharts());
        });
      } else {
        this.fetchManagementDashboard().then(() => {
          this.$nextTick(() => this.renderManagementCharts());
        });
      }
    },
    renderOperationalCharts() {
      this.destroyCharts();
      const d = this.dashboard; if (!d) return;

      // 1. Stock by Quality
      this.charts.stockByQuality = new Chart(document.getElementById('stockByQualityChart'), {
        type: 'bar',
        data: {
          labels: d.stock_overview.by_quality.map(i => i.name),
          datasets: [
              { label: 'Weight (kg)', data: d.stock_overview.by_quality.map(i => i.weight), backgroundColor: 'rgba(99, 102, 241, 0.7)', borderRadius: 4, yAxisID: 'yWeight' },
              { label: 'Reel Count', data: d.stock_overview.by_quality.map(i => i.count), type: 'line', borderColor: '#f59e0b', backgroundColor: '#f59e0b', tension: 0.3, pointRadius: 3, yAxisID: 'yCount' }
          ]
        },
        options: { 
            responsive: true,
            scales: { 
                yWeight: { position: 'left', title: { display: true, text: 'Weight (kg)' }, grid: { display: false } }, 
                yCount: { position: 'right', title: { display: true, text: 'Unit Count' } } 
            } 
        }
      });

      // 2. Stock Mix (Full vs Partial)
      this.charts.stockStatusPie = new Chart(document.getElementById('stockStatusPieChart'), {
        type: 'doughnut',
        data: { labels: d.stock_overview.status_distribution.map(i => i.label), datasets: [{ data: d.stock_overview.status_distribution.map(i => i.value), backgroundColor: ['#6366f1', '#f59e0b'], borderWidth: 0 }] },
        options: { cutout: '75%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } } }
      });

      // 3. Top Reels by Weight
      this.charts.topReels = new Chart(document.getElementById('topReelsChart'), {
          type: 'bar',
          data: {
              labels: d.partial_reel_tracking.top_remaining.map(i => i.reel_no),
              datasets: [{ label: 'Remaining Weight (kg)', data: d.partial_reel_tracking.top_remaining.map(i => i.weight), backgroundColor: 'rgba(16, 185, 129, 0.7)', borderRadius: 4 }]
          },
          options: { indexAxis: 'y', plugins: { legend: { display: false } } }
      });

      // 4. Returns Trend
      this.charts.returnsTrend = new Chart(document.getElementById('returnsTrendChart'), {
          type: 'line',
          data: {
              labels: Object.keys(d.supplier_return_tracking.over_time),
              datasets: [{ label: 'Reels Rejected', data: Object.values(d.supplier_return_tracking.over_time), borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', fill: true, tension: 0.4, pointRadius: 4 }]
          },
          options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } } }
      });

      // 5. Return Reasons
      this.charts.returnReasons = new Chart(document.getElementById('returnReasonsChart'), {
          type: 'pie',
          data: {
              labels: Object.keys(d.supplier_return_tracking.reasons),
              datasets: [{ data: Object.values(d.supplier_return_tracking.reasons), backgroundColor: ['#ef4444', '#f59e0b', '#6366f1', '#10b981'] }]
          },
          options: { plugins: { legend: { display: false } } }
      });

      // 6. Inventory Flow Trend
      this.charts.inventoryFlow = new Chart(document.getElementById('inventoryFlowChart'), {
          type: 'line',
          data: {
              labels: Object.keys(d.receiving_analysis.over_time),
              datasets: [
                  { label: 'Incoming (kg)', data: Object.values(d.receiving_analysis.over_time), borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.05)', fill: true, tension: 0.3 },
                  { label: 'Usage (kg)', data: Object.values(d.consumption_analysis.over_time), borderColor: '#6366f1', backgroundColor: 'rgba(99, 102, 241, 0.05)', fill: true, tension: 0.3 }
              ]
          },
          options: { interaction: { intersect: false, mode: 'index' }, scales: { x: { grid: { display: false } } } }
      });

      // 7. Last 12 Months Receipt vs Usage (Tonnage)
      const monthlyReceiptUsage = d.receiving_analysis.monthly_receipt_usage_12 || [];
      this.charts.receiptUsage12Month = new Chart(document.getElementById('receiptUsage12MonthChart'), {
          type: 'bar',
          data: {
              labels: monthlyReceiptUsage.map(i => i.label || i.month),
              datasets: [
                  { label: 'Received (tons)', data: monthlyReceiptUsage.map(i => i.received_tons), backgroundColor: 'rgba(16, 185, 129, 0.75)', borderColor: '#10b981', borderWidth: 1, borderRadius: 5 },
                  { label: 'Usage (tons)', data: monthlyReceiptUsage.map(i => i.used_tons), backgroundColor: 'rgba(99, 102, 241, 0.75)', borderColor: '#6366f1', borderWidth: 1, borderRadius: 5 }
              ]
          },
          options: {
              responsive: true,
              interaction: { intersect: false, mode: 'index' },
              plugins: {
                  tooltip: {
                      callbacks: {
                          label: context => `${context.dataset.label}: ${this.formatTons(context.parsed.y)} tons`
                      }
                  }
              },
              scales: {
                  x: { grid: { display: false } },
                  y: {
                      beginAtZero: true,
                      title: { display: true, text: 'Metric Tons' },
                      ticks: { callback: value => this.formatTons(value) }
                  }
              }
          }
      });

      // 8. Usage by Size
      this.charts.usageBySize = new Chart(document.getElementById('usageBySizeChart'), {
          type: 'bar',
          data: {
              labels: Object.keys(d.consumption_analysis.by_size),
              datasets: [{ label: 'Weight (kg)', data: Object.values(d.consumption_analysis.by_size), backgroundColor: '#8b5cf6', borderRadius: 4 }]
          },
          options: { plugins: { legend: { display: false } } }
      });
    },
    renderManagementCharts() {
      this.destroyCharts();
      const m = this.managementData; if (!m) return;

      const monthlyLabels = m.consumption.monthly_12.map(i => i.month);

      // 1. Monthly Purchase Analysis (Dual Axis)
      this.charts.mgmtRec12 = new Chart(document.getElementById('mgmtReceived12Chart'), {
        type: 'bar',
        data: {
          labels: m.receiving.monthly_12.map(i => i.month),
          datasets: [
            { label: 'Amount (₨)', data: m.receiving.monthly_12.map(i => i.amount), backgroundColor: 'rgba(16, 185, 129, 0.7)', borderRadius: 4, yAxisID: 'yAmount' },
            { label: 'Weight (kg)', data: m.receiving.monthly_12.map(i => i.weight), type: 'line', borderColor: '#3b82f6', backgroundColor: '#3b82f6', tension: 0.4, yAxisID: 'yWeight', pointRadius: 3 }
          ]
        },
        options: { 
            responsive: true,
            scales: { 
                yAmount: { position: 'left', title: { display: true, text: 'Value (₨)' }, grid: { display: false } }, 
                yWeight: { position: 'right', title: { display: true, text: 'Volume (kg)' } } 
            } 
        }
      });

      // 2. Monthly Consumption Analysis (Dual Axis)
      this.charts.mgmtCons12 = new Chart(document.getElementById('mgmtConsumption12Chart'), {
        type: 'bar',
        data: {
          labels: m.consumption.monthly_12.map(i => i.month),
          datasets: [
            { label: 'Amount (₨)', data: m.consumption.monthly_12.map(i => i.amount), backgroundColor: 'rgba(99, 102, 241, 0.7)', borderRadius: 4, yAxisID: 'yAmount' },
            { label: 'Weight (kg)', data: m.consumption.monthly_12.map(i => i.weight), type: 'line', borderColor: '#ef4444', backgroundColor: '#ef4444', tension: 0.4, yAxisID: 'yWeight', pointRadius: 3 }
          ]
        },
        options: { 
            responsive: true,
            scales: { 
                yAmount: { position: 'left', title: { display: true, text: 'Value (₨)' }, grid: { display: false } }, 
                yWeight: { position: 'right', title: { display: true, text: 'Volume (kg)' } } 
            } 
        }
      });

      // 3. Inward by Quality (Doughnut)
      this.charts.mgmtInwardQuality = new Chart(document.getElementById('mgmtInwardByQualityChart'), {
        type: 'doughnut',
        data: {
          labels: m.receiving.by_quality.map(i => i.name),
          datasets: [{
            data: m.receiving.by_quality.map(i => i.weight),
            backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899'],
            hoverOffset: 10
          }]
        },
        options: { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
      });

      // 4. Inward by Supplier (Horizontal Bar)
      this.charts.mgmtInwardSupplier = new Chart(document.getElementById('mgmtInwardBySupplierChart'), {
        type: 'bar',
        data: {
          labels: m.receiving.by_supplier.map(i => i.name),
          datasets: [{ label: 'Weight (kg)', data: m.receiving.by_supplier.map(i => i.weight), backgroundColor: 'rgba(245, 158, 11, 0.7)', borderRadius: 5 }]
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } } }
      });

      // 5. Investment by Quality (Stock Value)
      this.charts.mgmtQuality = new Chart(document.getElementById('mgmtAmountByQualityChart'), {
        type: 'bar',
        data: { labels: m.stock.by_quality.map(i => i.name), datasets: [{ label: 'Value (₨)', data: m.stock.by_quality.map(i => i.amount), backgroundColor: 'rgba(99, 102, 241, 0.7)', borderRadius: 4 }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } } }
      });

      // 6. Portfolio by Supplier (Stock Value)
      this.charts.mgmtSupplier = new Chart(document.getElementById('mgmtAmountBySupplierChart'), {
        type: 'bar',
        data: { labels: m.stock.by_supplier.map(i => i.name), datasets: [{ label: 'Value (₨)', data: m.stock.by_supplier.map(i => i.amount), backgroundColor: 'rgba(16, 185, 129, 0.7)', borderRadius: 4 }] },
        options: { plugins: { legend: { display: false } } }
      });

      // 7. Trend Chart
      this.charts.mgmtTrend = new Chart(document.getElementById('mgmtConsumptionTrendChart'), {
        type: 'line',
        data: {
          labels: m.consumption.over_time.map(i => i.date),
          datasets: [
            { label: 'Consumption (₨)', data: m.consumption.over_time.map(i => i.amount), borderColor: '#6366f1', fill: true, backgroundColor: 'rgba(99, 102, 241, 0.05)', tension: 0.3, pointRadius: 0 },
            { label: 'Purchase (₨)', data: m.receiving.over_time.map(i => i.amount), borderColor: '#10b981', fill: true, backgroundColor: 'rgba(16, 185, 129, 0.05)', tension: 0.3, pointRadius: 0 }
          ]
        },
        options: { 
            interaction: { intersect: false, mode: 'index' },
            scales: { x: { grid: { display: false } } }
        }
      });
    },
    destroyCharts() { Object.values(this.charts).forEach(c => c.destroy()); this.charts = {}; },
    formatNumber(val) { return Number(val || 0).toLocaleString(undefined, { maximumFractionDigits: 0 }); },
    formatTons(val) { return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 }); }
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
.bg-danger-soft { background-color: #fee2e2; }
.bg-indigo { background-color: #6366f1; }
.text-indigo { color: #6366f1; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }
.bg-gradient-premium { background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); }
.custom-dashboard-tabs :deep(.el-tabs__item) { font-weight: 600; font-size: 1rem; height: 50px; }
.custom-dashboard-tabs :deep(.el-tabs__active-bar) { background-color: #6366f1; height: 3px; }

.high-density-table { font-size: 0.85rem; }
.high-density-table th { padding: 0.5rem; font-weight: 700; color: #475569; }
.high-density-table td { padding: 0.4rem 0.5rem; }
.sticky-top { top: -1px; z-index: 10; box-shadow: 0 1px 0 rgba(0,0,0,0.05); }
.bg-indigo-soft { background-color: #e0e7ff; }
.indigo { color: #6366f1; }
</style>
