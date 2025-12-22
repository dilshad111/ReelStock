<template>
  <div class="container">
    <div class="mb-2">
      <h2 class="mb-0"><i class="bi bi-arrow-left-right"></i> Paper Reel Issue</h2>
    </div>
    <div class="mb-3">
      <button @click="toggleForm" class="btn btn-primary me-2">
        <i class="bi" :class="showForm ? 'bi-dash-circle' : 'bi-plus-circle'"></i>
        {{ showForm ? 'Close Issue Form' : 'Issue Reel' }}
      </button>
      <button @click="printTable" class="btn btn-secondary">Print Tables</button>
    </div>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>Issue Reel</h5>
        <form @submit.prevent="saveIssue()">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label>Reel No.</label>
                <input v-model="formData.reel_no" type="text" class="form-control" required @blur="fetchReel" :disabled="isEditing">
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
              <div class="mb-3">
                <label>Issue Date</label>
                <input v-model="formData.issue_date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Quantity Issued (kg)</label>
                <input v-model.number="formData.quantity_issued" @input="normalizeQuantities" type="number" step="0.01" min="0" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Return to Stock (kg)</label>
                <input v-model.number="formData.return_to_stock_weight" @input="normalizeQuantities" type="number" step="0.01" min="0" class="form-control">
              </div>
              <div v-if="formData.return_to_stock_weight > 0" class="mb-3">
                <label>Return Location</label>
                <select v-model="formData.return_location" class="form-control" required>
                  <option value="">Select Location</option>
                  <option value="GoDown">GoDown</option>
                  <option value="Factory">Factory</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Consumed Weight (kg)</label>
                <input :value="formattedBalance" type="text" class="form-control" readonly>
              </div>
              <div class="mb-3">
                <label>Issued To</label>
                <input v-model="formData.issued_to" type="text" class="form-control" required>
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
      <div class="col-12">
        <div class="row g-3 align-items-end mb-3">
          <div class="col-md-4 col-sm-6">
            <label class="form-label">Search Reel No.</label>
            <input v-model.trim="issueSearch" type="text" class="form-control" placeholder="Enter reel number to filter">
          </div>
        </div>
        <h4>Issues</h4>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Reel No.</th>
              <th>Quality</th>
              <th>Issue Date</th>
              <th>Quantity Issued</th>
              <th>Return to Stock</th>
              <th>Return Location</th>
              <th>Net Consumed</th>
              <th>Issued To</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="i in filteredIssues" :key="i.id">
              <td>{{ i.reel.reel_no }}</td>
              <td>{{ getQuality(i.reel) }}</td>
              <td>{{ i.issue_date }}</td>
              <td>{{ formatNumber(i.quantity_issued) }} kg</td>
              <td>{{ formatNumber(i.return_to_stock_weight || 0) }} kg</td>
              <td>{{ i.return_location || '-' }}</td>
              <td>{{ formatNumber(i.net_consumed_weight || 0) }} kg</td>
              <td>{{ i.issued_to }}</td>
              <td class="text-center" style="min-width: 150px;">
                <button class="btn btn-sm btn-warning me-1" @click="editIssue(i)">Edit</button>
                <button class="btn btn-sm btn-danger" @click="deleteIssue(i)">Delete</button>
              </td>
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
      reel: null,
      reelHistory: [],
      formData: {
        reel_no: '',
        issue_date: new Date().toISOString().substr(0,10),
        quantity_issued: '',
        return_to_stock_weight: 0,
        return_location: '',
        issued_to: 'Corrugation Plant',
        remarks: ''
      },
      showForm: false,
      editingIssueId: null,
      originalNetConsumed: 0,
      issueSearch: '',
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg',
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi'
    };
  },
  mounted() {
    this.fetchIssues();
    this.fetchSettings();
  },
  computed: {
    calculatedBalance() {
      const issued = Number(this.formData.quantity_issued) || 0;
      const returned = Number(this.formData.return_to_stock_weight) || 0;
      const balance = issued - returned;
      return balance < 0 ? 0 : balance;
    },
    formattedBalance() {
      return this.formatNumber(this.calculatedBalance);
    },
    isEditing() {
      return this.editingIssueId !== null;
    },
    filteredIssues() {
      if (!this.issueSearch) {
        return this.issues;
      }
      const term = this.issueSearch.trim().toLowerCase();
      return this.issues.filter(issue => issue?.reel?.reel_no?.toLowerCase().includes(term));
    }
  },
  methods: {
    fetchIssues() {
      axios.get('/api/reel-issues').then(response => {
        this.issues = response.data;
      });
    },
    fetchSettings() {
      axios.get('/api/setup/settings').then(response => {
        const settings = response.data || {};
        if (settings.company_name) this.companyName = settings.company_name;
        if (settings.company_address) this.companyAddress = settings.company_address;
        
        if (settings.company_logo) {
          this.companyLogo = window.location.origin + '/storage/' + settings.company_logo;
        } else {
          this.companyLogo = window.location.origin + '/images/quality-cartons-logo.svg';
        }
      }).catch(error => {
        console.error('Error fetching settings:', error);
      });
    },
    fetchReel() {
      const normalizedReelNo = this.normalizeReelNo(this.formData.reel_no);
      if (normalizedReelNo) {
        this.formData.reel_no = normalizedReelNo;
        axios.get(`/api/fetch-reel/${encodeURIComponent(normalizedReelNo)}`).then(response => {
          this.reel = response.data;
          const balance = parseFloat(this.reel.balance_weight) || 0;
          this.formData.quantity_issued = balance;
          this.formData.return_to_stock_weight = 0;
          this.originalNetConsumed = 0;
          this.loadReelHistory();
        }).catch(() => {
          alert('Reel not found or invalid');
          this.reel = null;
          this.reelHistory = [];
        });
      }
    },
    normalizeQuantities() {
      let issued = Number(this.formData.quantity_issued);
      let returned = Number(this.formData.return_to_stock_weight);

      if (!Number.isFinite(issued) || issued < 0) {
        issued = 0;
      }

      let maxIssued = Number(this.reel?.balance_weight);
      if (this.isEditing) {
        const allowance = Number(this.reel?.balance_weight) + Number(this.originalNetConsumed);
        maxIssued = Number.isFinite(allowance) ? allowance : issued;
      }
      if (!Number.isFinite(maxIssued) || maxIssued <= 0) {
        maxIssued = issued;
      }
      if (issued > maxIssued) {
        issued = maxIssued;
      }

      if (!Number.isFinite(returned) || returned < 0) {
        returned = 0;
      }

      if (returned > issued) {
        returned = issued;
      }

      this.formData.quantity_issued = issued;
      this.formData.return_to_stock_weight = returned;
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
      payload.quantity_issued = Number(payload.quantity_issued) || 0;
      payload.return_to_stock_weight = Number(payload.return_to_stock_weight) || 0;
      payload.net_consumed_weight = this.calculatedBalance;

      if (payload.return_to_stock_weight > payload.quantity_issued) {
        alert('Return to stock weight cannot exceed quantity issued.');
        return;
      }

      const request = this.editingIssueId
        ? axios.put(`/api/reel-issues/${this.editingIssueId}`, payload)
        : axios.post('/api/reel-issues', payload);

      request.then(() => {
        alert('Issue saved successfully.');
        this.fetchIssues();
        this.cancel();
      }).catch(error => {
        const message = error.response?.data?.error || 'Failed to save issue.';
        alert(message);
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
        return_to_stock_weight: 0,
        return_location: '',
        issued_to: 'Corrugation Plant',
        remarks: ''
      };
      this.reel = null;
      this.reelHistory = [];
      this.editingIssueId = null;
      this.originalNetConsumed = 0;
    },
    toggleForm() {
      this.showForm = !this.showForm;
      if (!this.showForm) {
        this.resetForm();
      }
    },
    editIssue(issue) {
      if (!issue) return;
      this.editingIssueId = issue.id;
      this.originalNetConsumed = Number(issue.net_consumed_weight) || 0;
      this.formData = {
        reel_no: issue.reel?.reel_no || '',
        issue_date: issue.issue_date,
        quantity_issued: Number(issue.quantity_issued) || 0,
        return_to_stock_weight: Number(issue.return_to_stock_weight) || 0,
        return_location: issue.return_location || '',
        issued_to: issue.issued_to,
        remarks: issue.remarks || ''
      };
      this.reel = issue.reel ? { ...issue.reel } : null;
      this.showForm = true;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    deleteIssue(issue) {
      if (!issue || !issue.id) {
        return;
      }
      if (!confirm('Are you sure you want to delete this issue?')) {
        return;
      }
      axios.delete(`/api/reel-issues/${issue.id}`)
        .then(() => {
          alert('Issue deleted successfully.');
          if (this.editingIssueId === issue.id) {
            this.cancel();
          }
          this.fetchIssues();
        })
        .catch(error => {
          const message = error.response?.data?.error || 'Failed to delete issue.';
          alert(message);
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
    },
    formatNumber(value) {
      const number = Number(value);
      if (!Number.isFinite(number)) {
        return '-';
      }
      return number.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    },
    printTable() {
      const printWindow = window.open('', '_blank');
      const logoHtml = this.companyLogo 
        ? `<div class="logo-section"><img src="${this.companyLogo}" alt="Logo" class="logo"></div>` 
        : '';
        
      printWindow.document.write(`
        <html>
          <head>
            <title>Reel Issue and Return - Print</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
            <style>
              body { margin: 20px; font-family: Arial, sans-serif; }
              .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
              .logo-section { flex-shrink: 0; }
              .logo { width: 1in; height: 1in; object-fit: contain; }
              .company-info { flex-grow: 1; text-align: center; }
              .company-name { font-size: 24px; font-weight: bold; margin: 0; }
              .company-address { font-size: 14px; margin: 5px 0 0 0; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 12px; }
              th { background-color: #f2f2f2; font-weight: bold; }
              h4 { margin-top: 20px; text-align: center; }
              @media print { body { margin: 0; } .header { border-bottom: 2px solid #000; } }
            </style>
          </head>
          <body>
            <div class="header">
              ${logoHtml}
              <div class="company-info">
                <div class="company-name">${this.companyName}</div>
                <div class="company-address">${this.companyAddress}</div>
              </div>
            </div>
            
            <h4>Paper Reel Issue Report</h4>

            <table class="table">
              <thead>
                <tr>
                  <th>Reel No.</th>
                  <th>Quality</th>
                  <th>Issue Date</th>
                  <th>Quantity Issued</th>
                  <th>Return to Stock</th>
                  <th>Return Location</th>
                  <th>Net Consumed</th>
                  <th>Issued To</th>
                </tr>
              </thead>
              <tbody>
                ${this.issues.map(i => `
                  <tr>
                    <td>${i.reel.reel_no}</td>
                    <td>${this.getQuality(i.reel)}</td>
                    <td>${this.formatDate(i.issue_date)}</td>
                    <td>${this.formatNumber(i.quantity_issued)} kg</td>
                    <td>${this.formatNumber(i.return_to_stock_weight || 0)} kg</td>
                    <td>${i.return_location || '-'}</td>
                    <td>${this.formatNumber(i.net_consumed_weight || 0)} kg</td>
                    <td>${i.issued_to}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
            <div style="margin-top: 20px; font-size: 10px; text-align: right;">
              Printed on: ${new Date().toLocaleString()}
            </div>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
      }, 500);
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
