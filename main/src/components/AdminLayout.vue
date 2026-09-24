<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { applySiteSettings } from '../utils/siteSettings'

defineProps({
  pageTitle: {
    type: String,
    default: 'Dashboard'
  },
  pageDescription: {
    type: String,
    default: ''
  }
})

const route = useRoute()
const router = useRouter()

// UI
const sidebarOpen = ref(false)
const profileMenuOpen = ref(false)
const notifOpen = ref(false)

// NOTIFIKASI
const notifikasis = ref([])

// USER
const userVersion = ref(0)

const user = computed(() => {
  userVersion.value

  try {
    return JSON.parse(sessionStorage.getItem('user') || 'null')
  } catch {
    return null
  }
})

// PENGATURAN SITUS
const siteName = ref('SUARAWARGA')
const siteLogo = ref(null)

let siteSettingsTimer = null

// MENU
const menuItems = [
  { key: 'dashboard', label: 'Dashboard', icon: '📊', to: '/admin' },
  { key: 'reports', label: 'Laporan', icon: '📋', to: '/admin/laporan' },
  { key: 'users', label: 'Pengguna', icon: '👥', to: '/admin/users' },
  { key: 'categories', label: 'Kategori', icon: '🏷️', to: '/admin/kategori' },
  { key: 'feedback', label: 'Masukan & Bug', icon: '💬', to: '/admin/feedback' },
  { key: 'settings', label: 'Pengaturan', icon: '⚙️', to: '/admin/settings' }
]

// MENU AKTIF
const activeKey = computed(() => {
  if (route.path === '/admin') return 'dashboard'
  if (route.path.startsWith('/admin/laporan')) return 'reports'
  if (route.path.startsWith('/admin/users')) return 'users'
  if (route.path.startsWith('/admin/kategori')) return 'categories'
  if (route.path.startsWith('/admin/feedback')) return 'feedback'
  if (route.path.startsWith('/admin/settings')) return 'settings'
  return null
})

// JUMLAH NOTIFIKASI BELUM DIBACA
const unreadCount = computed(() => {
  if (!Array.isArray(notifikasis.value)) return 0
  return notifikasis.value.filter(notif => !notif.is_read).length
})

// CLOSE MENU SAAT KLIK DI LUAR
function closeProfileMenu(event) {
  if (!event.target.closest('.admin-profile-wrapper')) {
    profileMenuOpen.value = false
  }

  if (!event.target.closest('.notif-wrapper')) {
    notifOpen.value = false
  }
}

// TOGGLE NOTIFIKASI
function toggleNotif() {
  notifOpen.value = !notifOpen.value
  profileMenuOpen.value = false
}

// TOGGLE PROFILE
function toggleProfile() {
  profileMenuOpen.value = !profileMenuOpen.value
  notifOpen.value = false
}

// FETCH PENGATURAN SITUS
async function fetchSiteSettings() {
  try {
    const res = await api.get('/settings')

    siteName.value = res.data?.site_name || 'SUARAWARGA'
    siteLogo.value = res.data?.site_logo || null

    applySiteSettings({
      site_name: siteName.value,
      site_logo: siteLogo.value
    })
  } catch (err) {
    console.error('Gagal mengambil pengaturan situs:', err)

    siteName.value = 'SUARAWARGA'
    siteLogo.value = null

    applySiteSettings({
      site_name: 'SUARAWARGA',
      site_logo: null
    })
  }
}

// UPDATE DARI LOCALSTORAGE
function updateSiteFromStorage() {
  const saved = localStorage.getItem('site_settings')

  if (!saved) {
    fetchSiteSettings()
    return
  }

  try {
    const settings = JSON.parse(saved)

    siteName.value = settings.site_name || 'SUARAWARGA'
    siteLogo.value = settings.site_logo || null

    applySiteSettings({
      site_name: siteName.value,
      site_logo: siteLogo.value
    })
  } catch (err) {
    console.error('Gagal membaca site settings:', err)
    fetchSiteSettings()
  }
}

// UPDATE DARI TAB LAIN
function handleStorage(event) {
  if (event.key !== 'site_settings' || !event.newValue) return

  try {
    const settings = JSON.parse(event.newValue)

    siteName.value = settings.site_name || 'SUARAWARGA'
    siteLogo.value = settings.site_logo || null

    applySiteSettings({
      site_name: siteName.value,
      site_logo: siteLogo.value
    })
  } catch (err) {
    console.error('Gagal membaca perubahan situs:', err)
  }
}

