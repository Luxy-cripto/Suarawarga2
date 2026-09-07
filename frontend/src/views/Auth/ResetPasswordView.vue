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
          <h1>Buat Kata Sandi Baru</h1>
          <p>Masukkan kata sandi baru untuk akun {{ email }}</p>
        </div>

        <div v-if="successMessage" class="auth-form">
          <div style="padding: 16px; background: var(--accent-light); border-radius: var(--radius-md); color: var(--accent-dark); font-size: 14px; margin-bottom: 20px;">
            ✅ {{ successMessage }}
          </div>
          <RouterLink to="/login" class="btn btn-primary auth-submit" style="text-align: center;">
            Masuk Sekarang
          </RouterLink>
        </div>

        <form v-else class="auth-form" @submit.prevent="handleSubmit">
          <transition name="shake">
            <div v-if="errorMessage" class="auth-error">
              ⚠️ {{ errorMessage }}
            </div>
          </transition>

          <div class="form-group">
            <label class="form-label" for="password">Kata Sandi Baru</label>
            <div class="input-icon-field">
              <span class="input-icon">🔒</span>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="form-control has-icon"
                placeholder="Minimal 6 karakter"
                required
              />
              <button type="button" class="password-toggle" @click="showPassword = !showPassword">
                {{ showPassword ? '🙈' : '👁️' }}
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi</label>
            <div class="input-icon-field">
              <span class="input-icon">🔒</span>
              <input
                id="password_confirmation"
                v-model="passwordConfirmation"
                type="password"
                class="form-control has-icon"
                placeholder="Ulangi kata sandi baru"
                required
              />
            </div>
          </div>

          <button type="submit" class="btn btn-primary auth-submit" :disabled="isLoading">
            <span v-if="isLoading" class="auth-spinner"></span>
            {{ isLoading ? 'Memproses...' : 'Reset Kata Sandi' }}
          </button>
        </form>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()

const email = ref('')
const token = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

onMounted(() => {
  email.value = route.query.email || ''
  token.value = route.query.token || ''
})

async function handleSubmit() {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await api.post('/reset-password', {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    successMessage.value = res.data.message
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal reset password. Link mungkin sudah kedaluwarsa.'
  } finally {
    isLoading.value = false
  }
}
</script>