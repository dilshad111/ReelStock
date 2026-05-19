<template>
  <div class="theme-toggle-wrapper">
    <button
      class="theme-toggle-btn"
      @click="toggleTheme"
      :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
    >
      <transition name="theme-icon" mode="out-in">
        <i v-if="isDark" class="bi bi-sun-fill theme-icon sun" key="sun"></i>
        <i v-else class="bi bi-moon-stars-fill theme-icon moon" key="moon"></i>
      </transition>
    </button>
  </div>
</template>

<script>
export default {
  name: 'ThemeSelectorComponent',
  data() {
    return {
      isDark: false
    };
  },
  mounted() {
    // Load saved preference from localStorage
    const saved = localStorage.getItem('reelstock-theme');
    if (saved === 'dark') {
      this.isDark = true;
      this.applyTheme('dark');
    } else {
      this.isDark = false;
      this.applyTheme('light');
    }
  },
  methods: {
    toggleTheme() {
      this.isDark = !this.isDark;
      const theme = this.isDark ? 'dark' : 'light';
      this.applyTheme(theme);
      localStorage.setItem('reelstock-theme', theme);
    },
    applyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      // Also set data-bs-theme for Bootstrap 5.3+ native dark mode support
      document.documentElement.setAttribute('data-bs-theme', theme);

      if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        document.body.classList.remove('light-mode');
      } else {
        document.body.classList.add('light-mode');
        document.body.classList.remove('dark-mode');
      }
    }
  }
};
</script>

<style scoped>
.theme-toggle-wrapper {
  display: flex;
  align-items: center;
}

.theme-toggle-btn {
  position: relative;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: 2px solid rgba(99, 102, 241, 0.25);
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(168, 85, 247, 0.08));
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.theme-toggle-btn:hover {
  transform: scale(1.12) rotate(15deg);
  border-color: rgba(99, 102, 241, 0.6);
  box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(168, 85, 247, 0.15));
}

.theme-toggle-btn:active {
  transform: scale(0.95);
}

.theme-icon {
  font-size: 1.15rem;
  transition: all 0.3s ease;
}

.theme-icon.sun {
  color: #fbbf24;
  filter: drop-shadow(0 0 6px rgba(251, 191, 36, 0.5));
}

.theme-icon.moon {
  color: #818cf8;
  filter: drop-shadow(0 0 6px rgba(129, 140, 248, 0.5));
}

/* Transition animations */
.theme-icon-enter-active {
  animation: iconIn 0.3s ease;
}
.theme-icon-leave-active {
  animation: iconOut 0.2s ease;
}

@keyframes iconIn {
  0% {
    opacity: 0;
    transform: rotate(-90deg) scale(0.4);
  }
  100% {
    opacity: 1;
    transform: rotate(0deg) scale(1);
  }
}

@keyframes iconOut {
  0% {
    opacity: 1;
    transform: rotate(0deg) scale(1);
  }
  100% {
    opacity: 0;
    transform: rotate(90deg) scale(0.4);
  }
}
</style>