// UPDATE TAB YANG SAMA
function handleSettingsUpdated() {
  updateSiteFromStorage()
}

// AUTO REFRESH SETIAP 3 DETIK
async function refreshSiteSettingsSilently() {
  try {
    const res = await api.get('/settings')

    const newSiteName = res.data?.site_name || 'SUARAWARGA'
    const newSiteLogo = res.data?.site_logo || null

    const changed =
      siteName.value !== newSiteName ||
      siteLogo.value !== newSiteLogo

    if (!changed) return

    const settings = {
      site_name: newSiteName,
      site_logo: newSiteLogo
    }

    siteName.value = newSiteName
    siteLogo.value = newSiteLogo

    applySiteSettings(settings)

    localStorage.setItem(
      'site_settings',
      JSON.stringify(settings)
    )
  } catch {
    // Abaikan error auto refresh
  }
}

// FETCH NOTIFIKASI
async function fetchNotifikasis() {
  try {
    const res = await api.get('/notifikasis')

    if (Array.isArray(res.data)) {
      notifikasis.value = res.data
    } else if (Array.isArray(res.data?.data)) {
      notifikasis.value = res.data.data
    } else {
      notifikasis.value = []
    }
  } catch (err) {
    console.error('Gagal mengambil notifikasi:', err)
    notifikasis.value = []
  }
}

// TANDAI SATU NOTIFIKASI
async function markAsRead(notif) {
  if (!notif || notif.is_read) return

  try {
    await api.put(`/notifikasis/${notif.id}/read`)
    notif.is_read = true
  } catch (err) {
    console.error('Gagal menandai notifikasi:', err)
  }
}

// TANDAI SEMUA NOTIFIKASI
async function markAllAsRead() {
  if (notifikasis.value.length === 0) return

  try {
    await api.put('/notifikasis/read-all')

    notifikasis.value.forEach(notif => {
      notif.is_read = true
    })
  } catch (err) {
    console.error('Gagal menandai semua notifikasi:', err)
  }
}

// LOGOUT
async function handleLogout() {
  try {
    await api.post('/logout')
  } catch (err) {
    console.log('Logout API:', err)
  }

  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')

  router.push('/login')
}

// USER UPDATED
function handleUserUpdated() {
  userVersion.value++
}

// IMAGE ERROR
function handleImageError(event) {
  event.target.style.display = 'none'
}

// MOUNT
onMounted(() => {
  document.addEventListener('click', closeProfileMenu)

  window.addEventListener(
    'user-updated',
    handleUserUpdated
  )

  window.addEventListener(
    'storage',
    handleStorage
  )

  window.addEventListener(
    'settings-updated',
    handleSettingsUpdated
  )

  fetchNotifikasis()
  fetchSiteSettings()

  siteSettingsTimer = setInterval(() => {
    refreshSiteSettingsSilently()
  }, 3000)
})

// UNMOUNT
onUnmounted(() => {
  document.removeEventListener('click', closeProfileMenu)

  window.removeEventListener(
    'user-updated',
    handleUserUpdated
  )

  window.removeEventListener(
    'storage',
    handleStorage
  )

  window.removeEventListener(
    'settings-updated',
    handleSettingsUpdated
  )

  clearInterval(siteSettingsTimer)
})
</script>

