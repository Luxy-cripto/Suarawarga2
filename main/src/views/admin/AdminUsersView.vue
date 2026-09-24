<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import AdminLayout from '../../components/AdminLayout.vue'

const users = ref([])
const loading = ref(true)
const search = ref('')
const filterRole = ref('')

const showAddPetugas = ref(false)
const addPetugasForm = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})
const addPetugasSaving = ref(false)
const addPetugasError = ref('')

const totalWarga = computed(() =>
  users.value.filter(user => user.role === 'warga').length
)

const totalPetugas = computed(() =>
  users.value.filter(user => user.role === 'petugas').length
)

const totalAdmin = computed(() =>
  users.value.filter(user => user.role === 'admin').length
)

const filteredUsers = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  return users.value.filter(user => {
    const name = user.name?.toLowerCase() || ''
    const email = user.email?.toLowerCase() || ''

    const matchSearch =
      !keyword ||
      name.includes(keyword) ||
      email.includes(keyword)

    const matchRole =
      !filterRole.value ||
      user.role === filterRole.value

    return matchSearch && matchRole
  })
})

function formatDate(dateStr) {
  if (!dateStr) return '-'

  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

function roleLabel(role) {
  if (role === 'admin') return '🛡️ Admin'
  if (role === 'petugas') return '🔧 Petugas'
  return '👤 Warga'
}

function roleClass(role) {
  if (role === 'admin') return 'role-admin'
  if (role === 'petugas') return 'role-petugas'
  return 'role-warga'
}

function userInitial(name) {
  return name?.charAt(0)?.toUpperCase() || '?'
}

async function fetchUsers() {
  loading.value = true

  try {
    const response = await api.get('/users')
    users.value = response.data?.data ?? response.data ?? []
  } catch (err) {
    console.error('Gagal mengambil pengguna:', err)
    users.value = []
  } finally {
    loading.value = false
  }
}

async function toggleStatus(user) {
  try {
    const response = await api.put(
      `/users/${user.id}/toggle-status`
    )

    user.is_active =
      response.data?.is_active ??
      !user.is_active
  } catch (err) {
    console.error('Gagal ubah status:', err)

    alert(
      err.response?.data?.message ||
      'Gagal mengubah status pengguna.'
    )
  }
}

async function deleteUser(user) {
  const yakin = confirm(
    `Yakin mau hapus user "${user.name}"?`
  )

  if (!yakin) return

  try {
    await api.delete(`/users/${user.id}`)

    users.value = users.value.filter(
      item => item.id !== user.id
    )
  } catch (err) {
    console.error('Gagal hapus user:', err)

    alert(
      err.response?.data?.message ||
      'Gagal menghapus pengguna.'
    )
  }
}

function openAddPetugas() {
  addPetugasForm.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
  }

  addPetugasError.value = ''
  showAddPetugas.value = true
}

function closeAddPetugas() {
  if (addPetugasSaving.value) return

  showAddPetugas.value = false
}

async function submitAddPetugas() {
  addPetugasError.value = ''

  const name = addPetugasForm.value.name.trim()
  const email = addPetugasForm.value.email.trim()

  if (!name || !email) {
    addPetugasError.value =
      'Nama dan email wajib diisi.'
    return
  }

  if (addPetugasForm.value.password.length < 8) {
    addPetugasError.value =
      'Password minimal 8 karakter.'
    return
  }

  if (
    addPetugasForm.value.password !==
    addPetugasForm.value.password_confirmation
  ) {
    addPetugasError.value =
      'Konfirmasi password tidak cocok.'
    return
  }

  addPetugasSaving.value = true

  try {
    const response = await api.post(
      '/users/petugas',
      {
        name,
        email,
        password: addPetugasForm.value.password,
        password_confirmation:
          addPetugasForm.value.password_confirmation
      }
    )

    const newUser = response.data?.data

    if (newUser) {
      users.value.unshift({
        ...newUser,
        role: 'petugas',
        is_active: newUser.is_active ?? true,
        laporans_count:
          newUser.laporans_count ?? 0
      })
    } else {
      await fetchUsers()
    }

    showAddPetugas.value = false
  } catch (err) {
    console.error(
      'Gagal membuat akun petugas:',
      err
    )

    const errors = err.response?.data?.errors

    if (errors) {
      const firstError =
        Object.values(errors)[0]

      addPetugasError.value =
        Array.isArray(firstError)
          ? firstError[0]
          : String(firstError)
    } else {
      addPetugasError.value =
        err.response?.data?.message ||
        'Gagal membuat akun petugas.'
    }
  } finally {
    addPetugasSaving.value = false
  }
}

onMounted(fetchUsers)
</script>

