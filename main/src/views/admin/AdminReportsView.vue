<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import AdminLayout from '../../components/AdminLayout.vue'
import api from '../../services/api'

const router = useRouter()

// =====================================================
// STATE
// =====================================================

const search = ref('')
const activeStatus = ref('semua')
const exportStatus = ref('semua')

const selectedIds = ref([])
const reports = ref([])

const loading = ref(false)
const error = ref('')

const petugasList = ref([])

// =====================================================
// STATUS
// =====================================================

const statusOptions = [
  { key: 'semua', label: 'Semua Status' },
  { key: 'baru', label: 'Menunggu' },
  { key: 'diproses', label: 'Diproses' },
  { key: 'selesai', label: 'Selesai' },
  { key: 'ditolak', label: 'Ditolak' }
]

// =====================================================
// CATEGORY
// =====================================================

const categoryMap = {
  jalan_rusak: {
    emoji: '🚧',
    label: 'Jalan'
  },
  sampah: {
    emoji: '🗑️',
    label: 'Sampah'
  },
  lampu_mati: {
    emoji: '💡',
    label: 'Lampu'
  },
  selokan: {
    emoji: '🌊',
    label: 'Selokan'
  },
  fasilitas: {
    emoji: '🏞️',
    label: 'Fasilitas'
  },
  keamanan: {
    emoji: '🛡️',
    label: 'Keamanan'
  },
  kebersihan: {
    emoji: '🧹',
    label: 'Kebersihan'
  },
  lainnya: {
    emoji: '📋',
    label: 'Lainnya'
  }
}

// =====================================================
// COMPUTED
// =====================================================

const filteredReports = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  return reports.value.filter((report) => {
    const kategoriNama =
      report.kategori_relasi?.nama ||
      report.kategoriRelasi?.nama ||
      report.kategori ||
      ''

    const matchSearch =
      !keyword ||
      report.judul?.toLowerCase().includes(keyword) ||
      report.deskripsi?.toLowerCase().includes(keyword) ||
      report.lokasi?.toLowerCase().includes(keyword) ||
      report.user?.name?.toLowerCase().includes(keyword) ||
      kategoriNama.toLowerCase().includes(keyword)

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

// =====================================================
// FETCH DATA
// =====================================================

async function fetchReports() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/laporans')

    reports.value = Array.isArray(response.data)
      ? response.data
      : response.data?.data || []
  } catch (err) {
    console.error('ERROR LAPORAN:', err)

    error.value =
      err.response?.data?.message ||
      'Gagal mengambil data laporan.'
  } finally {
    loading.value = false
  }
}

async function fetchPetugas() {
  try {
    const response = await api.get('/users/petugas')

    petugasList.value = Array.isArray(response.data)
      ? response.data
      : response.data?.data || []
  } catch (err) {
    console.error('ERROR PETUGAS:', err)

    petugasList.value = []
  }
}

// =====================================================
// EXPORT DATA
// =====================================================

async function exportData() {
  try {
    const response = await api.get('/laporans/export', {
      params: {
        status: exportStatus.value
      },
      responseType: 'blob'
    })

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = url

    const namaStatus = {
      semua: 'semua',
      baru: 'menunggu',
      diproses: 'diproses',
      selesai: 'selesai',
      ditolak: 'ditolak'
    }

    link.download =
      `laporan-warga-${namaStatus[exportStatus.value] || 'semua'}.xlsx`

    document.body.appendChild(link)
    link.click()
    link.remove()

    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('ERROR EXPORT LAPORAN:', err)

    let message = 'Gagal mengexport data laporan.'

    if (err.response?.data instanceof Blob) {
      try {
        const text = await err.response.data.text()
        const data = JSON.parse(text)

        message = data.message || message
      } catch {
        // Response bukan JSON
      }
    } else {
      message = err.response?.data?.message || message
    }

    alert(message)
  }
}

