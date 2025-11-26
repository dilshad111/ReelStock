<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-people"></i> Customers</h2>
      <button @click="showAddModal" class="btn btn-primary btn-lg">
        <i class="bi bi-plus-circle"></i> Add Customer
      </button>
    </div>

    <div class="card">
      <div class="card-body">
        <table class="table table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Customer Name</th>
              <th>Address</th>
              <th>Contact</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="customer in customers" :key="customer.id">
              <td>{{ customer.id }}</td>
              <td>{{ customer.customer_name }}</td>
              <td>{{ customer.address || '-' }}</td>
              <td>{{ customer.contact || '-' }}</td>
              <td>
                <button @click="editCustomer(customer)" class="btn btn-sm btn-warning me-2">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button @click="deleteCustomer(customer.id)" class="btn btn-sm btn-danger">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="customerModalLabel">{{ isEditing ? 'Edit Customer' : 'Add Customer' }}</h5>
            <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveCustomer">
              <div class="mb-3">
                <label for="customer_name" class="form-label">Customer Name *</label>
                <input type="text" class="form-control" id="customer_name" v-model="form.customer_name" required>
                <div v-if="errors.customer_name" class="text-danger">{{ errors.customer_name[0] }}</div>
              </div>
              <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" v-model="form.address" rows="3"></textarea>
                <div v-if="errors.address" class="text-danger">{{ errors.address[0] }}</div>
              </div>
              <div class="mb-3">
                <label for="contact" class="form-label">Contact</label>
                <input type="text" class="form-control" id="contact" v-model="form.contact">
                <div v-if="errors.contact" class="text-danger">{{ errors.contact[0] }}</div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary">{{ isEditing ? 'Update' : 'Save' }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CustomerComponent',
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      customers: [],
      form: {
        customer_name: '',
        address: '',
        contact: ''
      },
      isEditing: false,
      editingId: null,
      errors: {}
    };
  },
  mounted() {
    this.fetchCustomers();
  },
  methods: {
    fetchCustomers() {
      axios.get('/api/customers').then(response => {
        this.customers = response.data;
      }).catch(error => {
        console.error('Error fetching customers:', error);
        alert('Error loading customers');
      });
    },
    getModalInstance() {
      const modalEl = document.getElementById('customerModal');
      if (!modalEl || !window.bootstrap) {
        return null;
      }
      let modal = window.bootstrap.Modal.getInstance(modalEl);
      if (!modal) {
        modal = new window.bootstrap.Modal(modalEl);
      }
      return modal;
    },
    showAddModal() {
      this.resetForm();
      this.isEditing = false;
      this.errors = {};
      const modal = this.getModalInstance();
      if (modal) {
        modal.show();
        this.$nextTick(() => {
          const firstInput = document.getElementById('customer_name');
          if (firstInput) {
            firstInput.focus();
          }
        });
      }
    },
    editCustomer(customer) {
      this.form = { ...customer };
      this.isEditing = true;
      this.editingId = customer.id;
      this.errors = {};
      const modal = this.getModalInstance();
      if (modal) {
        modal.show();
      }
    },
    saveCustomer() {
      const url = this.isEditing ? `/api/customers/${this.editingId}` : '/api/customers';
      const method = this.isEditing ? 'put' : 'post';

      axios[method](url, this.form).then(response => {
        this.fetchCustomers();
        this.closeModal();
        this.resetForm();
      }).catch(error => {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors;
        } else {
          console.error('Error saving customer:', error);
          alert('Error saving customer');
        }
      });
    },
    closeModal() {
      const modal = this.getModalInstance();
      if (modal) {
        modal.hide();
      }
    },
    deleteCustomer(id) {
      if (confirm('Are you sure you want to delete this customer?')) {
        axios.delete(`/api/customers/${id}`).then(() => {
          this.fetchCustomers();
        }).catch(error => {
          console.error('Error deleting customer:', error);
          alert('Error deleting customer');
        });
      }
    },
    resetForm() {
      this.form = {
        customer_name: '',
        address: '',
        contact: ''
      };
      this.errors = {};
    }
  }
};
</script>

<style scoped>
.table th, .table td {
  vertical-align: middle;
}
</style>
