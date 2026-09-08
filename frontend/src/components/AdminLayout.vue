<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'

defineProps({
  pageTitle: {
    type: String,
    default: 'Dashboard',
  },
  pageDescription: {
    type: String,
    default: '',
  },
})

const route = useRoute()
const router = useRouter()

const sidebarOpen = ref(false)
const profileMenuOpen = ref(false)
const notifOpen = ref(false)

const notifikasis = ref([])
const userVersion = ref(0)

const user = computed(() => {
  userVersion.value
  try {
    return JSON.parse(localStorage.getItem('user') || 'null')
  } catch {
    return null
  }
})

const menuItems = [
  { key: 'dashboard', label: 'Dashboard', icon: '📊', to: '/admin' },
  { key: 'reports', label: 'Laporan', icon: '📋', to: '/admin/laporan' },
  { key: 'users', label: 'Pengguna', icon: '👥', to: '/admin/users' },
  { key: 'categories', label: 'Kategori', icon: '🏷️', to: '/admin/kategori' },
  { key: 'feedback', label: 'Masukan & Bug', icon: '💬', to: '/admin/feedback' },
  { key: 'settings', label: 'Pengaturan', icon: '⚙️', to: '/admin/settings' },
]

const activeKey = computed(() => {
  if (route.path === '/admin') return 'dashboard'
  if (route.path.startsWith('/admin/laporan')) return 'reports'
  if (route.path.startsWith('/admin/users')) return 'users'
  if (route.path.startsWith('/admin/kategori')) return 'categories'
  if (route.path.startsWith('/admin/feedback')) return 'feedback'
  if (route.path.startsWith('/admin/settings')) return 'settings'
  return null
})

const unreadCount = computed(() => {
  if (!Array.isArray(notifikasis.value)) return 0
  return notifikasis.value.filter((notif) => !notif.is_read).length
})

function closeProfileMenu(e) {
  if (!e.target.closest('.admin-profile-wrapper')) {
    profileMenuOpen.value = false
  }
  if (!e.target.closest('.notif-wrapper')) {
    notifOpen.value = false
  }
}

function toggleNotif() {
  notifOpen.value = !notifOpen.value
  profileMenuOpen.value = false
}

function toggleProfile() {
  profileMenuOpen.value = !profileMenuOpen.value
  notifOpen.value = false
}

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

async function markAsRead(notif) {
  if (!notif || notif.is_read) return

  try {
    await api.put(`/notifikasis/${notif.id}/read`)
    notif.is_read = true
  } catch (err) {
    console.error('Gagal menandai notifikasi:', err)
  }
}

async function markAllAsRead() {
  if (notifikasis.value.length === 0) return

  try {
    await api.put('/notifikasis/read-all')
    notifikasis.value.forEach((notif) => {
      notif.is_read = true
    })
  } catch (err) {
    console.error('Gagal menandai semua notifikasi:', err)
  }
}

async function handleLogout() {
  try {
    await api.post('/logout')
  } catch (err) {
    console.log('Logout API:', err)
  }

  localStorage.removeItem('token')
  localStorage.removeItem('user')

  router.push('/login')
}

function handleUserUpdated() {
  userVersion.value++
}

onMounted(() => {
  document.addEventListener('click', closeProfileMenu)
  window.addEventListener('user-updated', handleUserUpdated)
  fetchNotifikasis()
})

onUnmounted(() => {
  document.removeEventListener('click', closeProfileMenu)
  window.removeEventListener('user-updated', handleUserUpdated)
})
</script>

<template>
  <div class="admin-layout" :class="{ 'sidebar-open': sidebarOpen }">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">

      <div class="admin-sidebar-header">
        <RouterLink to="/" class="admin-logo">
          <div class="admin-logo-icon">📢</div>
          <span>SUARAWARGA</span>
        </RouterLink>

        <button class="sidebar-close" @click="sidebarOpen = false">✕</button>
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
          <span class="admin-nav-icon">{{ item.icon }}</span>
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
    <div v-if="sidebarOpen" class="admin-overlay" @click="sidebarOpen = false"></div>

    <!-- MAIN -->
    <div class="admin-main">

      <!-- TOPBAR -->
      <header class="admin-topbar">
        <button class="sidebar-toggle" @click="sidebarOpen = true">☰</button>

        <div class="admin-topbar-title">
          <h1>{{ pageTitle }}</h1>
          <p v-if="pageDescription">{{ pageDescription }}</p>
        </div>

        <div class="admin-topbar-actions">

          <!-- NOTIFIKASI -->
          <div class="notif-wrapper" style="position: relative;">
            <button class="admin-icon-btn" aria-label="Notifikasi" @click.stop="toggleNotif">
              🔔
              <span v-if="unreadCount > 0" class="admin-badge">{{ unreadCount }}</span>
            </button>

            <transition name="fade-in">
              <div v-if="notifOpen" class="admin-profile-menu" style="width: 320px; max-height: 400px; overflow-y: auto;">

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 8px 10px;">
                  <strong style="font-size: 14px;">Notifikasi</strong>
                  <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    style="font-size: 12px; color: var(--accent-dark); background: none; border: none; cursor: pointer;"
                  >
                    Tandai semua dibaca
                  </button>
                </div>

                <div v-if="notifikasis.length === 0" style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px;">
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
                  <div style="font-weight: 700; font-size: 13px;">{{ notif.judul }}</div>
                  <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">{{ notif.pesan }}</div>
                </div>

              </div>
            </transition>
          </div>

          <!-- PROFIL -->
          <div class="admin-profile-wrapper">
            <button class="admin-profile" @click.stop="toggleProfile" :aria-expanded="profileMenuOpen">
              <img
                v-if="user?.foto_url"
                :src="user.foto_url"
                class="admin-avatar"
                style="object-fit: cover;"
              />
              <div v-else class="admin-avatar">
                {{ user?.name?.charAt(0).toUpperCase() || 'A' }}
              </div>

              <div class="admin-profile-info">
                <p class="admin-profile-name">{{ user?.name || 'Admin' }}</p>
                <p class="admin-profile-role">Super Admin</p>
              </div>

              <span class="admin-profile-caret" :class="{ open: profileMenuOpen }">▾</span>
            </button>

            <transition name="fade-in">
              <div v-if="profileMenuOpen" class="admin-profile-menu">

                <RouterLink to="/admin/profile" class="admin-profile-menu-item" @click="profileMenuOpen = false">
                  👤 Lihat Profil
                </RouterLink>

                <RouterLink to="/admin/settings" class="admin-profile-menu-item" @click="profileMenuOpen = false">
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