<template>
  <AdminLayout
    page-title="Kelola Pengguna"
    page-description="Lihat dan kelola semua warga, petugas & admin"
  >

    <!-- STATISTIK -->
    <div class="admin-stats-grid">

      <div class="stat-card">
        <div class="stat-icon">👥</div>

        <div>
          <div class="stat-number">
            {{ totalWarga }}
          </div>

          <div class="stat-label">
            Warga
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon processing">
          🔧
        </div>

        <div>
          <div class="stat-number">
            {{ totalPetugas }}
          </div>

          <div class="stat-label">
            Petugas
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon processing">
          🛡️
        </div>

        <div>
          <div class="stat-number">
            {{ totalAdmin }}
          </div>

          <div class="stat-label">
            Admin
          </div>
        </div>
      </div>

    </div>

    <!-- FILTER -->
    <div class="card admin-filter-bar">

      <input
        v-model="search"
        type="text"
        class="form-control admin-search"
        placeholder="Cari nama atau email..."
      />

      <select
        v-model="filterRole"
        class="filter-select"
      >
        <option value="">
          Semua Role
        </option>

        <option value="warga">
          Warga
        </option>

        <option value="petugas">
          Petugas
        </option>

        <option value="admin">
          Admin
        </option>
      </select>

      <button
        type="button"
        class="btn btn-primary add-petugas-btn"
        @click="openAddPetugas"
      >
        + Tambah Petugas
      </button>

    </div>

    <!-- LOADING -->
    <div
      v-if="loading"
      class="users-loading"
    >
      <div class="users-spinner"></div>
      <p>Memuat data pengguna...</p>
    </div>

    <!-- TABLE -->
    <div
      v-else
      class="card admin-table-card"
    >

      <div class="admin-table-header">
        <div>
          <h2>
            Daftar Pengguna
          </h2>

          <p>
            {{ filteredUsers.length }} pengguna ditemukan
          </p>
        </div>
      </div>

      <div class="table-responsive">

        <table>

          <thead>
            <tr>
              <th>Pengguna</th>
              <th>Role</th>
              <th>Laporan</th>
              <th>Bergabung</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>

            <tr
              v-for="user in filteredUsers"
              :key="user.id"
            >

              <!-- PENGGUNA -->
              <td>

                <div class="admin-user-cell">

                  <div class="admin-user-avatar">
                    {{ userInitial(user.name) }}
                  </div>

                  <div class="user-main-info">

                    <div class="admin-table-title">
                      {{ user.name || 'Tanpa Nama' }}
                    </div>

                    <div class="admin-table-sub">
                      {{ user.email || '-' }}
                    </div>

                  </div>

                </div>

              </td>

              <!-- ROLE -->
              <td>

                <span
                  class="role-badge"
                  :class="roleClass(user.role)"
                >
                  {{ roleLabel(user.role) }}
                </span>

              </td>

              <!-- LAPORAN -->
              <td>
                {{ user.laporans_count ?? 0 }}
              </td>

              <!-- TANGGAL -->
              <td>
                {{ formatDate(user.created_at) }}
              </td>

              <!-- STATUS -->
              <td>

                <button
                  type="button"
                  class="status-toggle"
                  :class="
                    user.is_active
                      ? 'active'
                      : 'inactive'
                  "
                  @click="toggleStatus(user)"
                >

                  <span class="status-dot"></span>

                  {{
                    user.is_active
                      ? 'Aktif'
                      : 'Nonaktif'
                  }}

                </button>

              </td>

              <!-- AKSI -->
              <td>

                <div class="admin-row-actions">

                  <button
                    type="button"
                    class="admin-delete-btn"
                    title="Hapus pengguna"
                    @click="deleteUser(user)"
                  >
                    🗑️
                  </button>

                </div>

              </td>

            </tr>

            <tr
              v-if="filteredUsers.length === 0"
            >
              <td
                colspan="6"
                class="empty-users"
              >
                <div>
                  <span>👤</span>
                  <p>Tidak ada pengguna ditemukan.</p>
                </div>
              </td>
            </tr>

          </tbody>

        </table>

      </div>

    </div>

    <!-- MODAL TAMBAH PETUGAS -->
    <div
      v-if="showAddPetugas"
      class="modal-overlay"
      @click.self="closeAddPetugas"
    >

      <div class="modal-box">

        <div class="modal-header">

          <div>
            <h3>
              🔧 Tambah Akun Petugas
            </h3>

            <p>
              Buat akun petugas baru untuk menangani laporan warga.
            </p>
          </div>

          <button
            type="button"
            class="modal-close"
            :disabled="addPetugasSaving"
            @click="closeAddPetugas"
          >
            ✕
          </button>

        </div>

        <!-- ERROR -->
        <p
          v-if="addPetugasError"
          class="auth-error"
        >
          ⚠️ {{ addPetugasError }}
        </p>

        <form
          @submit.prevent="submitAddPetugas"
        >

          <!-- NAMA -->
          <div class="form-group">

            <label class="form-label">
              Nama
            </label>

            <input
              v-model="addPetugasForm.name"
              type="text"
              class="form-control"
              placeholder="Nama petugas"
              autocomplete="name"
              required
            />

          </div>

          <!-- EMAIL -->
          <div class="form-group">

            <label class="form-label">
              Email
            </label>

            <input
              v-model="addPetugasForm.email"
              type="email"
              class="form-control"
              placeholder="email@contoh.com"
              autocomplete="email"
              required
            />

          </div>

          <!-- PASSWORD -->
          <div class="form-group">

            <label class="form-label">
              Password
            </label>

            <input
              v-model="addPetugasForm.password"
              type="password"
              class="form-control"
              placeholder="Minimal 8 karakter"
              autocomplete="new-password"
              minlength="8"
              required
            />

          </div>

          <!-- KONFIRMASI -->
          <div class="form-group">

            <label class="form-label">
              Konfirmasi Password
            </label>

            <input
              v-model="addPetugasForm.password_confirmation"
              type="password"
              class="form-control"
              placeholder="Ulangi password"
              autocomplete="new-password"
              minlength="8"
              required
            />

          </div>

          <div class="modal-actions">

            <button
              type="button"
              class="btn btn-secondary"
              :disabled="addPetugasSaving"
              @click="closeAddPetugas"
            >
              Batal
            </button>

            <button
              type="submit"
              class="btn btn-primary"
              :disabled="addPetugasSaving"
            >
              {{
                addPetugasSaving
                  ? 'Menyimpan...'
                  : 'Simpan Petugas'
              }}
            </button>

          </div>

        </form>

      </div>

    </div>

  </AdminLayout>
