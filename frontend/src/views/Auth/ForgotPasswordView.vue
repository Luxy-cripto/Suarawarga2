<template>
  <div class="auth-page">
    <div class="auth-blob auth-blob-1"></div>
    <div class="auth-blob auth-blob-2"></div>

    <div class="auth-wrapper">
      <div class="auth-card card fade-in">

        <RouterLink to="/" class="auth-logo">
          <div class="auth-logo-icon">📢</div>
          <span>SUARAWARGA</span>
        </RouterLink>

        <div class="auth-header">
          <h1>Lupa Kata Sandi</h1>
          <p>Masukkan email kamu, kami akan kirim link buat reset password.</p>
        </div>

        <div v-if="successMessage" class="auth-form">
          <div style="padding: 16px; background: var(--accent-light); border-radius: var(--radius-md); color: var(--accent-dark); font-size: 14px; margin-bottom: 20px;">
            ✅ {{ successMessage }}
          </div>
          <p class="auth-footer-text">
            <RouterLink to="/login">← Kembali ke halaman login</RouterLink>
          </p>
        </div>

        <form v-else class="auth-form" @submit.prevent="handleSubmit">
          <transition name="shake">
            <div v-if="errorMessage" class="auth-error">
              ⚠️ {{ errorMessage }}
            </div>
          </transition>

          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <div class="input-icon-field">
              <span class="input-icon">✉️</span>
              <input
                id="email"
                v-model="email"
                type="email"
                class="form-control has-icon"
                placeholder="nama@email.com"
                autocomplete="email"
                required
              />
            </div>
          </div>

          <button type="submit" class="btn btn-primary auth-submit" :disabled="isLoading">
            <span v-if="isLoading" class="auth-spinner"></span>
            {{ isLoading ? 'Mengirim...' : 'Kirim Link Reset' }}
          </button>
        </form>

        <p v-if="!successMessage" class="auth-footer-text">
          Ingat kata sandi kamu? <RouterLink to="/login">Masuk di sini</RouterLink>
        </p>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/services/api'

const email = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

async function handleSubmit() {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await api.post('/forgot-password', { email: email.value })
    successMessage.value = res.data.message
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal mengirim link reset. Coba lagi.'
  } finally {
    isLoading.value = false
  }
}
</script>