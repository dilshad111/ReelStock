<template>
  <div class="container">
    <h2>Supplier Management</h2>
    <button @click="showForm = !showForm" class="btn btn-primary mb-3">Add Supplier</button>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>{{ editing ? 'Edit Supplier' : 'Add Supplier' }}</h5>
        <form @submit.prevent="saveSupplier">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label>Name</label>
                <input v-model="supplier.name" type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Contact Person</label>
                <input v-model="supplier.contact_person" type="text" class="form-control">
              </div>
              <div class="mb-3">
                <label>Phone</label>
                <input v-model="supplier.phone" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label>Address</label>
                <textarea v-model="supplier.address" class="form-control"></textarea>
              </div>
              <div class="mb-3">
                <label>Email</label>
                <input v-model="supplier.email" type="email" class="form-control">
              </div>
              <div class="mb-3">
                <label>Notes</label>
                <textarea v-model="supplier.notes" class="form-control"></textarea>
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
          <th>Supplier ID</th>
          <th>Name</th>
          <th>Contact Person</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="sup in suppliers" :key="sup.id">
          <td>{{ sup.supplier_id }}</td>
          <td>{{ sup.name }}</td>
          <td>{{ sup.contact_person }}</td>
          <td>{{ sup.phone }}</td>
          <td>{{ sup.email }}</td>
          <td>
            <button @click="editSupplier(sup)" class="btn btn-sm btn-warning">Edit</button>
            <button @click="deleteSupplier(sup.id)" class="btn btn-sm btn-danger">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: ['user'],
  data() {
    return {
      suppliers: [],
      supplier: {
        supplier_id: '',
        name: '',
        contact_person: '',
        address: '',
        phone: '',
        email: '',
        notes: ''
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
      this.fetchSuppliers();
    },
    fetchSuppliers() {
      axios.get('/api/suppliers').then(response => {
        this.suppliers = response.data;
      });
    },
    saveSupplier() {
      if (this.editing) {
        axios.put(`/api/suppliers/${this.supplier.id}`, this.supplier).then(() => {
          this.fetchSuppliers();
          this.cancel();
        });
      } else {
        axios.post('/api/suppliers', this.supplier).then(() => {
          this.fetchSuppliers();
          this.cancel();
        });
      }
    },
    editSupplier(sup) {
      this.supplier = { ...sup };
      this.editing = true;
      this.showForm = true;
    },
    deleteSupplier(id) {
      if (confirm('Are you sure?')) {
        axios.delete(`/api/suppliers/${id}`).then(() => {
          this.fetchSuppliers();
        });
      }
    },
    cancel() {
      this.supplier = {
        name: '',
        contact_person: '',
        address: '',
        phone: '',
        email: '',
        notes: ''
      };
      this.showForm = false;
      this.editing = false;
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
