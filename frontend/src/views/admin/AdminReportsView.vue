<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '../../components/AdminLayout.vue'
import api from '../../services/api'

const router = useRouter()

const search = ref('')
const activeStatus = ref('semua')
const selectedIds = ref([])
const reports = ref([])
const loading = ref(false)
const error = ref('')

const statusOptions = [
  { key: 'semua', label: 'Semua Status' },
  { key: 'baru', label: 'Menunggu' },
  { key: 'diproses', label: 'Diproses' },
  { key: 'selesai', label: 'Selesai' },
  { key: 'ditolak', label: 'Ditolak' }
]

const categoryMap = {
  jalan_rusak: { emoji: '🚧', label: 'Jalan' },
  sampah: { emoji: '🗑️', label: 'Sampah' },
  lampu_mati: { emoji: '💡', label: 'Lampu' },
  selokan: { emoji: '🌊', label: 'Selokan' },
  fasilitas: { emoji: '🏞️', label: 'Fasilitas' },
  lainnya: { emoji: '📋', label: 'Lainnya' }
}

const filteredReports = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  return reports.value.filter((report) => {
    const matchSearch =
      !keyword ||
      report.judul?.toLowerCase().includes(keyword) ||
      report.deskripsi?.toLowerCase().includes(keyword) ||
      report.lokasi?.toLowerCase().includes(keyword) ||
      report.user?.name?.toLowerCase().includes(keyword)

    const matchStatus =
      activeStatus.value === 'semua' ||
      report.status === activeStatus.value

    return matchSearch && matchStatus
  })
})

const allSelected = computed(() => {
  return (
    filteredReports.value.length > 0 &&
    filteredReports.value.every((report) =>
      selectedIds.value.includes(report.id)
    )
  )
})

async function fetchReports() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/laporans')
    reports.value = Array.isArray(response.data)
      ? response.data
      : response.data.data || []
  } catch (err) {
    console.error('ERROR LAPORAN:', err)

    error.value =
      err.response?.data?.message ||
      'Gagal mengambil data laporan.'
  } finally {
    loading.value = false
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIds.value = selectedIds.value.filter(
      (id) =>
        !filteredReports.value.some(
          (report) => report.id === id
        )
    )
  } else {
    const ids = filteredReports.value.map(
      (report) => report.id
    )

    selectedIds.value = [
      ...new Set([
        ...selectedIds.value,
        ...ids
      ])
    ]
  }
}

function toggleSelect(id) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter(
      (item) => item !== id
    )
  } else {
    selectedIds.value.push(id)
  }
}

async function updateStatus(report, newStatus) {
  if (!newStatus || report.status === newStatus) {
    return
  }

  const oldStatus = report.status

  try {
    await api.put(`/laporans/${report.id}`, {
      status: newStatus
    })

    report.status = newStatus
  } catch (err) {
    console.error('ERROR UPDATE STATUS:', err)

    report.status = oldStatus

    alert(
      err.response?.data?.message ||
      'Gagal mengubah status laporan.'
    )
  }
}

async function bulkUpdateStatus(status) {
  if (!status || selectedIds.value.length === 0) {
    return
  }

  const total = selectedIds.value.length

  const yakin = confirm(
    `Ubah status ${total} laporan menjadi "${statusLabel(status)}"?`
  )

  if (!yakin) {
    return
  }

  try {
    for (const id of selectedIds.value) {
      await api.put(`/laporans/${id}`, {
        status
      })
    }

    reports.value = reports.value.map((report) => {
      if (selectedIds.value.includes(report.id)) {
        return {
          ...report,
          status
        }
      }

      return report
    })

    selectedIds.value = []
  } catch (err) {
    console.error('ERROR BULK STATUS:', err)

    alert(
      err.response?.data?.message ||
      'Gagal mengubah status laporan.'
    )

    await fetchReports()
  }
}

async function deleteReport(id) {
  const report = reports.value.find(
    (item) => item.id === id
  )

  if (!report) {
    return
  }

  const yakin = confirm(
    `Hapus laporan "${report.judul}"?`
  )

  if (!yakin) {
    return
  }

  try {
    await api.delete(`/laporans/${id}`)

    reports.value = reports.value.filter(
      (item) => item.id !== id
    )

    selectedIds.value = selectedIds.value.filter(
      (item) => item !== id
    )
  } catch (err) {
    console.error('ERROR DELETE:', err)

    alert(
      err.response?.data?.message ||
      'Gagal menghapus laporan.'
    )
  }
}

async function bulkDelete() {
  if (selectedIds.value.length === 0) {
    return
  }

  const total = selectedIds.value.length

  const yakin = confirm(
    `Hapus ${total} laporan terpilih?`
  )

  if (!yakin) {
    return
  }

  try {
    for (const id of selectedIds.value) {
      await api.delete(`/laporans/${id}`)
    }

    reports.value = reports.value.filter(
      (report) =>
        !selectedIds.value.includes(report.id)
    )

    selectedIds.value = []
  } catch (err) {
    console.error('ERROR BULK DELETE:', err)

    alert(
      err.response?.data?.message ||
      'Gagal menghapus laporan.'
    )

    await fetchReports()
  }
}

function openDetail(id) {
  router.push(`/admin/reports/${id}`)
}

