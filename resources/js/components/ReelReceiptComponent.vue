<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-plus-circle"></i> Paper Reel Receipt</h2>
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
        <form @submit.prevent="isBulk ? saveBulkReceipt() : saveReceipt()" novalidate>
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
                <div class="mb-3">
                  <label>Reel received by</label>
                  <input v-model="bulkData.common.received_by" type="text" class="form-control" required>
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
                    <div class="mb-3">
                      <label>Reel received by</label>
                      <input v-model="receipt.received_by" type="text" class="form-control" required>
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
          <div class="d-flex align-items-center gap-2 mt-3">
            <button type="submit" class="btn btn-success">{{ isBulk ? 'Save Bulk' : (editing ? 'Update' : 'Save') }}</button>
            <button @click="cancel" type="button" class="btn btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="row mb-3 g-3">
      <div class="col-md-4">
        <input v-model="reelSearch" type="text" class="form-control" placeholder="Search Reel No. (without RL)">
      </div>
    </div>
    <table class="table table-striped table-sticky-header">
      <thead>
        <tr>
          <th width="50"><input type="checkbox" @change="toggleSelectAll" :checked="allSelected"></th>
          <th>ID</th>
          <th>Reel No.</th>
          <th>Date</th>
          <th class="text-start">Supplier</th>
          <th class="text-start">Paper Quality</th>
          <th>Size</th>
          <th>Weight Kg</th>
          <th>Rate/Kg PKR</th>
          <th>QC Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="r in receiptList" :key="r.id + '-' + (resolvePaperQuality(r.reel)?.id || 'none')">
          <tr>
            <td><input type="checkbox" :value="r.id" v-model="selectedReceipts"></td>
            <td>{{ r.id }}</td>
            <td class="text-center">
              <a href="#" @click.prevent="showHistory(r.reel.reel_no)" class="d-block text-decoration-none">
                <div>{{ getReelPrefix(r.reel.reel_no) }}</div>
                <div>{{ getReelSerial(r.reel.reel_no) }}</div>
              </a>
            </td>
            <td>{{ formatDate(r.receiving_date) }}</td>
            <td class="text-start">{{ r.reel.supplier ? r.reel.supplier.name : 'N/A' }}</td>
            <td class="text-start">{{ r.reel.paper_quality_display }}</td>
            <td>{{ formatReelSize(r.reel.reel_size) }}</td>
            <td class="text-center">{{ formatWeightKg(r.reel.original_weight) }}</td>
            <td class="text-center">{{ formatRate(r.rate_per_kg) }}</td>
            <td>{{ r.qc_status }}</td>
            <td style="min-width: 230px;">
              <button @click="editReceipt(r)" class="btn btn-sm btn-warning me-1">Edit</button>
              <button @click="printLabel(r)" class="btn btn-sm btn-info me-1">Print Label</button>
              <button @click="deleteReceipt(r)" class="btn btn-sm btn-danger">Delete</button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <div class="d-flex justify-content-center mt-3" v-if="pagination.last_page > 1">
      <nav aria-label="Page navigation">
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: pagination.current_page == 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(1)">First</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page == 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">Previous</a>
          </li>
          <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page == pagination.current_page }">
            <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page == pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">Next</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page == pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.last_page)">Last</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>

</template>

<script>
import axios from 'axios';
import { reactive } from 'vue';

