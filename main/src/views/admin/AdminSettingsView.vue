<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '../../components/AdminLayout.vue'
import api from '../../services/api'
import { applySiteSettings } from '../../utils/siteSettings'

// ======================================================
// PROFIL ADMIN
// ======================================================

const profile = ref({
  name: '',
  email: ''
})

const profileSaving = ref(false)
const profileSaved = ref(false)
const profileError = ref('')

// ======================================================
// PENGATURAN SITUS
// ======================================================

const site = ref({
  site_name: '',
  site_tagline: '',
  site_description: '',
  site_logo: null
})

const siteLogoFile = ref(null)
const siteLogoPreview = ref('')

const siteLoading = ref(true)
const siteSaving = ref(false)
const siteSaved = ref(false)
const siteError = ref('')

// ======================================================
// NOTIFIKASI
// ======================================================

const notifications = ref({
  newReport: true,
  reportResolved: true,
  weeklyDigest: false,
  newUser: true
})

// ======================================================
// KEAMANAN
// ======================================================

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const passwordError = ref('')
const passwordSaving = ref(false)
const passwordSaved = ref(false)

// ======================================================
// FETCH PROFIL
// ======================================================

async function fetchProfile() {
  try {
    const res = await api.get('/profile')

    profile.value = {
      name: res.data.name || '',
      email: res.data.email || ''
    }

    sessionStorage.setItem(
      'user',
      JSON.stringify(res.data)
    )
  } catch (err) {
    // Abaikan error
  }
}

// ======================================================
// SIMPAN PROFIL
// ======================================================

async function saveProfile() {
  profileSaving.value = true
  profileSaved.value = false
  profileError.value = ''

  try {
    const res = await api.post(
      '/profile',
      profile.value
    )

    sessionStorage.setItem(
      'user',
      JSON.stringify(res.data)
    )

    window.dispatchEvent(
      new Event('user-updated')
    )

    profileSaved.value = true

    setTimeout(() => {
      profileSaved.value = false
    }, 2500)
  } catch (err) {
    profileError.value =
      err.response?.data?.message ||
      'Gagal menyimpan profil.'
  } finally {
    profileSaving.value = false
  }
}

// ======================================================
// FETCH PENGATURAN SITUS
// ======================================================

async function fetchSettings() {
  siteLoading.value = true
  siteError.value = ''

  try {
    const res = await api.get('/settings')

    site.value = {
      site_name:
        res.data.site_name ||
        'SUARAWARGA',

      site_tagline:
        res.data.site_tagline || '',

      site_description:
        res.data.site_description || '',

      site_logo:
        res.data.site_logo || null
    }

    siteLogoPreview.value =
      res.data.site_logo || ''

    // Terapkan pengaturan saat halaman dibuka
    applySiteSettings({
      site_name:
        res.data.site_name ||
        'SUARAWARGA',

      site_logo:
        res.data.site_logo || null
    })
  } catch (err) {
    siteError.value =
      err.response?.data?.message ||
      'Gagal memuat pengaturan situs.'
  } finally {
    siteLoading.value = false
  }
}

// ======================================================
// PILIH LOGO
// ======================================================

function handleLogoChange(event) {
  const file = event.target.files?.[0]

  if (!file) {
    return
  }

  const allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/svg+xml'
  ]

  if (!allowedTypes.includes(file.type)) {
    siteError.value =
      'Format logo harus JPG, PNG, WEBP, atau SVG.'

    event.target.value = ''
    siteLogoFile.value = null

    return
  }

  if (file.size > 2 * 1024 * 1024) {
    siteError.value =
      'Ukuran logo maksimal 2 MB.'

    event.target.value = ''
    siteLogoFile.value = null

    return
  }

  siteError.value = ''

  siteLogoFile.value = file

  // Preview logo sebelum disimpan
  siteLogoPreview.value =
    URL.createObjectURL(file)
}

// ======================================================
// BATAL PILIH LOGO
// ======================================================

function cancelLogoChange() {
  siteLogoFile.value = null

  siteLogoPreview.value =
    site.value.site_logo || ''

  const input =
    document.getElementById('site-logo')

  if (input) {
    input.value = ''
  }

  siteError.value = ''
}

// ======================================================
// SIMPAN PENGATURAN SITUS
// ======================================================

