<template>
  <div class="container-fluid px-0 animate__animated animate__fadeIn">
    <!-- Premium Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
      <div class="header-title">
        <h2 class="dashboard-title"><i class="bi bi-rulers text-indigo me-2"></i>Unit of Measures (UoM)</h2>
        <p class="text-muted mb-0 small">Define and manage dynamic unit types for your raw material inventory</p>
      </div>
      <button @click="openCreateDialog" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Unit of Measure
      </button>
    </div>

    <!-- Interactive Create/Edit Form (Standard Card matching Finished Goods style) -->
    <div v-if="dialogVisible" class="card mb-4 shadow-sm border-0 animated fadeIn">
      <div class="card-header bg-transparent border-0 pt-3 pb-0">
        <h5 class="fw-bold text-slate-800"><i class="bi bi-pencil-square text-indigo me-1"></i> {{ editing ? 'Edit' : 'Add' }} Unit of Measure</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm" novalidate>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">UoM Name (e.g. KG, Meter, Pcs, Roll) <span class="text-danger">*</span></label>
                <input v-model="form.name" type="text" class="form-control text-uppercase" placeholder="Enter short, uppercase unit name" maxlength="30" required />
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Status <span class="text-danger">*</span></label>
                <select v-model="form.status" class="form-control" required>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success shadow-sm" :disabled="saving">
              <i class="bi bi-save me-1"></i> Save Changes
            </button>
            <button type="button" @click="closeDialog" class="btn btn-secondary shadow-sm">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Table / Listing Section -->
    <div class="card shadow-sm border-0 mb-5">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-sm text-nowrap align-middle mb-0">
            <thead>
              <tr class="text-uppercase small text-muted">
                <th class="ps-3 py-3" style="width: 100px;">S.No.</th>
                <th class="py-3">Unit Name / Code</th>
                <th class="text-center py-3" style="width: 180px;">Status</th>
                <th class="text-center py-3" style="width: 220px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in uoms" :key="row.id">
                <td class="ps-3 fw-semibold text-muted">{{ index + 1 }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <div :style="{ backgroundColor: getRandomColor(row.name) }" class="uom-avatar me-3 text-white fw-bold d-flex align-items-center justify-content-center">
                      {{ row.name.substring(0,2).toUpperCase() }}
                    </div>
                    <span class="fw-bold text-slate-700">{{ row.name }}</span>
                  </div>
                </td>
                <td class="text-center">
                  <span :class="['badge py-1 px-2.5 rounded-pill', row.status === 'active' ? 'bg-success-soft text-success border border-success-subtle' : 'bg-danger-soft text-danger border border-danger-subtle']">
                    {{ row.status.toUpperCase() }}
                  </span>
                </td>
                <td class="text-center">
                  <button @click="editUom(row)" class="btn btn-sm btn-outline-primary border-0 me-1" title="Edit">
                    <i class="bi bi-pencil-fill me-1"></i> Edit
                  </button>
                  <button @click="deleteUom(row.id)" class="btn btn-sm btn-outline-danger border-0" title="Delete">
                    <i class="bi bi-trash-fill me-1"></i> Delete
                  </button>
                </td>
              </tr>
              <tr v-if="uoms.length === 0 && !loading">
                <td colspan="4" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-3 opacity-20"></i>
                  <h5 class="text-muted mb-1">No Unit of Measures defined yet</h5>
                  <p class="text-muted-50 small mb-0">Create UoM codes like KG, Meters, Pcs to get started.</p>
                </td>
              </tr>
              <tr v-if="loading">
                <td colspan="4" class="text-center py-5 text-muted">
                  <div class="spinner-border spinner-border-sm text-indigo me-2" role="status"></div>
                  Loading Unit of Measures...
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
import { ElMessage, ElMessageBox } from 'element-plus';

export default {
  name: 'UnitOfMeasureComponent',
  props: ['user'],
  data() {
    return {
      uoms: [],
      loading: false,
      saving: false,
      dialogVisible: false,
      editing: false,
      form: {
        id: null,
        name: '',
        status: 'active'
      }
    };
  },
  mounted() {
    if (this.user) {
      this.fetchUoms();
    }
  },
  watch: {
    user(newVal) {
      if (newVal) {
        this.fetchUoms();
      }
    }
  },
  methods: {
    fetchUoms() {
      this.loading = true;
      axios.get('/api/unit-of-measures')
        .then(response => {
          this.uoms = response.data;
        })
        .catch(error => {
          console.error('Error fetching UoMs:', error);
          ElMessage.error('Failed to load Unit of Measures');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    openCreateDialog() {
      this.editing = false;
      this.form = {
        id: null,
        name: '',
        status: 'active'
      };
      this.dialogVisible = true;
    },
    editUom(row) {
      this.editing = true;
      this.form = { ...row };
      this.dialogVisible = true;
    },
    closeDialog() {
      this.dialogVisible = false;
    },
    submitForm() {
      if (!this.form.name || !this.form.name.trim()) {
        ElMessage.error('UoM name is required');
        return;
      }
      this.saving = true;
      const uppercaseName = this.form.name.trim().toUpperCase();
      const payload = { ...this.form, name: uppercaseName };

      const request = this.editing
        ? axios.put(`/api/unit-of-measures/${this.form.id}`, payload)
        : axios.post('/api/unit-of-measures', payload);

      request.then(() => {
        ElMessage.success(this.editing ? 'UoM updated successfully' : 'UoM added successfully');
        this.fetchUoms();
        this.closeDialog();
      })
      .catch(error => {
        console.error('Error saving UoM:', error);
        if (error.response && error.response.data.errors) {
          const messages = Object.values(error.response.data.errors).flat().join('\n');
          ElMessage.error(messages);
        } else {
          ElMessage.error('Failed to save Unit of Measure');
        }
      })
      .finally(() => {
        this.saving = false;
      });
    },
    deleteUom(id) {
      ElMessageBox.confirm(
        'Are you sure you want to delete this Unit of Measure? It might affect products linked to it.',
        'Warning',
        {
          confirmButtonText: 'Yes, Delete',
          cancelButtonText: 'Cancel',
          type: 'warning',
        }
      ).then(() => {
        axios.delete(`/api/unit-of-measures/${id}`)
          .then(() => {
            ElMessage.success('UoM deleted successfully');
            this.fetchUoms();
          })
          .catch(error => {
            console.error('Error deleting UoM:', error);
            ElMessage.error('Failed to delete Unit of Measure');
          });
      }).catch(() => {});
    },
    getRandomColor(str) {
      const colors = ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#3b82f6', '#8b5cf6', '#06b6d4'];
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
      }
      const index = Math.abs(hash) % colors.length;
      return colors[index];
    }
  }
};
</script>

<style scoped>
.dashboard-title {
  color: #1e293b;
  font-weight: 700;
  margin-bottom: 0;
}
.text-indigo {
  color: #4f46e5;
}
.bg-success-soft {
  background-color: #dcfce7;
  color: #15803d;
}
.bg-danger-soft {
  background-color: #fee2e2;
  color: #b91c1c;
}
.text-slate-800 {
  color: #1e293b;
}
.text-slate-700 {
  color: #334155;
}
.card {
  border-radius: 12px;
  border: none;
  background-color: #ffffff;
}
.table th {
  font-weight: 700;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
}
.table td {
  padding: 12px 8px;
}
.uom-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-size: 11px;
}
.animated {
  animation-duration: 0.3s;
  animation-fill-mode: both;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.fadeIn {
  animation-name: fadeIn;
}
</style>
