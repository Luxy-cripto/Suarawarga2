<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const mobileMenuOpen = ref(false)
const profileMenuOpen = ref(false)
const notifOpen = ref(false)
const notifikasis = ref([])
const loadingNotif = ref(false)
const notifError = ref('')
const userVersion = ref(0)

const siteName = ref('SUARAWARGA')
const siteTagline = ref('Suara masyarakat, perubahan nyata.')
const siteLogo = ref(null)

let siteSettingsTimer = null

const user = computed(() => {
  userVersion.value

  try {
    return JSON.parse(sessionStorage.getItem('user') || 'null')
  } catch {
    return null
  }
})

const isLoggedIn = computed(() => {
  userVersion.value
  return !!sessionStorage.getItem('token')
})

const isAdmin = computed(() => user.value?.role === 'admin')
const isPetugas = computed(() => user.value?.role === 'petugas')
const isWarga = computed(() => isLoggedIn.value && !isAdmin.value && !isPetugas.value)

const userPhoto = computed(() => {
  const currentUser = user.value
  if (!currentUser) return null

  if (currentUser.google_avatar && typeof currentUser.google_avatar === 'string') {
    return currentUser.google_avatar
  }

  if (currentUser.foto_url && typeof currentUser.foto_url === 'string') {
    return currentUser.foto_url
  }

  if (currentUser.foto && typeof currentUser.foto === 'string') {
    if (currentUser.foto.startsWith('http://') || currentUser.foto.startsWith('https://')) {
      return currentUser.foto
    }

    return `${window.location.origin}/storage/${currentUser.foto}`
  }

  return null
})

const userInitial = computed(() => {
  const name = user.value?.name || 'U'
  return name.trim().charAt(0).toUpperCase()
})

const roleLabel = computed(() => {
  if (isAdmin.value) return 'Administrator'
  if (isPetugas.value) return 'Petugas'
  return 'Warga'
})

const unreadCount = computed(() => {
  if (!Array.isArray(notifikasis.value)) return 0
  return notifikasis.value.filter(notification => !notification.is_read).length
})

// MENU
function closeMenu() {
  mobileMenuOpen.value = false
}

function closeProfileMenu() {
  profileMenuOpen.value = false
}

function closeAllMenus() {
  mobileMenuOpen.value = false
  profileMenuOpen.value = false
  notifOpen.value = false
}

function toggleProfileMenu() {
  profileMenuOpen.value = !profileMenuOpen.value
  notifOpen.value = false
}

function toggleNotif() {
  notifOpen.value = !notifOpen.value
  profileMenuOpen.value = false

  if (notifOpen.value) {
    fetchNotifikasis()
  }
}

// SITE SETTINGS
function applySiteSettings(settings) {
  if (!settings) return

  siteName.value = settings.site_name || 'SUARAWARGA'
  siteTagline.value = settings.site_tagline || 'Suara masyarakat, perubahan nyata.'
  siteLogo.value = settings.site_logo || null

  document.title = siteName.value

  let favicon = document.querySelector('link[rel="icon"]')

  if (!favicon) {
    favicon = document.createElement('link')
    favicon.rel = 'icon'
    document.head.appendChild(favicon)
  }

  favicon.href = siteLogo.value || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">📢</text></svg>'
}

async function fetchSiteSettings() {
  try {
    const res = await api.get('/settings')

    const settings = {
      site_name: res.data?.site_name || 'SUARAWARGA',
      site_tagline: res.data?.site_tagline || 'Suara masyarakat, perubahan nyata.',
      site_logo: res.data?.site_logo || null
    }

    applySiteSettings(settings)

    localStorage.setItem('site_settings', JSON.stringify(settings))
  } catch (err) {
    console.error('Gagal mengambil pengaturan situs:', err)
  }
}