async function saveSite() {
  siteSaving.value = true
  siteSaved.value = false
  siteError.value = ''

  try {
    const formData = new FormData()

    formData.append(
      'site_name',
      site.value.site_name
    )

    formData.append(
      'site_tagline',
      site.value.site_tagline
    )

    formData.append(
      'site_description',
      site.value.site_description
    )

    if (siteLogoFile.value) {
      formData.append(
        'site_logo',
        siteLogoFile.value
      )
    }

    // Laravel method spoofing
    formData.append(
      '_method',
      'PUT'
    )

    const res = await api.post(
      '/settings',
      formData
    )

    // Logo terbaru dari backend
    const newLogo =
      res.data.site_logo ||
      site.value.site_logo ||
      null

    site.value.site_logo = newLogo
    siteLogoPreview.value = newLogo

    siteLogoFile.value = null

    const input =
      document.getElementById('site-logo')

    if (input) {
      input.value = ''
    }

    // ==================================================
    // TERAPKAN LANGSUNG DI TAB ADMIN
    // ==================================================

    const updatedSettings = {
      site_name:
        site.value.site_name ||
        'SUARAWARGA',

      site_logo:
        newLogo
    }

    applySiteSettings(
      updatedSettings
    )

    // ==================================================
    // KIRIM KE TAB LAIN
    // ==================================================

    localStorage.setItem(
      'site_settings',
      JSON.stringify(
        updatedSettings
      )
    )

    // Event untuk komponen di tab yang sama
    window.dispatchEvent(
      new Event('settings-updated')
    )

    siteSaved.value = true

    setTimeout(() => {
      siteSaved.value = false
    }, 2500)
  } catch (err) {
    siteError.value =
      err.response?.data?.errors?.site_logo?.[0] ||
      err.response?.data?.message ||
      'Gagal menyimpan pengaturan situs.'
  } finally {
    siteSaving.value = false
  }
}

// ======================================================
// NOTIFIKASI
// ======================================================

function loadNotifPrefs() {
  const saved =
    sessionStorage.getItem(
      'admin_notif_prefs'
    )

  if (!saved) {
    return
  }

  try {
    notifications.value =
      JSON.parse(saved)
  } catch (err) {
    // Abaikan data rusak
  }
}

function saveNotifPrefs() {
  sessionStorage.setItem(
    'admin_notif_prefs',
    JSON.stringify(
      notifications.value
    )
  )
}

// ======================================================
// GANTI PASSWORD
// ======================================================

async function changePassword() {
  passwordError.value = ''

  if (
    !passwordForm.value.current_password ||
    !passwordForm.value.password ||
    !passwordForm.value.password_confirmation
  ) {
    passwordError.value =
      'Semua kolom wajib diisi.'

    return
  }

  if (
    passwordForm.value.password.length < 6
  ) {
    passwordError.value =
      'Kata sandi baru minimal 6 karakter.'

    return
  }

  if (
    passwordForm.value.password !==
    passwordForm.value.password_confirmation
  ) {
    passwordError.value =
      'Konfirmasi kata sandi tidak cocok.'

    return
  }

  passwordSaving.value = true

  try {
    await api.post('/profile', {
      name:
        profile.value.name,

      email:
        profile.value.email,

      current_password:
        passwordForm.value.current_password,

      password:
        passwordForm.value.password,

      password_confirmation:
        passwordForm.value.password_confirmation
    })

    passwordSaved.value = true

    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: ''
    }

    setTimeout(() => {
      passwordSaved.value = false
    }, 2500)
  } catch (err) {
    passwordError.value =
      err.response?.data?.errors
        ?.current_password?.[0] ||
      err.response?.data?.message ||
      'Gagal mengubah kata sandi.'
  } finally {
    passwordSaving.value = false
  }
}

// ======================================================
// LOAD DATA
// ======================================================

onMounted(() => {
  fetchProfile()
  fetchSettings()
  loadNotifPrefs()
})
</script>

