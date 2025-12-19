<template>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body text-center">
            <img src="/images/quality-cartons-logo.svg" alt="Quality Cartons Logo" class="mb-3" style="height: 60px;">
            <h2 class="company-name mb-4">QUALITY CARTONS (PVT.) LTD.</h2>
            <h3 class="card-title">Login to ReelStock</h3>
            <form @submit.prevent="login">
              <div class="mb-3">
                <label>Email</label>
                <input v-model="credentials.email" type="email" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Password</label>
                <input v-model="credentials.password" type="password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                {{ loading ? 'Logging in...' : 'Login' }}
              </button>
            </form>
            <p v-if="error" class="text-danger mt-3">{{ error }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      credentials: {
        email: '',
        password: ''
      },
      loading: false,
      error: null
    };
  },
  methods: {
    login() {
      this.loading = true;
      this.error = null;
      axios.post('/api/login', this.credentials).then(response => {
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        this.$emit('loggedIn', response.data.user);
        this.loading = false;
      }).catch(error => {
        this.error = 'Invalid credentials';
        this.loading = false;
      });
    }
  }
};
</script>

<style scoped>
.card {
  margin-top: 50px;
}
.company-name {
  font-family: 'Montserrat', sans-serif;
  font-weight: 700;
  font-size: 1.5rem;
  color: #333;
}
</style>
