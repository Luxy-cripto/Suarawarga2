<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '../../components/AdminLayout.vue'
import api from '../../services/api'

// Profil Admin
const profile = ref({ name: '', email: '' })
const profileSaving = ref(false)
const profileSaved = ref(false)
const profileError = ref('')

// Pengaturan Situs
const site = ref({
  site_name: '',
  site_tagline: '',
  site_description: '',
})
const siteLoading = ref(true)
const siteSaving = ref(false)
const siteSaved = ref(false)
const siteError = ref('')

// Notifikasi (preferensi lokal)
const notifications = ref({
  newReport: true,
  reportResolved: true,
  weeklyDigest: false,
  newUser: true,
})

// Keamanan
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordError = ref('')
const passwordSaving = ref(false)
const passwordSaved = ref(false)

async function fetchProfile() {
  try {
    const res = await api.get('/profile')
    profile.value = { name: res.data.name, email: res.data.email }
    localStorage.setItem('user', JSON.stringify(res.data))
  } catch (err) {
    // ignore
  }
}

async function saveProfile() {
  profileSaving.value = true
  profileSaved.value = false
  profileError.value = ''
  try {
    const res = await api.post('/profile', profile.value)
    localStorage.setItem('user', JSON.stringify(res.data))
    window.dispatchEvent(new Event('user-updated'))
    profileSaved.value = true
    setTimeout(() => (profileSaved.value = false), 2500)
  } catch (err) {
    profileError.value = err.response?.data?.message || 'Gagal menyimpan profil.'
  } finally {
    profileSaving.value = false
  }
}

async function fetchSettings() {
  siteLoading.value = true
  try {
    const res = await api.get('/settings')
    site.value = res.data
  } catch (err) {
    // ignore
  } finally {
    siteLoading.value = false
  }
}

async function saveSite() {
  siteSaving.value = true
  siteSaved.value = false
  siteError.value = ''
  try {
    await api.put('/settings', site.value)
    siteSaved.value = true
    setTimeout(() => (siteSaved.value = false), 2500)
  } catch (err) {
    siteError.value = 'Gagal menyimpan pengaturan situs.'
  } finally {
    siteSaving.value = false
  }
}

function loadNotifPrefs() {
  const saved = localStorage.getItem('admin_notif_prefs')
  if (saved) {
    try {
      notifications.value = JSON.parse(saved)
    } catch (err) {
      // ignore
    }
  }
}

function saveNotifPrefs() {
  localStorage.setItem('admin_notif_prefs', JSON.stringify(notifications.value))
}

async function changePassword() {
  passwordError.value = ''

  if (!passwordForm.value.current_password || !passwordForm.value.password || !passwordForm.value.password_confirmation) {
    passwordError.value = 'Semua kolom wajib diisi.'
    return
  }

  if (passwordForm.value.password.length < 6) {
    passwordError.value = 'Kata sandi baru minimal 6 karakter.'
    return
  }

  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = 'Konfirmasi kata sandi tidak cocok.'
    return
  }

  passwordSaving.value = true
  try {
    await api.post('/profile', {
      name: profile.value.name,
      email: profile.value.email,
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    })
    passwordSaved.value = true
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    setTimeout(() => (passwordSaved.value = false), 2500)
  } catch (err) {
    passwordError.value = err.response?.data?.errors?.current_password?.[0]
      || err.response?.data?.message
      || 'Gagal mengubah kata sandi.'
  } finally {
    passwordSaving.value = false
  }
}

onMounted(() => {
  fetchProfile()
  fetchSettings()
  loadNotifPrefs()
})
</script>

