<template>
  <div class="container">
    <h2><i class="bi bi-shield-lock"></i> User Rights Management</h2>
    <div class="row mb-4">
      <div class="col-md-4">
        <label>Select User</label>
        <select v-model="selectedUserId" @change="loadPermissions" class="form-control">
          <option value="">Select a user</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
        </select>
      </div>
    </div>

    <div v-if="selectedUserId">
      <h4>Permissions for {{ selectedUser ? selectedUser.name : '' }}</h4>
      <form @submit.prevent="savePermissions">
        <div class="row">
          <div v-for="menu in menus" :key="menu.key" class="col-md-6 mb-4">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">{{ menu.name }}</h5>
              </div>
              <div class="card-body">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" :id="menu.key + '-view'" v-model="permissions[menu.key].can_view">
                  <label class="form-check-label" :for="menu.key + '-view'">
                    Can View
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" :id="menu.key + '-edit'" v-model="permissions[menu.key].can_edit">
                  <label class="form-check-label" :for="menu.key + '-edit'">
                    Can Edit
                  </label>
                </div>
                <div v-if="menu.hasAmounts" class="form-check">
                  <input class="form-check-input" type="checkbox" :id="menu.key + '-amounts'" v-model="permissions[menu.key].can_see_amounts">
                  <label class="form-check-label" :for="menu.key + '-amounts'">
                    Can See Amounts/Prices
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-success">Save Permissions</button>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      users: [],
      selectedUserId: '',
      selectedUser: null,
      permissions: {},
      menus: [
        { key: 'dashboard', name: 'Dashboard', hasAmounts: true },
        { key: 'supplier', name: 'Supplier Management', hasAmounts: false },
        { key: 'reel_receipt', name: 'Paper Reel Receipt', hasAmounts: true },
        { key: 'reel_issue', name: 'Reel Issue and Return', hasAmounts: true },
        { key: 'monthly_consumption', name: 'Monthly Consumption Report', hasAmounts: true },
        { key: 'reel_stock', name: 'Reel Stock Report', hasAmounts: true },
        { key: 'reel_receipt_report', name: 'Reel Received Report', hasAmounts: true },
        { key: 'monthly_closing', name: 'Monthly Closing Stock', hasAmounts: true },
        { key: 'cartons', name: 'Cartons', hasAmounts: false }
      ]
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
      this.selectedUser = this.users.find(u => u.id == this.selectedUserId);
      axios.get(`/api/user-permissions/${this.selectedUserId}`).then(response => {
        const perms = response.data;
        this.permissions = {};
        this.menus.forEach(menu => {
          const perm = perms.find(p => p.menu === menu.key);
          this.permissions[menu.key] = {
            can_view: perm ? perm.can_view : false,
            can_edit: perm ? perm.can_edit : false,
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
            can_edit: false,
            can_see_amounts: false
          };
        });
        alert('Failed to load permissions. Using defaults. Check console for details.');
      });
    },
    savePermissions() {
      const payload = [];
      for (const [menu, perms] of Object.entries(this.permissions)) {
        payload.push({
          menu,
          can_view: perms.can_view,
          can_edit: perms.can_edit,
          can_see_amounts: perms.can_see_amounts
        });
      }
      axios.post(`/api/user-permissions/${this.selectedUserId}`, { permissions: payload }).then(() => {
        alert('Permissions saved successfully!');
      });
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