// =====================================================
// SELECTION
// =====================================================

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIds.value = selectedIds.value.filter(
      (id) =>
        !filteredReports.value.some(
          (report) => report.id === id
        )
    )

    return
  }

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

function toggleSelect(id) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter(
      (item) => item !== id
    )
  } else {
    selectedIds.value.push(id)
  }
}

// =====================================================
// UPDATE STATUS
// =====================================================

async function updateStatus(report, newStatus) {
  if (!newStatus || report.status === newStatus) {
    return
  }

  const oldStatus = report.status
  const oldAlasan = report.alasan_ditolak || null

  let alasanDitolak = null

  if (newStatus === 'ditolak') {
    alasanDitolak = prompt(
      'Alasan laporan ini ditolak:',
      report.alasan_ditolak || ''
    )

    if (!alasanDitolak || !alasanDitolak.trim()) {
      alert('Alasan ditolak wajib diisi.')
      return
    }
  }

  try {
    const payload = {
      status: newStatus
    }

    if (alasanDitolak) {
      payload.alasan_ditolak = alasanDitolak.trim()
    }

    const response = await api.put(
      `/laporans/${report.id}`,
      payload
    )

    const updatedReport =
      response.data?.data || response.data

    report.status =
      updatedReport?.status || newStatus

    if (newStatus === 'ditolak') {
      report.alasan_ditolak =
        updatedReport?.alasan_ditolak ||
        alasanDitolak.trim()
    } else {
      report.alasan_ditolak = null
    }
  } catch (err) {
    console.error('ERROR UPDATE STATUS:', err)

    report.status = oldStatus
    report.alasan_ditolak = oldAlasan

    alert(
      err.response?.data?.message ||
      'Gagal mengubah status laporan.'
    )
  }
}

// =====================================================
// ASSIGN PETUGAS
// =====================================================

async function assignPetugas(report, petugasId) {
  if (!petugasId) {
    return
  }

  const oldPetugas = report.petugas
  const oldAssignedAt = report.assigned_at
  const oldDeadlineAt = report.deadline_at

  try {
    const response = await api.put(
      `/laporans/${report.id}/assign`,
      {
        petugas_id: petugasId
      }
    )

    const data =
      response.data?.data || response.data

    report.petugas =
      data?.petugas ||
      petugasList.value.find(
        (petugas) =>
          petugas.id === Number(petugasId)
      ) ||
      null

    if (data?.assigned_at) {
      report.assigned_at = data.assigned_at
    }

    if (data?.deadline_at) {
      report.deadline_at = data.deadline_at
    }
  } catch (err) {
    console.error(
      'ERROR ASSIGN PETUGAS:',
      err
    )

    report.petugas = oldPetugas
    report.assigned_at = oldAssignedAt
    report.deadline_at = oldDeadlineAt

    alert(
      err.response?.data?.message ||
      'Gagal menugaskan petugas.'
    )
  }
}

// =====================================================
// BULK UPDATE STATUS
// =====================================================

async function bulkUpdateStatus(status) {
  if (
    !status ||
    selectedIds.value.length === 0
  ) {
    return
  }

  const total = selectedIds.value.length

  let alasanDitolak = null

  if (status === 'ditolak') {
    alasanDitolak = prompt(
      `Alasan ${total} laporan ini ditolak:`,
      ''
    )

    if (
      !alasanDitolak ||
      !alasanDitolak.trim()
    ) {
      alert('Alasan ditolak wajib diisi.')
      return
    }
  }

  const yakin = confirm(
    `Ubah status ${total} laporan menjadi "${statusLabel(status)}"?`
  )

  if (!yakin) {
    return
  }

  try {
    for (const id of selectedIds.value) {
      const payload = {
        status
      }

      if (alasanDitolak) {
        payload.alasan_ditolak =
          alasanDitolak.trim()
      }

      await api.put(
        `/laporans/${id}`,
        payload
      )
    }

    reports.value = reports.value.map(
      (report) => {
        if (
          !selectedIds.value.includes(
            report.id
          )
        ) {
          return report
        }

        return {
          ...report,
          status,
          alasan_ditolak:
            status === 'ditolak'
              ? alasanDitolak.trim()
              : null
        }
      }
    )

    selectedIds.value = []
  } catch (err) {
    console.error(
      'ERROR BULK STATUS:',
      err
    )

    alert(
      err.response?.data?.message ||
      'Gagal mengubah status laporan.'
    )

    await fetchReports()
  }
}

