<template>
  <div class="user-rights-management">
    <el-card class="box-card shadow-lg professional-card mb-4">
      <template #header>
        <div class="card-header d-flex justify-content-between align-items-center py-2">
          <div class="header-title">
            <span class="fs-4 fw-800 text-slate-800"><i class="bi bi-shield-lock-fill me-2 text-primary"></i>User Rights Management</span>
            <p class="text-muted mb-0 small">Configure granular access control for system modules</p>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="user-selector">
              <el-select v-model="selectedUserId" @change="loadPermissions" placeholder="Select User" filterable class="user-select-box">
                <el-option v-for="user in users" :key="user.id" :label="user.name" :value="user.id">
                   <div class="d-flex align-items-center">
                      <i class="bi bi-person-circle me-2"></i>
                      <span>{{ user.name }}</span>
                   </div>
                </el-option>
              </el-select>
            </div>
            <el-button type="primary" :disabled="!selectedUserId" @click="savePermissions" :loading="saving" class="shadow-sm">
              <i class="bi bi-save me-2"></i> Save Changes
            </el-button>
          </div>
        </div>
      </template>

      <div v-if="selectedUserId" class="permissions-container">
        <div class="user-banner mb-4 p-3 rounded bg-light border border-light-subtle d-flex align-items-center">
            <div class="avatar-box me-3">
                <i class="bi bi-person-badge-fill fs-2 text-primary"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">{{ selectedUser ? selectedUser.name : '' }}</h5>
                <span class="text-muted small">{{ selectedUser ? selectedUser.email : '' }}</span>
            </div>
        </div>

        <el-table 
          :data="menus" 
          style="width: 100%" 
          v-loading="loading"
          class="modern-table"
          :header-cell-style="{backgroundColor: '#f8fafc', color: '#475569', fontWeight: '800', fontSize: '13px', textTransform: 'uppercase'}"
        >
          <el-table-column label="System Module" min-width="250">
            <template #default="scope">
              <div v-if="scope.row.isHeader" class="category-header text-primary fw-bold text-uppercase d-flex align-items-center">
                  <i class="bi bi-folder2-open me-2"></i>
                  {{ scope.row.name }}
              </div>
              <div v-else class="module-name-cell ps-4">
                <i :class="getModuleIcon(scope.row.key)" class="me-2 text-muted opacity-50"></i>
                <span class="fw-bold text-slate-700">{{ scope.row.name }}</span>
              </div>
            </template>
          </el-table-column>
          
          <el-table-column label="View" width="100" align="center">
            <template #default="scope">
              <div v-if="scope.row.isHeader" class="header-action">
                <el-checkbox :indeterminate="getCategoryIndeterminate(scope.row.name, 'can_view')" @change="(val) => toggleCategory(scope.row.name, 'can_view', val)" />
                <div class="small-label">ALL</div>
              </div>
              <el-checkbox v-else v-model="permissions[scope.row.key].can_view" class="custom-checkbox" />
            </template>
          </el-table-column>
          
          <el-table-column label="Add" width="100" align="center">
            <template #default="scope">
              <div v-if="scope.row.isHeader" class="header-action">
                <el-checkbox :indeterminate="getCategoryIndeterminate(scope.row.name, 'can_add')" @change="(val) => toggleCategory(scope.row.name, 'can_add', val)" />
                <div class="small-label">ALL</div>
              </div>
              <el-checkbox v-else v-model="permissions[scope.row.key].can_add" class="custom-checkbox" />
            </template>
          </el-table-column>
          
          <el-table-column label="Edit" width="100" align="center">
            <template #default="scope">
              <div v-if="scope.row.isHeader" class="header-action">
                <el-checkbox :indeterminate="getCategoryIndeterminate(scope.row.name, 'can_edit')" @change="(val) => toggleCategory(scope.row.name, 'can_edit', val)" />
                <div class="small-label">ALL</div>
              </div>
              <el-checkbox v-else v-model="permissions[scope.row.key].can_edit" class="custom-checkbox" />
            </template>
          </el-table-column>
          
          <el-table-column label="Delete" width="100" align="center">
            <template #default="scope">
              <div v-if="scope.row.isHeader" class="header-action">
                <el-checkbox :indeterminate="getCategoryIndeterminate(scope.row.name, 'can_delete')" @change="(val) => toggleCategory(scope.row.name, 'can_delete', val)" />
                <div class="small-label">ALL</div>
              </div>
              <el-checkbox v-else v-model="permissions[scope.row.key].can_delete" class="custom-checkbox" />
            </template>
          </el-table-column>
          
          <el-table-column label="Price/Amounts" width="160" align="center">
            <template #default="scope">
              <div v-if="scope.row.isHeader" class="header-action">
                <el-checkbox :indeterminate="getCategoryIndeterminate(scope.row.name, 'can_see_amounts')" @change="(val) => toggleCategory(scope.row.name, 'can_see_amounts', val)" />
                <div class="small-label">ALL</div>
              </div>
              <template v-else>
                <el-checkbox v-if="scope.row.hasAmounts" v-model="permissions[scope.row.key].can_see_amounts" class="custom-checkbox danger-check" />
                <el-tag v-else size="small" type="info" plain effect="light">N/A</el-tag>
              </template>
            </template>
          </el-table-column>
        </el-table>
      </div>
      
      <div v-else class="empty-state text-center py-5">
          <div class="empty-icon mb-3">
              <i class="bi bi-person-lock fs-1 text-muted opacity-25"></i>
          </div>
          <h5 class="text-muted">Select a user to manage their system permissions</h5>
      </div>
    </el-card>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    const groupedMenus = [
      {
        category: 'Dashboards',
        items: [
          { key: 'dashboard', name: 'Operational Dashboard', hasAmounts: false },
          { key: 'management-dashboard', name: 'Management Dashboard', hasAmounts: true },
          { key: 'transport-dashboard', name: 'Transport Dashboard', hasAmounts: true },
        ]
      },
      {
        category: 'Reels Inventory',
        items: [
          { key: 'suppliers', name: 'Supplier Management', hasAmounts: false },
          { key: 'qualities', name: 'Paper Qualities', hasAmounts: false },
          { key: 'receipts', name: 'Paper Reel Receipt', hasAmounts: true },
          { key: 'issues', name: 'Reel Issue and Return', hasAmounts: true },
          { key: 'return-supplier', name: 'Return to Supplier', hasAmounts: true },
          { key: 'stock-alerts', name: 'Stock Alerts', hasAmounts: false },
          { key: 'monthly-consumption', name: 'Monthly Consumption Report', hasAmounts: true },
          { key: 'reel-stock', name: 'Reel Stock Report', hasAmounts: true },
          { key: 'reel-receipt', name: 'Reel Received Report', hasAmounts: true },
          { key: 'monthly-closing', name: 'Monthly Closing Stock', hasAmounts: true },
          { key: 'reel-stock-count', name: 'Stock Count', hasAmounts: true },
          { key: 'usage-intelligence', name: 'Usage Intelligence', hasAmounts: true },
          { key: 'old-reels', name: 'Old Reels Report', hasAmounts: true },
          { key: 'reconciliation', name: 'Stock Reconciliation', hasAmounts: false },
        ]
      },
      {
        category: 'Finished Goods',
        items: [
          { key: 'fg-dashboard', name: 'FG Dashboard', hasAmounts: false },
          { key: 'fg-products', name: 'Product Master', hasAmounts: false },
          { key: 'fg-receipts', name: 'Production Entry', hasAmounts: false },
          { key: 'fg-dispatches', name: 'Dispatch Entry', hasAmounts: false },
          { key: 'fg-reports', name: 'FG Reports', hasAmounts: false },
          { key: 'fg-inventory-email', name: 'Inventory Email Report', hasAmounts: false },
        ]
      },
      {
        category: 'Raw Materials Inventory',
        items: [
          { key: 'rm-dashboard', name: 'RM Dashboard', hasAmounts: false },
          { key: 'rm-items', name: 'Material Master', hasAmounts: false },
          { key: 'rm-receipts', name: 'RM Receiving (GRN)', hasAmounts: true },
          { key: 'rm-consumptions', name: 'RM Consumption', hasAmounts: true },
          { key: 'rm-reports', name: 'RM Reports', hasAmounts: true },
        ]
      },
      {
        category: 'Production & Job Cards',
        items: [
          { key: 'job-cards', name: 'Production Job Cards', hasAmounts: false },
          { key: 'production-dashboard', name: 'Production Analytics', hasAmounts: false },
        ]
      },
      {
        category: 'QC Inspection',
        items: [
          { key: 'qc-inspection', name: 'Reel Inspection', hasAmounts: false },
        ]
      },
      {
        category: 'Transport Management',
        items: [
          { key: 'customers', name: 'Customer Management', hasAmounts: false },
          { key: 'transporters', name: 'Transporter Management', hasAmounts: false },
          { key: 'vehicles', name: 'Vehicle Management', hasAmounts: false },
          { key: 'vehicle-types', name: 'Vehicle Classifications', hasAmounts: false },
          { key: 'cartage-rates', name: 'Cartage Rate Setup', hasAmounts: true },
          { key: 'cartage', name: 'Cartage Billing', hasAmounts: true },
          { key: 'cartage-list', name: 'Cartage Bill List', hasAmounts: true },
          { key: 'cartage-report', name: 'Cartage Report', hasAmounts: true },
          { key: 'cartage-increment', name: 'Cartage Rate Increment', hasAmounts: true },
          { key: 'cartage-increment-history', name: 'Increment History', hasAmounts: true },
        ]
      },
      {
        category: 'Administration',
        items: [
          { key: 'setup', name: 'System Setup', hasAmounts: false },
          { key: 'users', name: 'User Management', hasAmounts: false },
          { key: 'user-rights', name: 'User Rights Management', hasAmounts: false },
          { key: 'audit-log', name: 'Audit Logs', hasAmounts: false },
        ]
      }
    ];

    const menus = [];
    groupedMenus.forEach(group => {
        menus.push({ isHeader: true, name: group.category, keys: group.items.map(i => i.key) });
        group.items.forEach(item => {
            menus.push({ ...item, isHeader: false, category: group.category });
        });
    });
    const permissions = {};
    menus.forEach(m => {
      permissions[m.key] = { can_view: false, can_add: false, can_edit: false, can_delete: false, can_see_amounts: false };
    });
    return {
      users: [],
      selectedUserId: '',
      selectedUser: null,
      permissions,
      menus,
      loading: false,
      saving: false
    };
  },
  mounted() {
    this.loadUsers();
  },
  methods: {
    loadUsers() {
      axios.get('/api/users').then(response => {
        this.users = response.data;
      }).catch(error => {
        console.error('Error loading users:', error);
        alert('Failed to load users. Check console for details.');
      });
    },
    loadPermissions() {
      this.loading = true;
      this.selectedUser = this.users.find(u => u.id == this.selectedUserId);
      axios.get(`/api/user-permissions/${this.selectedUserId}`).then(response => {
        const perms = response.data;
        this.permissions = {};
        this.menus.forEach(menu => {
          const perm = perms.find(p => p.menu === menu.key);
          this.permissions[menu.key] = {
            can_view: perm ? perm.can_view : false,
            can_add: perm ? perm.can_add : false,
            can_edit: perm ? perm.can_edit : false,
            can_delete: perm ? perm.can_delete : false,
            can_see_amounts: perm ? perm.can_see_amounts : false
          };
        });
      }).catch(error => {
        console.error('Error loading permissions:', error);
        // Still set default permissions
        this.permissions = {};
        this.menus.forEach(menu => {
          this.permissions[menu.key] = {
            can_view: false,
            can_add: false,
            can_edit: false,
            can_delete: false,
            can_see_amounts: false
          };
        });
        this.$message.error('Failed to load permissions');
      }).finally(() => {
        this.loading = false;
      });
    },
    savePermissions() {
      this.saving = true;
      const payload = [];
      for (const [menu, perms] of Object.entries(this.permissions)) {
        payload.push({
          menu,
          can_view: perms.can_view,
          can_add: perms.can_add,
          can_edit: perms.can_edit,
          can_delete: perms.can_delete,
          can_see_amounts: perms.can_see_amounts
        });
      }
      axios.post(`/api/user-permissions/${this.selectedUserId}`, { permissions: payload }).then(() => {
        this.$message.success('Permissions saved successfully!');
      }).catch(() => {
        this.$message.error('Failed to save permissions');
      }).finally(() => {
          this.saving = false;
      });
    },
    getModuleIcon(key) {
        const icons = {
            dashboard: 'bi bi-speedometer2',
            'management-dashboard': 'bi bi-graph-up-arrow',
            suppliers: 'bi bi-truck',
            qualities: 'bi bi-file-earmark-text',
            receipts: 'bi bi-box-seam',
            issues: 'bi bi-arrow-left-right',
            'return-supplier': 'bi bi-reply-all',
            'stock-alerts': 'bi bi-exclamation-triangle',
            'monthly-consumption': 'bi bi-calculator',
            'reel-stock': 'bi bi-boxes',
            'reel-receipt': 'bi bi-file-earmark-text',
            'monthly-closing': 'bi bi-calendar-check',
            'reel-stock-count': 'bi bi-list-check',
            'usage-intelligence': 'bi bi-cpu',
            'old-reels': 'bi bi-hourglass-split',
            customers: 'bi bi-people',
            transporters: 'bi bi-signpost-split',
            vehicles: 'bi bi-truck-flatbed',
            'vehicle-types': 'bi bi-card-list',
            'cartage-rates': 'bi bi-tag',
            cartage: 'bi bi-receipt',
            'cartage-list': 'bi bi-view-list',
            'cartage-report': 'bi bi-file-bar-graph',
            'cartage-increment': 'bi bi-graph-up',
            'cartage-increment-history': 'bi bi-clock-history',
            'fg-dashboard': 'bi bi-speedometer',
            'fg-products': 'bi bi-box',
            'fg-receipts': 'bi bi-download',
            'fg-dispatches': 'bi bi-upload',
            'fg-reports': 'bi bi-file-earmark-spreadsheet',
            'fg-inventory-email': 'bi bi-envelope-at',
            'qc-inspection': 'bi bi-clipboard2-check',
            'rm-dashboard': 'bi bi-layers',
            'rm-items': 'bi bi-box-fill',
            'rm-receipts': 'bi bi-file-earmark-arrow-down',
            'rm-consumptions': 'bi bi-file-earmark-arrow-up',
            'rm-reports': 'bi bi-clipboard-data',
            'job-cards': 'bi bi-card-checklist',
            'production-dashboard': 'bi bi-cpu',
            users: 'bi bi-person-lines-fill',
            'user-rights': 'bi bi-shield-lock',
            'audit-log': 'bi bi-journal-text',
            setup: 'bi bi-gear'
        };
        return icons[key] || 'bi bi-grid';
    },
    toggleCategory(categoryName, type, val) {
        const header = this.menus.find(m => m.isHeader && m.name === categoryName);
        if (header && header.keys) {
            header.keys.forEach(key => {
                // If it's price/amount, check if the menu item actually has amounts
                if (type === 'can_see_amounts') {
                    const menuItem = this.menus.find(m => m.key === key);
                    if (menuItem && menuItem.hasAmounts) {
                        this.permissions[key][type] = val;
                    }
                } else {
                    this.permissions[key][type] = val;
                }
            });
        }
    },
    getCategoryIndeterminate(categoryName, type) {
        const header = this.menus.find(m => m.isHeader && m.name === categoryName);
        if (!header || !header.keys) return false;
        
        let checkedCount = 0;
        let totalCount = 0;
        
        header.keys.forEach(key => {
            const menuItem = this.menus.find(m => m.key === key);
            if (type === 'can_see_amounts') {
                if (menuItem && menuItem.hasAmounts) {
                    totalCount++;
                    if (this.permissions[key][type]) checkedCount++;
                }
            } else {
                totalCount++;
                if (this.permissions[key][type]) checkedCount++;
            }
        });
        
        return checkedCount > 0 && checkedCount < totalCount;
    }
  }
};
</script>

