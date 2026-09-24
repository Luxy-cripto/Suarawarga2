<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { applySiteSettings } from '@/utils/siteSettings'

const email = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// SITE SETTINGS
const siteName = ref('SUARAWARGA')
const siteTagline = ref('Suara masyarakat, perubahan nyata.')
const siteLogo = ref(null)

let siteSettingsTimer = null

const GENERIC_SUCCESS_MESSAGE =
  'Kalau email tersebut terdaftar, kami sudah kirim link reset password. Silakan cek inbox (dan folder spam) kamu.'

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
    const res = await api.get('/settings')

    const settings = {
      site_name: res.data?.site_name || 'SUARAWARGA',
      site_tagline: res.data?.site_tagline || 'Suara masyarakat, perubahan nyata.',
      site_logo: res.data?.site_logo || null
    }

    updateSiteSettings(settings)

    localStorage.setItem(
      'site_settings',
      JSON.stringify(settings)
    )
  } catch (err) {
    console.error('Gagal mengambil pengaturan situs:', err)
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
    const res = await api.get('/settings')

    const settings = {
      site_name: res.data?.site_name || 'SUARAWARGA',
      site_tagline: res.data?.site_tagline || 'Suara masyarakat, perubahan nyata.',
      site_logo: res.data?.site_logo || null
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
// FORGOT PASSWORD
// =====================================================

async function handleSubmit() {
  isLoading.value = true
  errorMessage.value = ''

  try {
    await api.post('/forgot-password', {
      email: email.value
    })

    successMessage.value = GENERIC_SUCCESS_MESSAGE
  } catch (err) {
    if (err.response?.status === 422) {
      errorMessage.value =
        err.response.data.message ||
        'Format email tidak valid.'
    } else if (err.request) {
      errorMessage.value =
        'Tidak dapat terhubung ke server. Coba lagi nanti.'
    } else {
      errorMessage.value =
        'Gagal mengirim link reset. Coba lagi.'
    }
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
          <h1>Lupa Kata Sandi</h1>

          <p>{{ siteTagline }}</p>
        </div>

        <!-- SUCCESS -->
        <div v-if="successMessage" class="auth-form">
          <div
            style="padding: 16px; background: var(--accent-light); border-radius: var(--radius-md); color: var(--accent-dark); font-size: 14px; margin-bottom: 20px;"
          >
            ✅ {{ successMessage }}
          </div>

          <p class="auth-footer-text">
            <RouterLink to="/login">
              ← Kembali ke halaman login
            </RouterLink>
          </p>
        </div>

        <!-- FORM -->
        <form
          v-else
          class="auth-form"
          @submit.prevent="handleSubmit"
        >
          <transition name="shake">
            <div
              v-if="errorMessage"
              class="auth-error"
            >
              ⚠️ {{ errorMessage }}
            </div>
          </transition>

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
                ? 'Mengirim...'
                : 'Kirim Link Reset'
            }}
          </button>
        </form>

        <!-- FOOTER -->
        <p
          v-if="!successMessage"
          class="auth-footer-text"
        >
          Ingat kata sandi kamu?

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
</style>