// =====================================================
// DELETE
// =====================================================

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
    await api.delete(
      `/laporans/${id}`
    )

    reports.value =
      reports.value.filter(
        (item) => item.id !== id
      )

    selectedIds.value =
      selectedIds.value.filter(
        (item) => item !== id
      )
  } catch (err) {
    console.error(
      'ERROR DELETE:',
      err
    )

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
      await api.delete(
        `/laporans/${id}`
      )
    }

    reports.value =
      reports.value.filter(
        (report) =>
          !selectedIds.value.includes(
            report.id
          )
      )

    selectedIds.value = []
  } catch (err) {
    console.error(
      'ERROR BULK DELETE:',
      err
    )

    alert(
      err.response?.data?.message ||
      'Gagal menghapus laporan.'
    )

    await fetchReports()
  }
}

// =====================================================
// NAVIGATION
// =====================================================

function openDetail(id) {
  router.push(
    `/admin/laporan/${id}`
  )
}

// =====================================================
// CATEGORY
// =====================================================

function getCategoryKey(report) {
  return (
    report.kategori ||
    report.kategori_relasi?.slug ||
    report.kategoriRelasi?.slug ||
    ''
  )
}

function categoryEmoji(report) {
  const key = getCategoryKey(report)

  return (
    categoryMap[key]?.emoji ||
    '📋'
  )
}

function categoryLabel(report) {
  const key = getCategoryKey(report)

  return (
    report.kategori_relasi?.nama ||
    report.kategoriRelasi?.nama ||
    categoryMap[key]?.label ||
    report.kategori ||
    'Lainnya'
  )
}

// =====================================================
// STATUS
// =====================================================

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

  return (
    map[status] ||
    'status-waiting'
  )
}

// =====================================================
// DATE
// =====================================================

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

  const minutes = Math.floor(
    diff / 60000
  )

  const hours = Math.floor(
    minutes / 60
  )

  const days = Math.floor(
    hours / 24
  )

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

// =====================================================
// DEADLINE
// =====================================================

function deadlineClass(report) {
  if (!report.deadline_at) {
    return 'deadline-none'
  }

  if (report.status === 'selesai') {
    return 'deadline-done'
  }

  const deadline = new Date(
    report.deadline_at
  )

  const now = new Date()

  if (deadline < now) {
    return 'deadline-late'
  }

  return 'deadline-active'
}

function deadlineLabel(report) {
  if (!report.deadline_at) {
    return 'Belum ada deadline'
  }

  if (report.status === 'selesai') {
    return 'Selesai'
  }

  const deadline = new Date(
    report.deadline_at
  )

  const now = new Date()

  if (deadline < now) {
    return `Terlambat • ${formatDate(report.deadline_at)}`
  }

  return formatDate(report.deadline_at)
}

// =====================================================
// MOUNTED
// =====================================================

onMounted(() => {
  fetchReports()
  fetchPetugas()
})
</script>

