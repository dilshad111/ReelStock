<template>
  <div class="container">
    <div class="mb-2">
      <h2 class="mb-0"><i class="bi bi-truck"></i> Reel Return to Supplier</h2>
    </div>
    <div class="mb-3 d-flex flex-wrap gap-2">
      <button class="btn btn-primary" @click="toggleForm">
        <i class="bi" :class="showForm ? 'bi-dash-circle' : 'bi-plus-circle'"></i>
        {{ showForm ? 'Close Form' : 'Return Reel to Supplier' }}
      </button>
      <button class="btn btn-outline-success" @click="printAllReturns" :disabled="!returns.length">
        <i class="bi bi-printer"></i> Print All Returns
      </button>
    </div>

    <div v-if="showForm" class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h5 class="card-title mb-0">{{ isEditing ? 'Update Supplier Return' : 'Record Supplier Return' }}</h5>
          <button class="btn btn-outline-secondary" type="button" @click="printCurrentChallan" :disabled="!canPrintCurrent">
            <i class="bi bi-printer"></i> Print Draft Challan
          </button>
        </div>
        <form @submit.prevent="saveReturn" novalidate>
          <div class="row g-3">
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Challan No.</label>
              <input v-model="formData.challan_no" type="text" class="form-control" readonly disabled>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Reel No.</label>
              <input
                v-model.trim="formData.reel_no"
                type="text"
                class="form-control"
                placeholder="Enter Reel No"
                required
                :disabled="isEditing"
                @blur="fetchReel"
              >
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Return Date</label>
              <input v-model="formData.return_date" type="date" class="form-control" required>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Quantity Returned (kg)</label>
              <input
                v-model.number="formData.remaining_weight"
                type="number"
                step="0.01"
                min="0"
                :max="reel ? Number(reel.balance_weight) : undefined"
                class="form-control"
                required
              >
              <div class="form-text" v-if="reel">Current balance: {{ formatNumber(reel.balance_weight) }} kg</div>
            </div>
            <div class="col-lg-4 col-md-6">
              <label class="form-label">Condition</label>
              <select v-model="formData.condition" class="form-select" required>
                <option value="good">Good</option>
                <option value="damaged">Damaged</option>
                <option value="qc_required">QC Required</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Remarks</label>
              <textarea v-model.trim="formData.remarks" rows="2" class="form-control" placeholder="Optional remarks"></textarea>
            </div>
          </div>

          <div v-if="reel" class="row g-3 mt-4">
            <div class="col-xl-4 col-lg-6">
              <div class="info-card">
                <h6>Supplier Information</h6>
                <p class="mb-1"><strong>Name:</strong> {{ getSupplierName(reel) }}</p>
                <p class="mb-1"><strong>Contact:</strong> {{ reel.supplier?.contact || 'N/A' }}</p>
                <p class="mb-0"><strong>Phone:</strong> {{ reel.supplier?.phone || 'N/A' }}</p>
              </div>
            </div>
            <div class="col-xl-4 col-lg-6">
              <div class="info-card">
                <h6>Reel Details</h6>
                <p class="mb-1"><strong>Quality:</strong> {{ getQuality(reel) }}</p>
                <p class="mb-1"><strong>Size:</strong> {{ reel.reel_size }}"</p>
                <p class="mb-1"><strong>Original Weight:</strong> {{ formatNumber(reel.original_weight) }} kg</p>
                <p class="mb-0"><strong>Current Balance:</strong> {{ formatNumber(reel.balance_weight) }} kg</p>
              </div>
            </div>
            <div class="col-xl-4">
              <div class="info-card">
                <h6>Recent Receipt</h6>
                <p class="mb-1"><strong>Date:</strong> {{ formatDate(latestReceipt?.receiving_date) }}</p>
                <p class="mb-0"><strong>QC Status:</strong> {{ (latestReceipt?.qc_status || 'N/A').replace('_', ' ') }}</p>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="btn btn-success">
              <i class="bi" :class="isEditing ? 'bi-check-circle' : 'bi-save'"></i>
              {{ isEditing ? 'Update Return' : 'Save Return' }}
            </button>
            <button v-if="!isEditing" type="button" class="btn btn-outline-primary" @click="addEntryToBatch" :disabled="!reel">
              <i class="bi bi-list-plus"></i> Add to Return List
            </button>
            <button type="button" class="btn btn-secondary" @click="cancel">
              <i class="bi bi-x-circle"></i> Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="returnEntries.length" class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h5 class="card-title mb-0">Pending Supplier Returns</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-success" type="button" @click="printPendingBatch" :disabled="!returnEntries.length">
              <i class="bi bi-printer"></i> Print Challan
            </button>
            <button class="btn btn-sm btn-success" type="button" @click="submitBatch" :disabled="batchSubmitting || !returnEntries.length">
              <i class="bi bi-send"></i> {{ batchSubmitting ? 'Submitting…' : 'Submit All' }}
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Challan No.</th>
                <th>Reel No.</th>
                <th>Supplier</th>
                <th>Quality / Size</th>
                <th>Return Date</th>
                <th class="text-end">Qty (kg)</th>
                <th>Condition</th>
                <th>Remarks</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in returnEntries" :key="`${item.challan_no}-${idx}`">
                <td>{{ idx + 1 }}</td>
                <td>{{ item.challan_no }}</td>
                <td>{{ item.reel_no }}</td>
                <td>{{ item.reel ? getSupplierName(item.reel) : '-' }}</td>
                <td>
                  <div>{{ item.reel ? getQuality(item.reel) : '-' }}</div>
                  <small class="text-muted" v-if="item.reel?.reel_size">{{ item.reel.reel_size }}"</small>
                </td>
                <td>{{ formatDate(item.return_date) }}</td>
                <td class="text-end">{{ formatNumber(item.remaining_weight) }}</td>
                <td class="text-capitalize">{{ item.condition.replace('_', ' ') }}</td>
                <td>{{ item.remarks || '-' }}</td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-danger" type="button" @click="removePendingEntry(idx)" :disabled="batchSubmitting">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3 text-end fw-semibold">
          Total Pending Quantity: {{ formatNumber(pendingTotalWeight) }} kg
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0">Returns to Supplier</h5>
          <button class="btn btn-sm btn-outline-primary" @click="fetchReturns" :disabled="loading">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>
        <div class="row g-3 align-items-end mb-3">
          <div class="col-md-4 col-sm-6">
            <label class="form-label">Search Reel No.</label>
            <input v-model.trim="returnSearch" type="text" class="form-control" placeholder="Enter reel number to filter">
          </div>
        </div>
        <div v-if="returns.length === 0" class="alert alert-info mb-0">
          No returns to supplier recorded yet.
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Challan No.</th>
                <th>Reel No.</th>
                <th>Date</th>
                <th>Supplier</th>
                <th>Quality</th>
                <th class="text-end">Qty Returned (kg)</th>
                <th>Condition</th>
                <th>Remarks</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredReturns" :key="item.id">
                <td>{{ item.challan_no || '-' }}</td>
                <td>{{ item.reel?.reel_no || '-' }}</td>
                <td>{{ formatDate(item.return_date) }}</td>
                <td>{{ getSupplierName(item.reel) }}</td>
                <td>{{ getQuality(item.reel) }}</td>
                <td class="text-end">{{ formatNumber(item.remaining_weight) }}</td>
                <td class="text-capitalize">{{ item.condition.replace('_', ' ') }}</td>
                <td>{{ item.remarks || '-' }}</td>
                <td class="text-center" style="min-width: 180px;">
                  <button class="btn btn-sm btn-warning me-1" type="button" @click="editReturn(item)">
                    <i class="bi bi-pencil"></i> Edit
                  </button>
                  <button class="btn btn-sm btn-outline-secondary me-1" type="button" @click="printExistingReturn(item)">
                    <i class="bi bi-printer"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" type="button" @click="deleteReturn(item)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
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

