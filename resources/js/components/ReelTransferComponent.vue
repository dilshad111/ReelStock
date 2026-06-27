<template>
  <div class="reel-transfer-page">
    <section class="rt-header">
      <div>
        <div class="rt-eyebrow">Reels Inventory</div>
        <h2><i class="bi bi-arrow-left-right"></i> Reel Location Transfer</h2>
        <p>Move reels between Warehouse and Factory without consuming stock weight.</p>
      </div>
      <button class="btn btn-primary rt-primary-btn" type="button" @click="resetForm">
        <i class="bi bi-plus-circle"></i>
        New Transfer
      </button>
    </section>

    <div class="rt-grid">
      <section class="rt-card">
        <div class="rt-section-title">
          <span>Transfer Entry</span>
          <small>Factory / Warehouse movement</small>
        </div>

        <form @submit.prevent="saveTransfer">
          <div class="rt-form-grid">
            <div class="rt-field">
              <label>Reel Number <span>*</span></label>
              <div class="rt-reel-search">
                <input
                  v-model.trim="form.reel_no"
                  type="text"
                  class="rt-input"
                  placeholder="e.g. 112742"
                  :disabled="saving"
                  @blur="fetchReel"
                  @keyup.enter.prevent="fetchReel"
                >
                <button class="btn btn-secondary" type="button" :disabled="saving || !form.reel_no" @click="fetchReel">
                  Fetch
                </button>
              </div>
            </div>

            <div class="rt-field">
              <label>Transfer Date <span>*</span></label>
              <input v-model="form.transfer_date" type="date" class="rt-input" required>
            </div>

            <div class="rt-field">
              <label>From Location</label>
              <input :value="currentLocation" type="text" class="rt-input rt-readonly" readonly>
            </div>

            <div class="rt-field">
              <label>To Location <span>*</span></label>
              <select v-model="form.to_location" class="rt-input" required :disabled="saving || !reel || Number(reel.balance_weight) <= 0">
                <option value="">Select destination</option>
                <option v-for="location in destinationLocations" :key="location" :value="location">
                  {{ location }}
                </option>
              </select>
            </div>

            <div class="rt-field">
              <label>Handled By</label>
              <input v-model.trim="form.handled_by" type="text" class="rt-input" placeholder="Employee / supervisor name">
            </div>

            <div class="rt-field rt-field-wide">
              <label>Remarks</label>
              <textarea v-model.trim="form.remarks" class="rt-input rt-textarea" placeholder="Reason, vehicle, shift, or handover notes"></textarea>
            </div>
          </div>

          <div v-if="reel" class="rt-reel-card">
            <div>
              <span>Reel No.</span>
              <strong>{{ reel.reel_no }}</strong>
            </div>
            <div>
              <span>Quality</span>
              <strong>{{ qualityLabel(reel) }}</strong>
            </div>
            <div>
              <span>Supplier</span>
              <strong>{{ reel.supplier?.name || '-' }}</strong>
            </div>
            <div>
              <span>Size</span>
              <strong>{{ formatSize(reel.reel_size) }}</strong>
            </div>
            <div>
              <span>Balance</span>
              <strong>{{ formatNumber(reel.balance_weight) }} kg</strong>
            </div>
            <div>
              <span>Current Location</span>
              <strong>{{ currentLocation }}</strong>
            </div>
          </div>

          <div class="rt-actions">
            <button class="btn btn-secondary" type="button" :disabled="saving" @click="resetForm">Discard</button>
            <button class="btn btn-primary" type="submit" :disabled="saving || !reel || Number(reel.balance_weight) <= 0">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              Save Transfer
            </button>
          </div>
        </form>
      </section>

      <section class="rt-card">
        <div class="rt-section-title">
          <span>Recent Location History</span>
          <small v-if="reel">{{ reel.reel_no }}</small>
          <small v-else>Fetch a reel to preview</small>
        </div>
        <div class="rt-history-list" v-if="reelTransfers.length">
          <div v-for="item in reelTransfers" :key="item.id" class="rt-history-item">
            <div class="rt-history-main">
              <strong>{{ formatDate(item.transfer_date) }}</strong>
              <span>{{ item.from_location }} -> {{ item.to_location }}</span>
            </div>
            <small>{{ item.handled_by || item.creator?.name || 'System' }}</small>
          </div>
        </div>
        <div v-else class="rt-empty">
          No transfer history found for this reel.
        </div>
      </section>
    </div>

    <section class="rt-card rt-table-card">
      <div class="rt-table-toolbar">
        <div>
          <div class="rt-section-title mb-0">
            <span>Transfer Register</span>
            <small>Audit trail of reel shifting</small>
          </div>
        </div>
        <div class="rt-filters">
          <input v-model.trim="filters.search" class="rt-input" placeholder="Search reel no..." @input="queueFetch">
          <select v-model="filters.location" class="rt-input" @change="fetchTransfers(1)">
            <option value="">All Locations</option>
            <option value="Warehouse">Warehouse</option>
            <option value="Factory">Factory</option>
          </select>
          <input v-model="filters.date_from" type="date" class="rt-input" @change="fetchTransfers(1)">
          <input v-model="filters.date_to" type="date" class="rt-input" @change="fetchTransfers(1)">
          <button class="btn btn-clear-filters" type="button" @click="clearFilters">Clear</button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table rt-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Reel No.</th>
              <th>Quality</th>
              <th>From</th>
              <th>To</th>
              <th>Handled By</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transfer in transfers" :key="transfer.id">
              <td>{{ formatDate(transfer.transfer_date) }}</td>
              <td class="fw-bold">{{ transfer.reel?.reel_no }}</td>
              <td>{{ qualityLabel(transfer.reel) }}</td>
              <td><span class="rt-location-badge">{{ transfer.from_location }}</span></td>
              <td><span class="rt-location-badge rt-location-active">{{ transfer.to_location }}</span></td>
              <td>{{ transfer.handled_by || transfer.creator?.name || '-' }}</td>
              <td>{{ transfer.remarks || '-' }}</td>
            </tr>
            <tr v-if="!transfers.length">
              <td colspan="7" class="text-center py-4 text-muted">No reel transfers recorded yet.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.last_page > 1" class="rt-pagination">
        <button class="btn btn-sm btn-secondary" :disabled="pagination.current_page <= 1" @click="fetchTransfers(pagination.current_page - 1)">
          Previous
        </button>
        <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <button class="btn btn-sm btn-secondary" :disabled="pagination.current_page >= pagination.last_page" @click="fetchTransfers(pagination.current_page + 1)">
          Next
        </button>
      </div>
    </section>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      reel: null,
      transfers: [],
      pagination: {
        current_page: 1,
        last_page: 1,
      },
      filters: {
        search: '',
        location: '',
        date_from: '',
        date_to: '',
      },
      form: {
        reel_no: '',
        transfer_date: new Date().toISOString().slice(0, 10),
        to_location: '',
        handled_by: '',
        remarks: '',
      },
      saving: false,
      searchTimer: null,
    };
  },
  computed: {
    currentLocation() {
      return this.reel?.current_location || 'Warehouse';
    },
    destinationLocations() {
      return ['Warehouse', 'Factory'].filter(location => location !== this.currentLocation);
    },
    reelTransfers() {
      return this.reel?.transfers || [];
    },
  },
  mounted() {
    this.fetchTransfers();
  },
  methods: {
    fetchReel() {
      const reelNo = this.normalizeReelNo(this.form.reel_no);
      if (!reelNo) return;
      this.form.reel_no = reelNo;
      axios.get(`/api/reel-transfers/fetch-reel/${encodeURIComponent(reelNo)}`)
        .then(({ data }) => {
          this.reel = data.data;
          this.form.to_location = this.destinationLocations[0] || '';
          if (this.reel && Number(this.reel.balance_weight) <= 0) {
            this.form.to_location = '';
            alert('this reel weight is 0kg no balance to move the reel to other location.');
          }
        })
        .catch(error => {
          this.reel = null;
          this.form.to_location = '';
          alert(error.response?.data?.message || 'Reel not found.');
        });
    },
    fetchTransfers(page = 1) {
      const params = { ...this.filters, page };
      axios.get('/api/reel-transfers', { params })
        .then(({ data }) => {
          this.transfers = data.data || [];
          this.pagination = {
            current_page: data.current_page || 1,
            last_page: data.last_page || 1,
          };
        })
        .catch(error => {
          alert(error.response?.data?.message || 'Failed to load reel transfers.');
        });
    },
    saveTransfer() {
      if (!this.reel) {
        alert('Please fetch a valid reel before saving.');
        return;
      }
      if (!this.form.to_location) {
        alert('Please select the destination location.');
        return;
      }
      this.saving = true;
      axios.post('/api/reel-transfers', {
        ...this.form,
        reel_no: this.normalizeReelNo(this.form.reel_no),
      }).then(({ data }) => {
        alert(data.message || 'Reel location transferred successfully.');
        const reelNo = this.form.reel_no;
        this.resetForm();
        this.form.reel_no = reelNo;
        this.fetchReel();
        this.fetchTransfers(1);
      }).catch(error => {
        alert(error.response?.data?.message || 'Failed to save transfer.');
      }).finally(() => {
        this.saving = false;
      });
    },
    resetForm() {
      this.reel = null;
      this.form = {
        reel_no: '',
        transfer_date: new Date().toISOString().slice(0, 10),
        to_location: '',
        handled_by: '',
        remarks: '',
      };
    },
    clearFilters() {
      this.filters = {
        search: '',
        location: '',
        date_from: '',
        date_to: '',
      };
      this.fetchTransfers(1);
    },
    queueFetch() {
      clearTimeout(this.searchTimer);
      this.searchTimer = setTimeout(() => this.fetchTransfers(1), 350);
    },
    normalizeReelNo(value) {
      if (!value) return '';
      const compact = String(value).trim().toUpperCase().replace(/\s+/g, '');
      return compact.startsWith('RL') ? compact : `RL${compact}`;
    },
    qualityLabel(reel) {
      if (!reel?.paper_quality && !reel?.paperQuality) return '-';
      const quality = reel.paper_quality || reel.paperQuality;
      const name = quality.quality || quality.item_code || 'N/A';
      return quality.gsm_range ? `${name} (${quality.gsm_range})` : name;
    },
    formatSize(value) {
      const number = Number(value);
      return Number.isFinite(number) ? `${number.toFixed(2)}"` : '-';
    },
    formatNumber(value) {
      const number = Number(value);
      if (!Number.isFinite(number)) return '0';
      return number.toLocaleString('en-US', { maximumFractionDigits: 2 });
    },
    formatDate(value) {
      if (!value) return '-';
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return '-';
      return date.toLocaleDateString('en-GB');
    },
  },
};
</script>

