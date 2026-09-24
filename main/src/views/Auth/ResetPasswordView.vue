<template>
  <div class="auth-page">
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
            <span v-else>📢</span>
          </div>

          <span>{{ siteName }}</span>
        </RouterLink>

        <!-- HEADER -->
        <div class="auth-header">
          <h1>Buat Kata Sandi Baru</h1>
          <p>Masukkan kata sandi baru untuk akun {{ email }}</p>
        </div>

        <!-- SUCCESS -->
        <div v-if="successMessage" class="auth-form">
          <div
            style="padding: 16px; background: var(--accent-light); border-radius: var(--radius-md); color: var(--accent-dark); font-size: 14px; margin-bottom: 20px;"
          >
            ✅ {{ successMessage }}
          </div>

          <RouterLink
            to="/login"
            class="btn btn-primary auth-submit"
            style="text-align: center;"
          >
            Masuk Sekarang
          </RouterLink>
        </div>

        <!-- FORM -->
        <form
          v-else
          class="auth-form"
          @submit.prevent="handleSubmit"
        >
          <transition name="shake">
            <div v-if="errorMessage" class="auth-error">
              ⚠️ {{ errorMessage }}
            </div>
          </transition>

          <!-- PASSWORD -->
          <div class="form-group">
            <label class="form-label" for="password">
              Kata Sandi Baru
            </label>

            <div class="input-icon-field">
              <span class="input-icon">🔒</span>

              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="form-control has-icon"
                placeholder="Minimal 6 karakter"
                required
                minlength="6"
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

          <!-- KONFIRMASI PASSWORD -->
          <div class="form-group">
            <label
              class="form-label"
              for="password_confirmation"
            >
              Konfirmasi Kata Sandi
            </label>

            <div class="input-icon-field">
              <span class="input-icon">🔒</span>

              <input
                id="password_confirmation"
                v-model="passwordConfirmation"
                :type="showPassword ? 'text' : 'password'"
                class="form-control has-icon"
                placeholder="Ulangi kata sandi baru"
                required
                minlength="6"
              />
            </div>
          </div>

          <!-- PASSWORD WARNING -->
          <div
            v-if="
              passwordConfirmation &&
              password !== passwordConfirmation
            "
            class="password-warning"
          >
            ⚠️ Konfirmasi kata sandi tidak sama.
          </div>

          <!-- SUBMIT -->
          <button
            type="submit"
            class="btn btn-primary auth-submit"
            :disabled="isLoading"
          >
            <span
              v-if="isLoading"
              class="auth-spinner"
            ></span>

            {{ isLoading ? 'Memproses...' : 'Reset Kata Sandi' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import api from '@/services/api'
import { applySiteSettings } from '@/utils/siteSettings'

const route = useRoute()

// =====================================================
// RESET PASSWORD
// =====================================================

const email = ref('')
const token = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const showPassword = ref(false)

// =====================================================
// SITE SETTINGS
// =====================================================

const siteName = ref('SUARAWARGA')
const siteTagline = ref('Suara masyarakat, perubahan nyata.')
const siteLogo = ref(null)

let siteSettingsTimer = null

// =====================================================
// UPDATE SITE SETTINGS
// =====================================================

function updateSiteSettings(settings) {
  if (!settings) return

  siteName.value =
    settings.site_name || 'SUARAWARGA'

  siteTagline.value =
    settings.site_tagline ||
    'Suara masyarakat, perubahan nyata.'

  siteLogo.value =
    settings.site_logo || null

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
    const res = await api.get('/settings')

    const settings = {
      site_name:
        res.data?.site_name || 'SUARAWARGA',

      site_tagline:
        res.data?.site_tagline ||
        'Suara masyarakat, perubahan nyata.',

      site_logo:
        res.data?.site_logo || null
    }

    updateSiteSettings(settings)

    localStorage.setItem(
      'site_settings',
      JSON.stringify(settings)
    )
  } catch (err) {
    console.error(
      'Gagal mengambil pengaturan situs:',
      err
    )
  }
}

// =====================================================
// UPDATE DARI TAB LAIN
// =====================================================

function handleStorageChange(event) {
  if (
    event.key !== 'site_settings' ||
    !event.newValue
  ) {
    return
  }

  try {
    const settings = JSON.parse(
      event.newValue
    )

    updateSiteSettings(settings)
  } catch (error) {
    console.error(
      'Gagal membaca site settings:',
      error
    )
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
    const settings = JSON.parse(saved)

    updateSiteSettings(settings)
  } catch {
    fetchSiteSettings()
  }
}

// =====================================================
// AUTO REFRESH SETIAP 3 DETIK
// =====================================================

async function refreshSiteSettingsSilently() {
  try {
    const res = await api.get('/settings')

    const newSiteName =
      res.data?.site_name ||
      'SUARAWARGA'

    const newSiteTagline =
      res.data?.site_tagline ||
      'Suara masyarakat, perubahan nyata.'

    const newSiteLogo =
      res.data?.site_logo ||
      null

    const changed =
      siteName.value !== newSiteName ||
      siteTagline.value !== newSiteTagline ||
      siteLogo.value !== newSiteLogo

    if (!changed) return

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
// RESET PASSWORD
// =====================================================

async function handleSubmit() {
  errorMessage.value = ''

  // VALIDASI PASSWORD
  if (password.value.length < 6) {
    errorMessage.value =
      'Kata sandi minimal 6 karakter.'

    return
  }

  // VALIDASI KONFIRMASI
  if (
    password.value !==
    passwordConfirmation.value
  ) {
    errorMessage.value =
      'Konfirmasi kata sandi tidak sama.'

    return
  }

  isLoading.value = true

  try {
    const res = await api.post(
      '/reset-password',
      {
        email: email.value,
        token: token.value,
        password: password.value,
        password_confirmation:
          passwordConfirmation.value
      }
    )

    successMessage.value =
      res.data.message
  } catch (err) {
    errorMessage.value =
      err.response?.data?.message ||
      'Gagal reset password. Link mungkin sudah kedaluwarsa.'

    console.error(
      'RESET PASSWORD ERROR:',
      err.response?.status ||
        err.message
    )
  } finally {
    isLoading.value = false
  }
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
  // email dan token berasal dari query URL
  email.value =
    route.query.email || ''

  token.value =
    route.query.token || ''

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

  clearInterval(
    siteSettingsTimer
  )
})
</script>

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
  border-radius: 8px;
  font-size: 13px;
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffe69c;
}
</style>