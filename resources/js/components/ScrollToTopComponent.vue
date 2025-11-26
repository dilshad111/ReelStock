<template>
  <a
    v-show="isVisible"
    href="#top"
    @click.prevent="scrollToTop"
    class="scroll-to-top"
    :class="{ 'show': isVisible }"
    aria-label="Scroll to top"
  >
    <i class="bi bi-arrow-up-circle-fill"></i>
  </a>
</template>

<script>
export default {
  name: 'ScrollToTop',
  data() {
    return {
      isVisible: false
    };
  },
  mounted() {
    window.addEventListener('scroll', this.handleScroll);
  },
  beforeUnmount() {
    window.removeEventListener('scroll', this.handleScroll);
  },
  methods: {
    handleScroll() {
      this.isVisible = window.scrollY > 300;
    },
    scrollToTop() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }
  }
};
</script>

<style scoped>
.scroll-to-top {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1050;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: var(--primary-color);
  transition: opacity 0.3s ease, transform 0.3s ease;
  opacity: 0;
  transform: translateY(20px);
}

.scroll-to-top.show {
  opacity: 1;
  transform: translateY(0);
}

.scroll-to-top:hover {
  transform: scale(1.05);
  color: var(--primary-color);
  text-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.scroll-to-top i {
  font-size: 2.5rem;
}
</style>