<style scoped>
.reel-transfer-page {
  color: #0f172a;
}

.rt-header,
.rt-card {
  background: #ffffff;
  border: 1px solid #dbe3ee;
  border-radius: 8px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
}

.rt-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 24px 28px;
  margin-bottom: 18px;
}

.rt-eyebrow {
  color: #2563eb;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.rt-header h2 {
  margin: 4px 0 6px;
  font-size: 2rem;
  font-weight: 800;
  color: #0f172a;
}

.rt-header p {
  margin: 0;
  color: #64748b;
  font-weight: 600;
}

.rt-primary-btn,
.rt-actions .btn,
.rt-reel-search .btn,
.rt-filters .btn {
  min-height: 44px;
  border-radius: 8px;
  font-weight: 800;
}

.rt-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.6fr);
  gap: 18px;
  margin-bottom: 18px;
}

.rt-card {
  padding: 22px;
}

.rt-section-title {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.rt-section-title span {
  color: #1e293b;
  font-size: 1.15rem;
  font-weight: 800;
}

.rt-section-title small {
  color: #64748b;
  font-weight: 700;
}

.rt-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.rt-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.rt-field-wide {
  grid-column: 1 / -1;
}

.rt-field label {
  color: #475569;
  font-size: 0.88rem;
  font-weight: 800;
}

.rt-field label span {
  color: #ef4444;
}

.rt-input {
  width: 100%;
  min-height: 48px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #ffffff;
  color: #0f172a;
  padding: 10px 14px;
  font-size: 1rem;
  font-weight: 650;
}

.rt-input::placeholder {
  color: #94a3b8;
}

.rt-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16);
  outline: none;
}