function handleSiteSettingsChange(event) {
  if (event.key !== 'site_settings' || !event.newValue) return

  try {
    applySiteSettings(JSON.parse(event.newValue))
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
    applySiteSettings(JSON.parse(saved))
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

    applySiteSettings(settings)
    localStorage.setItem('site_settings', JSON.stringify(settings))
  } catch {
    // Abaikan error auto refresh
  }
}

// NOTIFIKASI
async function fetchNotifikasis() {
  if (!isLoggedIn.value) {
    notifikasis.value = []
    return
  }

  loadingNotif.value = true
  notifError.value = ''

  try {
    const res = await api.get('/notifikasis')
    notifikasis.value = Array.isArray(res.data) ? res.data : res.data?.data || []
  } catch (err) {
    notifikasis.value = []

    if (err.response?.status !== 401) {
      notifError.value = err.response?.data?.message || 'Gagal mengambil notifikasi.'
    }
  } finally {
    loadingNotif.value = false
  }
}

async function markAsRead(notif) {
  try {
    await api.put(`/notifikasis/${notif.id}/read`)
    notif.is_read = true
    notifOpen.value = false

    if (!notif.laporan_id) return

    if (isPetugas.value) {
      await router.push({
        path: '/petugas',
        query: { laporan: notif.laporan_id }
      })
      return
    }

    await router.push(`/laporan/${notif.laporan_id}`)
  } catch (error) {
    console.error('Gagal menandai notifikasi:', error)
  }
}

async function markAllAsRead() {
  if (unreadCount.value === 0) return

  try {
    await api.put('/notifikasis/read-all')
    notifikasis.value.forEach(notification => {
      notification.is_read = true
    })
  } catch (err) {
    console.error('Gagal menandai semua notifikasi:', err)
  }
}

function formatNotifTime(date) {
  if (!date) return ''

  const created = new Date(date)
  const diff = new Date() - created

  if (diff < 0) return 'Baru saja'

  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (minutes < 1) return 'Baru saja'
  if (minutes < 60) return `${minutes} menit lalu`
  if (hours < 24) return `${hours} jam lalu`
  if (days < 7) return `${days} hari lalu`

  return created.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

// LOGOUT
async function handleLogout() {
  try {
    await api.post('/logout')
  } catch (err) {
    console.error('Logout API:', err)
  }

  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')

  notifikasis.value = []
  closeAllMenus()
  userVersion.value++

  router.push('/login')
}

// EVENT
function handleClickOutside(event) {
  if (
    !event.target.closest('.navbar-profile-wrapper') &&
    !event.target.closest('.navbar-notif-wrapper')
  ) {
    profileMenuOpen.value = false
    notifOpen.value = false
  }
}

function handleStorageChange(event) {
  if (event.key === 'token' || event.key === 'user') {
    userVersion.value++
    fetchNotifikasis()
  }

  handleSiteSettingsChange(event)
}

function handleUserUpdated() {
  userVersion.value++
}

function handleImageError(event) {
  event.target.style.display = 'none'
}

// LIFECYCLE
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('storage', handleStorageChange)
  window.addEventListener('user-updated', handleUserUpdated)
  window.addEventListener('settings-updated', handleSettingsUpdated)

  fetchNotifikasis()
  fetchSiteSettings()

  siteSettingsTimer = setInterval(() => {
    refreshSiteSettingsSilently()
  }, 3000)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('storage', handleStorageChange)
  window.removeEventListener('user-updated', handleUserUpdated)
  window.removeEventListener('settings-updated', handleSettingsUpdated)

  clearInterval(siteSettingsTimer)
})
</script>

