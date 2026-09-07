<script setup>
import { ref } from 'vue'
import AdminLayout from '../../components/AdminLayout.vue'

// Profil Admin
const profile = ref({
  name: 'Admin',
  email: 'admin@suarawarga.id',
})
const profileSaving = ref(false)
const profileSaved = ref(false)

// Pengaturan Situs
const site = ref({
  name: 'SUARAWARGA',
  tagline: 'Suara masyarakat, perubahan nyata.',
  description: 'Platform pelaporan masalah lingkungan dan fasilitas umum untuk warga.',
})
const siteSaving = ref(false)
const siteSaved = ref(false)

// Notifikasi
const notifications = ref({
  newReport: true,
  reportResolved: true,
  weeklyDigest: false,
  newUser: true,
})

// Keamanan
const passwordForm = ref({
  current: '',
  new: '',
  confirm: '',
})
const passwordError = ref('')
const passwordSaving = ref(false)
const passwordSaved = ref(false)

function saveProfile() {
  profileSaving.value = true
  profileSaved.value = false
  setTimeout(() => {
    profileSaving.value = false
    profileSaved.value = true
    setTimeout(() => (profileSaved.value = false), 2500)
  }, 600)
}

function saveSite() {
  siteSaving.value = true
  siteSaved.value = false
  setTimeout(() => {
    siteSaving.value = false
    siteSaved.value = true
    setTimeout(() => (siteSaved.value = false), 2500)
  }, 600)
}

function changePassword() {
  passwordError.value = ''

  if (!passwordForm.value.current || !passwordForm.value.new || !passwordForm.value.confirm) {
    passwordError.value = 'Semua kolom wajib diisi.'
    return
  }

  if (passwordForm.value.new.length < 8) {
    passwordError.value = 'Kata sandi baru minimal 8 karakter.'
    return
  }

  if (passwordForm.value.new !== passwordForm.value.confirm) {
    passwordError.value = 'Konfirmasi kata sandi tidak cocok.'
    return
  }

  passwordSaving.value = true
  setTimeout(() => {
    passwordSaving.value = false
    passwordSaved.value = true
    passwordForm.value = { current: '', new: '', confirm: '' }
    setTimeout(() => (passwordSaved.value = false), 2500)
  }, 600)
}
</script>

<template>
  <AdminLayout page-title="Pengaturan" page-description="Kelola profil admin, situs, dan preferensi lainnya">

    <div class="settings-grid">

      <!-- PROFIL ADMIN -->
      <div class="card settings-section">
        <h2>Profil Admin</h2>
        <p class="text-muted settings-desc">Informasi dasar akun admin kamu.</p>

        <form @submit.prevent="saveProfile">

          <div class="settings-avatar-row">
            <div class="admin-avatar settings-avatar">
              {{ profile.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="admin-table-title">{{ profile.name }}</p>
              <p class="admin-table-sub">Super Admin</p>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Nama</label>
            <input v-model="profile.name" type="text" class="form-control" />
          </div>

          <div class="form-group">
            <label class="form-label">Email</label>
            <input v-model="profile.email" type="email" class="form-control" />
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

        <form @submit.prevent="saveSite">

          <div class="form-group">
            <label class="form-label">Nama Situs</label>
            <input v-model="site.name" type="text" class="form-control" />
          </div>

          <div class="form-group">
            <label class="form-label">Tagline</label>
            <input v-model="site.tagline" type="text" class="form-control" />
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <textarea v-model="site.description" class="form-control"></textarea>
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
        <p class="text-muted settings-desc">Atur notifikasi email yang ingin kamu terima.</p>

        <div class="settings-toggle-list">

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">Laporan baru masuk</p>
              <p class="settings-toggle-sub">Dapat email tiap ada laporan baru dari warga</p>
            </div>
            <input type="checkbox" v-model="notifications.newReport" class="toggle-switch" />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">Laporan selesai</p>
              <p class="settings-toggle-sub">Notifikasi saat status laporan berubah jadi selesai</p>
            </div>
            <input type="checkbox" v-model="notifications.reportResolved" class="toggle-switch" />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">Ringkasan mingguan</p>
              <p class="settings-toggle-sub">Ringkasan aktivitas platform tiap minggu</p>
            </div>
            <input type="checkbox" v-model="notifications.weeklyDigest" class="toggle-switch" />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">Pengguna baru daftar</p>
              <p class="settings-toggle-sub">Notifikasi saat ada warga baru mendaftar</p>
            </div>
            <input type="checkbox" v-model="notifications.newUser" class="toggle-switch" />
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
            <input v-model="passwordForm.current" type="password" class="form-control" autocomplete="current-password" />
          </div>

          <div class="form-group">
            <label class="form-label">Kata Sandi Baru</label>
            <input v-model="passwordForm.new" type="password" class="form-control" placeholder="Minimal 8 karakter" autocomplete="new-password" />
          </div>

          <div class="form-group">
            <label class="form-label">Konfirmasi Kata Sandi Baru</label>
            <input v-model="passwordForm.confirm" type="password" class="form-control" autocomplete="new-password" />
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