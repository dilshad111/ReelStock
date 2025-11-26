<template>
  <div v-if="user && user.email === 'superadmin@qc.com'">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-gear"></i> Application Setup</h2>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header">
              <h5>Company Information</h5>
            </div>
            <div class="card-body">
              <form @submit.prevent="updateCompanyInfo">
                <div class="mb-3">
                  <label for="companyName" class="form-label">Company Name</label>
                  <input type="text" class="form-control" id="companyName" v-model="companyName" placeholder="Enter company name" required>
                </div>
                <div class="mb-3">
                  <label for="companyAddress" class="form-label">Company Address</label>
                  <textarea class="form-control" id="companyAddress" v-model="companyAddress" rows="3" placeholder="Company address"></textarea>
                </div>
                <div class="mb-3">
                  <label for="companyLogo" class="form-label">Company Logo</label>
                  <input type="file" class="form-control" id="companyLogo" @change="handleLogoUpload" accept="image/*">
                  <small class="form-text text-muted">Upload a logo image (JPEG, PNG, JPG, GIF, max 2MB)</small>
                  <div v-if="logoPreview" class="mt-2">
                    <img :src="logoPreview" alt="Logo Preview" style="max-width: 200px; max-height: 100px;">
                  </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Company Info</button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header">
              <h5>Reel No Format</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label for="reelPrefix" class="form-label">Reel Number Prefix</label>
                <input type="text" class="form-control" id="reelPrefix" v-model="reelPrefix" placeholder="e.g. RL2026" required>
                <small class="form-text text-muted">The prefix part of the reel number</small>
              </div>
              <div class="mb-3">
                <label for="reelPadding" class="form-label">Sequential Number Padding</label>
                <input type="number" class="form-control" id="reelPadding" v-model.number="reelPadding" min="1" max="10" required>
                <small class="form-text text-muted">Number of digits for the sequential part (default: 6)</small>
              </div>
              <div class="mb-3">
                <label for="reelNextNumber" class="form-label">Next Sequential Number</label>
                <input type="number" class="form-control" id="reelNextNumber" v-model.number="reelNextNumber" min="1" required>
                <small class="form-text text-muted">The numeric portion that will be used for the next new reel (e.g. 1 produces {{ reelPrefix }}{{ formattedNextNumber }})</small>
              </div>
              <button @click="updateReelPrefix" class="btn btn-primary">Update Reel Settings</button>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5>Data Management</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <button @click="resetAllData" class="btn btn-danger w-100 mb-2">Reset All Data</button>
                <small class="form-text text-muted">This will delete all records from all tables. Use with caution!</small>
              </div>

              <div class="mb-3">
                <label for="tableSelect" class="form-label">Delete Single Table Data</label>
                <select class="form-select" id="tableSelect" v-model="selectedTable">
                  <option value="">Select Table</option>
                  <option v-for="table in tables" :key="table" :value="table">{{ table }}</option>
                </select>
                <button @click="deleteTableData" class="btn btn-warning mt-2" :disabled="!selectedTable">Delete Table Data</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else class="container mt-5">
    <div class="alert alert-danger text-center">
      <h4>Access Denied</h4>
      <p>Super administrator privileges are required to access this page.</p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SetupComponent',
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      reelPrefix: 'RL111',
      reelPadding: 3,
      reelNextNumber: 1,
      companyName: '',
      companyAddress: '',
      logoPreview: '',
      selectedTable: '',
      tables: []
    };
  },
  mounted() {
    if (this.isSuperAdmin()) {
      if (localStorage.getItem('token')) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
      }
      this.fetchSettings();
      this.fetchTables();
    }
  },
  computed: {
    formattedNextNumber() {
      const padding = Number(this.reelPadding) > 0 ? Number(this.reelPadding) : 1;
      const nextNumber = Number(this.reelNextNumber) > 0 ? Number(this.reelNextNumber) : 1;
      return nextNumber.toString().padStart(padding, '0');
    }
  },
  methods: {
    isSuperAdmin() {
      return this.user && this.user.email === 'superadmin@qc.com';
    },
    fetchSettings() {
      axios.get('/api/setup/settings').then(response => {
        this.reelPrefix = response.data.reel_no_prefix || 'RL111';
        this.reelPadding = parseInt(response.data.reel_padding) || 3;
        this.reelNextNumber = parseInt(response.data.reel_next_number) || 1;
        this.companyName = response.data.company_name || '';
        this.companyAddress = response.data.company_address || '';
        if (response.data.company_logo) {
          this.logoPreview = '/storage/' + response.data.company_logo;
        }
      }).catch(error => {
        console.error('Error fetching settings:', error);
        alert('Failed to load settings');
      });
    },
    fetchTables() {
      axios.get('/api/setup/tables').then(response => {
        this.tables = response.data;
      }).catch(error => {
        console.error('Error fetching tables:', error);
      });
    },
    updateReelPrefix() {
      if (!this.isSuperAdmin()) return;
      const newPrefix = (this.reelPrefix || '').trim();
      const padding = Number(this.reelPadding) || 0;
      const nextNumber = Number(this.reelNextNumber) || 0;

      if (!newPrefix) {
        alert('Please enter a valid prefix.');
        return;
      }

      if (!Number.isInteger(padding) || padding < 1) {
        alert('Padding must be a positive whole number.');
        return;
      }

      if (!Number.isInteger(nextNumber) || nextNumber < 1) {
        alert('Next number must be a positive whole number.');
        return;
      }

      const promises = [
        axios.post('/api/setup/settings', { key: 'reel_no_prefix', value: newPrefix }),
        axios.post('/api/setup/settings', { key: 'reel_padding', value: padding.toString() }),
        axios.post('/api/setup/settings', { key: 'reel_next_number', value: nextNumber.toString() })
      ];
      Promise.all(promises).then(() => {
        alert('Reel settings updated successfully');
        this.fetchSettings();
      }).catch(error => {
        console.error('Error updating reel settings:', error);
        alert('Failed to update reel settings');
      });
    },
    updateCompanyInfo() {
      if (!this.isSuperAdmin()) return;
      const promises = [];
      if (this.companyName) {
        promises.push(axios.post('/api/setup/settings', { key: 'company_name', value: this.companyName }));
      }
      if (this.companyAddress) {
        promises.push(axios.post('/api/setup/settings', { key: 'company_address', value: this.companyAddress }));
      }
      Promise.all(promises).then(() => {
        alert('Company information updated successfully');
      }).catch(error => {
        console.error('Error updating company info:', error);
        alert('Failed to update company information');
      });
    },
    handleLogoUpload(event) {
      if (!this.isSuperAdmin()) return;
      const file = event.target.files[0];
      if (file) {
        const formData = new FormData();
        formData.append('logo', file);
        axios.post('/api/setup/upload-logo', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }).then(() => {
          alert('Logo uploaded successfully');
          this.fetchSettings(); // Refresh to show new logo
        }).catch(error => {
          console.error('Error uploading logo:', error);
          alert('Failed to upload logo');
        });
      }
    },
    resetAllData() {
      if (confirm('Are you sure you want to reset ALL data? This action cannot be undone!')) {
        axios.post('/api/setup/reset-all').then(() => {
          alert('All data reset successfully');
          // Optionally refresh the page or redirect
          window.location.reload();
        }).catch(error => {
          console.error('Error resetting data:', error);
          alert('Failed to reset data');
        });
      }
    },
    deleteTableData() {
      if (!this.selectedTable) return;
      if (confirm(`Are you sure you want to delete all data from ${this.selectedTable}? This action cannot be undone!`)) {
        axios.post('/api/setup/delete-table', { table: this.selectedTable }).then(() => {
          alert(`Table ${this.selectedTable} data deleted successfully`);
          this.selectedTable = '';
        }).catch(error => {
          console.error('Error deleting table data:', error);
          alert('Failed to delete table data');
        });
      }
    }
  }
};
</script>

<style scoped>
/* Add any custom styles if needed */
</style>
