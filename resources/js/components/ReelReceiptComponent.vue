<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Paper Reel Receipt</h2>
      <div class="d-flex gap-2">
        <button v-if="selectedReceipts.length > 0" @click="printBulkLabels" class="btn btn-success">
          <i class="bi bi-printer"></i> Print Selected Labels ({{ selectedReceipts.length }})
        </button>
        <button @click="toggleSelectAll" class="btn btn-outline-primary">
          {{ allSelected ? 'Deselect All' : 'Select All' }}
        </button>
      </div>
    </div>
    <button @click="addNewReceipt" class="btn btn-primary mb-3">Add Receipt</button>
    <button @click="addBulkForm" class="btn btn-secondary mb-3">Bulk Add Reels</button>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>{{ isBulk ? 'Bulk Add' : (editing ? 'Edit' : 'Add') }} Receipt{{ isBulk ? 's' : '' }}</h5>
        <form @submit.prevent="isBulk ? saveBulkReceipt() : saveReceipt()">
          <template v-if="isBulk">
            <!-- Bulk Entry Form -->
            <div class="row mb-3">
              <div class="col-md-6">
                <h6>Common Information (Shared by all reels)</h6>
                <div class="mb-3">
                  <label>Paper Quality</label>
                  <select v-model="bulkData.common.paper_quality_id" class="form-control" required>
                    <option value="">Select Quality</option>
                    <option v-for="q in qualities" :key="q.id" :value="q.id">{{ q.quality }} - {{ q.gsm_range }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label>Supplier</label>
                  <select v-model="bulkData.common.supplier_id" class="form-control" required>
                    <option value="">Select Supplier</option>
                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.supplier_id }} - {{ s.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label>Receiving Date</label>
                  <input v-model="bulkData.common.receiving_date" type="date" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <h6>Quality Details (Shared)</h6>
                <div class="mb-3">
                  <label>GSM</label>
                  <input v-model="bulkData.common.gsm" type="number" step="0.01" class="form-control">
                </div>
                <div class="mb-3">
                  <label>Bursting Strength</label>
                  <input v-model="bulkData.common.bursting_strength" type="number" step="0.01" class="form-control">
                </div>
                <div class="mb-3">
                  <label>Rate per kg (PKR)</label>
                  <input v-model="bulkData.common.rate_per_kg" type="number" step="0.01" class="form-control">
                </div>
                <div class="mb-3">
                  <label>QC Status</label>
                  <select v-model="bulkData.common.qc_status" class="form-control" required>
                    <option value="on_hold">On Hold</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label>Remarks</label>
                  <textarea v-model="bulkData.common.remarks" class="form-control"></textarea>
                </div>
              </div>
            </div>

            <h6>Reel Details</h6>
            <button @click="addReelRow" type="button" class="btn btn-sm btn-outline-primary mb-2">Add Reel</button>
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Size (inches)</th>
                  <th>Weight (kg)</th>
                  <th style="width: 170px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(reel, index) in bulkData.reels" :key="index">
                  <td><input v-model="reel.reel_size" type="number" step="0.01" class="form-control" required></td>
                  <td><input v-model="reel.reel_weight" type="number" step="0.01" class="form-control" required></td>
                  <td><button @click="removeReelRow(index)" type="button" class="btn btn-sm btn-danger">Remove</button></td>
                </tr>
              </tbody>
            </table>
          </template>
          <template v-else>
            <!-- Single Entry Form -->
            <ul class="nav nav-tabs" id="receiptTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Info</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="quality-tab" data-bs-toggle="tab" data-bs-target="#quality" type="button" role="tab">Quality Details</button>
              </li>
            </ul>
            <div class="tab-content" id="receiptTabsContent">
              <div class="tab-pane fade show active" id="basic" role="tabpanel">
                <div class="row mt-3">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label>Reel Number</label>
                      <input v-model="receipt.reel_no" type="text" class="form-control" disabled placeholder="Auto-generated for new receipts">
                    </div>
                    <div class="mb-3">
                      <label>Paper Quality</label>
                      <select v-model="receipt.paper_quality_id" class="form-control" required>
                        <option value="">Select Quality</option>
                        <option v-for="q in qualities" :key="q.id" :value="q.id">{{ q.quality }} - {{ q.gsm_range }}</option>
                        <option v-if="qualities.length === 0" disabled>No qualities available</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label>Supplier</label>
                      <select v-model="receipt.supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.supplier_id }} - {{ s.name }}</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label>Reel Size (inches)</label>
                      <input v-model="receipt.reel_size" type="number" step="0.01" class="form-control" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label>Reel Weight (kg)</label>
                      <input v-model="receipt.reel_weight" type="number" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label>Receiving Date</label>
                      <input v-model="receipt.receiving_date" type="date" class="form-control" required>
                    </div>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="quality" role="tabpanel">
                <div class="row mt-3">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label>GSM</label>
                      <input v-model="receipt.gsm" type="number" step="0.01" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label>Bursting Strength</label>
                      <input v-model="receipt.bursting_strength" type="number" step="0.01" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label>Rate per kg (PKR)</label>
                      <input v-model="receipt.rate_per_kg" type="number" step="0.01" class="form-control">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label>QC Status</label>
                      <select v-model="receipt.qc_status" class="form-control" required>
                        <option value="on_hold">On Hold</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label>Remarks</label>
                      <textarea v-model="receipt.remarks" class="form-control"></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
          <button type="submit" class="btn btn-success mt-3">{{ isBulk ? 'Save Bulk' : (editing ? 'Update' : 'Save') }}</button>
          <button @click="cancel" class="btn btn-secondary ms-2">Cancel</button>
        </form>
      </div>
    </div>

    <table class="table table-striped">
      <thead>
        <tr>
          <th width="50"><input type="checkbox" @change="toggleSelectAll" :checked="allSelected"></th>
          <th>ID</th>
          <th>Reel No.</th>
          <th>Date</th>
          <th>Supplier</th>
          <th>Paper Quality</th>
          <th>Size</th>
          <th>Weight</th>
          <th>Rate/Kg</th>
          <th>QC Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="r in receiptList" :key="r.id + '-' + (resolvePaperQuality(r.reel)?.id || 'none')">
          <tr>
            <td><input type="checkbox" :value="r.id" v-model="selectedReceipts"></td>
            <td>{{ r.id }}</td>
            <td>{{ r.reel.reel_no }}</td>
            <td>{{ formatDate(r.receiving_date) }}</td>
            <td>{{ r.reel.supplier ? r.reel.supplier.name : 'N/A' }}</td>
            <td>{{ getQuality(r) }}</td>
            <td>{{ r.reel.reel_size || 'N/A' }}</td>
            <td>{{ r.reel.original_weight || 'N/A' }}</td>
            <td>{{ formatRate(r.rate_per_kg) }}</td>
            <td>{{ r.qc_status }}</td>
            <td style="min-width: 170px;">
              <button @click="editReceipt(r)" class="btn btn-sm btn-warning me-1">Edit</button>
              <button @click="printLabel(r)" class="btn btn-sm btn-info">Print Label</button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios';
import { reactive } from 'vue';

export default {
  data() {
    return {
      receipts: reactive([]),
      suppliers: [],
      qualities: [],
      selectedReceipts: [], // Array of selected receipt IDs
      receipt: {
        id: null,
        reel_no: '',
        paper_quality_id: '',
        supplier_id: '',
        reel_size: '',
        reel_weight: '',
        receiving_date: new Date().toISOString().substr(0,10),
        gsm: '',
        bursting_strength: '',
        rate_per_kg: '',
        qc_status: 'approved',
        remarks: ''
      },
      bulkData: {
        common: {
          paper_quality_id: '',
          supplier_id: '',
          receiving_date: new Date().toISOString().substr(0,10),
          gsm: '',
          bursting_strength: '',
          rate_per_kg: '',
          qc_status: 'approved',
          remarks: ''
        },
        reels: []
      },
      showForm: false,
      editing: false,
      isBulk: false
    };
  },
  computed: {
    receiptList() {
      return [...this.receipts];
    },
    allSelected() {
      return this.receipts.length > 0 && this.selectedReceipts.length === this.receipts.length;
    }
  },
  mounted() {
    if (localStorage.getItem('token')) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    }
    this.fetchReceipts();
    this.fetchSuppliers();
    this.fetchQualities();
  },
  methods: {
    toggleSelectAll() {
      if (this.allSelected) {
        this.selectedReceipts = [];
      } else {
        this.selectedReceipts = this.receipts.map(r => r.id);
      }
    },
    printBulkLabels() {
      if (this.selectedReceipts.length === 0) return;

      // Get selected receipts
      const selectedReels = this.receipts.filter(r => this.selectedReceipts.includes(r.id));

      // Create bulk label print window
      const labelWindow = window.open('', '_blank', 'width=800,height=600');
      labelWindow.document.write(this.generateBulkLabelHTML(selectedReels));
      labelWindow.document.close();
      labelWindow.print();
    },
    generateBulkLabelHTML(selectedReels) {
      // Group reels into sets of 3 (each with 2 copies = 6 labels per page)
      const labelGroups = [];
      for (let i = 0; i < selectedReels.length; i += 3) {
        labelGroups.push(selectedReels.slice(i, i + 3));
      }

      const pages = labelGroups.map(group => {
        const labels = [];
        group.forEach(reel => {
          // Create 2 identical labels for each reel
          for (let copy = 0; copy < 2; copy++) {
            labels.push(this.createLabelHTML(reel));
          }
        });

        // Fill remaining spaces with empty labels if needed
        while (labels.length < 6) {
          labels.push('<div class="label empty"></div>');
        }

        return `<div class="page">${labels.join('')}</div>`;
      }).join('');

      return `
        <html>
          <head>
            <title>Bulk Reel Labels</title>
            <style>
              body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
              .page {
                page-break-after: always;
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr 1fr 1fr;
                height: 297mm;
                width: 210mm;
                padding: 5mm;
                box-sizing: border-box;
                gap: 0;
              }
              .label {
                border: 2px solid black;
                padding: 8px;
                margin: 2.5mm;
                text-align: center;
                display: flex;
                flex-direction: column;
                justify-content: center;
              }
              .reel-no {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
                color: #000;
              }
              .supplier {
                font-size: 12px;
                font-weight: bold;
                margin-bottom: 3px;
              }
              .quality {
                font-size: 10px;
                margin-bottom: 5px;
              }
              .details {
                font-size: 9px;
                line-height: 1.2;
              }
              .details div { margin: 2px 0; }
              @media print { body { margin: 0; } }
            </style>
          </head>
          <body>
            ${pages}
          </body>
        </html>
      `;
    },
    printLabel(receipt) {
      const labelContent = `
        <html>
          <head>
            <title>Reel Label</title>
            <style>
              body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
              .page {
                page-break-after: always;
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr 1fr 1fr;
                height: 297mm;
                width: 210mm;
                padding: 5mm;
                box-sizing: border-box;
                gap: 0;
              }
              .label {
                border: 2px solid black;
                padding: 8px;
                margin: 2.5mm;
                text-align: center;
                display: flex;
                flex-direction: column;
                justify-content: center;
                height: calc((297mm - 10mm) / 3);
                box-sizing: border-box;
              }
              .empty { border: none; }
              .reel-no {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
                color: #000;
              }
              .supplier {
                font-size: 12px;
                font-weight: bold;
                margin-bottom: 3px;
              }
              .quality {
                font-size: 10px;
                margin-bottom: 5px;
              }
              .details {
                font-size: 9px;
                line-height: 1.2;
              }
              .details div { margin: 2px 0; }
              @media print { body { margin: 0; } }
            </style>
          </head>
          <body>
            <div class="page">
              ${this.createLabelHTML(receipt)}
              ${this.createLabelHTML(receipt)}
              <div class="label empty"></div>
              <div class="label empty"></div>
              <div class="label empty"></div>
              <div class="label empty"></div>
            </div>
          </body>
        </html>
      `;

      const labelWindow = window.open('', '_blank', 'width=800,height=600');
      labelWindow.document.write(labelContent);
      labelWindow.document.close();
      labelWindow.print();
    },
    createLabelHTML(receipt) {
      return `
        <div class="label">
          <div class="reel-no">${receipt.reel.reel_no}</div>
          <div class="supplier">${receipt.reel.supplier ? receipt.reel.supplier.name : 'N/A'}</div>
          <div class="quality">${(() => {
            const pq = this.resolvePaperQuality(receipt.reel);
            if (!pq) return 'N/A';
            return `${pq.quality || pq.item_code || 'N/A'} (${pq.gsm_range || ''})`.trim();
          })()}</div>
          <div class="details">
            <div>Size: ${receipt.reel.reel_size}"</div>
            <div>Weight: ${receipt.reel.original_weight} kg</div>
            <div>Date: ${this.formatDate(receipt.receiving_date)}</div>
            <div>QC: ${receipt.qc_status}</div>
          </div>
        </div>
      `;
    },
    resolvePaperQuality(reel) {
      if (!reel) return null;
      return reel.paperQuality || reel.paper_quality || null;
    },
    getQuality(r) {
      const pq = this.resolvePaperQuality(r.reel);
      if (!pq) return 'N/A';
      const name = pq.quality || pq.item_code;
      const gsm = pq.gsm_range ? ` (${pq.gsm_range})` : '';
      return `${name}${gsm}`;
    },
    formatRate(rate) {
      if (rate === null || rate === undefined || rate === '') {
        return '-';
      }
      const numericRate = Number(rate);
      if (Number.isNaN(numericRate)) {
        return '-';
      }
      return `PKR ${numericRate.toFixed(2)}`;
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
    fetchReceipts() {
      axios.get('/api/reel-receipts').then(response => {
        if (Array.isArray(response.data)) {
          this.receipts = reactive(response.data.map(item => {
            item = reactive(item);
            if (item.reel) {
              item.reel = reactive(item.reel);
              if (item.reel.paperQuality) {
                item.reel.paperQuality = reactive(item.reel.paperQuality);
              }
              if (item.reel.supplier) {
                item.reel.supplier = reactive(item.reel.supplier);
              }
            }
            return item;
          }));
          this.$nextTick(() => {
            this.$forceUpdate();
          });
        } else {
          alert('Error loading receipts: ' + (response.data.error || 'Unknown error'));
          this.receipts = reactive([]);
        }
        // Clear selections when data is refreshed
        this.selectedReceipts = [];
      }).catch(error => {
        console.error('Error fetching receipts:', error);
        alert('Failed to load receipts. Please check your connection.');
        this.receipts = reactive([]);
      });
    },
    fetchSuppliers() {
      axios.get('/api/suppliers').then(response => {
        this.suppliers = response.data;
      }).catch(error => {
        console.error('Error fetching suppliers:', error);
      });
    },
    fetchQualities() {
      axios.get('/api/paper-qualities').then(response => {
        this.qualities = response.data;
      }).catch(error => {
        console.error('Error fetching qualities:', error);
        alert('Failed to load paper qualities. Please check your connection.');
      });
    },
    addNewReceipt() {
      this.isBulk = false;
      this.editing = false;
      this.showForm = true;
      this.generateNextReelNo();
    },
    addBulkForm() {
      this.isBulk = true;
      this.showForm = true;
      this.editing = false;
      // Add one empty reel row by default
      if (this.bulkData.reels.length === 0) {
        this.addReelRow();
      }
    },
    addReelRow() {
      this.bulkData.reels.push({
        reel_size: '',
        reel_weight: ''
      });
    },
    removeReelRow(index) {
      this.bulkData.reels.splice(index, 1);
    },
    saveReceipt() {
      if (this.editing) {
        axios.put('/api/reel-receipts/' + this.receipt.id, this.receipt).then(() => {
          this.fetchReceipts();
          this.cancel();
        });
      } else {
        axios.post('/api/reel-receipts', this.receipt).then(() => {
          this.fetchReceipts();
          this.cancel();
        });
      }
    },
    saveBulkReceipt() {
      axios.post('/api/reel-receipts/bulk', this.bulkData).then(() => {
        this.fetchReceipts();
        this.cancel();
        alert('Bulk receipt saved successfully!');
      }).catch(error => {
        console.error('Error saving bulk receipt:', error);
        alert('Failed to save bulk receipt. Please check your data.');
      });
    },
    printLabel(receipt) {
      const labelContent = `
        <html>
          <head>
            <title>Reel Label</title>
            <style>
              body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
              .page {
                page-break-after: always;
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr 1fr 1fr;
                height: 297mm;
                width: 210mm;
                padding: 5mm;
                box-sizing: border-box;
                gap: 0;
              }
              .label {
                border: 2px solid black;
                padding: 8px;
                margin: 2.5mm;
                text-align: center;
                display: flex;
                flex-direction: column;
                justify-content: center;
                height: calc((297mm - 10mm) / 3);
                box-sizing: border-box;
              }
              .empty { border: none; }
              .reel-no {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
                color: #000;
              }
              .supplier {
                font-size: 12px;
                font-weight: bold;
                margin-bottom: 3px;
              }
              .quality {
                font-size: 10px;
                margin-bottom: 5px;
              }
              .details {
                font-size: 9px;
                line-height: 1.2;
              }
              .details div { margin: 2px 0; }
              @media print { body { margin: 0; } }
            </style>
          </head>
          <body>
            <div class="page">
              ${this.createLabelHTML(receipt)}
              ${this.createLabelHTML(receipt)}
              <div class="label empty"></div>
              <div class="label empty"></div>
              <div class="label empty"></div>
              <div class="label empty"></div>
            </div>
          </body>
        </html>
      `;

      const labelWindow = window.open('', '_blank', 'width=800,height=600');
      labelWindow.document.write(labelContent);
      labelWindow.document.close();
      labelWindow.print();
    },
    editReceipt(r) {
      this.receipt = {
        id: r.id,
        reel_no: r.reel.reel_no,
        paper_quality_id: r.reel.paper_quality_id,
        supplier_id: r.reel.supplier_id,
        reel_size: r.reel.reel_size,
        reel_weight: r.reel.original_weight,
        receiving_date: r.receiving_date,
        gsm: r.gsm,
        bursting_strength: r.bursting_strength,
        rate_per_kg: r.rate_per_kg,
        qc_status: r.qc_status,
        remarks: r.remarks
      };
      this.editing = true;
      this.showForm = true;
      // Scroll to top for editing
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    cancel() {
      this.isBulk = false;
      this.receipt = {
        id: null,
        reel_no: '',
        paper_quality_id: '',
        supplier_id: '',
        reel_size: '',
        reel_weight: '',
        receiving_date: new Date().toISOString().substr(0,10),
        gsm: '',
        bursting_strength: '',
        rate_per_kg: '',
        qc_status: 'approved',
        remarks: ''
      };
      this.bulkData = {
        common: {
          paper_quality_id: '',
          supplier_id: '',
          receiving_date: new Date().toISOString().substr(0,10),
          gsm: '',
          bursting_strength: '',
          rate_per_kg: '',
          qc_status: 'approved',
          remarks: ''
        },
        reels: []
      };
      this.showForm = false;
      this.editing = false;
      // Clear selections
      this.selectedReceipts = [];
    },
    generateNextReelNo() {
      // Generate preview of next reel number
      // This is a client-side preview, actual generation happens server-side
      axios.get('/api/reel-receipts?limit=1&orderBy=id&sort=desc').then(response => {
        if (response.data.length > 0) {
          const lastReel = response.data[0].reel.reel_no;
          const lastNum = parseInt(lastReel.substr(6));
          const nextNum = lastNum + 1;
          this.receipt.reel_no = 'RL2026' + nextNum.toString().padStart(6, '0');
        } else {
          this.receipt.reel_no = 'RL2026000001';
        }
      }).catch(() => {
        // Fallback
        this.receipt.reel_no = 'RL2026000001';
      });
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
