<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-primary"><i class="bi bi-bell-fill me-2"></i> Stock Alerts Configuration</h2>
      <button @click="toggleForm" class="btn btn-primary">
        <i class="bi" :class="showForm ? 'bi-dash-lg' : 'bi-plus-lg'"></i> {{ showForm ? 'Cancel' : 'Add New Alert' }}
      </button>
    </div>

    <!-- Add Alert Form -->
    <div v-if="showForm" class="card shadow-sm border-0 mb-4 animate__animated animate__fadeIn">
      <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0">Define Stock Alert Target</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="saveAlert">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label small fw-bold">Paper Quality</label>
              <select v-model="form.paper_quality_id" class="form-select" required @change="fetchAvailableDetails">
                <option value="">Select Quality</option>
                <option v-for="q in qualities" :key="q.id" :value="q.id">{{ q.quality }} ({{ q.gsm_range }})</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold">Reel Size (inches)</label>
              <el-autocomplete
                v-model="form.reel_size"
                :fetch-suggestions="querySize"
                placeholder="Enter or select Size"
                class="w-100"
                clearable
                required
              ></el-autocomplete>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold">Alert Based On</label>
              <select v-model="form.alert_type" class="form-select" required>
                <option value="reels">Number of Reels</option>
                <option value="weight">Total Weight (Kgs)</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold">Threshold Value</label>
              <input v-model.number="form.threshold_value" type="number" step="0.01" class="form-control" placeholder="Target min quantity" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button type="submit" class="btn btn-success w-100" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span> {{ form.id ? 'Update' : 'Save' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Active Alerts Table -->
    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4">Paper Quality</th>
                <th>Reel Size</th>
                <th>Threshold</th>
                <th>Current Stock</th>
                <th>Type</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="alert in alerts" :key="alert.id">
                <td class="ps-4">
                  <div class="fw-bold">
                    {{ alert.paper_quality?.quality }}
                    <span v-if="alert.paper_quality?.gsm_range" class="text-muted small fw-normal">({{ alert.paper_quality.gsm_range }})</span>
                  </div>
                </td>
                <td>{{ alert.reel_size }}&quot;</td>
                <td><span class="badge bg-warning text-dark">{{ alert.threshold_value }}</span></td>
                <td>
                  <span class="fw-bold" :class="alert.current_value < alert.threshold_value ? 'text-danger' : 'text-success'">
                    {{ alert.current_value }}
                  </span>
                </td>
                <td><span class="text-uppercase small fw-bold text-muted">{{ alert.alert_type }}</span></td>
                <td>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" :checked="alert.is_active" @change="toggleAlert(alert)">
                    <span class="small" :class="alert.is_active ? 'text-success' : 'text-danger'">{{ alert.is_active ? 'Active' : 'Inactive' }}</span>
                  </div>
                </td>
                <td class="text-end pe-4">
                  <button @click="editAlert(alert)" class="btn btn-sm btn-outline-primary border-0 me-1">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button @click="deleteAlert(alert.id)" class="btn btn-sm btn-outline-danger border-0">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="alerts.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                  No stock alerts configured.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Triggered Alerts (Live Check) -->
    <div class="mt-5 mb-4 border-top pt-4">
      <h4 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i> Active Stock Alerts (Low Inventory)</h4>
      <div class="row g-3">
        <div v-for="t in triggered" :key="t.id" class="col-md-4">
          <div class="card border-0 shadow-sm bg-danger bg-opacity-10 border-start border-danger border-4">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="fw-bold mb-0">{{ t.quality_name }} <span v-if="t.gsm_range" class="small fw-normal">({{ t.gsm_range }})</span></h6>
                <span class="badge bg-danger">LOW STOCK</span>
              </div>
              <div class="small text-muted mb-3">Reel Size: {{ t.reel_size }}&quot;</div>
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="small fw-bold">Current</div>
                  <div class="h5 mb-0">{{ t.current_value }} <span class="small text-muted">{{ t.alert_type }}</span></div>
                </div>
                <div class="text-end">
                  <div class="small fw-bold">Threshold</div>
                  <div class="h5 mb-0">{{ t.threshold_value }}</div>
                </div>
              </div>
              <div class="mt-3">
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar bg-danger" role="progressbar" :style="{ width: (t.current_value / t.threshold_value * 100) + '%' }"></div>
                </div>
                <div class="small text-danger mt-1 fw-bold">Shortage: {{ t.shortage.toFixed(2) }} {{ t.alert_type }}</div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="triggered.length === 0" class="col-12 text-center py-4 bg-light rounded">
          <span class="text-success"><i class="bi bi-check-circle-fill me-2"></i> All stock levels are within defined targets.</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: ['user'],
  data() {
    return {
      alerts: [],
      triggered: [],
      qualities: [],
      availableSizes: [],
      showForm: false,
      loading: false,
      form: {
        id: null,
        paper_quality_id: '',
        reel_size: '',
        alert_type: 'reels',
        threshold_value: '',
        is_active: true
      }
    }
  },
  mounted() {
    this.fetchAlerts();
    this.fetchQualities();
    this.fetchTriggered();
    // Refresh triggered alerts every 30 seconds
    setInterval(this.fetchTriggered, 30000);
  },
  methods: {
    toggleForm() {
      if (this.showForm) {
        this.resetForm();
      }
      this.showForm = !this.showForm;
    },
    fetchAlerts() {
      axios.get('/api/stock-alerts').then(res => {
        this.alerts = res.data;
      });
    },
    fetchQualities() {
      axios.get('/api/paper-qualities').then(res => {
        this.qualities = res.data;
      });
    },
    fetchTriggered() {
      axios.get('/api/stock-alerts/triggered').then(res => {
        this.triggered = res.data;
        this.$emit('update-triggered-count', this.triggered.length);
      });
    },
    fetchAvailableDetails() {
      if (!this.form.paper_quality_id) return;
      
      // We can reuse some report endpoints to get available sizes/gsm for a quality
      axios.get('/api/reports/reel-stock', { params: { quality: this.form.paper_quality_id } }).then(res => {
          const reels = res.data;
          // Note: Since the report might not return all attributes we need directly, 
          // we'll extract them from the available stock data if possible.
          // In a more robust system, we'd have specific endpoints for this.
          this.availableSizes = [...new Set(reels.map(r => r.reel_size.toString()))].map(s => ({ value: s }));

      });

      // Get available sizes specifically
      axios.get('/api/reports/reel-stock/sizes', { params: { quality: this.form.paper_quality_id } }).then(res => {
          this.availableSizes = res.data.map(s => ({ value: s.toString() }));
      });
    },

    querySize(queryString, cb) {
      const results = queryString
        ? this.availableSizes.filter(s => s.value.includes(queryString))
        : this.availableSizes;
      cb(results);
    },
    saveAlert() {
      this.loading = true;
      const method = this.form.id ? 'put' : 'post';
      const url = this.form.id ? `/api/stock-alerts/${this.form.id}` : '/api/stock-alerts';
      
      axios[method](url, this.form).then(() => {
        this.fetchAlerts();
        this.fetchTriggered();
        this.showForm = false;
        this.resetForm();
        this.$message.success(this.form.id ? 'Stock alert updated successfully' : 'Stock alert configured successfully');
      }).finally(() => {
        this.loading = false;
      });
    },
    editAlert(alert) {
      this.form = {
        id: alert.id,
        paper_quality_id: alert.paper_quality_id,
        reel_size: alert.reel_size,
        alert_type: alert.alert_type,
        threshold_value: alert.threshold_value,
        is_active: alert.is_active
      };
      this.showForm = true;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    resetForm() {
      this.form = {
        id: null,
        paper_quality_id: '',
        reel_size: '',
        alert_type: 'reels',
        threshold_value: '',
        is_active: true
      };
    },
    toggleAlert(alert) {
      axios.post(`/api/stock-alerts/${alert.id}/toggle`).then(() => {
        alert.is_active = !alert.is_active;
        this.fetchTriggered();
      });
    },
    deleteAlert(id) {
      if (confirm('Are you sure you want to delete this alert configuration?')) {
        axios.delete(`/api/stock-alerts/${id}`).then(() => {
          this.fetchAlerts();
          this.fetchTriggered();
          this.$message.info('Alert configuration deleted');
        });
      }
    }
  }
}
</script>

<style scoped>
.form-check-input:checked {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
}
.animate__animated {
  animation-duration: 0.5s;
}
</style>
