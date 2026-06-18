<template>
  <div
    class="login-page-wrapper"
    :style="{ backgroundImage: `url(${loginBackground})` }"
  >
    <div class="login-overlay"></div>
    <div class="login-card-container animate__animated animate__fadeIn">
      <div class="login-card-glass">
        <button
          type="button"
          class="login-close-btn"
          aria-label="Close window"
          title="Close"
          @click="closeWindow"
        >
          <i class="bi bi-x-lg"></i>
        </button>
        <!-- Logo and Corporate branding -->
        <div class="text-center mb-4">
          <img src="/images/quality-cartons-logo.svg" alt="Quality Cartons" class="login-logo mb-3">
          <h2 class="company-title">QUALITY CARTONS</h2>
          <p class="company-subtitle">(PVT.) LTD.</p>
        </div>
        <h3 class="login-title mb-4">Login to ReelStock ERP</h3>
        
        <form @submit.prevent="login">
          <!-- Email Input -->
          <div class="form-group mb-3 text-start">
            <label class="form-label"><i class="bi bi-envelope-fill me-2"></i>Email Address</label>
            <input v-model="credentials.email" type="email" class="form-control" placeholder="name@company.com" required>
          </div>
          
          <!-- Password Input with Toggle -->
          <div class="form-group mb-4 text-start">
            <label class="form-label"><i class="bi bi-lock-fill me-2"></i>Password</label>
            <div class="input-group">
              <input v-model="credentials.password" :type="showPassword ? 'text' : 'password'" class="form-control password-field" placeholder="••••••••" required>
              <button class="btn btn-outline-secondary toggle-pass-btn" type="button" @click="showPassword = !showPassword" tabindex="-1">
                <i class="bi" :class="showPassword ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
              </button>
            </div>
          </div>
          
          <!-- Submit Button -->
          <button type="submit" class="btn btn-primary w-100 login-submit-btn" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ loading ? 'Securing Session...' : 'Login' }}
          </button>
        </form>
        
        <p v-if="error" class="error-msg text-center mt-3 animate__animated animate__shakeX"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ error }}</p>
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
      showPassword: false,
      loading: false,
      error: null,
      loginBackground: '/images/login_bg.png'
    };
  },
  methods: {
    closeWindow() {
      window.close();
      setTimeout(() => {
        if (!window.closed) {
          window.location.href = 'about:blank';
        }
      }, 150);
    },
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
.login-page-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: #0f172a;
  background-repeat: no-repeat;
  background-position: center center;
  background-attachment: fixed;
  background-size: cover;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 99999;
  font-family: 'Montserrat', sans-serif;
  overflow: hidden;
}

.login-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.75) 0%, rgba(30, 41, 59, 0.6) 100%);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  z-index: 1;
}

.login-card-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 460px;
  padding: 20px;
}

.login-card-glass {
  position: relative;
  background: rgba(30, 41, 59, 0.65) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4) !important;
  backdrop-filter: blur(16px) !important;
  -webkit-backdrop-filter: blur(16px) !important;
  border-radius: 20px;
  padding: 40px 35px;
  color: #f1f5f9;
  transition: all 0.3s ease;
}

.login-close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 34px;
  height: 34px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 8px;
  background: rgba(15, 23, 42, 0.55);
  color: #cbd5e1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.login-close-btn:hover {
  color: #ffffff;
  border-color: rgba(248, 113, 113, 0.5);
  background: rgba(127, 29, 29, 0.55);
}

.login-card-glass:hover {
  border-color: rgba(99, 102, 241, 0.25) !important;
  box-shadow: 0 20px 50px rgba(99, 102, 241, 0.15) !important;
}

.login-logo {
  height: 75px;
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
  transition: transform 0.3s ease;
}

.login-logo:hover {
  transform: scale(1.05) rotate(5deg);
}

.company-title {
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: 2px;
  margin: 0;
  color: #ffffff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.company-subtitle {
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 3px;
  color: #a5b4fc;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.login-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #cbd5e1;
  text-align: center;
  letter-spacing: 0.5px;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #cbd5e1;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.form-control {
  background: rgba(15, 23, 42, 0.6) !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  color: #f1f5f9 !important;
  border-radius: 10px !important;
  padding: 11px 15px !important;
  font-size: 0.95rem !important;
  font-weight: 500 !important;
  transition: all 0.25s ease !important;
}

.form-control:focus {
  border-color: #818cf8 !important;
  box-shadow: 0 0 12px rgba(129, 140, 248, 0.3) !important;
  background: rgba(15, 23, 42, 0.8) !important;
}

.form-control::placeholder {
  color: #64748b !important;
  font-size: 0.9rem;
}

.input-group {
  position: relative;
}

.password-field {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}

.toggle-pass-btn {
  background: rgba(15, 23, 42, 0.6) !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  border-left: none !important;
  color: #94a3b8 !important;
  border-top-right-radius: 10px !important;
  border-bottom-right-radius: 10px !important;
  padding: 0 15px !important;
  transition: all 0.25s ease !important;
}

.toggle-pass-btn:hover {
  color: #818cf8 !important;
  background: rgba(15, 23, 42, 0.8) !important;
}

.login-submit-btn {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
  border: none !important;
  border-radius: 10px !important;
  padding: 12px !important;
  font-weight: 700 !important;
  font-size: 0.95rem !important;
  letter-spacing: 0.5px !important;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35) !important;
  color: #ffffff !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  margin-top: 10px;
}

.login-submit-btn:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5) !important;
}

.login-submit-btn:active {
  transform: translateY(1px) !important;
}

.error-msg {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #f87171;
  padding: 10px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
}
</style>