<template>
  <nav class="navbar">
    <div class="navbar-container">
      <!-- LOGO -->
      <RouterLink to="/" class="navbar-brand" @click="closeMenu">
        <div class="navbar-logo">
          <img
            v-if="siteLogo"
            :src="siteLogo"
            :alt="siteName"
            @error="handleImageError"
          />
          <span v-else>📢</span>
        </div>

        <div class="navbar-brand-text">
          <h1>{{ siteName }}</h1>
          <p>{{ siteTagline }}</p>
        </div>
      </RouterLink>

      <!-- MENU DESKTOP -->
      <div class="navbar-menu">
        <RouterLink
          to="/"
          class="navbar-link"
          :class="{ active: route.path === '/' }"
        >
          Beranda
        </RouterLink>

        <RouterLink
          to="/laporan"
          class="navbar-link"
          :class="{ active: route.path.startsWith('/laporan') }"
        >
          Laporan
        </RouterLink>

        <RouterLink
          to="/about"
          class="navbar-link"
          :class="{ active: route.path === '/about' }"
        >
          Tentang
        </RouterLink>
      </div>

      <!-- BAGIAN KANAN -->
      <div class="navbar-actions">
        <!-- BELUM LOGIN -->
        <template v-if="!isLoggedIn">
          <RouterLink to="/login" class="navbar-login">
            Masuk
          </RouterLink>
        </template>

        <!-- SUDAH LOGIN -->
        <template v-else>
          <!-- NOTIFIKASI -->
          <div class="navbar-notif-wrapper">
            <button
              type="button"
              class="navbar-icon-btn"
              title="Notifikasi"
              @click.stop="toggleNotif"
            >
              🔔
              <span v-if="unreadCount > 0" class="navbar-badge">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
              </span>
            </button>

            <div
              v-if="notifOpen"
              class="navbar-dropdown navbar-notif-dropdown"
              @click.stop
            >
              <div class="notif-header">
                <div>
                  <strong>Notifikasi</strong>
                  <small v-if="unreadCount > 0">
                    {{ unreadCount }} belum dibaca
                  </small>
                </div>

                <button
                  v-if="unreadCount > 0"
                  type="button"
                  class="notif-read-all"
                  @click="markAllAsRead"
                >
                  Tandai dibaca
                </button>
              </div>

              <div v-if="loadingNotif" class="notif-state">
                <span class="notif-spinner"></span>
                Memuat notifikasi...
              </div>

              <div v-else-if="notifError" class="notif-state notif-error">
                <div>{{ notifError }}</div>
                <button type="button" class="notif-retry" @click="fetchNotifikasis">
                  🔄 Coba lagi
                </button>
              </div>

              <div v-else-if="notifikasis.length === 0" class="notif-state">
                <div class="notif-empty-icon">🔔</div>
                <div>Belum ada notifikasi</div>
              </div>

              <div v-else class="notif-list">
                <button
                  v-for="notif in notifikasis"
                  :key="notif.id"
                  type="button"
                  class="notif-item"
                  :class="{ unread: !notif.is_read }"
                  @click="markAsRead(notif)"
                >
                  <div class="notif-icon">💬</div>

                  <div class="notif-content">
                    <div class="notif-title">
                      <span>{{ notif.judul }}</span>
                      <span v-if="!notif.is_read" class="notif-dot"></span>
                    </div>

                    <p>{{ notif.pesan }}</p>

                    <small>
                      {{ formatNotifTime(notif.created_at) }}
                    </small>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- PROFILE -->
          <div class="navbar-profile-wrapper">
            <button
              type="button"
              class="navbar-profile"
              @click.stop="toggleProfileMenu"
            >
              <div class="navbar-avatar">
                <img
                  v-if="userPhoto"
                  :src="userPhoto"
                  alt="Foto profil"
                  referrerpolicy="no-referrer"
                  @error="handleImageError"
                />
                <span v-else>{{ userInitial }}</span>
              </div>

              <div class="navbar-user-info">
                <strong>{{ user?.name || 'Pengguna' }}</strong>
                <small>{{ roleLabel }}</small>
              </div>

              <span
                class="navbar-caret"
                :class="{ open: profileMenuOpen }"
              >
                ▾
              </span>
            </button>

            <div
              v-if="profileMenuOpen"
              class="navbar-dropdown navbar-profile-dropdown"
              @click.stop
            >
              <div class="profile-dropdown-header">
                <div class="navbar-avatar large">
                  <img
                    v-if="userPhoto"
                    :src="userPhoto"
                    alt="Foto profil"
                    referrerpolicy="no-referrer"
                    @error="handleImageError"
                  />
                  <span v-else>{{ userInitial }}</span>
                </div>

                <div>
                  <strong>{{ user?.name || 'Pengguna' }}</strong>
                  <small>{{ user?.email || '' }}</small>
                </div>
              </div>

              <div class="dropdown-divider"></div>

              <RouterLink
                to="/profil"
                class="dropdown-item"
                @click="closeProfileMenu"
              >
                👤
                <span>Lihat Profil</span>
              </RouterLink>

              <RouterLink
                v-if="isAdmin"
                to="/admin"
                class="dropdown-item"
                @click="closeProfileMenu"
              >
                🛠️
                <span>Dashboard Admin</span>
              </RouterLink>

              <RouterLink
                v-if="isPetugas"
                to="/petugas"
                class="dropdown-item"
                @click="closeProfileMenu"
              >
                🔧
                <span>Dashboard Petugas</span>
              </RouterLink>

              <div class="dropdown-divider"></div>

              <button
                type="button"
                class="dropdown-item danger"
                @click="handleLogout"
              >
                🚪
                <span>Logout</span>
              </button>
            </div>
          </div>
        </template>

        <!-- BUAT LAPORAN -->
        <RouterLink
          v-if="isWarga || !isLoggedIn"
          to="/laporan/buat"
          class="navbar-report-btn"
        >
          <span>📢</span>
          <span class="report-text">Buat Laporan</span>
        </RouterLink>

        <!-- HAMBURGER -->
        <button
          type="button"
          class="navbar-mobile-btn"
          :class="{ open: mobileMenuOpen }"
          :aria-expanded="mobileMenuOpen"
          aria-label="Buka menu"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </div>

    <!-- MOBILE MENU -->
    <transition name="navbar-mobile">
      <div v-if="mobileMenuOpen" class="navbar-mobile-menu">
        <div class="mobile-nav-links">
          <RouterLink
            to="/"
            class="mobile-nav-link"
            :class="{ active: route.path === '/' }"
            @click="closeMenu"
          >
            🏠
            <span>Beranda</span>
          </RouterLink>

          <RouterLink
            to="/laporan"
            class="mobile-nav-link"
            :class="{ active: route.path.startsWith('/laporan') }"
            @click="closeMenu"
          >
            📋
            <span>Laporan</span>
          </RouterLink>

          <RouterLink
            to="/about"
            class="mobile-nav-link"
            :class="{ active: route.path === '/about' }"
            @click="closeMenu"
          >
            ℹ️
            <span>Tentang Aplikasi</span>
          </RouterLink>
        </div>

        <div class="mobile-divider"></div>

        <template v-if="!isLoggedIn">
          <RouterLink
            to="/login"
            class="mobile-nav-link"
            @click="closeMenu"
          >
            👤
            <span>Masuk</span>
          </RouterLink>
        </template>

        <template v-else>
          <div class="mobile-user-card">
            <div class="navbar-avatar">
              <img
                v-if="userPhoto"
                :src="userPhoto"
                alt="Foto profil"
                referrerpolicy="no-referrer"
                @error="handleImageError"
              />
              <span v-else>{{ userInitial }}</span>
            </div>

            <div>
              <strong>{{ user?.name || 'Pengguna' }}</strong>
              <small>{{ roleLabel }}</small>
            </div>
          </div>

          <button
            type="button"
            class="mobile-nav-link"
            @click="toggleNotif"
          >
            🔔
            <span>Notifikasi</span>

            <span
              v-if="unreadCount > 0"
              class="mobile-notif-badge"
            >
              {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
          </button>

          <RouterLink
            to="/profil"
            class="mobile-nav-link"
            @click="closeMenu"
          >
            👤
            <span>Lihat Profil</span>
          </RouterLink>

          <RouterLink
            v-if="isAdmin"
            to="/admin"
            class="mobile-nav-link"
            @click="closeMenu"
          >
            🛠️
            <span>Dashboard Admin</span>
          </RouterLink>

          <RouterLink
            v-if="isPetugas"
            to="/petugas"
            class="mobile-nav-link"
            @click="closeMenu"
          >
            🔧
            <span>Dashboard Petugas</span>
          </RouterLink>

          <RouterLink
            v-if="isWarga"
            to="/laporan/buat"
            class="mobile-report-btn"
            @click="closeMenu"
          >
            📢 Buat Laporan
          </RouterLink>

          <div class="mobile-divider"></div>

          <button
            type="button"
            class="mobile-nav-link danger"
            @click="handleLogout"
          >
            🚪
            <span>Logout</span>
          </button>
        </template>
      </div>
    </transition>
  </nav>
</template>

<style scoped>
.navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(255, 255, 255, 0.92);
  border-bottom: 1px solid #e2e8f0;
  backdrop-filter: blur(14px);
}

