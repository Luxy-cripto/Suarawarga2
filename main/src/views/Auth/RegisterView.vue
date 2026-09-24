<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '@/services/api'
import { applySiteSettings } from '@/utils/siteSettings'

const router = useRouter()

// FORM
const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')

// UI
const isLoading = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const agreeTerms = ref(false)

// SITE SETTINGS
const siteName = ref('SUARAWARGA')
const siteTagline = ref('Suara masyarakat, perubahan nyata.')
const siteLogo = ref(null)

let siteSettingsTimer = null

// =====================================================
// SITE SETTINGS
// =====================================================

function updateSiteSettings(settings) {
  if (!settings) return

  siteName.value = settings.site_name || 'SUARAWARGA'
  siteTagline.value = settings.site_tagline || 'Suara masyarakat, perubahan nyata.'
  siteLogo.value = settings.site_logo || null

  applySiteSettings({
    site_name: siteName.value,
    site_logo: siteLogo.value
  })
}

async function fetchSiteSettings() {
  try {
    const response = await api.get('/settings')

    const settings = {
      site_name: response.data?.site_name || 'SUARAWARGA',
      site_tagline: response.data?.site_tagline || 'Suara masyarakat, perubahan nyata.',
      site_logo: response.data?.site_logo || null
    }

    updateSiteSettings(settings)

    localStorage.setItem(
      'site_settings',
      JSON.stringify(settings)
    )
  } catch (error) {
    console.error('Gagal mengambil pengaturan situs:', error)
  }
}

function handleStorageChange(event) {
  if (event.key !== 'site_settings' || !event.newValue) return

  try {
    updateSiteSettings(JSON.parse(event.newValue))
  } catch (error) {
    console.error('Gagal membaca site settings:', error)
  }
}

function handleSettingsUpdated() {
  const saved = localStorage.getItem('site_settings')

  if (!saved) {
    fetchSiteSettings()
    return
  }

  try {
    updateSiteSettings(JSON.parse(saved))
  } catch {
    fetchSiteSettings()
  }
}

async function refreshSiteSettingsSilently() {
  try {
    const response = await api.get('/settings')

    const settings = {
      site_name: response.data?.site_name || 'SUARAWARGA',
      site_tagline: response.data?.site_tagline || 'Suara masyarakat, perubahan nyata.',
      site_logo: response.data?.site_logo || null
    }

    const changed =
      siteName.value !== settings.site_name ||
      siteTagline.value !== settings.site_tagline ||
      siteLogo.value !== settings.site_logo

    if (!changed) return

    updateSiteSettings(settings)

    localStorage.setItem(
      'site_settings',
      JSON.stringify(settings)
    )
  } catch {
    // Abaikan error auto refresh
  }
}

// =====================================================
// REGISTER
// =====================================================

async function handleRegister() {
  errorMessage.value = ''

  if (!name.value.trim()) {
    errorMessage.value = 'Nama lengkap wajib diisi.'
    return
  }

  if (!email.value.trim()) {
    errorMessage.value = 'Email wajib diisi.'
    return
  }

  if (password.value.length < 8) {
    errorMessage.value = 'Kata sandi minimal 8 karakter.'
    return
  }

  if (password.value !== confirmPassword.value) {
    errorMessage.value = 'Konfirmasi kata sandi tidak sama.'
    return
  }

  if (!agreeTerms.value) {
    errorMessage.value = 'Kamu harus menyetujui Syarat & Ketentuan.'
    return
  }

  isLoading.value = true

  try {
    const registerData = {
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: confirmPassword.value
    }

    const response = await api.post(
      '/register',
      registerData
    )

    const token =
      response.data.token ||
      response.data.access_token

    if (!token) {
      throw new Error(
        'Token register tidak ditemukan dari server.'
      )
    }

    sessionStorage.setItem(
      'token',
      token
    )

    if (response.data.user) {
      sessionStorage.setItem(
        'user',
        JSON.stringify(response.data.user)
      )
    }

    await router.push('/')
  } catch (err) {
    if (err.response) {
      if (err.response.status === 422) {
        const errors = err.response.data.errors

        if (errors) {
          const firstError = Object.values(errors)[0]

          errorMessage.value =
            Array.isArray(firstError)
              ? firstError[0]
              : firstError
        } else {
          errorMessage.value =
            err.response.data.message ||
            'Data yang dimasukkan tidak valid.'
        }
      } else if (err.response.status >= 500) {
        errorMessage.value =
          'Terjadi kesalahan pada server Laravel. Cek terminal Laravel.'
      } else {
        errorMessage.value =
          err.response.data.message ||
          'Gagal membuat akun.'
      }
    } else if (err.request) {
      errorMessage.value =
        'Tidak dapat terhubung ke server Laravel. Pastikan php artisan serve sedang berjalan.'
    } else {
      errorMessage.value =
        err.message ||
        'Terjadi kesalahan saat membuat akun.'
    }

    console.error(
      'REGISTER ERROR:',
      err.response?.status || err.message
    )
  } finally {
    isLoading.value = false
  }
}

// =====================================================
// GOOGLE REGISTER
// =====================================================

function registerGoogle() {
  const apiBaseUrl =
    import.meta.env.VITE_API_URL ||
    'http://127.0.0.1:8000'

  window.location.href =
    `${apiBaseUrl}/auth/google`
}

// =====================================================
// IMAGE ERROR
// =====================================================

function handleLogoError(event) {
  event.target.style.display = 'none'
}

// =====================================================
// LIFECYCLE
// =====================================================

