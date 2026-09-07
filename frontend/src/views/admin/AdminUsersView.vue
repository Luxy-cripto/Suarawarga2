<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import AdminLayout from '../../components/AdminLayout.vue'

const users = ref([])
const loading = ref(true)
const search = ref('')
const filterRole = ref('')

const totalWarga = computed(() => users.value.filter(u => u.role === 'warga').length)
const totalAdmin = computed(() => users.value.filter(u => u.role === 'admin').length)

const filteredUsers = computed(() => {
  return users.value.filter((u) => {
    const matchSearch =
      u.name.toLowerCase().includes(search.value.toLowerCase()) ||
      u.email.toLowerCase().includes(search.value.toLowerCase())
    const matchRole = filterRole.value ? u.role === filterRole.value : true
    return matchSearch && matchRole
  })
})

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

async function fetchUsers() {
  loading.value = true
  try {
    const res = await api.get('/users')
    users.value = res.data
  } catch (err) {
    users.value = []
  } finally {
    loading.value = false
  }
}

async function toggleStatus(user) {
  try {
    const res = await api.put(`/users/${user.id}/toggle-status`)
    user.is_active = res.data.is_active
  } catch (err) {
    console.error('Gagal ubah status', err)
  }
}

async function deleteUser(user) {
  if (!confirm(`Yakin mau hapus user "${user.name}"?`)) return
  try {
    await api.delete(`/users/${user.id}`)
    users.value = users.value.filter((u) => u.id !== user.id)
  } catch (err) {
    console.error('Gagal hapus user', err)
  }
}

onMounted(fetchUsers)
</script>

<template>
  <AdminLayout page-title="Kelola Pengguna" page-description="Lihat dan kelola semua warga & admin">

    <div class="admin-stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div>
          <div class="stat-number">{{ totalWarga }}</div>
          <div class="stat-label">Warga</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon processing">🛡️</div>
        <div>
          <div class="stat-number">{{ totalAdmin }}</div>
          <div class="stat-label">Admin</div>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="card admin-filter-bar">
      <input
        v-model="search"
        type="text"
        class="form-control admin-search"
        placeholder="Cari nama atau email..."
      />
      <select v-model="filterRole" class="filter-select">
        <option value="">Semua Role</option>
        <option value="warga">Warga</option>
        <option value="admin">Admin</option>
      </select>
    </div>

    <p v-if="loading" class="text-muted" style="padding: 20px;">Memuat data...</p>

    <div v-else class="card admin-table-card">
      <div class="admin-table-header">
        <h2>Daftar Pengguna ({{ filteredUsers.length }})</h2>
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
            <tr v-for="user in filteredUsers" :key="user.id">
              <td>
                <div class="admin-user-cell">
                  <div class="admin-user-avatar">{{ user.name.charAt(0).toUpperCase() }}</div>
                  <div>
                    <div class="admin-table-title">{{ user.name }}</div>
                    <div class="admin-table-sub">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="role-badge" :class="user.role === 'admin' ? 'role-admin' : 'role-warga'">
                  {{ user.role === 'admin' ? '🛡️ Admin' : '👤 Warga' }}
                </span>
              </td>
              <td>{{ user.laporans_count ?? 0 }}</td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td>
                <button
                  class="status-toggle"
                  :class="user.is_active ? 'active' : 'inactive'"
                  @click="toggleStatus(user)"
                >
                  <span class="status-dot"></span>
                  {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                </button>
              </td>
              <td>
                <div class="admin-row-actions">
                  <button class="admin-delete-btn" @click="deleteUser(user)">🗑️</button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td colspan="6" class="text-center text-muted" style="padding: 30px;">
                Tidak ada pengguna ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </AdminLayout>
</template>