<template>
  <AdminLayout
    page-title="Pengaturan"
    page-description="Kelola profil admin, situs, dan preferensi lainnya"
  >
    <div class="settings-grid">

      <!-- ============================================
           PROFIL ADMIN
      ============================================= -->

      <div class="card settings-section">
        <h2>Profil Admin</h2>

        <p class="text-muted settings-desc">
          Informasi dasar akun admin kamu.
        </p>

        <p
          v-if="profileError"
          class="auth-error"
        >
          {{ profileError }}
        </p>

        <form @submit.prevent="saveProfile">

          <div class="settings-avatar-row">
            <div class="admin-avatar settings-avatar">
              {{
                profile.name
                  ?.charAt(0)
                  .toUpperCase()
              }}
            </div>

            <div>
              <p class="admin-table-title">
                {{ profile.name }}
              </p>

              <p class="admin-table-sub">
                Super Admin
              </p>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">
              Nama
            </label>

            <input
              v-model="profile.name"
              type="text"
              class="form-control"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">
              Email
            </label>

            <input
              v-model="profile.email"
              type="email"
              class="form-control"
              required
            />
          </div>

          <div class="settings-actions">
            <span
              v-if="profileSaved"
              class="settings-saved"
            >
              ✓ Tersimpan
            </span>

            <button
              type="submit"
              class="btn btn-primary"
              :disabled="profileSaving"
            >
              {{
                profileSaving
                  ? 'Menyimpan...'
                  : 'Simpan Profil'
              }}
            </button>
          </div>
        </form>
      </div>

      <!-- ============================================
           PENGATURAN SITUS + LOGO
      ============================================= -->

      <div class="card settings-section">
        <h2>Pengaturan Situs</h2>

        <p class="text-muted settings-desc">
          Informasi umum yang tampil di halaman publik.
        </p>

        <p
          v-if="siteError"
          class="auth-error"
        >
          {{ siteError }}
        </p>

        <p
          v-if="siteLoading"
          class="text-muted"
        >
          Memuat pengaturan...
        </p>

        <form
          v-else
          @submit.prevent="saveSite"
        >

          <!-- LOGO -->

          <div class="form-group">
            <label class="form-label">
              Logo Situs
            </label>

            <div class="logo-settings">

              <div class="logo-preview">
                <img
                  v-if="siteLogoPreview"
                  :src="siteLogoPreview"
                  alt="Logo SUARAWARGA"
                />

                <div
                  v-else
                  class="logo-placeholder"
                >
                  📢
                </div>
              </div>

              <div class="logo-upload-info">

                <p class="logo-upload-title">
                  Logo SUARAWARGA
                </p>

                <p class="text-muted logo-upload-desc">
                  Gunakan gambar JPG, PNG, WEBP,
                  atau SVG. Maksimal 2 MB.
                </p>

                <div class="logo-upload-actions">

                  <label
                    for="site-logo"
                    class="btn btn-secondary"
                  >
                    Pilih Logo
                  </label>

                  <button
                    v-if="siteLogoFile"
                    type="button"
                    class="btn btn-danger-outline"
                    @click="cancelLogoChange"
                  >
                    Batal
                  </button>

                </div>

                <input
                  id="site-logo"
                  type="file"
                  accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml"
                  class="logo-file-input"
                  @change="handleLogoChange"
                />

                <p
                  v-if="siteLogoFile"
                  class="logo-selected"
                >
                  ✓ {{ siteLogoFile.name }}
                </p>

              </div>
            </div>
          </div>

          <!-- NAMA SITUS -->

          <div class="form-group">
            <label class="form-label">
              Nama Situs
            </label>

            <input
              v-model="site.site_name"
              type="text"
              class="form-control"
              maxlength="100"
              required
            />
          </div>

          <!-- TAGLINE -->

          <div class="form-group">
            <label class="form-label">
              Tagline
            </label>

            <input
              v-model="site.site_tagline"
              type="text"
              class="form-control"
              maxlength="255"
            />
          </div>

          <!-- DESKRIPSI -->

          <div class="form-group">
            <label class="form-label">
              Deskripsi
            </label>

            <textarea
              v-model="site.site_description"
              class="form-control"
              maxlength="1000"
              rows="4"
            ></textarea>
          </div>

          <div class="settings-actions">

            <span
              v-if="siteSaved"
              class="settings-saved"
            >
              ✓ Pengaturan tersimpan
            </span>

            <button
              type="submit"
              class="btn btn-primary"
              :disabled="siteSaving"
            >
              {{
                siteSaving
                  ? 'Menyimpan...'
                  : 'Simpan Pengaturan'
              }}
            </button>

          </div>
        </form>
      </div>

      <!-- ============================================
           NOTIFIKASI
      ============================================= -->

      <div class="card settings-section">
        <h2>Notifikasi</h2>

        <p class="text-muted settings-desc">
          Atur preferensi notifikasi kamu
          (disimpan di perangkat ini).
        </p>

        <div class="settings-toggle-list">

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">
                Laporan baru masuk
              </p>

              <p class="settings-toggle-sub">
                Dapat notifikasi tiap ada laporan baru dari warga
              </p>
            </div>

            <input
              v-model="notifications.newReport"
              type="checkbox"
              class="toggle-switch"
              @change="saveNotifPrefs"
            />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">
                Laporan selesai
              </p>

              <p class="settings-toggle-sub">
                Notifikasi saat status laporan berubah jadi selesai
              </p>
            </div>

            <input
              v-model="notifications.reportResolved"
              type="checkbox"
              class="toggle-switch"
              @change="saveNotifPrefs"
            />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">
                Ringkasan mingguan
              </p>

              <p class="settings-toggle-sub">
                Ringkasan aktivitas platform tiap minggu
              </p>
            </div>

            <input
              v-model="notifications.weeklyDigest"
              type="checkbox"
              class="toggle-switch"
              @change="saveNotifPrefs"
            />
          </label>

          <label class="settings-toggle-row">
            <div>
              <p class="settings-toggle-title">
                Pengguna baru daftar
              </p>

              <p class="settings-toggle-sub">
                Notifikasi saat ada warga baru mendaftar
              </p>
            </div>

            <input
              v-model="notifications.newUser"
              type="checkbox"
              class="toggle-switch"
              @change="saveNotifPrefs"
            />
          </label>

        </div>
      </div>

      <!-- ============================================
           KEAMANAN
      ============================================= -->

      <div class="card settings-section">
        <h2>Keamanan</h2>

        <p class="text-muted settings-desc">
          Ubah kata sandi akun admin kamu.
        </p>

        <form @submit.prevent="changePassword">

          <div
            v-if="passwordError"
            class="auth-error"
          >
            ⚠️ {{ passwordError }}
          </div>

          <div class="form-group">
            <label class="form-label">
              Kata Sandi Saat Ini
            </label>

            <input
              v-model="passwordForm.current_password"
              type="password"
              class="form-control"
              autocomplete="current-password"
            />
          </div>

          <div class="form-group">
            <label class="form-label">
              Kata Sandi Baru
            </label>

            <input
              v-model="passwordForm.password"
              type="password"
              class="form-control"
              placeholder="Minimal 6 karakter"
              autocomplete="new-password"
            />
          </div>

          <div class="form-group">
            <label class="form-label">
              Konfirmasi Kata Sandi Baru
            </label>

            <input
              v-model="passwordForm.password_confirmation"
              type="password"
              class="form-control"
              autocomplete="new-password"
            />
          </div>

          <div class="settings-actions">

            <span
              v-if="passwordSaved"
              class="settings-saved"
            >
              ✓ Kata sandi diperbarui
            </span>

            <button
              type="submit"
              class="btn btn-primary"
              :disabled="passwordSaving"
            >
              {{
                passwordSaving
                  ? 'Memperbarui...'
                  : 'Ubah Kata Sandi'
              }}
            </button>

          </div>

        </form>
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
.logo-settings {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
}