.navbar-container {
  width: min(100% - 32px, 1280px);
  min-height: 76px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 28px;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #0f172a;
  text-decoration: none;
}

.navbar-logo {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 13px;
  background: linear-gradient(135deg, #1e3a8a, #0d9488);
  font-size: 20px;
  box-shadow: 0 6px 16px rgba(30, 58, 138, 0.18);
  transition: 0.25s ease;
}

.navbar-logo img {
  width: 100%;
  height: 100%;
  padding: 7px;
  object-fit: contain;
}

.navbar-brand:hover .navbar-logo {
  transform: scale(1.05) rotate(3deg);
}

.navbar-brand-text h1 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #0f172a;
}

.navbar-brand-text p {
  margin: 2px 0 0;
  color: #64748b;
  font-size: 11px;
}

.navbar-menu {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 32px;
}

.navbar-link {
  position: relative;
  padding: 7px 0;
  color: #475569;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.2s ease;
}

.navbar-link::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  width: 0;
  height: 2px;
  border-radius: 999px;
  background: #0d9488;
  transition: width 0.2s ease;
}

.navbar-link:hover,
.navbar-link.active {
  color: #0d9488;
}

.navbar-link:hover::after,
.navbar-link.active::after {
  width: 100%;
}

.navbar-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.navbar-login {
  padding: 9px 15px;
  border-radius: 9px;
  color: #334155;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: 0.2s ease;
}

