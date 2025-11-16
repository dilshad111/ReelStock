<template>
  <div class="theme-selector dropdown">
    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="themeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-palette"></i> Theme
    </button>
    <ul class="dropdown-menu" aria-labelledby="themeDropdown">
      <li v-for="theme in themes" :key="theme.id">
        <a class="dropdown-item" href="#" @click="setTheme(theme.id)">
          <i :class="theme.icon" class="me-2"></i>
          {{ theme.name }}
          <i v-if="currentTheme === theme.id" class="bi bi-check-lg ms-auto"></i>
        </a>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: 'ThemeSelector',
  data() {
    return {
      themes: [
        { id: 'light', name: 'Light', icon: 'bi bi-sun' },
        { id: 'dark', name: 'Dark', icon: 'bi bi-moon' },
        { id: 'blue', name: 'Blue', icon: 'bi bi-droplet' },
        { id: 'green', name: 'Green', icon: 'bi bi-tree' },
        { id: 'purple', name: 'Purple', icon: 'bi bi-gem' },
        { id: 'orange', name: 'Orange', icon: 'bi bi-brightness-high' }
      ],
      currentTheme: 'light'
    };
  },
  mounted() {
    // Load saved theme or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';
    this.setTheme(savedTheme);
  },
  methods: {
    setTheme(themeId) {
      this.currentTheme = themeId;
      localStorage.setItem('theme', themeId);
      document.documentElement.setAttribute('data-theme', themeId);

      // Emit event to parent components if needed
      this.$emit('theme-changed', themeId);
    }
  }
};
</script>

<style scoped>
.theme-selector .dropdown-item {
  display: flex;
  align-items: center;
}

.theme-selector .dropdown-item i:last-child {
  margin-left: auto;
}
</style>