<template>
  <AdminLayout page-title="Pengaturan" page-description="Kelola profil admin, situs, dan preferensi lainnya">

    <div class="settings-grid">

      <!-- PROFIL ADMIN -->
      <div class="card settings-section">
        <h2>Profil Admin</h2>
        <p class="text-muted settings-desc">Informasi dasar akun admin kamu.</p>

        <p v-if="profileError" class="auth-error">{{ profileError }}</p>

        <form @submit.prevent="saveProfile">

          <div class="settings-avatar-row">
            <div class="admin-avatar settings-avatar">
              {{ profile.name?.charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="admin-table-title">{{ profile.name }}</p>
              <p class="admin-table-sub">Super Admin</p>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Nama</label>
            <input v-model="profile.name" type="text" class="form-control" required />
          </div>

          <div class="form-group">
            <label class="form-label">Email</label>
            <input v-model="profile.email" type="email" class="form-control" required />
          </div>

          <div class="settings-actions">
            <span v-if="profileSaved" class="settings-saved">✓ Tersimpan</span>
            <button type="submit" class="btn btn-primary" :disabled="profileSaving">
              {{ profileSaving ? 'Menyimpan...' : 'Simpan Profil' }}
            </button>
          </div>

        </form>
      </div>

      <!-- PENGATURAN SITUS -->
      <div class="card settings-section">
        <h2>Pengaturan Situs</h2>
        <p class="text-muted settings-desc">Informasi umum yang tampil di halaman publik.</p>

        <p v-if="siteError" class="auth-error">{{ siteError }}</p>
        <p v-if="siteLoading" class="text-muted">Memuat pengaturan...</p>

        <form v-else @submit.prevent="saveSite">

          <div class="form-group">
            <label class="form-label">Nama Situs</label>
            <input v-model="site.site_name" type="text" class="form-control" />
          </div>

          <div class="form-group">
            <label class="form-label">Tagline</label>
            <input v-model="site.site_tagline" type="text" class="form-control" />
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <textarea v-model="site.site_description" class="form-control"></textarea>
          </div>

          <div class="settings-actions">
            <span v-if="siteSaved" class="settings-saved">✓ Tersimpan</span>
            <button type="submit" class="btn btn-primary" :disabled="siteSaving">
              {{ siteSaving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </button>
          </div>

        </form>
      </div>

      <!-- NOTIFIKASI -->
      <div class="card settings-section">
        <h2>Notifikasi</h2>
        <p class="text-muted settings-desc">Atur preferensi notifikasi kamu (disimpan di perangkat ini).</p>

        <div class="settings-toggle-list">

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">Laporan baru masuk</p>
              <p class="settings-toggle-sub">Dapat notifikasi tiap ada laporan baru dari warga</p>
            </div>
            <input type="checkbox" v-model="notifications.newReport" class="toggle-switch" @change="saveNotifPrefs" />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">Laporan selesai</p>
              <p class="settings-toggle-sub">Notifikasi saat status laporan berubah jadi selesai</p>
            </div>
            <input type="checkbox" v-model="notifications.reportResolved" class="toggle-switch" @change="saveNotifPrefs" />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">Ringkasan mingguan</p>
              <p class="settings-toggle-sub">Ringkasan aktivitas platform tiap minggu</p>
            </div>
            <input type="checkbox" v-model="notifications.weeklyDigest" class="toggle-switch" @change="saveNotifPrefs" />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">Pengguna baru daftar</p>
              <p class="settings-toggle-sub">Notifikasi saat ada warga baru mendaftar</p>
            </div>
            <input type="checkbox" v-model="notifications.newUser" class="toggle-switch" @change="saveNotifPrefs" />
          </label>

        </div>
      </div>

      <!-- KEAMANAN -->
      <div class="card settings-section">
        <h2>Keamanan</h2>
        <p class="text-muted settings-desc">Ubah kata sandi akun admin kamu.</p>

        <form @submit.prevent="changePassword">

          <div v-if="passwordError" class="auth-error">
            ⚠️ {{ passwordError }}
          </div>

          <div class="form-group">
            <label class="form-label">Kata Sandi Saat Ini</label>
            <input v-model="passwordForm.current_password" type="password" class="form-control" autocomplete="current-password" />
          </div>

          <div class="form-group">
            <label class="form-label">Kata Sandi Baru</label>
            <input v-model="passwordForm.password" type="password" class="form-control" placeholder="Minimal 6 karakter" autocomplete="new-password" />
          </div>

          <div class="form-group">
            <label class="form-label">Konfirmasi Kata Sandi Baru</label>
            <input v-model="passwordForm.password_confirmation" type="password" class="form-control" autocomplete="new-password" />
          </div>

          <div class="settings-actions">
            <span v-if="passwordSaved" class="settings-saved">✓ Kata sandi diperbarui</span>
            <button type="submit" class="btn btn-primary" :disabled="passwordSaving">
              {{ passwordSaving ? 'Memperbarui...' : 'Ubah Kata Sandi' }}
            </button>
          </div>

        </form>
      </div>

    </div>

  </AdminLayout>
</template>