function categoryEmoji(kategori) {
  return categoryMap[kategori]?.emoji || '📋'
}

function categoryLabel(kategori) {
  return categoryMap[kategori]?.label || kategori || 'Lainnya'
}

function statusLabel(status) {
  const map = {
    baru: 'Menunggu',
    diproses: 'Diproses',
    selesai: 'Selesai',
    ditolak: 'Ditolak'
  }

  return map[status] || status || '-'
}

function statusClass(status) {
  const map = {
    baru: 'status-waiting',
    diproses: 'status-processing',
    selesai: 'status-success',
    ditolak: 'status-rejected'
  }

  return map[status] || 'status-waiting'
}

function formatDate(date) {
  if (!date) {
    return '-'
  }

  return new Date(date).toLocaleDateString(
    'id-ID',
    {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }
  )
}

function formatRelativeTime(date) {
  if (!date) {
    return '-'
  }

  const now = new Date()
  const created = new Date(date)
  const diff = now - created

  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (minutes < 1) {
    return 'Baru saja'
  }

  if (minutes < 60) {
    return `${minutes} menit lalu`
  }

  if (hours < 24) {
    return `${hours} jam lalu`
  }

  if (days === 1) {
    return 'Kemarin'
  }

  if (days < 7) {
    return `${days} hari lalu`
  }

  return formatDate(date)
}

onMounted(fetchReports)
</script>

<template>
  <AdminLayout
    page-title="Kelola Laporan"
    page-description="Tinjau, ubah status, dan kelola laporan warga"
  >
    <div class="card admin-filter-bar">
      <div class="search-box admin-search">
        <span class="search-icon">🔎</span>

        <input
          v-model="search"
          type="text"
          placeholder="Cari laporan..."
        />
      </div>

      <select
        v-model="activeStatus"
        class="filter-select"
      >
        <option
          v-for="status in statusOptions"
          :key="status.key"
          :value="status.key"
        >
          {{ status.label }}
        </option>
      </select>
    </div>

    <div
      v-if="error"
      class="card"
      style="padding: 20px; margin-bottom: 16px; color: #dc2626;"
    >
      ⚠️ {{ error }}

      <button
        class="btn btn-secondary"
        style="margin-left: 15px;"
        @click="fetchReports"
      >
        🔄 Coba Lagi
      </button>
    </div>

    <transition name="fade-in">
      <div
        v-if="selectedIds.length"
        class="bulk-bar"
      >
        <span>
          {{ selectedIds.length }} laporan dipilih
        </span>

        <div class="bulk-bar-actions">
          <select
            class="filter-select"
            @change="bulkUpdateStatus($event.target.value)"
          >
            <option value="" disabled selected>
              Ubah status ke...
            </option>

            <option
              v-for="status in statusOptions.filter(
                (item) => item.key !== 'semua'
              )"
              :key="status.key"
              :value="status.key"
            >
              {{ status.label }}
            </option>
          </select>

          <button
            class="btn btn-danger"
            @click="bulkDelete"
          >
            🗑️ Hapus
          </button>
        </div>
      </div>
    </transition>

    <div class="card admin-table-card">
      <div
        v-if="loading"
        style="padding: 60px; text-align: center; color: var(--text-muted);"
      >
        Memuat data laporan...
      </div>

      <div
        v-else
        class="table-responsive"
      >
        <table>
          <thead>
            <tr>
              <th class="admin-checkbox-cell">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                />
              </th>

              <th>Laporan</th>
              <th>Pelapor</th>
              <th>Kategori</th>
              <th>Waktu</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="report in filteredReports"
              :key="report.id"
            >
              <td class="admin-checkbox-cell">
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(report.id)"
                  @change="toggleSelect(report.id)"
                />
              </td>

              <td>
                <p class="admin-table-title">
                  {{ report.judul }}
                </p>

                <p class="admin-table-sub">
                  📍
                  {{ report.lokasi || 'Lokasi tidak tersedia' }}
                </p>
              </td>

              <td>
                {{ report.user?.name || 'Warga' }}
              </td>

              <td>
                {{ categoryEmoji(report.kategori) }}
                {{ categoryLabel(report.kategori) }}
              </td>

              <td>
                {{ formatRelativeTime(report.created_at) }}
              </td>

              <td>
                <select
                  :value="report.status"
                  class="admin-status-select"
                  :class="statusClass(report.status)"
                  @change="updateStatus(
                    report,
                    $event.target.value
                  )"
                >
                  <option
                    v-for="status in statusOptions.filter(
                      (item) => item.key !== 'semua'
                    )"
                    :key="status.key"
                    :value="status.key"
                  >
                    {{ status.label }}
                  </option>
                </select>
              </td>

              <td>
                <div class="admin-row-actions">
                  <button
                    class="report-detail"
                    @click="openDetail(report.id)"
                  >
                    Detail
                  </button>

                  <button
                    class="admin-delete-btn"
                    title="Hapus laporan"
                    @click="deleteReport(report.id)"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="!filteredReports.length"
          class="empty-state"
        >
          <div class="empty-icon">🔍</div>

          <h3>Tidak ada laporan</h3>

          <p>
            {{
              search || activeStatus !== 'semua'
                ? 'Coba ubah kata kunci atau filter status.'
                : 'Belum ada laporan dari warga.'
            }}
          </p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>