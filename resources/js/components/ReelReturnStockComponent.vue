<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-arrow-repeat"></i> Reel Return to Stock</h2>
      <button @click="toggleForm" class="btn btn-primary">
        <i class="bi" :class="showForm ? 'bi-dash-circle' : 'bi-plus-circle'"></i>
        {{ showForm ? 'Close Form' : 'Return Reel to Stock' }}
      </button>
    </div>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5 class="card-title mb-3">Record Reel Return to Stock</h5>
        <form @submit.prevent="saveReturn" novalidate>
          <div v-if="reelError" class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ reelError }}
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Reel No.</label>
              <input
                v-model.trim="formData.reel_no"
                type="text"
                class="form-control"
                placeholder="Enter Reel No"
                required
                @blur="fetchReel"
              >
              <div v-if="reel" class="mt-3">
                <div class="fw-bold mb-1">Reel Details</div>
                <ul class="list-unstyled small mb-0">
                  <li><strong>Supplier:</strong> {{ getSupplierName(reel) }}</li>
                  <li><strong>Quality:</strong> {{ getQuality(reel) }}</li>
                  <li><strong>Size:</strong> {{ reel.reel_size }}"</li>
                  <li><strong>Original Weight:</strong> {{ formatNumber(reel.original_weight) }} kg</li>
                  <li><strong>Current Balance:</strong> {{ formatNumber(reel.balance_weight) }} kg</li>
                  <li><strong>Status:</strong> {{ reel.status }}</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Return Date</label>
                <input v-model="formData.return_date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Balance Weight After Return (kg)</label>
                <input v-model.number="formData.remaining_weight" type="number" step="0.01" min="0" class="form-control" required>
                <div class="form-text">Set the reel balance after returning it to stock.</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Condition</label>
                <select v-model="formData.condition" class="form-select" required>
                  <option value="good">Good</option>
                  <option value="damaged">Damaged</option>
                  <option value="qc_required">QC Required</option>
                </select>
              </div>
              <div>
                <label class="form-label">Remarks</label>
                <textarea v-model.trim="formData.remarks" rows="3" class="form-control" placeholder="Optional remarks"></textarea>
              </div>
            </div>
          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Save Return</button>
            <button type="button" class="btn btn-secondary" @click="cancel"><i class="bi bi-x-circle"></i> Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0">Recent Returns to Stock</h5>
          <button class="btn btn-sm btn-outline-primary" @click="fetchReturns" :disabled="loading">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>
        <div v-if="returns.length === 0" class="alert alert-info mb-0">
          No returns to stock recorded yet.
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Reel No.</th>
                <th>Date</th>
                <th>Supplier</th>
                <th>Quality</th>
                <th class="text-end">Balance (kg)</th>
                <th>Condition</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in returns" :key="item.id">
                <td>{{ item.reel.reel_no }}</td>
                <td>{{ formatDate(item.return_date) }}</td>
                <td>{{ getSupplierName(item.reel) }}</td>
                <td>{{ getQuality(item.reel) }}</td>
                <td class="text-end">{{ formatNumber(item.remaining_weight) }}</td>
                <td class="text-capitalize">{{ item.condition.replace('_', ' ') }}</td>
                <td>{{ item.remarks || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ReelReturnStockComponent',
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      returns: [],
      reel: null,
      loading: false,
      showForm: false,
      reelError: '',
      reelHasIssueHistory: false,
      reelAlreadyInStock: false,
      formData: {
        reel_no: '',
        return_date: new Date().toISOString().substr(0, 10),
        remaining_weight: '',
        condition: 'good',
        remarks: '',
        returned_to: 'stock'
      }
    };
  },
  mounted() {
    this.ensureAuthHeader();
    this.fetchReturns();
  },
  watch: {
    user() {
      this.ensureAuthHeader();
    }
  },
  methods: {
    ensureAuthHeader() {
      const token = localStorage.getItem('token');
      if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      }
    },
    toggleForm() {
      this.showForm = !this.showForm;
      if (!this.showForm) {
        this.cancel();
      }
    },
    navigateToSupplier() {
      if (this.$root?.setView) {
        this.$root.setView('return-supplier');
      } else {
        window.location.href = '/return-supplier';
      }
    },
    fetchReturns() {
      this.loading = true;
      axios.get('/api/reel-returns', { params: { returned_to: 'stock' } })
        .then(response => {
          this.returns = Array.isArray(response.data) ? response.data : [];
        })
        .catch(error => {
          console.error('Error fetching returns:', error);
          alert('Failed to load returns to stock.');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    normalizeReelNo(value) {
      if (!value) return '';
      let reelNo = String(value).trim().toUpperCase();
      if (!reelNo) return '';
      reelNo = reelNo.replace(/\s+/g, '');
      if (!reelNo.startsWith('RL')) {
        reelNo = `RL${reelNo.replace(/^RL/i, '')}`;
      }
      return reelNo;
    },
    fetchReel() {
      const normalized = this.normalizeReelNo(this.formData.reel_no);
      if (!normalized) {
        return;
      }
      this.formData.reel_no = normalized;
      axios.get(`/api/fetch-reel-return/${encodeURIComponent(normalized)}`)
        .then(response => {
          const reelData = response.data;
          this.reelHasIssueHistory = !!reelData?.has_issue_history;
          this.reelAlreadyInStock = !!reelData?.already_in_stock;
          if (!this.reelHasIssueHistory || this.reelAlreadyInStock) {
            this.reel = null;
            this.reelError = 'This reel is already in the stock.';
            this.formData.remaining_weight = '';
            alert(this.reelError);
            return;
          }
          this.reelError = '';
          this.reel = reelData;
          if (this.reel && this.reel.balance_weight !== undefined) {
            this.formData.remaining_weight = Number(this.reel.balance_weight);
          }
        })
        .catch(() => {
          this.reel = null;
          this.reelHasIssueHistory = false;
          this.reelAlreadyInStock = false;
          this.reelError = 'Reel not found or invalid.';
          alert(this.reelError);
        });
    },
    saveReturn() {
      if (!this.formData.reel_no) {
        alert('Please enter a reel number.');
        return;
      }
      if (!this.reelHasIssueHistory || this.reelAlreadyInStock) {
        this.reelError = 'This reel is already in the stock.';
        alert(this.reelError);
        return;
      }
      const payload = {
        ...this.formData,
        reel_no: this.normalizeReelNo(this.formData.reel_no),
        remaining_weight: Number(this.formData.remaining_weight) || 0,
        returned_to: 'stock'
      };
      axios.post('/api/reel-returns', payload)
        .then(() => {
          alert('Return saved successfully.');
          this.fetchReturns();
          this.cancel();
        })
        .catch(error => {
          const message = error.response?.data?.error || 'Failed to save return.';
          alert(message);
        });
    },
    cancel() {
      this.formData = {
        reel_no: '',
        return_date: new Date().toISOString().substr(0, 10),
        remaining_weight: '',
        condition: 'good',
        remarks: '',
        returned_to: 'stock'
      };
      this.reel = null;
      this.reelError = '';
      this.reelHasIssueHistory = false;
      this.reelAlreadyInStock = false;
      this.showForm = false;
    },
    getQuality(reel) {
      if (!reel) return 'N/A';
      const pq = reel.paperQuality || reel.paper_quality;
      if (!pq) return 'N/A';
      const name = pq.quality || pq.item_code || 'N/A';
      const gsm = pq.gsm_range ? ` (${pq.gsm_range})` : '';
      return `${name}${gsm}`;
    },
    getSupplierName(reel) {
      if (!reel || !reel.supplier) return 'N/A';
      return reel.supplier.name || 'N/A';
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) return dateString;
      return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
    },
    formatNumber(value) {
      const number = Number(value);
      if (Number.isNaN(number)) return value || '-';
      return number.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
  }
};
</script>

<style scoped>
.list-unstyled li + li {
  margin-top: 2px;
}
</style>