.logo-preview {
  width: 96px;
  height: 96px;
  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  overflow: hidden;

  border: 1px solid #e2e8f0;
  border-radius: 14px;

  background: #ffffff;
}

.logo-preview img {
  width: 100%;
  height: 100%;

  object-fit: contain;

  padding: 10px;
}

.logo-placeholder {
  font-size: 38px;
}

.logo-upload-info {
  flex: 1;
}

.logo-upload-title {
  margin: 0 0 5px;

  font-weight: 700;
  color: #0f172a;
}

.logo-upload-desc {
  margin: 0 0 12px;

  font-size: 13px;
  line-height: 1.5;
}

.logo-upload-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo-file-input {
  display: none;
}

.logo-selected {
  margin: 10px 0 0;

  font-size: 13px;
  font-weight: 600;

  color: #0d9488;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  padding: 9px 14px;

  border: 1px solid #cbd5e1;
  border-radius: 8px;

  background: #ffffff;
  color: #334155;

  font-size: 14px;
  font-weight: 600;

  cursor: pointer;

  transition: 0.2s ease;
}

.btn-secondary:hover {
  background: #f1f5f9;
}

.btn-danger-outline {
  padding: 9px 14px;

  border: 1px solid #fecaca;
  border-radius: 8px;

  background: #ffffff;
  color: #dc2626;

  font-size: 14px;
  font-weight: 600;

  cursor: pointer;

  transition: 0.2s ease;
}

.btn-danger-outline:hover {
  background: #fef2f2;
}

@media (max-width: 640px) {
  .logo-settings {
    align-items: flex-start;
    flex-direction: column;
  }

  .logo-upload-info {
    width: 100%;
  }
}
</style>