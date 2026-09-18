<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

// STATE
const mobileMenuOpen = ref(false)
const profileMenuOpen = ref(false)
const notifOpen = ref(false)
const notifikasis = ref([])
const loadingNotif = ref(false)
const notifError = ref('')
const userVersion = ref(0)
const siteName = ref('SUARAWARGA')
const siteTagline = ref('Suara masyarakat, perubahan nyata.')

// USER
const user = computed(() => {
  userVersion.value
  try {
    return JSON.parse(sessionStorage.getItem('user') || 'null')
  } catch {
    return null
  }
})
const isLoggedIn = computed(() => !!sessionStorage.getItem('token'))
const isAdmin = computed(() => user.value?.role === 'admin')

// NOTIFICATION
const unreadCount = computed(() => {
  if (!Array.isArray(notifikasis.value)) return 0
  return notifikasis.value.filter(n => !n.is_read).length
})

// MENU
function closeMenu() { mobileMenuOpen.value = false }
function closeProfileMenu() { profileMenuOpen.value = false }
function toggleProfileMenu() {
  profileMenuOpen.value = !profileMenuOpen.value
  notifOpen.value = false
}
function toggleNotif() {
  notifOpen.value = !notifOpen.value
  profileMenuOpen.value = false
  if (notifOpen.value) fetchNotifikasis()
}

// FETCH SITE SETTINGS
async function fetchSiteSettings() {
  try {
    const res = await api.get('/settings')
    siteName.value = res.data.site_name || 'SUARAWARGA'
    siteTagline.value = res.data.site_tagline || 'Suara masyarakat, perubahan nyata.'
  } catch (err) {
    // pakai default kalau gagal
  }
}

// FETCH NOTIFICATION
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
    if (!notif.is_read) {
      await api.put(`/notifikasis/${notif.id}/read`)
      notif.is_read = true
    }
    notifOpen.value = false
    if (notif.laporan_id) router.push(`/laporan/${notif.laporan_id}`)
  } catch (err) {
    if (notif.laporan_id) {
      notifOpen.value = false
      router.push(`/laporan/${notif.laporan_id}`)
    }
  }
}