</template>

<style scoped>
.role-petugas {
  background: #e0efff;
  color: #1769aa;
}

.add-petugas-btn {
  margin-left: auto;
  white-space: nowrap;
}

/* =====================================================
   LOADING
===================================================== */

.users-loading {
  min-height: 260px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #64748b;
}

.users-spinner {
  width: 34px;
  height: 34px;
  border: 3px solid #e2e8f0;
  border-top-color: #0d9d98;
  border-radius: 50%;
  animation: users-spin 0.8s linear infinite;
}

.users-loading p {
  margin: 0;
  font-size: 13px;
}

@keyframes users-spin {
  to {
    transform: rotate(360deg);
  }
}

/* =====================================================
   TABLE
===================================================== */

.user-main-info {
  min-width: 0;
}

.admin-table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.admin-table-header h2 {
  margin: 0;
  color: #0f172a;
  font-size: 16px;
}

.admin-table-header p {
  margin: 4px 0 0;
  color: #94a3b8;
  font-size: 11px;
}

.admin-user-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.admin-user-avatar {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #0d9d98);
  color: white;
  font-size: 13px;
  font-weight: 800;
}

.admin-table-title {
  color: #334155;
  font-size: 13px;
  font-weight: 700;
}

.admin-table-sub {
  max-width: 220px;
  margin-top: 3px;
  overflow: hidden;
  color: #94a3b8;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* =====================================================
   ROLE
===================================================== */

.role-petugas {
  background: #e0efff;
  color: #1769aa;
}

/* =====================================================
   EMPTY
===================================================== */

.empty-users {
  padding: 45px 20px !important;
  text-align: center;
}

.empty-users span {
  display: block;
  margin-bottom: 8px;
  font-size: 32px;
}

.empty-users p {
  margin: 0;
  color: #94a3b8;
  font-size: 13px;
}

/* =====================================================
   ACTION
===================================================== */

.admin-row-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.admin-delete-btn {
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #fee2e2;
  border-radius: 9px;
  background: #fff7f7;
  cursor: pointer;
  transition: 0.2s ease;
}

.admin-delete-btn:hover {
  background: #fee2e2;
  transform: translateY(-1px);
}

/* =====================================================
   MODAL
===================================================== */

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
}

.modal-box {
  width: 100%;
  max-width: 420px;
  max-height: calc(100vh - 40px);
  overflow-y: auto;
  padding: 24px;
  border-radius: 16px;
  background: var(--surface, #ffffff);
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.2);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 18px;
}

.modal-header h3 {
  margin: 0;
  color: var(--text, #172b4d);
  font-size: 17px;
}

.modal-header p {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 11px;
  line-height: 1.5;
}

.modal-close {
  width: 30px;
  height: 30px;
  flex-shrink: 0;
  border: none;
  border-radius: 8px;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
}

.modal-close:hover {
  background: #e2e8f0;
  color: #172b4d;
}

.modal-close:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* =====================================================
   ERROR
===================================================== */

.auth-error {
  margin: 0 0 15px;
  padding: 10px 12px;
  border: 1px solid #fecaca;
  border-radius: 9px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 12px;
  line-height: 1.5;
}

/* =====================================================
   FORM
===================================================== */

.form-group {
  margin-bottom: 14px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  color: #475569;
  font-size: 11px;
  font-weight: 700;
}

.modal-box .form-control {
  width: 100%;
}

/* =====================================================
   MODAL ACTION
===================================================== */

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 700px) {
  .admin-filter-bar {
    flex-wrap: wrap;
  }

  .admin-search {
    width: 100%;
  }

  .add-petugas-btn {
    margin-left: 0;
  }

  .modal-box {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .admin-filter-bar {
    align-items: stretch;
  }

  .filter-select,
  .add-petugas-btn {
    width: 100%;
  }

  .modal-overlay {
    padding: 12px;
  }

  .modal-box {
    max-height: calc(100vh - 24px);
    padding: 18px;
    border-radius: 14px;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-actions .btn {
    width: 100%;
  }
}
</style>