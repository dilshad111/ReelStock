<template>
  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-lg">
          <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>My Profile</h5>
          </div>
          <div class="card-body p-4">
            <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
              {{ successMessage }}
              <button type="button" class="btn-close" @click="successMessage = ''"></button>
            </div>
            <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
              {{ errorMessage }}
              <button type="button" class="btn-close" @click="errorMessage = ''"></button>
            </div>

            <form @submit.prevent="updateProfile">
              <div class="row mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-bold">Full Name</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                    <input v-model="form.name" type="text" class="form-control" required placeholder="Enter your name">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                    <input v-model="form.email" type="email" class="form-control" required placeholder="Enter your email">
                  </div>
                </div>
              </div>

              <hr class="my-4">
              <h6 class="text-muted mb-3"><i class="fas fa-lock me-2"></i>Change Password (Optional)</h6>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">New Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                    <input v-model="form.password" type="password" class="form-control" placeholder="Minimum 8 characters">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Confirm New Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-check-double"></i></span>
                    <input v-model="form.password_confirmation" type="password" class="form-control" placeholder="Repeat new password">
                  </div>
                </div>
              </div>
              
              <div class="alert alert-info py-2" v-if="form.password">
                <small><i class="fas fa-info-circle me-1"></i>Leave password fields blank if you don't want to change it.</small>
              </div>

              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary py-2 fw-bold" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="fas fa-save me-2" v-else></i>
                  Update Profile
                </button>
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
  props: ['user'],
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      loading: false,
      successMessage: '',
      errorMessage: ''
    };
  },
  mounted() {
    if (this.user) {
      this.populateForm();
    }
  },
  watch: {
    user(newVal) {
      if (newVal) {
        this.populateForm();
      }
    }
  },
  methods: {
    populateForm() {
      this.form.name = this.user.name;
      this.form.email = this.user.email;
    },
    updateProfile() {
      this.loading = true;
      this.successMessage = '';
      this.errorMessage = '';

      axios.post('/api/user/profile', this.form)
        .then(response => {
          this.successMessage = response.data.message;
          this.form.password = '';
          this.form.password_confirmation = '';
          
          // Update user in localStorage and root
          if (this.$root) {
              this.$root.user = response.data.user;
              localStorage.setItem('user', JSON.stringify(response.data.user));
          }
        })
        .catch(error => {
          console.error(error);
          this.errorMessage = error.response?.data?.message || 'An error occurred while updating profile.';
        })
        .finally(() => {
          this.loading = false;
        });
    }
  }
};
</script>

<style scoped>
.card {
    border-radius: 12px;
}
.card-header {
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}
.input-group-text {
    border-right: none;
    background-color: #f8f9fa;
}
.form-control {
    border-left: none;
}
.form-control:focus {
    box-shadow: none;
    border-color: #dee2e6;
}
</style>
