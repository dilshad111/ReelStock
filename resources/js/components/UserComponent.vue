<template>
  <div class="container">
    <div v-if="user && user.role && user.role.name === 'Admin'">
      <h2>User Management</h2>
      <button @click="showForm = !showForm" class="btn btn-primary mb-3">Add User</button>

      <div v-if="showForm" class="card mb-3">
        <div class="card-body">
          <h5>{{ editing ? 'Edit User' : 'Add User' }}</h5>
          <form @submit.prevent="saveUser">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label>Name</label>
                  <input v-model="formUser.name" type="text" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label>Email</label>
                  <input v-model="formUser.email" type="email" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label>Password</label>
                  <input v-model="formUser.password" type="password" class="form-control" :required="!editing">
                  <small v-if="editing" class="text-muted">Leave blank to keep current password</small>
                </div>
                <div class="mb-3">
                  <label>Role</label>
                  <select v-model="formUser.role_id" class="form-control" required>
                    <option value="">Select Role</option>
                    <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                  </select>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-success">{{ editing ? 'Update' : 'Save' }}</button>
            <button @click="cancel" class="btn btn-secondary">Cancel</button>
          </form>
        </div>
      </div>

      <table class="table table-striped">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td>{{ u.role ? u.role.name : '' }}</td>
            <td>
              <button @click="editUser(u)" class="btn btn-warning btn-sm">Edit</button>
              <button @click="deleteUser(u)" class="btn btn-danger btn-sm">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else>
      <h2>Access Denied</h2>
      <p>You do not have permission to view this page.</p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: ['user'],
  data() {
    return {
      users: [],
      roles: [],
      formUser: {
        name: '',
        email: '',
        password: '',
        role_id: ''
      },
      showForm: false,
      editing: false
    };
  },
  mounted() {
    if (this.user) {
      this.setAuthAndFetch();
    }
  },
  watch: {
    user(newVal) {
      if (newVal) {
        this.setAuthAndFetch();
      }
    }
  },
  methods: {
    setAuthAndFetch() {
      if (localStorage.getItem('token')) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
      }
      this.fetchUsers();
      this.fetchRoles();
    },
    fetchUsers() {
      axios.get('/api/users').then(response => {
        this.users = response.data;
      }).catch(error => {
        alert('Error fetching users: ' + error.response.data.message);
      });
    },
    fetchRoles() {
      axios.get('/api/roles').then(response => {
        this.roles = response.data;
      }).catch(error => {
        alert('Error fetching roles: ' + error.response.data.message);
      });
    },
    saveUser() {
      if (this.editing) {
        axios.put(`/api/users/${this.formUser.id}`, this.formUser).then(() => {
          this.fetchUsers();
          this.cancel();
        }).catch(error => {
          alert('Error updating user: ' + error.response.data.message);
        });
      } else {
        axios.post('/api/users', this.formUser).then(() => {
          this.fetchUsers();
          this.cancel();
        }).catch(error => {
          alert('Error creating user: ' + error.response.data.message);
        });
      }
    },
    editUser(u) {
      this.formUser = { ...u };
      this.editing = true;
      this.showForm = true;
    },
    deleteUser(u) {
      if (confirm('Are you sure?')) {
        axios.delete(`/api/users/${u.id}`).then(() => {
          this.fetchUsers();
        }).catch(error => {
          alert('Error deleting user: ' + error.response.data.message);
        });
      }
    },
    cancel() {
      this.formUser = {
        name: '',
        email: '',
        password: '',
        role_id: ''
      };
      this.showForm = false;
      this.editing = false;
    }
  }
};
</script>