.navbar-login:hover {
  background: #f1f5f9;
}

.navbar-icon-btn {
  position: relative;
  width: 40px;
  height: 40px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  font-size: 17px;
  transition: 0.2s ease;
}

.navbar-icon-btn:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
  transform: translateY(-1px);
}

.navbar-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #ef4444;
  color: white;
  font-size: 9px;
  font-weight: 800;
  border: 2px solid white;
}

.navbar-profile-wrapper,
.navbar-notif-wrapper {
  position: relative;
}

.navbar-profile {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 4px 7px 4px 4px;
  border: 0;
  border-radius: 11px;
  background: transparent;
  cursor: pointer;
  transition: 0.2s ease;
}

.navbar-profile:hover {
  background: #f8fafc;
}

.navbar-avatar {
  width: 38px;
  height: 38px;
  min-width: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 50%;
  background: linear-gradient(135deg, #1e3a8a, #0d9488);
  color: white;
  font-size: 14px;
  font-weight: 800;
}

.navbar-avatar.large {
  width: 42px;
  height: 42px;
  min-width: 42px;
}

.navbar-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.navbar-user-info {
  max-width: 120px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.navbar-user-info strong {
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #334155;
  font-size: 13px;
}

.navbar-user-info small {
  margin-top: 1px;
  color: #64748b;
  font-size: 10px;
}

.navbar-caret {
  color: #64748b;
  font-size: 13px;
  transition: transform 0.2s ease;
}

.navbar-caret.open {
  transform: rotate(180deg);
}

.navbar-report-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 17px;
  border-radius: 9px;
  background: linear-gradient(135deg, #1e3a8a, #1e40af);
  color: white;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 5px 13px rgba(30, 58, 138, 0.2);
  transition: 0.2s ease;
}

.navbar-report-btn:hover {
  transform: translateY(-1px);
  background: linear-gradient(135deg, #0d9488, #0f766e);
}

.navbar-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  border-radius: 13px;
  background: white;
  box-shadow: 0 15px 40px rgba(15, 23, 42, 0.13);
}

.navbar-profile-dropdown {
  width: 270px;
}

.navbar-notif-dropdown {
  width: 350px;
  max-width: calc(100vw - 30px);
  max-height: 430px;
  overflow-y: auto;
}

.profile-dropdown-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px;
}

.profile-dropdown-header strong {
  display: block;
  color: #0f172a;
  font-size: 13px;
}

.profile-dropdown-header small {
  display: block;
  max-width: 180px;
  margin-top: 2px;
  overflow: hidden;
  color: #64748b;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dropdown-divider {
  height: 1px;
  background: #e2e8f0;
}

.dropdown-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 14px;
  border: 0;
  background: white;
  color: #334155;
  font-size: 13px;
  font-weight: 600;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
  transition: 0.2s ease;
}

