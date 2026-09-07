```vue
<template>
  <div class="auth-page">

    <!-- Dekorasi background -->
    <div class="auth-blob auth-blob-1"></div>
    <div class="auth-blob auth-blob-2"></div>

    <div class="auth-wrapper">

      <div class="auth-card card fade-in">

        <!-- Logo -->
        <RouterLink to="/" class="auth-logo">
          <div class="auth-logo-icon">📢</div>
          <span>SUARAWARGA</span>
        </RouterLink>

        <!-- Header -->
        <div class="auth-header">
          <h1>Masuk ke akunmu</h1>
          <p>Yuk lanjut laporkan dan pantau masalah di sekitarmu.</p>
        </div>

        <!-- FORM LOGIN -->
        <form class="auth-form" @submit.prevent="handleLogin">

          <!-- Error -->
          <transition name="shake">
            <div v-if="errorMessage" class="auth-error">
              ⚠️ {{ errorMessage }}
            </div>
          </transition>

          <!-- EMAIL -->
          <div class="form-group">
            <label class="form-label" for="email">
              Email
            </label>

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

          <!-- PASSWORD -->
          <div class="form-group">

            <div class="form-label-row">
              <label class="form-label" for="password">
                Kata Sandi
              </label>

            <RouterLink to="/forgot-password" class="auth-link-small">
              Lupa kata sandi?
            </RouterLink>
            </div>

            <div class="input-icon-field">

              <span class="input-icon">🔒</span>

              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="form-control has-icon"
                placeholder="Masukkan kata sandi"
                autocomplete="current-password"
                required
              />

              <button
                type="button"
                class="password-toggle"
                @click="showPassword = !showPassword"
              >
                {{ showPassword ? '🙈' : '👁️' }}
              </button>

            </div>
          </div>

          <!-- LOGIN BUTTON -->
          <button
            type="submit"
            class="btn btn-primary auth-submit"
            :disabled="isLoading"
          >

            <span
              v-if="isLoading"
              class="auth-spinner"
            ></span>

            {{ isLoading ? 'Memproses...' : 'Masuk' }}

          </button>

        </form>

        <!-- DIVIDER -->
        <div class="auth-divider">
          <span>atau</span>
        </div>

        <!-- GOOGLE -->
        <button
          type="button"
          class="btn btn-secondary auth-submit auth-google"
          @click="loginGoogle"
        >
          <span class="auth-google-icon">G</span>
          Masuk dengan Google
        </button>

        <!-- REGISTER -->
        <p class="auth-footer-text">
          Belum punya akun?

          <RouterLink to="/register">
            Daftar sekarang
          </RouterLink>
        </p>

      </div>

    </div>

  </div>
</template>


<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

// ==============================
// STATE
// ==============================

const email = ref('')
const password = ref('')

const isLoading = ref(false)
const errorMessage = ref('')

const showPassword = ref(false)


// ==============================
// LOGIN
// ==============================

async function handleLogin() {

  // Jangan kirim kalau kosong
  if (!email.value || !password.value) {
    errorMessage.value = 'Email dan kata sandi wajib diisi.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {

    console.log('LOGIN DATA:', {
      email: email.value,
      password: password.value
    })

    // Request ke Laravel
    const response = await api.post('/login', {
      email: email.value,
      password: password.value
    })

    console.log('LOGIN RESPONSE:', response.data)

    // ==============================
    // AMBIL TOKEN
    // ==============================

    const token =
      response.data.token ||
      response.data.access_token

    if (!token) {
      throw new Error('Token login tidak ditemukan dari server.')
    }

    // Simpan token
    localStorage.setItem('token', token)


    // ==============================
    // SIMPAN USER
    // ==============================

    if (response.data.user) {
      localStorage.setItem(
        'user',
        JSON.stringify(response.data.user)
      )
    }


    // ==============================
    // CEK ROLE
    // ==============================

    const user = response.data.user

    console.log('USER LOGIN:', user)

    if (user?.role === 'admin') {

      console.log('Redirect ke admin')

      await router.push('/admin')

    } else {

      console.log('Redirect ke home')

      await router.push('/')

    }

  } catch (err) {

    console.error('LOGIN ERROR:', err)

    // ==============================
    // ERROR DARI LARAVEL
    // ==============================

    if (err.response) {

      console.error(
        'STATUS:',
        err.response.status
      )

      console.error(
        'DATA:',
        err.response.data
      )

      // Laravel validation error
      if (err.response.status === 422) {

        const errors = err.response.data.errors

        if (errors) {

          const firstError =
            Object.values(errors)[0]

          errorMessage.value =
            Array.isArray(firstError)
              ? firstError[0]
              : firstError

        } else {

          errorMessage.value =
            err.response.data.message ||
            'Data login tidak valid.'

        }

      }

      // Unauthorized
      else if (err.response.status === 401) {

        errorMessage.value =
          err.response.data.message ||
          'Email atau password salah.'

      }

      // Server error
      else if (err.response.status >= 500) {

        errorMessage.value =
          'Terjadi kesalahan pada server Laravel.'

      }

      else {

        errorMessage.value =
          err.response.data.message ||
          'Login gagal.'

      }

    }

    // Tidak ada response
    else if (err.request) {

      errorMessage.value =
        'Tidak dapat terhubung ke server Laravel.'

    }

    // Error lainnya
    else {

      errorMessage.value =
        err.message ||
        'Terjadi kesalahan saat login.'

    }

  } finally {

    isLoading.value = false

  }
}


// ==============================
// GOOGLE LOGIN
// ==============================

function loginGoogle() {

  // Kalau Laravel menyediakan endpoint Google OAuth
  window.location.href =
    'http://127.0.0.1:8000/auth/google'

}
</script>
```