<template>
  <AdminLayout
    page-title="Kelola Laporan"
    page-description="Tinjau, ubah status, dan kelola laporan warga"
  >
    <!-- FILTER -->
    <div class="card admin-filter-bar">
      <div class="search-box admin-search">
        <span class="search-icon">🔎</span>

        <input
          v-model="search"
          type="text"
          placeholder="Cari laporan, pelapor, lokasi..."
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

      <select
        v-model="exportStatus"
        class="filter-select export-status-select"
      >
        <option value="semua">
          Export Semua Laporan
        </option>

        <option value="baru">
          Export Laporan Menunggu
        </option>

        <option value="diproses">
          Export Laporan Diproses
        </option>

        <option value="selesai">
          Export Laporan Selesai
        </option>

        <option value="ditolak">
          Export Laporan Ditolak
        </option>
      </select>

      <button
        class="btn-export"
        type="button"
        @click="exportData"
      >
        📊 Export Data
      </button>
    </div>

    <!-- ERROR -->
    <div
      v-if="error"
      class="card error-card"
    >
      <span>⚠️ {{ error }}</span>

      <button
        class="btn btn-secondary"
        @click="fetchReports"
      >
        🔄 Coba Lagi
      </button>
    </div>

    <!-- BULK ACTION -->
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
            @change="
              bulkUpdateStatus(
                $event.target.value
              )
            "
          >
            <option
              value=""
              disabled
              selected
            >
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

    <!-- TABLE -->
    <div class="card admin-table-card">
      <div
        v-if="loading"
        class="loading-state"
      >
        <div class="loading-spinner"></div>

        <p>
          Memuat data laporan...
        </p>
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
              <th>Petugas</th>
              <th>Deadline</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="report in filteredReports"
              :key="report.id"
            >
              <!-- CHECKBOX -->
              <td class="admin-checkbox-cell">
                <input
                  type="checkbox"
                  :checked="
                    selectedIds.includes(
                      report.id
                    )
                  "
                  @change="
                    toggleSelect(report.id)
                  "
                />
              </td>

              <!-- LAPORAN -->
              <td>
                <p class="admin-table-title">
                  {{ report.judul }}
                </p>

                <p class="admin-table-sub">
                  📍
                  {{
                    report.lokasi ||
                    'Lokasi tidak tersedia'
                  }}
                </p>
              </td>

              <!-- PELAPOR -->
              <td>
                <div class="reporter-cell">
                  <div class="reporter-avatar">
                    {{
                      report.user?.name
                        ?.charAt(0)
                        ?.toUpperCase() ||
                      'W'
                    }}
                  </div>

                  <span>
                    {{
                      report.user?.name ||
                      'Warga'
                    }}
                  </span>
                </div>
              </td>

              <!-- KATEGORI -->
              <td>
                <span class="category-cell">
                  {{ categoryEmoji(report) }}
                  {{ categoryLabel(report) }}
                </span>
              </td>

              <!-- WAKTU -->
              <td>
                <span
                  class="time-main"
                  :title="
                    formatDate(
                      report.created_at
                    )
                  "
                >
                  {{
                    formatRelativeTime(
                      report.created_at
                    )
                  }}
                </span>
              </td>

              <!-- STATUS -->
              <td>
                <select
                  :value="report.status"
                  class="admin-status-select"
                  :class="
                    statusClass(
                      report.status
                    )
                  "
                  @change="
                    updateStatus(
                      report,
                      $event.target.value
                    )
                  "
                >
                  <option
                    v-for="status in statusOptions.filter(
                      (item) =>
                        item.key !== 'semua'
                    )"
                    :key="status.key"
                    :value="status.key"
                  >
                    {{ status.label }}
                  </option>
                </select>
              </td>

              <!-- PETUGAS -->
              <td>
                <select
                  :value="
                    report.petugas?.id || ''
                  "
                  class="admin-petugas-select"
                  :class="{
                    'belum-ditugaskan':
                      !report.petugas
                  }"
                  @change="
                    assignPetugas(
                      report,
                      $event.target.value
                    )
                  "
                >
                  <option
                    value=""
                    disabled
                  >
                    {{
                      petugasList.length
                        ? '-- Pilih Petugas --'
                        : 'Belum ada akun petugas'
                    }}
                  </option>

                  <option
                    v-for="petugas in petugasList"
                    :key="petugas.id"
                    :value="petugas.id"
                  >
                    🔧 {{ petugas.name }}
                  </option>
                </select>

                <small
                  v-if="report.petugas"
                  class="petugas-name"
                >
                  {{ report.petugas.name }}
                </small>
              </td>

              <!-- DEADLINE -->
              <td>
                <span
                  class="deadline-badge"
                  :class="
                    deadlineClass(report)
                  "
                >
                  {{
                    deadlineLabel(report)
                  }}
                </span>
              </td>

              <!-- ACTION -->
              <td>
                <div class="admin-row-actions">
                  <button
                    class="report-detail"
                    @click="
                      openDetail(
                        report.id
                      )
                    "
                  >
                    Detail
                  </button>

                  <button
                    class="admin-delete-btn"
                    title="Hapus laporan"
                    @click="
                      deleteReport(
                        report.id
                      )
                    "
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- EMPTY -->
        <div v-if="!filteredReports.length" class="empty-state">
          <div class="empty-icon">
            🔍
          </div>
          <h3>
            Tidak ada laporan
          </h3>
          <p>
            {{
              search ||
              activeStatus !== 'semua'
                ? 'Coba ubah kata kunci atau filter status.'
                : 'Belum ada laporan dari warga.'
            }}
          </p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.admin-filter-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  margin-bottom: 16px;
}