<template>
  <div
    class="admin-layout"
    :class="{ 'sidebar-open': sidebarOpen }"
  >
    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
      <div class="admin-sidebar-header">
        <RouterLink to="/" class="admin-logo">
          <div class="admin-logo-icon">
            <img
              v-if="siteLogo"
              :src="siteLogo"
              :alt="siteName"
              @error="handleImageError"
            />

            <span v-else>📢</span>
          </div>

          <span>{{ siteName }}</span>
        </RouterLink>

        <button
          class="sidebar-close"
          @click="sidebarOpen = false"
        >
          ✕
        </button>
      </div>

      <nav class="admin-nav">
        <RouterLink
          v-for="item in menuItems"
          :key="item.key"
          :to="item.to"
          class="admin-nav-link"
          :class="{ active: activeKey === item.key }"
          @click="sidebarOpen = false"
        >
          <span class="admin-nav-icon">
            {{ item.icon }}
          </span>
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="admin-sidebar-footer">
        <button
          class="admin-nav-link"
          style="width: 100%; text-align: left; background: none; border: none;"
          @click="handleLogout"
        >
          <span class="admin-nav-icon">🚪</span>
          Keluar
        </button>
      </div>
    </aside>

    <!-- MOBILE OVERLAY -->
    <div
      v-if="sidebarOpen"
      class="admin-overlay"
      @click="sidebarOpen = false"
    ></div>

    <!-- MAIN -->
    <div class="admin-main">
      <!-- TOPBAR -->
      <header class="admin-topbar">
        <button
          class="sidebar-toggle"
          @click="sidebarOpen = true"
        >
          ☰
        </button>

        <div class="admin-topbar-title">
          <h1>{{ pageTitle }}</h1>

          <p v-if="pageDescription">
            {{ pageDescription }}
          </p>
        </div>

        <div class="admin-topbar-actions">
          <!-- NOTIFIKASI -->
          <div
            class="notif-wrapper"
            style="position: relative;"
          >
            <button
              class="admin-icon-btn"
              aria-label="Notifikasi"
              @click.stop="toggleNotif"
            >
              🔔

              <span
                v-if="unreadCount > 0"
                class="admin-badge"
              >
                {{ unreadCount }}
              </span>
            </button>

            <transition name="fade-in">
              <div
                v-if="notifOpen"
                class="admin-profile-menu"
                style="width: 320px; max-height: 400px; overflow-y: auto;"
              >
                <div
                  style="display: flex; justify-content: space-between; align-items: center; padding: 6px 8px 10px;"
                >
                  <strong style="font-size: 14px;">
                    Notifikasi
                  </strong>

                  <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    style="font-size: 12px; color: var(--accent-dark); background: none; border: none; cursor: pointer;"
                  >
                    Tandai semua dibaca
                  </button>
                </div>

                <div
                  v-if="notifikasis.length === 0"
                  style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px;"
                >
                  Belum ada notifikasi
                </div>

                <div
                  v-for="notif in notifikasis"
                  :key="notif.id"
                  class="admin-profile-menu-item"
                  :style="!notif.is_read ? 'background: var(--primary-light);' : ''"
                  style="cursor: pointer;"
                  @click="markAsRead(notif)"
                >
                  <div style="font-weight: 700; font-size: 13px;">
                    {{ notif.judul }}
                  </div>

                  <div
                    style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"
                  >
                    {{ notif.pesan }}
                  </div>
                </div>
              </div>
            </transition>
          </div>

          <!-- PROFIL -->
          <div class="admin-profile-wrapper">
            <button
              class="admin-profile"
              @click.stop="toggleProfile"
              :aria-expanded="profileMenuOpen"
            >
              <img
                v-if="user?.foto_url"
                :src="user.foto_url"
                class="admin-avatar"
                style="object-fit: cover;"
              />

              <div
                v-else
                class="admin-avatar"
              >
                {{
                  user?.name?.charAt(0).toUpperCase() ||
                  'A'
                }}
              </div>

              <div class="admin-profile-info">
                <p class="admin-profile-name">
                  {{ user?.name || 'Admin' }}
                </p>

                <p class="admin-profile-role">
                  Super Admin
                </p>
              </div>

              <span
                class="admin-profile-caret"
                :class="{ open: profileMenuOpen }"
              >
                ▾
              </span>
            </button>

            <transition name="fade-in">
              <div
                v-if="profileMenuOpen"
                class="admin-profile-menu"
              >
                <RouterLink
                  to="/admin/profile"
                  class="admin-profile-menu-item"
                  @click="profileMenuOpen = false"
                >
                  👤 Lihat Profil
                </RouterLink>

                <RouterLink
                  to="/admin/settings"
                  class="admin-profile-menu-item"
                  @click="profileMenuOpen = false"
                >
                  ⚙️ Pengaturan Profil
                </RouterLink>

                <div class="admin-profile-menu-divider"></div>

                <button
                  class="admin-profile-menu-item danger"
                  style="width: 100%; text-align: left; background: none; border: none; cursor: pointer;"
                  @click="handleLogout"
                >
                  🚪 Keluar
                </button>
              </div>
            </transition>
          </div>
        </div>
      </header>

      <!-- CONTENT -->
      <main class="admin-content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.admin-logo-icon {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
  border-radius: 10px;
}

.admin-logo-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.admin-logo-icon span {
  font-size: 24px;
  line-height: 1;
}
</style>