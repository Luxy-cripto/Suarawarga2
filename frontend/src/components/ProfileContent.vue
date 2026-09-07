<template>
  <div class="profile-grid">
    <!-- KARTU PROFIL -->
    <div class="card profile-hero">
      <img
        v-if="user?.foto_url"
        :src="user.foto_url"
        class="profile-hero-avatar"
        style="object-fit: cover;"
      />
      <div v-else class="profile-hero-avatar">{{ user?.name?.charAt(0).toUpperCase() }}</div>

      <h2>{{ user?.name }}</h2>
      <span class="role-badge" :class="user?.role === 'admin' ? 'role-admin' : 'role-warga'">
        {{ user?.role === 'admin' ? '🛡️ Super Admin' : '👤 Warga' }}
      </span>

      <div class="profile-hero-meta">
        <div class="profile-meta-row">
          <span class="profile-meta-label">✉️ Email</span>
          <span>{{ user?.email }}</span>
        </div>
        <div class="profile-meta-row">
          <span class="profile-meta-label">📅 Bergabung</span>
          <span>{{ formatDate(user?.created_at) }}</span>
        </div>
      </div>

      <button class="btn btn-primary profile-edit-btn" @click="startEdit">✏️ Edit Profil</button>
    </div>

    <!-- KANAN: STATS + FORM EDIT / AKTIVITAS -->
    <div class="profile-right">
      <div class="profile-stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📋</div>
          <div>
            <div class="stat-number">{{ user?.laporans_count ?? 0 }}</div>
            <div class="stat-label">{{ user?.role === 'admin' ? 'Laporan Dibuat' : 'Total Laporan Saya' }}</div>
          </div>
        </div>
      </div>

      <!-- Mode EDIT -->
      <div v-if="editing" class="card settings-section">
        <h2>Edit Profil</h2>
        <p class="text-muted settings-desc">Ubah foto, nama, email, atau password akun kamu.</p>

        <p v-if="error" class="auth-error">{{ error }}</p>
        <p v-if="success" style="color: var(--accent-dark); font-size: 13px; font-weight: 600; margin-bottom: 14px;">{{ success }}</p>

        <form @submit.prevent="handleSave">

          <!-- FOTO -->
          <div class="form-group">
            <label class="form-label">Foto Profil</label>
            <input type="file" accept="image/*" class="form-control" @change="handleFileChange" />
            <div v-if="previewUrl || user?.foto_url" style="margin-top: 10px;">
              <img :src="previewUrl || user?.foto_url" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;" />
            </div>
          </div>

          <!-- NAMA -->
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input v-model="form.name" type="text" class="form-control" required />
          </div>

          <!-- EMAIL -->
          <div class="form-group">
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" class="form-control" required />
          </div>

          <!-- DIVIDER GANTI PASSWORD -->
          <div style="border-top: 1px solid var(--border); margin: 24px 0 16px; padding-top: 16px;">
            <h3 style="font-size: 15px; margin-bottom: 4px;">🔒 Ganti Password</h3>
            <p class="text-muted" style="font-size: 13px; margin-bottom: 14px;">
              Kosongkan bagian ini kalau tidak ingin mengubah password.
            </p>
          </div>

          <!-- PASSWORD BARU -->
          <div class="form-group">
            <label class="form-label">Password Baru</label>
            <input v-model="form.password" type="password" class="form-control" placeholder="Minimal 6 karakter" />
          </div>

          <div class="form-group" v-if="form.password">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input v-model="form.password_confirmation" type="password" class="form-control" />
          </div>

          <!-- PASSWORD SAAT INI (wajib kalau mau ganti password) -->
          <div class="form-group" v-if="form.password">
            <label class="form-label">Password Saat Ini</label>
            <input v-model="form.current_password" type="password" class="form-control" placeholder="Masukkan password lama untuk konfirmasi" required />
            <p style="margin-top: 6px;">
              <RouterLink to="/forgot-password" class="auth-link-small">Lupa password lama? Reset lewat email →</RouterLink>
            </p>
          </div>

          <div class="settings-actions">
            <button type="button" class="btn btn-secondary" @click="editing = false">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving" class="auth-spinner"></span>
              <span v-else>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Mode LIHAT: info tambahan -->
      <div v-else class="card settings-section">
        <h2>Tentang Akun</h2>
        <p class="text-muted settings-desc">
          Klik "Edit Profil" di kartu sebelah kiri untuk mengubah foto, nama, email, atau password akun kamu.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
const editing = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const previewUrl = ref(null)
const fotoFile = ref(null)

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  current_password: '',
})

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

async function fetchProfile() {
  try {
    const res = await api.get('/profile')
    user.value = res.data
    localStorage.setItem('user', JSON.stringify(res.data))
  } catch (err) {
    // ignore
  }
}

function handleFileChange(e) {
  const file = e.target.files[0]
  fotoFile.value = file
  previewUrl.value = file ? URL.createObjectURL(file) : null
}

function startEdit() {
  form.value = {
    name: user.value?.name || '',
    email: user.value?.email || '',
    password: '',
    password_confirmation: '',
    current_password: '',
  }
  error.value = ''
  success.value = ''
  fotoFile.value = null
  previewUrl.value = null
  editing.value = true
}

async function handleSave() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const data = new FormData()
    data.append('name', form.value.name)
    data.append('email', form.value.email)
    if (form.value.password) {
      data.append('password', form.value.password)
      data.append('password_confirmation', form.value.password_confirmation)
      data.append('current_password', form.value.current_password)
    }
    if (fotoFile.value) {
      data.append('foto', fotoFile.value)
    }

    const res = await api.post('/profile', data)

    user.value = res.data
    localStorage.setItem('user', JSON.stringify(res.data))
    window.dispatchEvent(new Event('user-updated'))
    success.value = 'Profil berhasil diperbarui.'
    editing.value = false
    fotoFile.value = null
    previewUrl.value = null
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.errors?.current_password?.[0] || 'Gagal menyimpan perubahan.'
  } finally {
    saving.value = false
  }
}

onMounted(fetchProfile)
</script>