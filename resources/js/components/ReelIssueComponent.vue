<template>
  <div class="container">
    <h2>Paper Reel Issue and Return</h2>
    <div class="mb-3">
      <label>Action</label>
      <select v-model="type" @change="resetForm" class="form-control">
        <option value="issue">Issue Reel</option>
        <option value="return">Return Reel</option>
      </select>
    </div>
    <button @click="showForm = !showForm" class="btn btn-primary mb-3">{{ type === 'issue' ? 'Issue Reel' : 'Return Reel' }}</button>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>{{ type === 'issue' ? 'Issue Reel' : 'Return Reel' }}</h5>
        <form @submit.prevent="type === 'issue' ? saveIssue() : saveReturn()">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label>Reel No.</label>
                <input v-model="formData.reel_no" type="text" class="form-control" required @blur="fetchReel">
              </div>
              <div v-if="reel" class="mb-3">
                <p>Quality: {{ getQuality(reel) }}</p>
                <p>Supplier: {{ getSupplierName(reel) }}</p>
                <p>Size: {{ reel.reel_size }}</p>
                <p>Balance Weight: {{ reel.balance_weight }} kg</p>
              </div>
              <div v-if="reelHistory.length" class="reel-history card">
                <div class="card-body p-3">
                  <h6 class="card-title">Reel History</h6>
                  <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Type</th>
                          <th>Details</th>
                          <th class="text-end">Weight</th>
                          <th class="text-end">Balance</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(entry, idx) in reelHistory" :key="idx">
                          <td>{{ formatDate(entry.date) }}</td>
                          <td>{{ entry.type }}</td>
                          <td>{{ entry.details }}</td>
                          <td class="text-end">{{ entry.weight }} kg</td>
                          <td class="text-end">{{ entry.balance }} kg</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div v-if="type === 'issue'">
                <div class="mb-3">
                  <label>Issue Date</label>
                  <input v-model="formData.issue_date" type="date" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label>Quantity Issued (kg)</label>
                  <input v-model="formData.quantity_issued" type="number" step="0.01" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label>Issued To</label>
                  <input v-model="formData.issued_to" type="text" class="form-control" required>
                </div>
              </div>
              <div v-else>
                <div class="mb-3">
                  <label>Return Date</label>
                  <input v-model="formData.return_date" type="date" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label>Remaining Weight (kg)</label>
                  <input v-model="formData.remaining_weight" type="number" step="0.01" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label>Return To</label>
                  <select v-model="formData.return_to" class="form-control">
                    <option value="stock">Stock</option>
                    <option value="supplier">Supplier</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label>Condition</label>
                  <select v-model="formData.condition" class="form-control" required>
                    <option value="good">Good</option>
                    <option value="damaged">Damaged</option>
                    <option value="qc_required">QC Required</option>
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label>Remarks</label>
                <textarea v-model="formData.remarks" class="form-control"></textarea>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-success">Save</button>
          <button @click="cancel" class="btn btn-secondary">Cancel</button>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <h4>Issues</h4>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Reel No.</th>
              <th>Quality</th>
              <th>Issue Date</th>
              <th>Quantity Issued</th>
              <th>Issued To</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="i in issues" :key="i.id">
              <td>{{ i.reel.reel_no }}</td>
              <td>{{ getQuality(i.reel) }}</td>
              <td>{{ i.issue_date }}</td>
              <td>{{ i.quantity_issued }} kg</td>
              <td>{{ i.issued_to }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="col-md-6">
        <h4>Returns</h4>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Reel No.</th>
              <th>Quality</th>
              <th>Return Date</th>
              <th>Remaining Weight</th>
              <th>Condition</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in returns" :key="r.id">
              <td>{{ r.reel.reel_no }}</td>
              <td>{{ getQuality(r.reel) }}</td>
              <td>{{ r.return_date }}</td>
              <td>{{ r.remaining_weight }} kg</td>
              <td>{{ r.condition }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      issues: [],
      returns: [],
      reel: null,
      reelHistory: [],
      type: 'issue',
      formData: {
        reel_no: '',
        issue_date: new Date().toISOString().substr(0,10),
        quantity_issued: '',
        issued_to: '',
        return_date: new Date().toISOString().substr(0,10),
        remaining_weight: '',
        return_to: 'stock',
        condition: 'good',
        remarks: ''
      },
      showForm: false
    };
  },
  mounted() {
    this.fetchIssues();
    this.fetchReturns();
  },
  methods: {
    fetchIssues() {
      axios.get('/api/reel-issues').then(response => {
        this.issues = response.data;
      });
    },
    fetchReturns() {
      axios.get('/api/reel-returns').then(response => {
        this.returns = response.data;
      });
    },
    fetchReel() {
      const normalizedReelNo = this.normalizeReelNo(this.formData.reel_no);
      if (normalizedReelNo) {
        this.formData.reel_no = normalizedReelNo;
        axios.get(`/api/fetch-reel/${encodeURIComponent(normalizedReelNo)}`).then(response => {
          this.reel = response.data;
          if (this.type === 'issue') {
            this.formData.quantity_issued = parseFloat(this.reel.balance_weight) || 0;
          }
          this.loadReelHistory();
        }).catch(() => {
          alert('Reel not found or invalid');
          this.reel = null;
          this.reelHistory = [];
        });
      }
    },
    loadReelHistory() {
      axios.get(`/api/reports/reel-stock/${this.formData.reel_no}/history`).then(response => {
        this.reelHistory = response.data.history || [];
      }).catch(error => {
        console.error('Error fetching reel history:', error);
        this.reelHistory = [];
      });
    },
    saveIssue() {
      const payload = { ...this.formData, reel_no: this.normalizeReelNo(this.formData.reel_no) };
      axios.post('/api/reel-issues', payload).then(() => {
        this.fetchIssues();
        this.cancel();
      });
    },
    saveReturn() {
      const payload = { ...this.formData, reel_no: this.normalizeReelNo(this.formData.reel_no) };
      axios.post('/api/reel-returns', payload).then(() => {
        this.fetchReturns();
        this.cancel();
      });
    },
    cancel() {
      this.showForm = false;
      this.resetForm();
    },
    resetForm() {
      this.formData = {
        reel_no: '',
        issue_date: new Date().toISOString().substr(0,10),
        quantity_issued: '',
        issued_to: '',
        return_date: new Date().toISOString().substr(0,10),
        remaining_weight: '',
        return_to: 'stock',
        condition: 'good',
        remarks: ''
      };
      this.reel = null;
      this.reelHistory = [];
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) return '-';
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    },
    normalizeReelNo(value) {
      if (!value) return '';
      let reelNo = String(value).trim().toUpperCase();
      if (reelNo === '') return '';
      reelNo = reelNo.replace(/\s+/g, '');
      if (reelNo.startsWith('RL')) {
        return reelNo;
      }
      return `RL${reelNo.replace(/^RL/i, '')}`;
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
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
