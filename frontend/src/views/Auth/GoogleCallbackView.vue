<template>
  <div class="auth-page">
    <div class="auth-wrapper">
      <div class="auth-card card fade-in" style="text-align: center;">
        <div v-if="loading">
          <div class="auth-spinner" style="width: 32px; height: 32px; border-width: 3px; margin: 0 auto 16px; border-top-color: var(--primary);"></div>
          <p>Menyelesaikan login dengan Google...</p>
        </div>
        <div v-else-if="error">
          <p class="auth-error">⚠️ {{ error }}</p>
          <RouterLink to="/login" class="btn btn-primary" style="margin-top: 16px;">Kembali ke Login</RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref('')

async function fetchUserAndRedirect(token) {
  localStorage.setItem('token', token)

  try {
    const res = await api.get('/profile')
    localStorage.setItem('user', JSON.stringify(res.data))

    if (res.data.role === 'admin') {
      router.push('/admin')
    } else {
      router.push('/')
    }
  } catch (err) {
    localStorage.removeItem('token')
    error.value = 'Gagal mengambil data akun. Silakan coba lagi.'
    loading.value = false
  }
}

onMounted(() => {
  const token = route.query.token

  if (!token) {
    error.value = 'Login dengan Google gagal. Token tidak ditemukan.'
    loading.value = false
    return
  }

  fetchUserAndRedirect(token)
})
</script>