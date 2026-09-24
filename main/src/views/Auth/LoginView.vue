<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { applySiteSettings } from '@/utils/siteSettings'

const router = useRouter()
const route = useRoute()

// =====================================================
// LOGIN
// =====================================================

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)

// =====================================================
// SITE SETTINGS
// =====================================================

const siteName = ref('SUARAWARGA')
const siteTagline = ref('Suara masyarakat, perubahan nyata.')
const siteLogo = ref(null)

let siteSettingsTimer = null

// =====================================================
// SIMPAN SESI
// =====================================================

function simpanSesi(token, user) {
  sessionStorage.setItem('token', token)

  if (user) {
    sessionStorage.setItem(
      'user',
      JSON.stringify(user)
    )
  }
}

// =====================================================
// TERAPKAN SITE SETTINGS
// =====================================================

function updateSiteSettings(settings) {
  if (!settings) return

  siteName.value =
    settings.site_name ||
    'SUARAWARGA'

  siteTagline.value =
    settings.site_tagline ||
    'Suara masyarakat, perubahan nyata.'

  siteLogo.value =
    settings.site_logo ||
    null

  // Update title + favicon
  applySiteSettings({
    site_name: siteName.value,
    site_logo: siteLogo.value
  })
}

// =====================================================
// FETCH SITE SETTINGS
// =====================================================

async function fetchSiteSettings() {
  try {
    const response = await api.get('/settings')

    const settings = {
      site_name:
        response.data?.site_name ||
        'SUARAWARGA',

      site_tagline:
        response.data?.site_tagline ||
        'Suara masyarakat, perubahan nyata.',

      site_logo:
        response.data?.site_logo ||
        null
    }

    updateSiteSettings(settings)

    // Simpan untuk komunikasi antar-tab
    localStorage.setItem(
      'site_settings',
      JSON.stringify(settings)
    )
  } catch (error) {
    console.error(
      'Gagal mengambil pengaturan situs:',
      error
    )
  }
}

// =====================================================
// UPDATE DARI LOCALSTORAGE
// =====================================================

function handleStorageChange(event) {
  // Pengaturan situs
  if (
    event.key === 'site_settings' &&
    event.newValue
  ) {
    try {
      const settings =
        JSON.parse(event.newValue)

      updateSiteSettings(settings)
    } catch (error) {
      console.error(
        'Gagal membaca site settings:',
        error
      )
    }
  }
}

// =====================================================
// UPDATE TAB YANG SAMA
// =====================================================

function handleSettingsUpdated() {
  const saved =
    localStorage.getItem(
      'site_settings'
    )

  if (!saved) {
    fetchSiteSettings()
    return
  }

  try {
    const settings =
      JSON.parse(saved)

    updateSiteSettings(settings)
  } catch {
    fetchSiteSettings()
  }
}

// =====================================================
// AUTO REFRESH SITE SETTINGS
// =====================================================

async function refreshSiteSettingsSilently() {
  try {
    const response =
      await api.get('/settings')

    const newSiteName =
      response.data?.site_name ||
      'SUARAWARGA'

    const newSiteTagline =
      response.data?.site_tagline ||
      'Suara masyarakat, perubahan nyata.'

    const newSiteLogo =
      response.data?.site_logo ||
      null

    const changed =
      siteName.value !== newSiteName ||
      siteTagline.value !== newSiteTagline ||
      siteLogo.value !== newSiteLogo

    if (!changed) {
      return
    }

    const settings = {
      site_name: newSiteName,
      site_tagline: newSiteTagline,
      site_logo: newSiteLogo
    }

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
// LOGIN
// =====================================================

async function handleLogin() {
  if (!email.value || !password.value) {
    errorMessage.value =
      'Email dan kata sandi wajib diisi.'

    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.post(
      '/login',
      {
        email: email.value,
        password: password.value
      }
    )

    const user = response.data.user

    const token =
      response.data.token ||
      response.data.access_token

    if (!token) {
      throw new Error(
        'Token login tidak ditemukan dari server.'
      )
    }

    // ==================================================
    // ADMIN
    // ==================================================

    if (user?.role === 'admin') {
      // Admin hanya boleh login melalui /admin/login
      if (route.name === 'admin-login') {
        simpanSesi(token, user)

        await router.replace({
          name: 'admin-dashboard'
        })
      } else {
        sessionStorage.removeItem('token')
        sessionStorage.removeItem('user')

        await router.replace({
          name: 'login-warning'
        })
      }

      return
    }

    // ==================================================
    // WARGA & PETUGAS
    // ==================================================

    simpanSesi(token, user)

    if (user?.role === 'petugas') {
      await router.push('/petugas')
    } else {
      await router.push('/')
    }
  } catch (err) {
    console.error(
      'LOGIN ERROR:',
      err
    )

    if (err.response) {
      const status =
        err.response.status

      if (status === 422) {
        const errors =
          err.response.data.errors

        const firstError =
          errors
            ? Object.values(errors)[0]
            : null

        errorMessage.value =
          Array.isArray(firstError)
            ? firstError[0]
            : (
                firstError ||
                err.response.data.message ||
                'Data login tidak valid.'
              )
      } else if (status === 401) {
        errorMessage.value =
          err.response.data.message ||
          'Email atau password salah.'
      } else if (status === 403) {
        errorMessage.value =
          err.response.data.message ||
          'Akun tidak memiliki izin untuk login.'
      } else if (status >= 500) {
        errorMessage.value =
          'Terjadi kesalahan pada server Laravel.'
      } else {
        errorMessage.value =
          err.response.data.message ||
          'Login gagal.'
      }
    } else if (err.request) {
      errorMessage.value =
        'Tidak dapat terhubung ke server Laravel.'
    } else {
      errorMessage.value =
        err.message ||
        'Terjadi kesalahan saat login.'
    }
  } finally {
    isLoading.value = false
  }
}

// =====================================================
// LOGIN GOOGLE
// =====================================================

function loginGoogle() {
  window.location.href =
    'http://127.0.0.1:8000/auth/google'
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

  // Cek perubahan setiap 3 detik
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

  clearInterval(
    siteSettingsTimer
  )
})
</script>

<template>
  <div class="auth-page">
    <div class="auth-blob auth-blob-1"></div>
    <div class="auth-blob auth-blob-2"></div>

    <div class="auth-wrapper">
      <div class="auth-card card fade-in">

        <!-- LOGO DINAMIS -->
        <RouterLink
          to="/"
          class="auth-logo"
        >
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
          <h1>Masuk ke akunmu</h1>

          <p>
            {{ siteTagline }}
          </p>
        </div>

        <!-- LOGIN FORM -->
        <form
          class="auth-form"
          @submit.prevent="handleLogin"
        >
          <transition name="shake">
            <div
              v-if="errorMessage"
              class="auth-error"
            >
              ⚠️ {{ errorMessage }}
            </div>
          </transition>

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
            <div class="form-label-row">
              <label
                class="form-label"
                for="password"
              >
                Kata Sandi
              </label>

              <RouterLink
                to="/forgot-password"
                class="auth-link-small"
              >
                Lupa kata sandi?
              </RouterLink>
            </div>

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
                placeholder="Masukkan kata sandi"
                autocomplete="current-password"
                required
              />

              <button
                type="button"
                class="password-toggle"
                @click="
                  showPassword =
                    !showPassword
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

          <!-- LOGIN -->
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
                ? 'Memproses...'
                : 'Masuk'
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
          @click="loginGoogle"
        >
          <span class="auth-google-icon">
            G
          </span>

          Masuk dengan Google
        </button>

        <!-- FOOTER -->
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
</style>