<style scoped>
.user-rights-management {
    padding: 30px;
    background-color: #f1f5f9;
    min-height: calc(100vh - 120px);
    font-family: 'Montserrat', sans-serif;
}

.professional-card {
    border: none;
    border-radius: 16px;
}

.fw-800 { font-weight: 800; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }

.user-select-box {
    width: 280px;
}

.user-banner {
    border-radius: 12px;
}

.modern-table :deep(.el-table__row) {
    transition: background-color 0.2s;
}

.modern-table :deep(.el-table__row:hover) {
    background-color: #f8fafc !important;
}

.custom-checkbox :deep(.el-checkbox__inner) {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

.danger-check :deep(.el-checkbox__input.is-checked .el-checkbox__inner) {
    background-color: #ef4444;
    border-color: #ef4444;
}

:deep(.el-table__header th) {
    padding: 12px 0;
}

:deep(.el-table__row td) {
    padding: 14px 0;
}

.category-header {
    font-size: 11px;
    letter-spacing: 1.5px;
    background: #f1f5f9;
    padding: 4px 12px;
    border-radius: 4px;
    display: inline-block;
}

.modern-table :deep(.el-table__row) {
    background-color: transparent;
}

.modern-table :deep(.el-table__row:has(.category-header)) {
    background-color: #f1f5f9 !important;
    pointer-events: auto;
}

.header-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.small-label {
    font-size: 9px;
    font-weight: 800;
    margin-top: 2px;
    color: #64748b;
}

.module-name-cell {
    display: flex;
    align-items: center;
    font-size: 14px;
}
</style>