const CHALLAN_PREFIX = 'RT';
const CHALLAN_PADDING = 3;

const defaultFormState = () => ({
  challan_no: '',
  reel_no: '',
  return_date: new Date().toISOString().split('T')[0],
  remaining_weight: '',
  condition: 'good',
  remarks: '',
  returned_to: 'supplier',
});

export default {
  name: 'ReelReturnSupplierComponent',
  props: {
    user: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      returns: [],
      returnEntries: [],
      reel: null,
      latestReceipt: null,
      loading: false,
      showForm: false,
      formData: defaultFormState(),
      editingId: null,
      returnSearch: '',
      batchSubmitting: false,
      nextChallanIndex: 1,
      currentBatchChallanNo: null,
    };
  },
  computed: {
    filteredReturns() {
      if (!this.returnSearch) {
        return this.returns;
      }
      const term = this.returnSearch.trim().toLowerCase();
      return this.returns.filter((item) => item?.reel?.reel_no?.toLowerCase().includes(term));
    },
    isEditing() {
      return this.editingId !== null;
    },
    canPrintCurrent() {
      return !!(
        this.formData.challan_no &&
        this.formData.reel_no &&
        this.formData.return_date &&
        Number(this.formData.remaining_weight) > 0
      );
    },
    pendingTotalWeight() {
      return this.returnEntries.reduce((total, entry) => total + (Number(entry.remaining_weight) || 0), 0);
    },
  },
  watch: {
    user() {
      this.ensureAuthHeader();
    },
  },
  mounted() {
    this.ensureAuthHeader();
    this.fetchReturns();
  },
  methods: {
    ensureAuthHeader() {
      const token = localStorage.getItem('token');
      if (token) {
        axios.defaults.headers.common.Authorization = `Bearer ${token}`;
      }
    },
    formatChallanNumber(seq) {
      return `${CHALLAN_PREFIX}${String(seq).padStart(CHALLAN_PADDING, '0')}`;
    },
    parseChallanNumber(value) {
      if (!value) return null;
      const match = value.toString().trim().toUpperCase().match(/^RT(\d+)$/);
      return match ? Number(match[1]) : null;
    },
    recalculateNextChallan() {
      const allNumbers = [
        ...this.returns.map((item) => this.parseChallanNumber(item.challan_no)).filter((n) => Number.isFinite(n)),
        ...this.returnEntries.map((item) => this.parseChallanNumber(item.challan_no)).filter((n) => Number.isFinite(n)),
      ];
      const maxNumber = allNumbers.length ? Math.max(...allNumbers) : 0;
      this.nextChallanIndex = maxNumber + 1;
      if (!this.isEditing) {
        this.formData.challan_no = this.currentBatchChallanNo
          ? this.currentBatchChallanNo
          : this.formatChallanNumber(this.nextChallanIndex);
      }
    },
    toggleForm() {
      this.showForm = !this.showForm;
      if (this.showForm) {
        if (!this.isEditing) {
          this.currentBatchChallanNo = null;
          this.formData = { ...defaultFormState(), challan_no: this.formatChallanNumber(this.nextChallanIndex) };
        }
      } else {
        if (!this.returnEntries.length) {
          this.currentBatchChallanNo = null;
        }
        this.clearForm();
      }
    },
    fetchReturns() {
      this.loading = true;
      axios
        .get('/api/reel-returns', { params: { returned_to: 'supplier' } })
        .then((response) => {
          const data = Array.isArray(response.data) ? response.data : [];
          this.returns = data.map((item) => ({
            ...item,
            challan_no: item.challan_no || this.formatChallanNumber(this.parseChallanNumber(item.id) || 0),
          }));
          this.recalculateNextChallan();
        })
        .catch((error) => {
          console.error('Error fetching supplier returns:', error);
          alert('Failed to load supplier returns.');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    normalizeReelNo(value) {
      if (!value) return '';
      let reelNo = String(value).trim().toUpperCase();
      reelNo = reelNo.replace(/\s+/g, '');
      if (!reelNo.startsWith('RL')) {
        reelNo = `RL${reelNo.replace(/^RL/i, '')}`;
      }
      return reelNo;
    },
    fetchReel() {
      if (this.isEditing) {
        return;
      }
      const normalized = this.normalizeReelNo(this.formData.reel_no);
      if (!normalized) {
        return;
      }
      this.formData.reel_no = normalized;
      axios
        .get(`/api/fetch-reel-return/${encodeURIComponent(normalized)}`)
        .then((response) => {
          this.setReelContext(response.data);
          if (this.reel && this.reel.balance_weight !== undefined) {
            this.formData.remaining_weight = Number(this.reel.balance_weight);
          }
        })
        .catch(() => {
          this.reel = null;
          this.latestReceipt = null;
          alert('Reel not found or invalid.');
        });
    },
    setReelContext(reelData) {
      this.reel = reelData || null;
      this.latestReceipt = reelData?.latest_receipt || reelData?.receipts?.[0] || null;
    },
    validatePayload(payload) {
      if (!payload.challan_no) {
        alert('Challan number is missing.');
        return false;
      }
      if (!payload.reel_no) {
        alert('Please enter a reel number.');
        return false;
      }
      if (!payload.return_date) {
        alert('Please select a return date.');
        return false;
      }
      if (!payload.remaining_weight || payload.remaining_weight <= 0) {
        alert('Please enter quantity returned greater than zero.');
        return false;
      }
      if (this.reel && payload.remaining_weight > Number(this.reel.balance_weight)) {
        alert('Returned quantity cannot exceed current balance weight.');
        return false;
      }
      return true;
    },
    buildPayload() {
      return {
        challan_no: this.formData.challan_no,
        reel_no: this.normalizeReelNo(this.formData.reel_no),
        return_date: this.formData.return_date,
        remaining_weight: Number(this.formData.remaining_weight) || 0,
        returned_to: 'supplier',
        condition: this.formData.condition,
        remarks: this.formData.remarks,
      };
    },
    handleSaveSuccess(message) {
      alert(message);
      this.fetchReturns();
      this.advanceChallanIndex();
      this.clearForm();
      this.showForm = false;
    },
    saveReturn() {
      const payload = this.buildPayload();
      if (!this.validatePayload(payload)) {
        return;
      }

      const request = this.editingId
        ? axios.put(`/api/reel-returns/${this.editingId}`, payload)
        : axios.post('/api/reel-returns', payload);

      request
        .then(() => {
          this.handleSaveSuccess(this.editingId ? 'Return updated successfully.' : 'Return recorded successfully.');
        })
        .catch((error) => {
          const message = error.response?.data?.error || error.response?.data?.message || 'Failed to save supplier return.';
          alert(message);
        });
    },
    addEntryToBatch() {
      const payload = this.buildPayload();
      if (!this.validatePayload(payload)) {
        return;
      }
      if (!this.currentBatchChallanNo) {
        this.currentBatchChallanNo = payload.challan_no || this.formatChallanNumber(this.nextChallanIndex);
      }
      payload.challan_no = this.currentBatchChallanNo;
      this.returnEntries.push({
        ...payload,
        reel: this.reel ? JSON.parse(JSON.stringify(this.reel)) : null,
      });
      this.formData = { ...defaultFormState(), challan_no: this.currentBatchChallanNo };
      this.reel = null;
      this.latestReceipt = null;
      alert('Entry added to pending list.');
    },
    removePendingEntry(index) {
      this.returnEntries.splice(index, 1);
      if (!this.returnEntries.length) {
        this.currentBatchChallanNo = null;
        this.recalculateNextChallan();
      }
    },
    async submitBatch() {
      if (!this.returnEntries.length || this.batchSubmitting) {
        return;
      }
      this.batchSubmitting = true;
      try {
        const challanNo = this.currentBatchChallanNo || this.formatChallanNumber(this.nextChallanIndex);
        const challanNumberValue = challanNo;
        const requests = this.returnEntries.map((entry) => {
          const { reel, ...payload } = entry;
          return axios.post('/api/reel-returns', {
            ...payload,
            challan_no: challanNumberValue,
          });
        });
        await Promise.all(requests);
        alert('All returns submitted successfully.');
        const lastNumber = this.parseChallanNumber(challanNumberValue);
        if (Number.isFinite(lastNumber)) {
          this.nextChallanIndex = lastNumber + 1;
        }
        this.returnEntries = [];
        this.currentBatchChallanNo = null;
        this.clearForm();
        this.fetchReturns();
      } catch (error) {
        const message = error.response?.data?.error || 'Failed to submit one of the returns.';
        alert(message);
      } finally {
        this.batchSubmitting = false;
      }
    },
    editReturn(item) {
      this.showForm = true;
      this.editingId = item.id;
      this.setReelContext(item.reel);
      this.formData = {
        challan_no: item.challan_no || this.formatChallanNumber(this.nextChallanIndex),
        reel_no: item.reel?.reel_no || '',
        return_date: item.return_date,
        remaining_weight: Number(item.remaining_weight) || 0,
        condition: item.condition,
        remarks: item.remarks || '',
        returned_to: 'supplier',
      };
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    deleteReturn(item) {
      if (!item || !item.id) {
        return;
      }
      if (!confirm('Are you sure you want to delete this return?')) {
        return;
      }
      axios
        .delete(`/api/reel-returns/${item.id}`)
        .then(() => {
          alert('Return deleted successfully.');
          if (this.editingId === item.id) {
            this.clearForm();
            this.showForm = false;
          }
          this.fetchReturns();
        })
        .catch((error) => {
          const message = error.response?.data?.error || 'Failed to delete return.';
          alert(message);
        });
    },
    cancel() {
      if (!this.returnEntries.length) {
        this.currentBatchChallanNo = null;
      }
      this.clearForm();
      this.showForm = false;
    },
    clearForm() {
      const challanNo = this.currentBatchChallanNo || this.formatChallanNumber(this.nextChallanIndex);
      this.formData = { ...defaultFormState(), challan_no: challanNo };
      this.reel = null;
      this.latestReceipt = null;
      this.editingId = null;
    },
    advanceChallanIndex() {
      this.nextChallanIndex += 1;
      this.currentBatchChallanNo = null;
      if (!this.isEditing) {
        this.formData.challan_no = this.formatChallanNumber(this.nextChallanIndex);
      }
    },
    buildPrintRows(entries) {
      return entries.map((entry, idx) => ({
        sr: idx + 1,
        challan_no: entry.challan_no,
        quality: entry.reel ? this.getQuality(entry.reel) : '-',
        size: entry.reel?.reel_size ? `${entry.reel.reel_size}"` : '-',
        return_date: this.formatDate(entry.return_date),
        quantity: this.formatNumber(entry.remaining_weight),
        condition: entry.condition.replace('_', ' '),
        remarks: entry.remarks || '-',
      }));
    },
    buildChallanHTML(entries, preparedBy = '-') {
      const rows = this.buildPrintRows(entries);
      if (!rows.length) {
        return null;
      }
      const challanNumber = rows[0].challan_no || '-';
      const challanDate = rows[0].return_date || '-';
      const supplierNames = [...new Set(entries.map((entry) => (entry.reel ? this.getSupplierName(entry.reel) : '-')))]
        .filter((name) => name && name !== '-')
        .join(', ') || '-';

      const tableRows = rows
        .map(
          (row) => `
            <tr>
              <td class="text-center">${row.sr}</td>
              <td>${row.quality}</td>
              <td>${row.size}</td>
              <td>${row.return_date}</td>
              <td class="text-end">${row.quantity}</td>
              <td>${row.condition}</td>
              <td>${row.remarks}</td>
            </tr>
          `,
        )
        .join('');

      return `
        <html>
          <head>
            <title>Reel Return to Supplier Challan</title>
            <style>
              body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20mm; }
              h1, h3 { margin: 0; }
              .header { text-align: center; margin-bottom: 16px; }
              .meta { margin-bottom: 18px; }
              .meta table { width: 100%; border-collapse: collapse; border: 1px solid #000; }
              .meta td { padding: 6px 8px; border: 1px solid #000; }
              .meta td.label { font-weight: 600; background-color: #f4f4f4; width: 30%; }
              table.challan { width: 100%; border-collapse: collapse; }
              table.challan th, table.challan td { border: 1px solid #000; padding: 6px 8px; font-size: 13px; }
              table.challan th { background-color: #f0f0f0; }
              .text-center { text-align: center; }
              .text-end { text-align: right; }
              footer { margin-top: 40px; display: flex; justify-content: space-between; position: fixed; bottom: 30mm; left: 20mm; right: 20mm; }
              .signature { width: 45%; border-top: 1px solid #000; text-align: center; padding-top: 6px; }
              .footer-spacer { height: 80px; }
              @media print {
                body { margin: 0; padding: 10mm; }
                footer { position: fixed; bottom: 15mm; left: 10mm; right: 10mm; }
                .footer-spacer { height: 120px; }
              }
            </style>
          </head>
          <body>
            <div class="header">
              <h1>QUALITY CARTONS (PVT.) LTD.</h1>
              <h3>Reel Return to Supplier Challan</h3>
            </div>
            <div class="meta">
              <table>
                <tr>
                  <td class="label">Challan No.</td><td>${challanNumber}</td>
                  <td class="label">Challan Date</td><td>${challanDate}</td>
                </tr>
                <tr><td class="label">Supplier(s)</td><td colspan="3">${supplierNames}</td></tr>
              </table>
            </div>
            <table class="challan">
              <thead>
                <tr>
                  <th class="text-center">Sr#</th>
                  <th>Quality</th>
                  <th>Size</th>
                  <th>Return Date</th>
                  <th class="text-end">Qty (kg)</th>
                  <th>Condition</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                ${tableRows}
              </tbody>
            </table>
            <div class="footer-spacer"></div>
            <footer>
              <div class="signature">Returned By</div>
              <div class="signature">Received By (Supplier)</div>
            </footer>
          </body>
        </html>
      `;
    },
    printChallanHTML(html) {
      if (!html) {
        alert('Nothing to print.');
        return;
      }
      const printWindow = window.open('', '_blank', 'width=1024,height=768');
      printWindow.document.write(html);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
    },
    printCurrentChallan() {
      const payload = this.buildPayload();
      if (!this.validatePayload(payload)) {
        return;
      }
      const html = this.buildChallanHTML([
        {
          ...payload,
          reel: this.reel,
        },
      ], this.user?.name);
      this.printChallanHTML(html);
    },
    printPendingBatch() {
      if (!this.returnEntries.length) {
        alert('No pending entries to print.');
        return;
      }
      const html = this.buildChallanHTML(this.returnEntries, this.user?.name);
      this.printChallanHTML(html);
    },
    collectEntriesByChallan(challanNo) {
      if (!challanNo) {
        return [];
      }
      return this.returns
        .filter((entry) => entry.challan_no === challanNo)
        .map((entry) => ({
          challan_no: entry.challan_no,
          reel_no: entry.reel?.reel_no,
          return_date: entry.return_date,
          remaining_weight: entry.remaining_weight,
          condition: entry.condition,
          remarks: entry.remarks,
          reel: entry.reel,
        }));
    },
    printExistingReturn(item) {
      if (!item) return;
      const challanEntries = this.collectEntriesByChallan(item.challan_no);
      const payload = challanEntries.length ? challanEntries : [
        {
          challan_no: item.challan_no,
          reel_no: item.reel?.reel_no,
          return_date: item.return_date,
          remaining_weight: item.remaining_weight,
          condition: item.condition,
          remarks: item.remarks,
          reel: item.reel,
        },
      ];
      const html = this.buildChallanHTML(payload, this.user?.name);
      this.printChallanHTML(html);
    },
    printAllReturns() {
      if (!this.returns.length) {
        alert('No returns to print.');
        return;
      }
      const html = this.buildChallanHTML(
        this.returns.map((item) => ({
          challan_no: item.challan_no,
          reel_no: item.reel?.reel_no,
          return_date: item.return_date,
          remaining_weight: item.remaining_weight,
          condition: item.condition,
          remarks: item.remarks,
          reel: item.reel,
        })),
        this.user?.name,
      );
      this.printChallanHTML(html);
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
    formatDate(value) {
      if (!value) return '-';
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return value || '-';
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      return `${day}/${month}/${date.getFullYear()}`;
    },
    formatNumber(value) {
      const number = Number(value);
      if (!Number.isFinite(number)) return '-';
      return number.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    },
  },
};
</script>

<style scoped>
.info-card {
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 12px 16px;
  background-color: #f8f9fa;
  height: 100%;
}

.info-card h6 {
  font-weight: 600;
  margin-bottom: 10px;
}
</style>