onMounted(() => {
  fetchSiteSettings()

  window.addEventListener(
    'storage',
    handleStorageChange
  )

  window.addEventListener(
    'settings-updated',
    handleSettingsUpdated
  )

  siteSettingsTimer = setInterval(() => {
    refreshSiteSettingsSilently()
  }, 3000)
})

onUnmounted(() => {
  window.removeEventListener(
    'storage',
    handleStorageChange
  )

  window.removeEventListener(
    'settings-updated',
    handleSettingsUpdated
  )

  clearInterval(siteSettingsTimer)
})
</script>

<template>
  <div class="auth-page">
    <!-- Background decoration -->
    <div class="auth-blob auth-blob-1"></div>
    <div class="auth-blob auth-blob-2"></div>

    <div class="auth-wrapper">
      <div class="auth-card card fade-in">

        <!-- LOGO -->
        <RouterLink to="/" class="auth-logo">
          <div class="auth-logo-icon">
            <img
              v-if="siteLogo"
              :src="siteLogo"
              :alt="siteName"
              @error="handleLogoError"
            />

            <span v-else>
              📢
            </span>
          </div>

          <span>
            {{ siteName }}
          </span>
        </RouterLink>

        <!-- HEADER -->
        <div class="auth-header">
          <h1>Buat Akun Warga</h1>

          <p>
            {{ siteTagline }}
          </p>
        </div>

        <!-- FORM -->
        <form
          class="auth-form"
          @submit.prevent="handleRegister"
        >

          <!-- ERROR -->
          <transition name="shake">
            <div
              v-if="errorMessage"
              class="auth-error"
            >
              ⚠️ {{ errorMessage }}
            </div>
          </transition>

          <!-- NAMA -->
          <div class="form-group">
            <label
              class="form-label"
              for="name"
            >
              Nama Lengkap
            </label>

            <div class="input-icon-field">
              <span class="input-icon">
                👤
              </span>

              <input
                id="name"
                v-model="name"
                type="text"
                class="form-control has-icon"
                placeholder="Nama kamu"
                autocomplete="name"
                required
              />
            </div>
          </div>

          <!-- EMAIL -->
          <div class="form-group">
            <label
              class="form-label"
              for="email"
            >
              Email
            </label>

            <div class="input-icon-field">
              <span class="input-icon">
                ✉️
              </span>

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
            <label
              class="form-label"
              for="password"
            >
              Kata Sandi
            </label>

            <div class="input-icon-field">
              <span class="input-icon">
                🔒
              </span>

              <input
                id="password"
                v-model="password"
                :type="
                  showPassword
                    ? 'text'
                    : 'password'
                "
                class="form-control has-icon"
                placeholder="Minimal 8 karakter"
                autocomplete="new-password"
                required
                minlength="8"
              />

              <button
                type="button"
                class="password-toggle"
                @click="
                  showPassword = !showPassword
                "
              >
                {{
                  showPassword
                    ? '🙈'
                    : '👁️'
                }}
              </button>
            </div>
          </div>

          <!-- KONFIRMASI PASSWORD -->
          <div class="form-group">
            <label
              class="form-label"
              for="confirm-password"
            >
              Konfirmasi Kata Sandi
            </label>

            <div class="input-icon-field">
              <span class="input-icon">
                🔒
              </span>

              <input
                id="confirm-password"
                v-model="confirmPassword"
                :type="
                  showConfirmPassword
                    ? 'text'
                    : 'password'
                "
                class="form-control has-icon"
                placeholder="Ulangi kata sandi"
                autocomplete="new-password"
                required
                minlength="8"
              />

              <button
                type="button"
                class="password-toggle"
                @click="
                  showConfirmPassword =
                    !showConfirmPassword
                "
              >
                {{
                  showConfirmPassword
                    ? '🙈'
                    : '👁️'
                }}
              </button>
            </div>
          </div>

          <!-- PASSWORD WARNING -->
          <div
            v-if="
              confirmPassword &&
              password !== confirmPassword
            "
            class="password-warning"
          >
            ⚠️ Konfirmasi kata sandi tidak sama.
          </div>

          <!-- TERMS -->
          <label class="auth-checkbox">
            <input
              type="checkbox"
              v-model="agreeTerms"
            />

            <span>
              Saya menyetujui

              <a
                href="#"
                @click.prevent
              >
                Syarat & Ketentuan
              </a>

              dan

              <a
                href="#"
                @click.prevent
              >
                Kebijakan Privasi
              </a>
            </span>
          </label>

          <!-- REGISTER BUTTON -->
          <button
            type="submit"
            class="btn btn-primary auth-submit"
            :disabled="isLoading"
          >
            <span
              v-if="isLoading"
              class="auth-spinner"
            ></span>

            {{
              isLoading
                ? 'Membuat akun...'
                : 'Daftar Sekarang'
            }}
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
          @click="registerGoogle"
        >
          <span class="auth-google-icon">
            G
          </span>

          Daftar dengan Google
        </button>

        <!-- LOGIN -->
        <p class="auth-footer-text">
          Sudah punya akun?

          <RouterLink to="/login">
            Masuk di sini
          </RouterLink>
        </p>

      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-logo-icon {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
  border-radius: 12px;
}

.auth-logo-icon img {
  width: 100%;
  height: 100%;
  padding: 6px;
  object-fit: contain;
}

.auth-logo-icon span {
  font-size: 24px;
  line-height: 1;
}

.password-warning {
  margin-top: -10px;
  margin-bottom: 15px;
  padding: 10px 12px;
  border: 1px solid #ffe69c;
  border-radius: 8px;
  background: #fff3cd;
  color: #856404;
  font-size: 13px;
}
</style>