.dropdown-item:hover {
  background: #f8fafc;
  color: #0d9488;
}

.dropdown-item.danger {
  color: #dc2626;
}

.dropdown-item.danger:hover {
  background: #fef2f2;
  color: #b91c1c;
}

.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 13px 14px;
  border-bottom: 1px solid #e2e8f0;
}

.notif-header strong {
  display: block;
  color: #0f172a;
  font-size: 14px;
}

.notif-header small {
  display: block;
  margin-top: 2px;
  color: #64748b;
  font-size: 10px;
}

.notif-read-all {
  border: 0;
  background: none;
  color: #0d9488;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}

.notif-list {
  display: flex;
  flex-direction: column;
}

.notif-item {
  width: 100%;
  display: flex;
  gap: 10px;
  padding: 12px 13px;
  border: 0;
  border-bottom: 1px solid #f1f5f9;
  background: white;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s ease;
}

.notif-item:hover {
  background: #f8fafc;
}

.notif-item.unread {
  background: #f0fdfa;
}

.notif-icon {
  width: 34px;
  height: 34px;
  min-width: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #e0f2fe;
  font-size: 15px;
}

.notif-content {
  min-width: 0;
  flex: 1;
}

.notif-title {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #0f172a;
  font-size: 12px;
  font-weight: 700;
}

.notif-dot {
  width: 7px;
  height: 7px;
  min-width: 7px;
  border-radius: 50%;
  background: #0ea5e9;
}

.notif-content p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 11px;
  line-height: 1.5;
}

.notif-content small {
  display: block;
  margin-top: 4px;
  color: #94a3b8;
  font-size: 10px;
}

.notif-state {
  padding: 30px 15px;
  color: #64748b;
  font-size: 12px;
  text-align: center;
}

.notif-empty-icon {
  margin-bottom: 7px;
  font-size: 28px;
}

.notif-error {
  color: #dc2626;
}

.notif-retry {
  margin-top: 10px;
  padding: 6px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 7px;
  background: white;
  color: #475569;
  font-size: 11px;
  cursor: pointer;
}

.notif-spinner {
  width: 18px;
  height: 18px;
  display: inline-block;
  margin-right: 6px;
  vertical-align: middle;
  border: 2px solid #cbd5e1;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation: navbar-spin 0.7s linear infinite;
}