async function markAllAsRead() {
  if (unreadCount.value === 0) return
  try {
    await api.put('/notifikasis/read-all')
    notifikasis.value.forEach(n => (n.is_read = true))
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

  return created.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function handleLogout() {
  try {
    await api.post('/logout')
  } catch (err) {
    console.error('Logout API:', err)
  }
  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')
  notifikasis.value = []
  mobileMenuOpen.value = false
  profileMenuOpen.value = false
  notifOpen.value = false
  userVersion.value++
  router.push('/login')
}

function handleClickOutside(e) {
  if (!e.target.closest('.admin-profile-wrapper') && !e.target.closest('.notif-wrapper')) {
    profileMenuOpen.value = false
    notifOpen.value = false
  }
}

function handleStorageChange(e) {
  if (e.key === 'token' || e.key === 'user') {
    userVersion.value++
    fetchNotifikasis()
  }
}

function handleUserUpdated() {
  userVersion.value++
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('storage', handleStorageChange)
  window.addEventListener('user-updated', handleUserUpdated)
  fetchNotifikasis()
  fetchSiteSettings()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('storage', handleStorageChange)
  window.removeEventListener('user-updated', handleUserUpdated)
})
</script>

<template>
  <nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur-md">
    <div class="mx-auto grid max-w-7xl grid-cols-[auto_1fr_auto] items-center gap-6 px-6 py-4">

      <!-- LOGO -->
      <RouterLink to="/" class="group flex items-center gap-3" @click="closeMenu">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-900 to-teal-600 text-xl shadow-md shadow-blue-900/20 transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3">
          📢
        </div>
        <div>
          <h1 class="text-lg font-bold tracking-tight text-slate-900">{{ siteName }}</h1>
          <p class="hidden text-xs text-slate-500 sm:block">{{ siteTagline }}</p>
        </div>
      </RouterLink>

      <!-- MENU DESKTOP -->
      <div class="hidden items-center justify-center gap-8 md:flex">
        <RouterLink
          to="/"
          class="group relative py-1 text-sm font-medium transition-colors duration-300"
          :class="route.path === '/' ? 'text-teal-600' : 'text-slate-600 hover:text-teal-600'"
        >
          Beranda
          <span
            class="absolute -bottom-1 left-0 h-0.5 rounded-full bg-teal-600 transition-all duration-300"
            :class="route.path === '/' ? 'w-full' : 'w-0 group-hover:w-full'"
          ></span>
        </RouterLink>

        <RouterLink
          to="/laporan"
          class="group relative py-1 text-sm font-medium transition-colors duration-300"
          :class="route.path.startsWith('/laporan') ? 'text-teal-600' : 'text-slate-600 hover:text-teal-600'"
        >
          Laporan
          <span
            class="absolute -bottom-1 left-0 h-0.5 rounded-full bg-teal-600 transition-all duration-300"
            :class="route.path.startsWith('/laporan') ? 'w-full' : 'w-0 group-hover:w-full'"
          ></span>
        </RouterLink>

        <RouterLink
          to="/about"
          class="group relative py-1 text-sm font-medium transition-colors duration-300"
          :class="route.path === '/about' ? 'text-teal-600' : 'text-slate-600 hover:text-teal-600'"
        >
          Tentang
          <span
            class="absolute -bottom-1 left-0 h-0.5 rounded-full bg-teal-600 transition-all duration-300"
            :class="route.path === '/about' ? 'w-full' : 'w-0 group-hover:w-full'"
          ></span>
        </RouterLink>
      </div>

      <!-- BAGIAN KANAN -->
      <div class="flex items-center justify-end gap-3">

        <!-- BELUM LOGIN -->
        <template v-if="!isLoggedIn">
          <RouterLink to="/login" class="hidden rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 transition-colors duration-300 hover:bg-slate-100 sm:block">
            Masuk
          </RouterLink>
        </template>

        <!-- SUDAH LOGIN -->
        <template v-else>

          <!-- NOTIFIKASI -->
          <div class="notif-wrapper" style="position: relative;">
            <button type="button" class="admin-icon-btn hidden sm:block" @click.stop="toggleNotif" title="Notifikasi">
              🔔
              <span v-if="unreadCount > 0" class="admin-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
            </button>

            <div
              v-if="notifOpen"
              class="admin-profile-menu"
              style="width: 350px; max-width: calc(100vw - 30px); max-height: 430px; overflow-y: auto; right: 0; left: auto;"
              @click.stop
            >
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px 12px; border-bottom: 1px solid var(--border); margin-bottom: 4px;">
                <div>
                  <strong style="font-size: 15px;">Notifikasi</strong>
                  <div v-if="unreadCount > 0" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">{{ unreadCount }} belum dibaca</div>
                </div>
                <button v-if="unreadCount > 0" type="button" @click="markAllAsRead" style="font-size: 12px; color: var(--accent-dark); background: none; border: none; cursor: pointer;">
                  Tandai semua dibaca
                </button>
              </div>

              <div v-if="loadingNotif" style="padding: 25px 15px; text-align: center; color: var(--text-muted); font-size: 13px;">
                Memuat notifikasi...
              </div>

              <div v-else-if="notifError" style="padding: 20px; text-align: center; color: #dc2626; font-size: 13px;">
                {{ notifError }}
                <button type="button" @click="fetchNotifikasis" class="btn btn-secondary" style="margin-top: 10px; font-size: 12px;">🔄 Coba lagi</button>
              </div>

              <div v-else-if="notifikasis.length === 0" style="padding: 30px 15px; text-align: center; color: var(--text-muted); font-size: 13px;">
                <div style="font-size: 30px; margin-bottom: 8px;">🔔</div>
                <div>Belum ada notifikasi</div>
              </div>

              <div
                v-else
                v-for="notif in notifikasis"
                :key="notif.id"
                class="admin-profile-menu-item"
                :style="!notif.is_read ? 'background: var(--primary-light);' : ''"
                style="cursor: pointer; display: block; padding: 12px 10px; border-bottom: 1px solid var(--border);"
                @click="markAsRead(notif)"
              >
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                  <div style="width: 34px; height: 34px; min-width: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e0f2fe; font-size: 16px;">💬</div>
                  <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                      <div style="font-weight: 700; font-size: 13px; color: var(--text-primary);">{{ notif.judul }}</div>
                      <span v-if="!notif.is_read" style="width: 7px; height: 7px; min-width: 7px; border-radius: 50%; background: #0ea5e9;"></span>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px; line-height: 1.5;">{{ notif.pesan }}</div>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">{{ formatNotifTime(notif.created_at) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- PROFILE -->
          <div class="admin-profile-wrapper hidden sm:block">
            <button type="button" class="admin-profile" @click.stop="toggleProfileMenu">
              <img v-if="user?.foto_url" :src="user.foto_url" class="admin-avatar" style="object-fit: cover;" alt="Foto profil" />
              <div v-else class="admin-avatar">{{ user?.name?.charAt(0).toUpperCase() }}</div>
              <span class="text-sm font-semibold text-slate-700">{{ user?.name }}</span>
              <span class="admin-profile-caret" :class="{ open: profileMenuOpen }">▾</span>
            </button>

            <div v-if="profileMenuOpen" class="admin-profile-menu">
              <RouterLink to="/profil" class="admin-profile-menu-item" @click="closeProfileMenu">👤 Lihat Profil</RouterLink>
              <RouterLink v-if="isAdmin" to="/admin" class="admin-profile-menu-item" @click="closeProfileMenu">🛠️ Kembali ke Dashboard</RouterLink>
              <div class="admin-profile-menu-divider"></div>
              <button type="button" class="admin-profile-menu-item danger" style="width: 100%; text-align: left; background: none; border: none;" @click="handleLogout">
                🚪 Logout
              </button>
            </div>
          </div>
        </template>

        <!-- BUAT LAPORAN DESKTOP -->
        <RouterLink
          to="/laporan/buat"
          class="hidden rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-900/25 transition-all duration-300 hover:-translate-y-0.5 hover:from-teal-600 hover:to-teal-500 hover:shadow-lg hover:shadow-blue-900/30 sm:block"
        >
          Buat Laporan
        </RouterLink>

        <!-- HAMBURGER -->
        <button type="button" class="mobile-menu-btn md:hidden" @click="mobileMenuOpen = !mobileMenuOpen" :aria-expanded="mobileMenuOpen" aria-label="Buka menu">
          <span :class="{ open: mobileMenuOpen }"></span>
          <span :class="{ open: mobileMenuOpen }"></span>
          <span :class="{ open: mobileMenuOpen }"></span>
        </button>
      </div>
    </div>

    <!-- MOBILE MENU -->
    <transition name="mobile-menu">
      <div v-if="mobileMenuOpen" class="mobile-menu md:hidden">
        <RouterLink to="/" class="mobile-menu-link" @click="closeMenu">🏠 Beranda</RouterLink>
        <RouterLink to="/laporan" class="mobile-menu-link" @click="closeMenu">📋 Laporan</RouterLink>
        <RouterLink to="/about" class="mobile-menu-link" @click="closeMenu">ℹ️ Tentang Aplikasi</RouterLink>

        <div class="mobile-menu-divider"></div>

        <template v-if="!isLoggedIn">
          <RouterLink to="/login" class="mobile-menu-link" @click="closeMenu">👤 Masuk</RouterLink>
        </template>

        <template v-else>
          <RouterLink to="/profil" class="mobile-menu-link" @click="closeMenu">
            👤 Lihat Profil <span v-if="user?.name">({{ user.name }})</span>
          </RouterLink>
          <RouterLink v-if="isAdmin" to="/admin" class="mobile-menu-link" @click="closeMenu">🛠️ Kembali ke Dashboard</RouterLink>
          <button type="button" class="mobile-menu-link" @click="handleLogout" style="text-align: left; background: none; border: none;">🚪 Logout</button>
        </template>

        <RouterLink to="/laporan/buat" class="btn btn-primary mobile-menu-cta" @click="closeMenu">📢 Buat Laporan</RouterLink>
      </div>
    </transition>
  </nav>
</template>