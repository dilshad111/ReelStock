<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="bi bi-graph-up-arrow"></i> Usage Intelligence & Patterns</h2>
      <div class="d-flex gap-2">
        <button @click="fetchData" class="btn btn-primary"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white highlight-card">
          <div class="card-body">
            <h6 class="card-subtitle mb-2 opacity-75">Avg Consumption / Reel</h6>
            <h4 class="card-title">{{ data.avg_consumption }} kg</h4>
            <i class="bi bi-speedometer2 position-absolute end-0 bottom-0 m-3 opacity-25 display-4"></i>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white highlight-card">
          <div class="card-body">
            <h6 class="card-subtitle mb-2 opacity-75">Total Reels Consumed</h6>
            <h4 class="card-title">{{ data.total_reels_consumed }}</h4>
            <i class="bi bi-recycle position-absolute end-0 bottom-0 m-3 opacity-25 display-4"></i>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white highlight-card">
          <div class="card-body">
            <h6 class="card-subtitle mb-2 opacity-75">Most Used Quality</h6>
            <h4 class="card-title">{{ topQuality }}</h4>
            <i class="bi bi-award position-absolute end-0 bottom-0 m-3 opacity-25 display-4"></i>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-white highlight-card">
          <div class="card-body">
            <h6 class="card-subtitle mb-2 opacity-75">Most Used Size</h6>
            <h4 class="card-title">{{ topSizeGsm }}</h4>
            <i class="bi bi-rulers position-absolute end-0 bottom-0 m-3 opacity-25 display-4"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row 1: Usage Patterns -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0">Most Consumed Qualities (kg)</h5>
          </div>
          <div class="card-body">
            <canvas id="qualityUsageChart" height="250"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0">Most Consumed Sizes & GSM (kg)</h5>
          </div>
          <div class="card-body">
            <canvas id="sizeGsmUsageChart" height="250"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row 2: Trends -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card shadow-sm">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Consumption Trends</h5>
            <div class="btn-group btn-group-sm">
              <button @click="trendType = 'monthly'" class="btn" :class="trendType === 'monthly' ? 'btn-primary' : 'btn-outline-primary'">Monthly</button>
              <button @click="trendType = 'quarterly'" class="btn" :class="trendType === 'quarterly' ? 'btn-primary' : 'btn-outline-primary'">Quarterly</button>
            </div>
          </div>
          <div class="card-body">
            <canvas id="consumptionTrendChart" height="100"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Predictive Analytics Section -->
    <div class="card shadow-sm border-primary mb-4">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-white">Predictive Analytics: Future Reel Demand</h5>
        <div class="d-flex gap-2">
            <select v-model="filter.paper_quality_id" @change="fetchPrediction" class="form-select form-select-sm" style="width: 200px;">
                <option :value="null">All Qualities</option>
                <option v-for="q in qualities" :key="q.id" :value="q.id">{{ q.quality }}</option>
            </select>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <canvas id="predictionChart" height="200"></canvas>
          </div>
          <div class="col-md-4">
            <div class="table-responsive">
              <table class="table table-hover table-sm">
                <thead class="table-light">
                  <tr>
                    <th>Forecast Month</th>
                    <th class="text-end">Predicted Weight (kg)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in prediction.forecast" :key="p.month">
                    <td><strong>{{ formatMonthYear(p.month) }}</strong></td>
                    <td class="text-end text-primary fw-bold">{{ formatNumber(p.predicted_weight) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="alert alert-info py-2 px-3 mt-3">
              <small><i class="bi bi-info-circle"></i> Forecasting uses a linear trend model based on historical consumption data.</small>
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
      data: {
        most_used_quality: {},
        most_used_size_gsm: {},
        avg_consumption: 0,
        total_reels_consumed: 0,
        monthly_trends: {},
        quarterly_trends: {}
      },
      prediction: {
        actual: [],
        forecast: []
      },
      qualities: [],
      filter: {
        paper_quality_id: null
      },
      trendType: 'monthly',
      charts: {}
    }
  },
  computed: {
    topQuality() {
      const entries = Object.entries(this.data.most_used_quality);
      return entries.length > 0 ? entries[0][0] : 'N/A';
    },
    topSizeGsm() {
      const entries = Object.entries(this.data.most_used_size_gsm);
      return entries.length > 0 ? entries[0][0] : 'N/A';
    }
  },
  watch: {
    trendType() {
      this.renderTrendChart();
    }
  },
  mounted() {
    this.fetchData();
    this.fetchQualities();
    this.fetchPrediction();
  },
  methods: {
    fetchData() {
      axios.get('/api/reports/usage-intelligence').then(res => {
        this.data = res.data;
        this.$nextTick(() => {
          this.renderUsageCharts();
          this.renderTrendChart();
        });
      });
    },
    fetchQualities() {
        axios.get('/api/paper-qualities').then(res => {
            this.qualities = res.data;
        });
    },
    fetchPrediction() {
      axios.get('/api/reports/predictive-analytics', { params: this.filter }).then(res => {
        this.prediction = res.data;
        this.$nextTick(() => {
          this.renderPredictionChart();
        });
      });
    },
    renderUsageCharts() {
      this.destroyChart('qualityUsageChart');
      this.destroyChart('sizeGsmUsageChart');

      const qLabel = Object.keys(this.data.most_used_quality);
      const qValue = Object.values(this.data.most_used_quality);
      this.charts.qualityUsageChart = new Chart(document.getElementById('qualityUsageChart'), {
        type: 'bar',
        data: {
          labels: qLabel,
          datasets: [{
            label: 'Weight (kg)',
            data: qValue,
            backgroundColor: 'rgba(99, 102, 241, 0.7)',
            borderRadius: 8
          }]
        },
        options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
      });

      const sLabel = Object.keys(this.data.most_used_size_gsm);
      const sValue = Object.values(this.data.most_used_size_gsm);
      this.charts.sizeGsmUsageChart = new Chart(document.getElementById('sizeGsmUsageChart'), {
        type: 'bar',
        data: {
          labels: sLabel,
          datasets: [{
            label: 'Weight (kg)',
            data: sValue,
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderRadius: 8
          }]
        },
        options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
      });
    },
    renderTrendChart() {
      this.destroyChart('consumptionTrendChart');
      const trendData = this.trendType === 'monthly' ? this.data.monthly_trends : this.data.quarterly_trends;
      const labels = Object.keys(trendData);
      const values = Object.values(trendData).map(v => v.weight);

      this.charts.consumptionTrendChart = new Chart(document.getElementById('consumptionTrendChart'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Consumption (kg)',
            data: values,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
          }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });
    },
    renderPredictionChart() {
      this.destroyChart('predictionChart');
      
      const actualLabels = this.prediction.actual.map(d => d.month);
      const actualValues = this.prediction.actual.map(d => d.total_weight);
      
      const forecastLabels = this.prediction.forecast.map(d => d.month);
      const forecastValues = this.prediction.forecast.map(d => d.predicted_weight);
      
      // Combine for chart
      const allLabels = [...actualLabels, ...forecastLabels];
      
      this.charts.predictionChart = new Chart(document.getElementById('predictionChart'), {
        type: 'line',
        data: {
          labels: allLabels,
          datasets: [
            {
              label: 'Actual Consumption',
              data: [...actualValues, ...forecastLabels.map(() => null)],
              borderColor: '#64748b',
              backgroundColor: '#64748b',
              pointRadius: 4
            },
            {
              label: 'Predicted Demand',
              data: [...actualLabels.map((_, i) => i === actualLabels.length - 1 ? actualValues[i] : null), ...forecastValues],
              borderColor: '#6366f1',
              backgroundColor: 'rgba(99, 102, 241, 0.2)',
              borderDash: [5, 5],
              fill: true,
              pointRadius: 6,
              pointHoverRadius: 8
            }
          ]
        },
        options: {
          responsive: true,
          interaction: { intersect: false, mode: 'index' },
          plugins: {
            title: { display: true, text: 'Actual vs Predicted Consumption' }
          },
          scales: { y: { beginAtZero: true } }
        }
      });
    },
    destroyChart(id) {
      if (this.charts[id]) {
        this.charts[id].destroy();
        delete this.charts[id];
      }
    },
    formatNumber(val) {
      return Number(val).toLocaleString(undefined, { maximumFractionDigits: 0 });
    },
    formatMonthYear(dateStr) {
        const [year, month] = dateStr.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleString('en-US', { month: 'short', year: 'numeric' });
    }
  },
  beforeUnmount() {
    Object.values(this.charts).forEach(c => c.destroy());
  }
}
</script>

<style scoped>
.highlight-card {
  position: relative;
  overflow: hidden;
  border: none;
  border-radius: 12px;
  transition: transform 0.3s ease;
}
.highlight-card:hover {
  transform: translateY(-5px);
}
.card-header h5 {
    color: #475569;
}
</style>