.navbar-mobile-btn {
  width: 40px;
  height: 40px;
  display: none;
  padding: 8px;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  background: white;
  cursor: pointer;
}

.navbar-mobile-btn span {
  display: block;
  width: 20px;
  height: 2px;
  margin: 4px auto;
  border-radius: 999px;
  background: #334155;
  transition: 0.2s ease;
}

.navbar-mobile-btn.open span:nth-child(1) {
  transform: translateY(6px) rotate(45deg);
}

.navbar-mobile-btn.open span:nth-child(2) {
  opacity: 0;
}

.navbar-mobile-btn.open span:nth-child(3) {
  transform: translateY(-6px) rotate(-45deg);
}

.navbar-mobile-menu {
  display: none;
  padding: 10px 16px 18px;
  border-top: 1px solid #f1f5f9;
  background: white;
}

.mobile-nav-links {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.mobile-nav-link {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 11px 12px;
  border: 0;
  border-radius: 9px;
  background: transparent;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
}

.mobile-nav-link:hover,
.mobile-nav-link.active {
  background: #f0fdfa;
  color: #0d9488;
}

.mobile-nav-link.danger {
  color: #dc2626;
}

.mobile-nav-link.danger:hover {
  background: #fef2f2;
}

.mobile-user-card {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  padding: 12px;
  border-radius: 11px;
  background: #f8fafc;
}

.mobile-user-card strong {
  display: block;
  color: #0f172a;
  font-size: 13px;
}

.mobile-user-card small {
  display: block;
  margin-top: 2px;
  color: #64748b;
  font-size: 10px;
}

.mobile-notif-badge {
  min-width: 19px;
  height: 19px;
  margin-left: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 5px;
  border-radius: 999px;
  background: #ef4444;
  color: white;
  font-size: 9px;
  font-weight: 800;
}

.mobile-report-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 8px;
  padding: 11px;
  border-radius: 9px;
  background: linear-gradient(135deg, #1e3a8a, #1e40af);
  color: white;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
}

.mobile-divider {
  height: 1px;
  margin: 8px 0;
  background: #e2e8f0;
}

.navbar-mobile-enter-active,
.navbar-mobile-leave-active {
  transition: all 0.2s ease;
}

.navbar-mobile-enter-from,
.navbar-mobile-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@keyframes navbar-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 900px) {
  .navbar-menu {
    gap: 20px;
  }

  .navbar-user-info {
    display: none;
  }

  .navbar-report-btn {
    padding: 9px 12px;
  }
}

@media (max-width: 767px) {
  .navbar-container {
    min-height: 68px;
    width: min(100% - 24px, 1280px);
    gap: 10px;
  }

  .navbar-brand-text p {
    display: none;
  }

  .navbar-brand-text h1 {
    font-size: 16px;
  }

  .navbar-logo {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }

  .navbar-menu {
    display: none;
  }

  .navbar-actions {
    gap: 7px;
  }

  .navbar-icon-btn {
    width: 38px;
    height: 38px;
  }

  .navbar-profile {
    padding: 0;
  }

  .navbar-caret {
    display: none;
  }

  .navbar-profile-wrapper {
    display: none;
  }

  .navbar-report-btn {
    display: none;
  }

  .navbar-mobile-btn {
    display: block;
  }

  .navbar-mobile-menu {
    display: block;
  }

  .navbar-notif-dropdown {
    position: fixed;
    top: 65px;
    right: 12px;
    left: 12px;
    width: auto;
    max-width: none;
  }
}

@media (max-width: 480px) {
  .navbar-container {
    width: min(100% - 16px, 1280px);
  }

  .navbar-brand {
    gap: 8px;
  }

  .navbar-logo {
    width: 38px;
    height: 38px;
  }

  .navbar-brand-text h1 {
    font-size: 15px;
  }

  .navbar-mobile-btn {
    width: 38px;
    height: 38px;
  }
}
</style>