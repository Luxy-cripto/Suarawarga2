<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const pageLoading = ref(false)
let loadingTimer = null

router.beforeEach(() => {
  clearTimeout(loadingTimer)
  pageLoading.value = true
})

router.afterEach(() => {
  loadingTimer = setTimeout(() => {
    pageLoading.value = false
  }, 450)
})

router.onError(() => {
  pageLoading.value = false
})
</script>

<template>
  <!-- HALAMAN -->
  <div
    class="app-page"
    :class="{ 'is-loading': pageLoading }"
  >
    <RouterView />
  </div>

  <!-- LOADING -->
  <Transition name="page-loading">
    <div
      v-if="pageLoading"
      class="page-loading-overlay"
    >
      <div class="loading-content">
        <div class="loading-spinner"></div>

        <span>Memuat...</span>
      </div>
    </div>
  </Transition>
</template>

<style>
/* =================================
   HALAMAN SAAT LOADING
================================= */

.app-page {
  transition:
    filter 0.25s ease,
    transform 0.25s ease;
}

.app-page.is-loading {
  filter: blur(4px);
  transform: scale(0.995);
}

/* =================================
   LOADING OVERLAY
================================= */

.page-loading-overlay {
  position: fixed;
  inset: 0;

  z-index: 99999;

  display: flex;
  align-items: center;
  justify-content: center;

  background: rgba(255, 255, 255, 0.18);

  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);

  pointer-events: all;
}

/* =================================
   LOADING BOX
================================= */

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 10px;

  min-width: 110px;
  padding: 18px 22px;

  border-radius: 16px;

  background: rgba(255, 255, 255, 0.92);

  box-shadow:
    0 8px 30px rgba(0, 0, 0, 0.12);

  color: #333;

  font-size: 14px;
  font-weight: 600;
}

/* =================================
   SPINNER
================================= */

.loading-spinner {
  width: 34px;
  height: 34px;

  border: 4px solid #e5e7eb;
  border-top-color: #2563eb;

  border-radius: 50%;

  animation: loading-spin 0.7s linear infinite;
}

@keyframes loading-spin {
  to {
    transform: rotate(360deg);
  }
}

/* =================================
   ANIMASI OVERLAY
================================= */

.page-loading-enter-active {
  transition: opacity 0.15s ease;
}

.page-loading-leave-active {
  transition: opacity 0.3s ease;
}

.page-loading-enter-from,
.page-loading-leave-to {
  opacity: 0;
}
</style>