.rt-readonly {
  background: #f1f5f9;
}

.rt-textarea {
  min-height: 84px;
  resize: vertical;
}

.rt-reel-search {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 100px;
  gap: 10px;
}

.rt-reel-card {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 18px;
  padding: 16px;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  background: #eff6ff;
}

.rt-reel-card span {
  display: block;
  color: #64748b;
  font-size: 0.76rem;
  font-weight: 800;
  text-transform: uppercase;
}

.rt-reel-card strong {
  color: #0f172a;
  font-size: 0.98rem;
}

.rt-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}

.rt-history-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.rt-history-item {
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
}

.rt-history-main {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  color: #0f172a;
  font-weight: 800;
}

.rt-history-item small {
  color: #64748b;
  font-weight: 700;
}

.rt-empty {
  min-height: 160px;
  display: grid;
  place-items: center;
  color: #64748b;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  font-weight: 800;
}

.rt-table-toolbar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.rt-filters {
  display: grid;
  grid-template-columns: 190px 160px 150px 150px 96px;
  gap: 10px;
}

.rt-filters .rt-input {
  min-height: 44px;
}

.rt-table {
  margin: 0;
  color: #0f172a;
}

.rt-table thead th {
  background: #eaf0f7;
  color: #334155;
  border-color: #cbd5e1;
  font-size: 0.78rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.rt-table td {
  border-color: #e2e8f0;
  vertical-align: middle;
  font-weight: 650;
}

.rt-location-badge {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 3px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #334155;
  font-weight: 800;
}

.rt-location-active {
  background: #dbeafe;
  color: #1d4ed8;
}

.rt-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-top: 16px;
  color: #475569;
  font-weight: 800;
}