export default {
  props: {
    user: {
      type: Object,
      default: null
    }
  },
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
        received_by: 'Afzal',
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
          received_by: 'Afzal',
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
      isBulk: false,
      selectedReel: null,
      history: [],
      reelSearch: '',
      reelPrefix: 'RL111',
      reelPadding: 3,
      reelNextNumber: 1,
      pendingNextNumber: null,
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 0
      }
    };
  },
  computed: {
    receiptList() {
      return this.receipts;
    },
    allSelected() {
      return this.receipts.length > 0 && this.selectedReceipts.length === this.receipts.length;
    },
    pages() {
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      let start = Math.max(1, current - 2);
      let end = Math.min(last, current + 2);
      let pages = [];
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    }
  },
  watch: {
    reelSearch() {
      this.fetchReceipts({ reel_no: this.reelSearch || undefined });
    }
  },
  mounted() {
    if (localStorage.getItem('token')) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    }
    this.fetchReelSettings().then(() => {
      this.fetchReceipts();
    });
    this.fetchSuppliers();
    this.fetchQualities();
  },
  methods: {
    fetchReelSettings() {
      return axios.get('/api/setup/settings').then(response => {
        const settings = response.data || {};
        if (settings.reel_no_prefix) {
          this.reelPrefix = settings.reel_no_prefix;
        }
        if (settings.reel_padding) {
          const padding = parseInt(settings.reel_padding, 10);
          if (!Number.isNaN(padding) && padding > 0) {
            this.reelPadding = padding;
          }
        }
        if (settings.reel_next_number) {
          const nextNum = parseInt(settings.reel_next_number, 10);
          if (!Number.isNaN(nextNum) && nextNum > 0) {
            this.reelNextNumber = nextNum;
          }
        }
        this.pendingNextNumber = this.reelNextNumber;
      }).catch(() => {
        this.pendingNextNumber = this.reelNextNumber;
      });
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
    formatRate(rate) {
      if (rate === null || rate === undefined || rate === '') {
        return '-';
      }
      const numericRate = Number(rate);
      if (Number.isNaN(numericRate)) {
        return '-';
      }
      return numericRate.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    getReelPrefix(reelNo) {
      if (!reelNo) {
        return '';
      }
      return reelNo.length > 6 ? reelNo.substring(0, 6) : reelNo;
    },
    getReelSerial(reelNo) {
      if (!reelNo || reelNo.length <= 6) {
        return '';
      }
      return reelNo.substring(6);
    },
    formatReelSize(size) {
      if (size === null || size === undefined || size === '') {
        return 'N/A';
      }
      const numericSize = Number(size);
      if (Number.isNaN(numericSize)) {
        return `${size}"`;
      }
      return `${Math.round(numericSize)}"`;
    },
    formatWeightKg(weight) {
      if (weight === null || weight === undefined || weight === '') {
        return 'N/A';
      }
      const numericWeight = Number(weight);
      if (Number.isNaN(numericWeight)) {
        return weight;
      }
      return Math.round(numericWeight).toLocaleString('en-US');
    },
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
      const allLabels = [];
      selectedReels.forEach(reel => {
        // Create 2 identical labels for each reel
        for (let copy = 0; copy < 2; copy++) {
          allLabels.push(this.createLabelHTML(reel));
        }
      });

      const pages = [];
      for (let i = 0; i < allLabels.length; i += 6) {
        const pageLabels = allLabels.slice(i, i + 6);
        pages.push(this.buildPageHTML(pageLabels));
      }

      if (pages.length === 0) {
        pages.push(this.buildPageHTML([]));
      }

      return `
        <html>
          <head>
            <title>Bulk Reel Labels</title>
            <style>
              body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
              .page {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-template-rows: repeat(3, minmax(0, 1fr));
                gap: 4mm;
                padding: 6mm;
                box-sizing: border-box;
                width: 210mm;
                height: 297mm;
                page-break-after: always;
                align-items: stretch;
                justify-items: stretch;
              }
              .slot {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
                padding: 0;
                min-width: 0;
                min-height: 0;
              }
              .label {
                border: none;
                padding: 0;
                text-align: center;
                display: flex;
                flex-direction: column;
                justify-content: center;
                box-sizing: border-box;
                width: 96mm;
                height: 90mm;
                flex-shrink: 0;
                margin: 0 auto;
              }
              .label-table {
                width: 100%;
                border-collapse: collapse;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                font-weight: bold;
                height: 100%;
                border: 4px solid black;
              }
              .label-cell {
                border: 1px solid black;
                padding: 1px;
                text-align: left;
                height: 24px;
                box-sizing: border-box;
              }
              .label-header {
                font-weight: bold;
                background-color: #f0f0f0;
                width: 40%;
              }
              .label-value {
                font-weight: bold;
              }
              .label-blank {
                height: 24px;
              }
              .label-heading {
                font-size: 20px;
                font-weight: 900;
                text-align: center;
                background-color: #e0e0e0;
              }
              .label-quality {
                font-weight: bold;
              }
              .label-checkboxes {
                text-align: center;
                font-weight: bold;
              }
              @media print {
                body { margin: 0; }
                @page { size: A4 portrait; margin: 0; }
              }
            </style>
          </head>
          <body>
            ${pages.join('')}
          </body>
        </html>
      `;
    },
    printLabel(receipt) {
      const labelCopies = [
        this.createLabelHTML(receipt),
        this.createLabelHTML(receipt)
      ];

      const labelContent = `
        <html>
          <head>
            <title>Reel Label</title>
            <style>
              body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
              .page {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-template-rows: repeat(3, minmax(0, 1fr));
                gap: 4mm;
                padding: 6mm;
                box-sizing: border-box;
                width: 210mm;
                height: 297mm;
                align-items: stretch;
                justify-items: stretch;
              }
              .slot {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
                padding: 0;
                min-width: 0;
                min-height: 0;
              }
              .label {
                border: none;
                padding: 0;
                text-align: center;
                display: flex;
                flex-direction: column;
                justify-content: center;
                box-sizing: border-box;
                width: 96mm;
                height: 90mm;
                flex-shrink: 0;
                margin: 0 auto;
              }
              .label-table {
                width: 100%;
                border-collapse: collapse;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                font-weight: bold;
                height: 100%;
                border: 4px solid black;
              }
              .label-cell {
                border: 1px solid black;
                padding: 1px;
                text-align: left;
                height: 24px;
                box-sizing: border-box;
              }
              .label-header {
                font-weight: bold;
                background-color: #f0f0f0;
                width: 40%;
              }
              .label-value {
                font-weight: bold;
              }
              .label-blank {
                height: 24px;
              }
              .label-heading {
                font-size: 20px;
                font-weight: 900;
                text-align: center;
                background-color: #e0e0e0;
              }
              .label-quality {
                font-weight: bold;
              }
              .label-checkboxes {
                text-align: center;
                font-weight: bold;
              }
              @media print {
                body { margin: 0; }
                @page { size: A4 portrait; margin: 0; }
              }
            </style>
          </head>
          <body>
            ${this.buildPageHTML(labelCopies)}
          </body>
        </html>
      `;

      const labelWindow = window.open('', '_blank', 'width=800,height=600');
      labelWindow.document.write(labelContent);
      labelWindow.document.close();
      labelWindow.print();
    },
    createLabelHTML(receipt) {
      const quality = receipt.reel.paper_quality_display || 'N/A';
      return `
        <div class="label">
          <table class="label-table">
            <tr>
              <td colspan="2" class="label-cell label-heading" style="border-bottom: 3px solid black;">QUALITY CARTONS (PVT.) LTD.</td>
            </tr>
            <tr>
              <td class="label-cell label-header">Reel No:</td>
              <td class="label-cell label-value" style="text-align: center; font-size: 20px; font-weight: bold;">${receipt.reel.reel_no}</td>
            </tr>
            <tr>
              <td class="label-cell label-header">Supplier:</td>
              <td class="label-cell label-value">${receipt.reel.supplier ? receipt.reel.supplier.name : 'N/A'}</td>
            </tr>
            <tr>
              <td class="label-cell label-header">Quality:</td>
              <td class="label-cell label-quality">${quality}</td>
            </tr>
            <tr>
              <td class="label-cell label-header">Size:</td>
              <td class="label-cell label-value" style="font-size: 21px;">${receipt.reel.reel_size}"</td>
            </tr>
            <tr>
              <td class="label-cell label-header">Original Weight:</td>
              <td class="label-cell label-value" style="font-size: 21px;">${receipt.reel.original_weight} kg</td>
            </tr>
            <tr>
              <td class="label-cell label-header">1st Use:</td>
              <td class="label-cell label-blank">&nbsp;</td>
            </tr>
            <tr>
              <td class="label-cell label-header">2nd Use:</td>
              <td class="label-cell label-blank">&nbsp;</td>
            </tr>
            <tr>
              <td class="label-cell label-header">3rd Use:</td>
              <td class="label-cell label-blank">&nbsp;</td>
            </tr>
            <tr>
              <td class="label-cell label-header">Date:</td>
              <td class="label-cell label-value" style="font-size: 21px;">${this.formatDate(receipt.receiving_date)}</td>
            </tr>
            <tr>
              <td class="label-cell label-header">Shelf Life:</td>
              <td class="label-cell label-value" style="font-size: 21px;">${receipt.shelf_life || '10 years'}</td>
            </tr>
            <tr>
              <td class="label-cell label-header">Received By:</td>
              <td class="label-cell label-value" style="font-size: 21px;">${receipt.received_by || 'Afzal'}</td>
            </tr>
            <tr>
              <td colspan="2" class="label-cell label-checkboxes" style="font-size: 20px;">
                <strong>[✓] Approve</strong> &nbsp;&nbsp;&nbsp; [ ] Rejected
              </td>
            </tr>
          </table>
        </div>
      `;
    },
    buildPageHTML(labels) {
      const slots = [];
      for (let index = 0; index < 6; index++) {
        const labelContent = labels[index] || '';
        const slotClass = labelContent ? 'slot' : 'slot empty';
        slots.push(`<div class="${slotClass}" data-slot="${index + 1}">${labelContent}</div>`);
      }
      return `<div class="page">${slots.join('')}</div>`;
    },
    resolvePaperQuality(reel) {
      if (!reel) return null;
      return reel.paperQuality || reel.paper_quality || null;
    },
    getQuality(r) {
      const pq = this.resolvePaperQuality(r.reel);
      if (!pq) return 'N/A';
      const name = pq.quality || pq.item_code;
      const gsm = pq.gsm_range ? ` ${pq.gsm_range}` : '';
      return `${name}${gsm}`;
    },
    fetchReceipts(params = {}) {
      axios.get('/api/reel-receipts', { params }).then(response => {
        if (response.data.data && Array.isArray(response.data.data)) {
          this.receipts = reactive(response.data.data.map(item => {
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
          this.pagination = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total
          };
          if (response.data.reel_prefix) {
            this.reelPrefix = response.data.reel_prefix;
          }
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
    goToPage(page) {
      if (page < 1 || page > this.pagination.last_page) return;
      this.fetchReceipts({ page: page, reel_no: this.reelSearch || undefined });
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
      const requiredFields = ['paper_quality_id', 'supplier_id', 'reel_size', 'reel_weight', 'receiving_date', 'qc_status'];
      const missing = requiredFields.filter(field => !this.receipt[field]);
      if (missing.length) {
        alert('Please fill all required fields before saving.');
        return;
      }

      const payload = this.prepareReceiptPayload(this.receipt);
      const request = this.editing
        ? axios.put('/api/reel-receipts/' + this.receipt.id, payload)
        : axios.post('/api/reel-receipts', payload);

      request.then(() => {
        if (!this.editing) {
          this.advanceReelNumber(1);
        }
        this.fetchReceipts();
        this.cancel();
      }).catch(error => {
        console.error('Error saving receipt:', error);
        const apiErrors = error.response?.data?.errors;
        if (apiErrors) {
          const messages = Object.values(apiErrors).flat().join('\n');
          alert('Failed to save receipt:\n' + messages);
        } else {
          const errorMsg = error.response?.data?.error || error.message;
          alert('Failed to save receipt: ' + errorMsg);
        }
      });
    },
    saveBulkReceipt() {
      const reelCount = Array.isArray(this.bulkData.reels) ? this.bulkData.reels.length : 0;
      axios.post('/api/reel-receipts/bulk', this.bulkData).then(() => {
        if (reelCount > 0) {
          this.advanceReelNumber(reelCount);
        }
        this.fetchReceipts();
        this.cancel();
        alert('Bulk receipt saved successfully!');
      }).catch(error => {
        console.error('Error saving bulk receipt:', error);
        alert('Failed to save bulk receipt. Please check your data.');
      });
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
        received_by: r.received_by || 'Afzal',
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
    deleteReceipt(receipt) {
      if (!receipt || !receipt.id) {
        return;
      }
      if (!confirm('Are you sure you want to delete this receipt?')) {
        return;
      }
      axios.delete(`/api/reel-receipts/${receipt.id}`)
        .then(() => {
          if (this.editing && this.receipt.id === receipt.id) {
            this.cancel();
          }
          alert('Receipt deleted successfully.');
          this.fetchReceipts();
        })
        .catch(error => {
          const message = error.response?.data?.error || error.response?.data?.message || 'Failed to delete receipt.';
          alert(message);
        });
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
        received_by: 'Afzal',
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
          received_by: 'Afzal',
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
      const prefix = this.reelPrefix || 'RL111';
      const padding = Number(this.reelPadding) > 0 ? Number(this.reelPadding) : 3;
      const nextNum = Number(this.pendingNextNumber ?? this.reelNextNumber) || 1;
      this.receipt.reel_no = prefix + nextNum.toString().padStart(padding, '0');
    },
    advanceReelNumber(count = 1) {
      const increment = Number.isFinite(count) && count > 0 ? Math.floor(count) : 1;
      const current = Number(this.pendingNextNumber ?? this.reelNextNumber) || 1;
      const newNext = current + increment;
      this.pendingNextNumber = newNext;
      this.reelNextNumber = newNext;
      const value = newNext.toString();
      axios.post('/api/setup/settings', { key: 'reel_next_number', value })
        .catch(error => {
          console.error('Failed to persist next reel number:', error);
        });
    },
    showHistory(reelNo) {
      axios.get(`/api/reports/reel-stock/${reelNo}/history`).then(response => {
        this.selectedReel = response.data.reel;
        this.history = Array.isArray(response.data.history) ? response.data.history : [];
        this.openHistoryWindow();
      }).catch(error => {
        console.error('Error fetching history:', error);
        const errorMsg = error.response?.data?.error || error.message;
        alert('Error loading reel history: ' + errorMsg);
      });
    },
    openHistoryWindow() {
      const historyWindow = window.open('', '_blank');
      if (!historyWindow) {
        alert('Unable to open history window. Please allow pop-ups for this site.');
        return;
      }

      historyWindow.document.write(this.generateHistoryHTML());
      historyWindow.document.close();
    },
    escapeHtml(value) {
      if (value === null || value === undefined) {
        return '';
      }
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },
    generateHistoryHTML() {
      if (!this.selectedReel) {
        return '<html><body><p>No history available.</p></body></html>';
      }

      const rows = this.history.length
        ? this.history.map(entry => `
          <tr>
            <td>${this.escapeHtml(this.formatDate(entry.date))}</td>
            <td>${this.escapeHtml(entry.type)}</td>
            <td>${this.escapeHtml(entry.details)}</td>
            <td class="text-end">${this.escapeHtml(this.formatWeight(entry.weight))}</td>
            <td class="text-end">${this.escapeHtml(this.formatWeight(entry.balance))}</td>
          </tr>
        `).join('')
        : '<tr><td colspan="5" class="text-center">No history found.</td></tr>';

      return `
        <html>
          <head>
            <title>Reel History - ${this.escapeHtml(this.selectedReel.reel_no)}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              h1 { margin-bottom: 10px; }
              h2 { margin-top: 30px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ccc; padding: 8px; }
              th { background: #f5f5f5; }
              .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
              .summary-card { border: 1px solid #ddd; padding: 12px; border-radius: 4px; background: #fafafa; }
              .label { font-weight: bold; color: #555; }
              .value { font-size: 1.1em; }
              .meta { margin-top: 6px; color: #666; font-size: 0.9em; }
            </style>
          </head>
          <body>
            <h1>Reel History</h1>
            <div class="summary-grid">
              <div class="summary-card">
                <div class="label">Reel No.</div>
                <div class="value">${this.escapeHtml(this.selectedReel.reel_no)}</div>
                <div class="meta">Supplier: ${this.escapeHtml(this.selectedReel.supplier)}</div>
              </div>
              <div class="summary-card">
                <div class="label">Quality</div>
                <div class="value">${this.escapeHtml(this.selectedReel.quality)}</div>
                <div class="meta">GSM: ${this.escapeHtml(this.selectedReel.gsm ?? 'N/A')}</div>
              </div>
              <div class="summary-card">
                <div class="label">Original Weight</div>
                <div class="value">${this.escapeHtml(this.formatWeight(this.selectedReel.original_weight))} kg</div>
                <div class="meta">Current Balance: ${this.escapeHtml(this.formatWeight(this.selectedReel.current_balance ?? 0))} kg</div>
              </div>
              <div class="summary-card">
                <div class="label">Bursting Strength</div>
                <div class="value">${this.escapeHtml(this.selectedReel.bursting_strength ?? 'N/A')}</div>
              </div>
            </div>

            <h2>Consumption History</h2>
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Details</th>
                  <th class="text-end">Weight (kg)</th>
                  <th class="text-end">Balance (kg)</th>
                </tr>
              </thead>
              <tbody>
                ${rows}
              </tbody>
            </table>
          </body>
        </html>
      `;
    },
    prepareReceiptPayload(receipt) {
      const payload = { ...receipt };

      ['reel_size', 'reel_weight'].forEach(field => {
        if (payload[field] !== null && payload[field] !== undefined && payload[field] !== '') {
          payload[field] = Number(payload[field]);
        }
      });

      ['gsm', 'bursting_strength', 'rate_per_kg'].forEach(field => {
        payload[field] = this.toNullableNumber(payload[field]);
      });

      if (!payload.reel_no) {
        delete payload.reel_no;
      }

      if (!this.editing) {
        delete payload.id;
      }

      return payload;
    },
    toNullableNumber(value) {
      if (value === null || value === undefined || value === '') {
        return null;
      }
      const number = Number(value);
      return Number.isNaN(number) ? null : number;
    },
    formatWeight(value) {
      if (value === null || value === undefined) {
        return '-';
      }
      const num = Number(value);
      if (Number.isNaN(num)) {
        return '-';
      }
      return num >= 0 ? `${num.toFixed(2)}` : `(${Math.abs(num).toFixed(2)})`;
    },
    printTable() {
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Paper Reel Receipt - Print</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
            <style>
              body { margin: 20px; }
              table { width: 100%; border-collapse: collapse; }
              th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
              th { background-color: #f2f2f2; text-align: center; font-size: 0.75rem; }
              .text-center { text-align: center; }
              .text-end { text-align: right; }
              .text-start { text-align: left; }
              .fw-bold { font-weight: bold; }
              @media print { body { margin: 0; } }
            </style>
          </head>
          <body>
            <h2>Paper Reel Receipt</h2>
            <table class="table table-striped table-sticky-header">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Reel No.</th>
                  <th>Date</th>
                  <th class="text-start">Supplier</th>
                  <th class="text-start">Paper Quality</th>
                  <th>Size</th>
                  <th>Weight Kg</th>
                  <th>Rate/Kg PKR</th>
                  <th>QC Status</th>
                </tr>
              </thead>
              <tbody>
                ${this.receiptList.map(r => `
                  <tr>
                    <td class="text-center">${r.id}</td>
                    <td class="text-center">
                      <div>${this.getReelPrefix(r.reel.reel_no)}</div>
                      <div>${this.getReelSerial(r.reel.reel_no)}</div>
                    </td>
                    <td>${this.formatDate(r.receiving_date)}</td>
                    <td class="text-start">${r.reel.supplier ? r.reel.supplier.name : 'N/A'}</td>
                    <td class="text-start">${this.getQuality(r)}</td>
                    <td>${this.formatReelSize(r.reel.reel_size)}</td>
                    <td class="text-center fw-bold">${this.formatWeightKg(r.reel.original_weight)}</td>
                    <td class="text-center">${this.formatRate(r.rate_per_kg)}</td>
                    <td>${r.qc_status}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  }
};
</script>

<style scoped>
.table-sticky-header thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background-color: #f8f9fa;
  text-align: center;
  vertical-align: middle;
  padding: 4px 6px;
}

.table-sticky-header tbody td {
  vertical-align: middle;
  padding: 4px 6px;
}

.table-sticky-header tbody td:not(.text-start):not(.text-center):not(.text-end) {
  text-align: center;
}
</style>