.admin-search {
  flex: 1;
}

.filter-select {
  min-width: 170px;
  padding: 10px 12px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 10px;
  background: var(--surface, #fff);
  color: var(--text, #172b4d);
  font-family: inherit;
  font-size: 13px;
  outline: none;
}

.filter-select:focus {
  border-color: var(--accent, #0d9488);
}

.export-status-select {
  min-width: 210px;
}

.btn-export {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 40px;
  padding: 10px 14px;
  border: 1px solid var(--accent, #0d9488);
  border-radius: 10px;
  background: var(--accent, #0d9488);
  color: #fff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition:
    background 0.2s ease,
    transform 0.2s ease;
}

.btn-export:hover {
  background: #0f766e;
  transform: translateY(-1px);
}

.btn-export:active {
  transform: translateY(0);
}

.error-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 16px 20px;
  margin-bottom: 16px;
  color: #dc2626;
}

.bulk-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 18px;
  margin-bottom: 16px;
  border-radius: 12px;
  background: var(--surface-soft, #f8fafc);
  border: 1px solid var(--border, #e2e8f0);
}

.bulk-bar > span {
  font-size: 14px;
  font-weight: 600;
  color: var(--text, #172b4d);
}

.bulk-bar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-danger {
  border: none;
  cursor: pointer;
  padding: 10px 14px;
  border-radius: 9px;
  background: #dc2626;
  color: #fff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
}

.btn-danger:hover {
  background: #b91c1c;
}

.admin-table-card {
  overflow: hidden;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 1100px;
  border-collapse: collapse;
}

th {
  padding: 14px 12px;
  background: var(--surface-soft, #f8fafc);
  border-bottom: 1px solid var(--border, #e2e8f0);
  color: var(--text-secondary, #64748b);
  font-size: 12px;
  font-weight: 700;
  text-align: left;
  white-space: nowrap;
}

td {
  padding: 14px 12px;
  border-bottom: 1px solid var(--border, #e2e8f0);
  color: var(--text, #172b4d);
  font-size: 13px;
  vertical-align: middle;
}

tbody tr:hover {
  background: var(--surface-soft, #f8fafc);
}

.admin-checkbox-cell {
  width: 42px;
  text-align: center;
}

.admin-checkbox-cell input {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.admin-table-title {
  max-width: 240px;
  margin: 0 0 5px;
  overflow: hidden;
  color: var(--text, #172b4d);
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.admin-table-sub {
  max-width: 240px;
  margin: 0;
  overflow: hidden;
  color: var(--text-secondary, #64748b);
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.reporter-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 130px;
}

.reporter-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 30px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #475569;
  font-size: 12px;
  font-weight: 700;
}

.category-cell {
  white-space: nowrap;
}

.time-main {
  color: var(--text-secondary, #64748b);
  white-space: nowrap;
}

.admin-status-select,
.admin-petugas-select {
  padding: 7px 10px;
  border-radius: 8px;
  border: 1px solid var(--border, #e2e8f0);
  background: var(--surface-soft, #f8fafc);
  font-family: inherit;
  font-size: 12px;
  cursor: pointer;
  outline: none;
}

.admin-status-select:focus,
.admin-petugas-select:focus {
  border-color: var(--accent, #0d9488);
}

.status-waiting {
  color: #92400e;
  border-color: #fcd34d;
  background: #fffbeb;
}

.status-processing {
  color: #1d4ed8;
  border-color: #93c5fd;
  background: #eff6ff;
}

.status-success {
  color: #047857;
  border-color: #6ee7b7;
  background: #ecfdf5;
}

.status-rejected {
  color: #b91c1c;
  border-color: #fca5a5;
  background: #fef2f2;
}

.admin-petugas-select {
  min-width: 170px;
  color: var(--text, #172b4d);
}

.admin-petugas-select.belum-ditugaskan {
  color: var(--text-secondary, #94a3b8);
  border-style: dashed;
}

.petugas-name {
  display: block;
  margin-top: 4px;
  color: var(--text-secondary, #64748b);
  font-size: 10px;
}

.deadline-badge {
  display: inline-flex;
  align-items: center;
  padding: 5px 8px;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.deadline-none {
  color: #64748b;
  background: #f1f5f9;
}

.deadline-active {
  color: #1d4ed8;
  background: #eff6ff;
}

.deadline-done {
  color: #047857;
  background: #ecfdf5;
}

.deadline-late {
  color: #b91c1c;
  background: #fef2f2;
}

.admin-row-actions {
  display: flex;
  align-items: center;
  gap: 7px;
  white-space: nowrap;
}

.report-detail {
  padding: 7px 11px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 8px;
  background: var(--surface, #fff);
  color: var(--text, #172b4d);
  font-family: inherit;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.report-detail:hover {
  background: var(--surface-soft, #f8fafc);
  border-color: var(--accent, #0d9488);
}

.admin-delete-btn {
  width: 32px;
  height: 32px;
  border: 1px solid #fecaca;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  transition: background 0.15s ease;
}

.admin-delete-btn:hover {
  background: #fef2f2;
}

.loading-state {
  padding: 70px 20px;
  text-align: center;
  color: var(--text-muted, #94a3b8);
}

.loading-spinner {
  width: 28px;
  height: 28px;
  margin: 0 auto 12px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--accent, #0d9488);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-state p {
  margin: 0;
  font-size: 13px;
}

.empty-state {
  padding: 70px 20px;
  text-align: center;
}

.empty-icon {
  margin-bottom: 10px;
  font-size: 40px;
}

.empty-state h3 {
  margin: 0 0 6px;
  color: var(--text, #172b4d);
}

.empty-state p {
  margin: 0;
  color: var(--text-secondary, #64748b);
  font-size: 13px;
}

.fade-in-enter-active,
.fade-in-leave-active {
  transition: opacity 0.2s ease;
}

.fade-in-enter-from,
.fade-in-leave-to {
  opacity: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 900px) {
  .admin-filter-bar {
    align-items: stretch;
    flex-direction: column;
  }

  .filter-select,
  .btn-export {
    width: 100%;
  }

  .bulk-bar {
    align-items: stretch;
    flex-direction: column;
  }

  .bulk-bar-actions {
    justify-content: flex-end;
  }
}

@media (max-width: 600px) {
  .bulk-bar-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .bulk-bar-actions .filter-select,
  .bulk-bar-actions .btn {
    width: 100%;
  }
}
</style>