@media (max-width: 1100px) {
  .rt-grid,
  .rt-form-grid,
  .rt-reel-card {
    grid-template-columns: 1fr;
  }

  .rt-table-toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .rt-filters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .rt-header {
    align-items: stretch;
    flex-direction: column;
  }

  .rt-reel-search,
  .rt-filters {
    grid-template-columns: 1fr;
  }
}

:global([data-theme="dark"] .reel-transfer-page),
:global(body.dark-mode .reel-transfer-page) {
  color: #f8fafc;
}

:global([data-theme="dark"] .reel-transfer-page .rt-header),
:global([data-theme="dark"] .reel-transfer-page .rt-card),
:global(body.dark-mode .reel-transfer-page .rt-header),
:global(body.dark-mode .reel-transfer-page .rt-card) {
  background: #172235;
  border-color: #34445c;
  box-shadow: none;
}

:global([data-theme="dark"] .reel-transfer-page .rt-header h2),
:global([data-theme="dark"] .reel-transfer-page .rt-section-title span),
:global([data-theme="dark"] .reel-transfer-page .rt-reel-card strong),
:global([data-theme="dark"] .reel-transfer-page .rt-history-main),
:global(body.dark-mode .reel-transfer-page .rt-header h2),
:global(body.dark-mode .reel-transfer-page .rt-section-title span),
:global(body.dark-mode .reel-transfer-page .rt-reel-card strong),
:global(body.dark-mode .reel-transfer-page .rt-history-main) {
  color: #f8fafc;
}

:global([data-theme="dark"] .reel-transfer-page .rt-header p),
:global([data-theme="dark"] .reel-transfer-page .rt-section-title small),
:global([data-theme="dark"] .reel-transfer-page .rt-field label),
:global([data-theme="dark"] .reel-transfer-page .rt-reel-card span),
:global([data-theme="dark"] .reel-transfer-page .rt-history-item small),
:global(body.dark-mode .reel-transfer-page .rt-header p),
:global(body.dark-mode .reel-transfer-page .rt-section-title small),
:global(body.dark-mode .reel-transfer-page .rt-field label),
:global(body.dark-mode .reel-transfer-page .rt-reel-card span),
:global(body.dark-mode .reel-transfer-page .rt-history-item small) {
  color: #b9c7dc;
}

:global([data-theme="dark"] .reel-transfer-page .rt-input),
:global(body.dark-mode .reel-transfer-page .rt-input) {
  background: #1d2a3d;
  border-color: #4a5d78;
  color: #f8fafc;
}

:global([data-theme="dark"] .reel-transfer-page .rt-input::placeholder),
:global(body.dark-mode .reel-transfer-page .rt-input::placeholder) {
  color: #8fa1b9;
}

:global([data-theme="dark"] .reel-transfer-page .rt-readonly),
:global(body.dark-mode .reel-transfer-page .rt-readonly) {
  background: #22314a;
}

:global([data-theme="dark"] .reel-transfer-page .rt-reel-card),
:global(body.dark-mode .reel-transfer-page .rt-reel-card) {
  background: #101b2d;
  border-color: #36506f;
}

:global([data-theme="dark"] .reel-transfer-page .rt-history-item),
:global(body.dark-mode .reel-transfer-page .rt-history-item) {
  background: #101b2d;
  border-color: #36506f;
}

:global([data-theme="dark"] .reel-transfer-page .rt-empty),
:global(body.dark-mode .reel-transfer-page .rt-empty) {
  border-color: #4a5d78;
  color: #b9c7dc;
}

:global([data-theme="dark"] .reel-transfer-page .rt-table),
:global(body.dark-mode .reel-transfer-page .rt-table) {
  color: #f8fafc;
}

:global([data-theme="dark"] .reel-transfer-page .rt-table thead th),
:global(body.dark-mode .reel-transfer-page .rt-table thead th) {
  background: #22314a;
  color: #f8fafc;
  border-color: #4a5d78;
}

:global([data-theme="dark"] .reel-transfer-page .rt-table td),
:global(body.dark-mode .reel-transfer-page .rt-table td) {
  border-color: #34445c;
  color: #f8fafc;
}

:global([data-theme="dark"] .reel-transfer-page .rt-location-badge),
:global(body.dark-mode .reel-transfer-page .rt-location-badge) {
  background: #24354f;
  color: #dbeafe;
}

:global([data-theme="dark"] .reel-transfer-page .rt-location-active),
:global(body.dark-mode .reel-transfer-page .rt-location-active) {
  background: #1d4ed8;
  color: #ffffff;
}
</style>
