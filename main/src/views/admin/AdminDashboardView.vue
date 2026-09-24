<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../../services/api'
import AdminLayout from '../../components/AdminLayout.vue'

const laporans = ref([])
const loading = ref(true)
const refreshing = ref(false)

const statusOptions = [
  { key: 'baru', label: 'Menunggu', icon: '🕐' },
  { key: 'diproses', label: 'Diproses', icon: '⚙️' },
  { key: 'selesai', label: 'Selesai', icon: '✓' },
  { key: 'ditolak', label: 'Ditolak', icon: '✕' }
]

const stats = computed(() => [
  {
    label: 'Total Laporan',
    value: laporans.value.length,
    icon: '📋',
    variant: ''
  },
  {
    label: 'Menunggu',
    value: countStatus('baru'),
    icon: '🕐',
    variant: 'waiting'
  },
  {
    label: 'Diproses',
    value: countStatus('diproses'),
    icon: '⚙️',
    variant: 'processing'
  },
  {
    label: 'Selesai',
    value: countStatus('selesai'),
    icon: '✓',
    variant: 'success'
  },
  {
    label: 'Ditolak',
    value: countStatus('ditolak'),
    icon: '✕',
    variant: 'rejected'
  }
])

const recentLaporans = computed(() => {
  return [...laporans.value]
    .sort((a, b) => {
      return new Date(b.created_at || 0) - new Date(a.created_at || 0)
    })
    .slice(0, 5)
})

const statusStats = computed(() => {
  const total = laporans.value.length || 1

  return [
    {
      label: 'Menunggu',
      key: 'baru',
      icon: '🕐',
      value: countStatus('baru'),
      percent: percentage(countStatus('baru'), total)
    },
    {
      label: 'Diproses',
      key: 'diproses',
      icon: '⚙️',
      value: countStatus('diproses'),
      percent: percentage(countStatus('diproses'), total)
    },
    {
      label: 'Selesai',
      key: 'selesai',
      icon: '✓',
      value: countStatus('selesai'),
      percent: percentage(countStatus('selesai'), total)
    },
    {
      label: 'Ditolak',
      key: 'ditolak',
      icon: '✕',
      value: countStatus('ditolak'),
      percent: percentage(countStatus('ditolak'), total)
    }
  ]
})

const completionRate = computed(() => {
  if (!laporans.value.length) return 0

  return Math.round(
    (countStatus('selesai') / laporans.value.length) * 100
  )
})

const kategoriStats = computed(() => {
  const result = {}

  laporans.value.forEach(laporan => {
    const nama = getCategoryName(laporan)

    result[nama] = (result[nama] || 0) + 1
  })

  return Object.entries(result)
    .map(([nama, jumlah]) => ({
      nama,
      jumlah
    }))
    .sort((a, b) => b.jumlah - a.jumlah)
    .slice(0, 6)
})

const totalKategori = computed(() => {
  return laporans.value.length || 1
})

const currentYear = new Date().getFullYear()

const monthlyStats = computed(() => {
  const monthNames = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des'
  ]

  const result = monthNames.map((nama, index) => ({
    nama,
    bulan: index,
    jumlah: 0
  }))

  laporans.value.forEach(laporan => {
    if (!laporan.created_at) return

    const date = new Date(laporan.created_at)

    if (date.getFullYear() !== currentYear) return

    result[date.getMonth()].jumlah++
  })

  return result
})

const maxMonthlyValue = computed(() => {
  const max = Math.max(
    ...monthlyStats.value.map(item => item.jumlah)
  )

  return max || 1
})

const yearlyTotal = computed(() => {
  return monthlyStats.value.reduce(
    (total, item) => total + item.jumlah,
    0
  )
})

const deadlineStats = computed(() => {
  let selesai = 0
  let terlambat = 0
  let aktif = 0
  let tanpaDeadline = 0

  laporans.value.forEach(laporan => {
    if (laporan.status === 'ditolak') return

    if (laporan.status === 'selesai') {
      selesai++
      return
    }

    if (!laporan.deadline_at) {
      tanpaDeadline++
      return
    }

    if (new Date(laporan.deadline_at) < new Date()) {
      terlambat++
    } else {
      aktif++
    }
  })

  return {
    selesai,
    terlambat,
    aktif,
    tanpaDeadline
  }
})

const deadlineLateRate = computed(() => {
  const total =
    deadlineStats.value.aktif +
    deadlineStats.value.terlambat +
    deadlineStats.value.selesai

  if (!total) return 0

  return Math.round(
    (deadlineStats.value.terlambat / total) * 100
  )
})

function countStatus(status) {
  return laporans.value.filter(
    laporan => laporan.status === status
  ).length
}

function percentage(value, total) {
  if (!total) return 0

  return Math.round((value / total) * 100)
}

function getCategoryName(laporan) {
  return (
    laporan.kategori_relasi?.nama ||
    laporan.kategoriRelasi?.nama ||
    laporan.kategori ||
    'Lainnya'
  )
}

function kategoriPercent(jumlah) {
  return Math.round(
    (jumlah / totalKategori.value) * 100
  )
}

function monthlyHeight(jumlah) {
  if (!jumlah) return 4

  return Math.max(
    Math.round((jumlah / maxMonthlyValue.value) * 100),
    8
  )
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
  if (!date) return '-'

  return new Date(date).toLocaleDateString(
    'id-ID',
    {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    }
  )
}

function getCategoryIcon(laporan) {
  const kategori = getCategoryName(laporan).toLowerCase()

  if (kategori.includes('jalan')) return '🚧'
  if (kategori.includes('sampah')) return '🗑️'
  if (kategori.includes('lampu')) return '💡'
  if (kategori.includes('selokan')) return '🌊'
  if (kategori.includes('fasilitas')) return '🏞️'
  if (kategori.includes('keamanan')) return '🛡️'
  if (kategori.includes('kebersihan')) return '🧹'

  return '📋'
}

function deadlineClass(laporan) {
  if (laporan.status === 'selesai') {
    return 'deadline-completed'
  }

  if (!laporan.deadline_at) {
    return 'deadline-none'
  }

  return new Date(laporan.deadline_at) < new Date()
    ? 'deadline-late'
    : 'deadline-active'
}

function deadlineLabel(laporan) {
  if (laporan.status === 'selesai') {
    return 'Selesai'
  }

  if (!laporan.deadline_at) {
    return 'Belum ada deadline'
  }

  return new Date(laporan.deadline_at) < new Date()
    ? 'Terlambat'
    : 'Dalam batas waktu'
}

async function fetchLaporans(isRefresh = false) {
  if (isRefresh) {
    refreshing.value = true
  } else {
    loading.value = true
  }

  try {
    const res = await api.get('/laporans')

    laporans.value = Array.isArray(res.data)
      ? res.data
      : Array.isArray(res.data?.data)
        ? res.data.data
        : []
  } catch (err) {
    console.error('Gagal mengambil laporan:', err)
    laporans.value = []
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

async function updateStatus(laporan, newStatus) {
  const oldStatus = laporan.status

  try {
    await api.put(`/laporans/${laporan.id}`, {
      status: newStatus
    })

    laporan.status = newStatus
  } catch (err) {
    laporan.status = oldStatus

    console.error(
      'Gagal update status:',
      err
    )
  }
}

onMounted(() => {
  fetchLaporans()
})
</script>

<template>
  <AdminLayout
    page-title="Dashboard"
    page-description="Ringkasan aktivitas SUARAWARGA hari ini"
  >
    <!-- WELCOME -->
    <div class="dashboard-welcome">
      <div>
        <span class="dashboard-eyebrow">
          📊 OVERVIEW
        </span>

        <h1>
          Pantau laporan masyarakat
        </h1>

        <p>
          Kelola dan pantau perkembangan laporan
          masyarakat secara real-time.
        </p>
      </div>

      <div class="welcome-actions">
        <button
          class="dashboard-refresh-button"
          :disabled="refreshing"
          @click="fetchLaporans(true)"
        >
          {{ refreshing ? '⟳ Memuat...' : '↻ Refresh' }}
        </button>

        <RouterLink
          to="/admin/laporan"
          class="dashboard-main-button"
        >
          📋 Kelola Laporan
        </RouterLink>
      </div>
    </div>

    <!-- STATISTIK UTAMA -->
    <div class="admin-stats-grid dashboard-stats-grid">
      <div
        v-for="s in stats"
        :key="s.label"
        class="stat-card dashboard-stat"
      >
        <div
          class="stat-icon"
          :class="s.variant"
        >
          {{ s.icon }}
        </div>

        <div class="dashboard-stat-content">
          <div class="stat-number">
            {{ s.value }}
          </div>

          <div class="stat-label">
            {{ s.label }}
          </div>
        </div>

        <div class="stat-decoration"></div>
      </div>
    </div>

    <!-- STATUS + KATEGORI -->
    <div class="dashboard-grid">
      <!-- STATUS -->
      <div class="card dashboard-panel">
        <div class="dashboard-panel-header">
          <div>
            <span class="panel-eyebrow">
              STATUS LAPORAN
            </span>

            <h2>
              Perkembangan laporan
            </h2>
          </div>

          <span class="panel-icon">
            📈
          </span>
        </div>

        <div class="status-list">
          <div
            v-for="item in statusStats"
            :key="item.key"
            class="status-item"
          >
            <div class="status-item-top">
              <div class="status-name">
                <span>
                  {{ item.icon }}
                </span>

                {{ item.label }}
              </div>

              <strong>
                {{ item.value }}
              </strong>
            </div>

            <div class="progress">
              <div
                class="progress-bar"
                :style="{
                  width: `${item.percent}%`
                }"
              ></div>
            </div>

            <div class="status-percent">
              {{ item.percent }}%
            </div>
          </div>
        </div>

        <div class="completion-box">
          <div class="completion-icon">
            🎯
          </div>

          <div class="completion-info">
            <strong>
              Tingkat penyelesaian
            </strong>

            <span>
              {{ completionRate }}% laporan telah selesai
            </span>
          </div>

          <div class="completion-number">
            {{ completionRate }}%
          </div>
        </div>
      </div>

      <!-- KATEGORI -->
      <div class="card dashboard-panel">
        <div class="dashboard-panel-header">
          <div>
            <span class="panel-eyebrow">
              KATEGORI
            </span>

            <h2>
              Laporan berdasarkan kategori
            </h2>
          </div>

          <span class="panel-icon">
            🏷️
          </span>
        </div>

        <div
          v-if="kategoriStats.length"
          class="category-stat-list"
        >
          <div
            v-for="kategori in kategoriStats"
            :key="kategori.nama"
            class="category-stat-item"
          >
            <div class="category-stat-icon">
              {{ getCategoryIcon({
                kategori: kategori.nama
              }) }}
            </div>

            <div class="category-stat-info">
              <div class="category-stat-title">
                <strong>
                  {{ kategori.nama }}
                </strong>

                <span>
                  {{ kategori.jumlah }}
                </span>
              </div>

              <div class="progress">
                <div
                  class="progress-bar"
                  :style="{
                    width: `${kategoriPercent(kategori.jumlah)}%`
                  }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-else
          class="dashboard-empty"
        >
          <span>📋</span>
          <p>Belum ada data kategori.</p>
        </div>
      </div>
    </div>

    <!-- REKAP BULANAN -->
    <div class="card dashboard-panel monthly-panel">
      <div class="dashboard-panel-header">
        <div>
          <span class="panel-eyebrow">
            REKAP TAHUN {{ currentYear }}
          </span>

          <h2>
            Perkembangan laporan bulanan
          </h2>

          <p class="panel-description">
            Jumlah laporan yang masuk setiap bulan.
          </p>
        </div>

        <div class="year-total">
          <strong>
            {{ yearlyTotal }}
          </strong>

          <span>
            laporan
          </span>
        </div>
      </div>

      <div class="monthly-chart">
        <div
          v-for="item in monthlyStats"
          :key="item.bulan"
          class="monthly-column"
        >
          <div class="monthly-value">
            {{ item.jumlah }}
          </div>

          <div class="monthly-bar-area">
            <div
              class="monthly-bar"
              :style="{
                height: `${monthlyHeight(item.jumlah)}%`
              }"
            ></div>
          </div>

          <span class="monthly-label">
            {{ item.nama }}
          </span>
        </div>
      </div>
    </div>

    <!-- DEADLINE -->
    <div class="card dashboard-panel deadline-panel">
      <div class="dashboard-panel-header">
        <div>
          <span class="panel-eyebrow">
            MONITORING DEADLINE
          </span>

          <h2>
            Status pengerjaan
          </h2>

          <p class="panel-description">
            Ringkasan deadline laporan yang sedang dikerjakan.
          </p>
        </div>

        <span class="panel-icon">
          ⏰
        </span>
      </div>

      <div class="deadline-grid">
        <div class="deadline-card deadline-card-success">
          <div class="deadline-card-icon">
            ✓
          </div>

          <div>
            <strong>
              {{ deadlineStats.selesai }}
            </strong>

            <span>
              Selesai
            </span>
          </div>
        </div>

        <div class="deadline-card deadline-card-active">
          <div class="deadline-card-icon">
            ⏳
          </div>

          <div>
            <strong>
              {{ deadlineStats.aktif }}
            </strong>

            <span>
              Masih berjalan
            </span>
          </div>
        </div>

        <div class="deadline-card deadline-card-late">
          <div class="deadline-card-icon">
            !
          </div>

          <div>
            <strong>
              {{ deadlineStats.terlambat }}
            </strong>

            <span>
              Terlambat
            </span>
          </div>
        </div>

        <div class="deadline-card deadline-card-none">
          <div class="deadline-card-icon">
            —
          </div>

          <div>
            <strong>
              {{ deadlineStats.tanpaDeadline }}
            </strong>

            <span>
              Tanpa deadline
            </span>
          </div>
        </div>
      </div>

      <div class="deadline-summary">
        <div>
          <strong>
            Tingkat keterlambatan
          </strong>

          <span>
            {{ deadlineLateRate }}% dari laporan yang memiliki status pengerjaan
          </span>
        </div>

        <div class="deadline-summary-number">
          {{ deadlineLateRate }}%
        </div>
      </div>
    </div>

    <!-- QUICK ACTION -->
    <div class="quick-actions">
      <RouterLink
        to="/admin/laporan"
        class="quick-action-card"
      >
        <div class="quick-action-icon">
          📋
        </div>

        <div>
          <strong>
            Semua Laporan
          </strong>

          <span>
            Lihat seluruh laporan masyarakat
          </span>
        </div>

        <b>→</b>
      </RouterLink>

      <RouterLink
        to="/admin/kategori"
        class="quick-action-card"
      >
        <div class="quick-action-icon">
          🏷️
        </div>

        <div>
          <strong>
            Kelola Kategori
          </strong>

          <span>
            Atur kategori laporan
          </span>
        </div>

        <b>→</b>
      </RouterLink>

      <RouterLink
        to="/admin/users"
        class="quick-action-card"
      >
        <div class="quick-action-icon">
          👥
        </div>

        <div>
          <strong>
            Kelola Pengguna
          </strong>

          <span>
            Lihat pengguna SUARAWARGA
          </span>
        </div>

        <b>→</b>
      </RouterLink>
    </div>

    <!-- LAPORAN TERBARU -->
    <div class="card admin-table-card dashboard-recent">
      <div class="admin-table-header">
        <div>
          <span class="panel-eyebrow">
            AKTIVITAS TERBARU
          </span>

          <h2>
            Laporan terbaru
          </h2>

          <p class="text-muted">
            {{ laporans.length }}
            laporan ditemukan
          </p>
        </div>

        <RouterLink
          to="/admin/laporan"
          class="btn btn-secondary"
        >
          Lihat Semua →
        </RouterLink>
      </div>

      <!-- LOADING -->
      <div
        v-if="loading"
        class="dashboard-loading"
      >
        <div class="loading-spinner-small"></div>
        <span>Memuat laporan...</span>
      </div>

      <!-- TABLE -->
      <div
        v-else-if="recentLaporans.length"
        class="table-responsive"
      >
        <table>
          <thead>
            <tr>
              <th>Laporan</th>
              <th>Pelapor</th>
              <th>Kategori</th>
              <th>Status</th>
              <th>Deadline</th>
              <th>Tanggal</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="laporan in recentLaporans"
              :key="laporan.id"
            >
              <td>
                <div class="report-table-info">
                  <div class="report-mini-icon">
                    {{ getCategoryIcon(laporan) }}
                  </div>

                  <div>
                    <p class="admin-table-title">
                      {{ laporan.judul }}
                    </p>

                    <p class="admin-table-sub">
                      📍
                      {{ laporan.lokasi || '-' }}
                    </p>
                  </div>
                </div>
              </td>

              <td>
                <div class="user-table-info">
                  <div class="user-avatar-mini">
                    {{
                      laporan.user?.name
                        ?.charAt(0)
                        ?.toUpperCase() || 'U'
                    }}
                  </div>

                  <span>
                    {{ laporan.user?.name || 'Pengguna' }}
                  </span>
                </div>
              </td>

              <td>
                <span class="category-badge">
                  {{ getCategoryName(laporan) }}
                </span>
              </td>

              <td>
                <select
                  class="admin-status-select"
                  :class="statusClass(laporan.status)"
                  :value="laporan.status"
                  @change="
                    updateStatus(
                      laporan,
                      $event.target.value
                    )
                  "
                >
                  <option
                    v-for="opt in statusOptions"
                    :key="opt.key"
                    :value="opt.key"
                  >
                    {{ opt.icon }}
                    {{ opt.label }}
                  </option>
                </select>
              </td>

              <td>
                <span
                  class="deadline-badge"
                  :class="deadlineClass(laporan)"
                >
                  {{ deadlineLabel(laporan) }}
                </span>
              </td>

              <td>
                <span class="date-text">
                  {{ formatDate(laporan.created_at) }}
                </span>
              </td>

              <td>
                <RouterLink
                  :to="`/laporan/${laporan.id}`"
                  class="report-detail"
                >
                  Detail →
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY -->
      <div
        v-else
        class="dashboard-empty large"
      >
        <div>
          📋
        </div>

        <h3>
          Belum ada laporan
        </h3>

        <p>
          Laporan masyarakat akan muncul di sini.
        </p>

        <RouterLink
          to="/admin/laporan"
          class="btn btn-primary"
        >
          Kelola Laporan
        </RouterLink>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* ================================
   WELCOME
================================ */

.dashboard-welcome {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 24px;
  padding: 26px 30px;
  border-radius: 20px;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white;
  box-shadow: 0 12px 30px rgba(37, 99, 235, 0.18);
}

.dashboard-eyebrow,
.panel-eyebrow {
  display: block;
  margin-bottom: 7px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1px;
  text-transform: uppercase;
  opacity: 0.7;
}

.dashboard-welcome h1 {
  margin: 0 0 6px;
  font-size: 24px;
  font-weight: 800;
}

.dashboard-welcome p {
  margin: 0;
  opacity: 0.85;
  font-size: 14px;
}

.welcome-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.dashboard-main-button,
.dashboard-refresh-button {
  padding: 12px 18px;
  border-radius: 12px;
  font-weight: 700;
  white-space: nowrap;
  transition: 0.2s;
}

.dashboard-main-button {
  background: white;
  color: #2563eb;
  text-decoration: none;
}

.dashboard-main-button:hover,
.dashboard-refresh-button:hover {
  transform: translateY(-2px);
}

.dashboard-refresh-button {
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.14);
  color: white;
  cursor: pointer;
}

.dashboard-refresh-button:disabled {
  opacity: 0.6;
  cursor: wait;
}

/* ================================
   STAT
================================ */

.dashboard-stats-grid {
  grid-template-columns: repeat(5, 1fr);
}

.dashboard-stat {
  position: relative;
  overflow: hidden;
  transition: 0.25s;
}

.dashboard-stat:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
}

.dashboard-stat-content {
  position: relative;
  z-index: 2;
}

.stat-decoration {
  position: absolute;
  right: -25px;
  bottom: -30px;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: rgba(37, 99, 235, 0.05);
}

/* ================================
   GRID
================================ */

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 22px;
  margin-top: 22px;
}

.dashboard-panel {
  padding: 24px;
}

.dashboard-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 22px;
}

.dashboard-panel-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
}

.dashboard-panel .panel-eyebrow {
  color: #2563eb;
  opacity: 1;
}

.panel-description {
  margin: 5px 0 0;
  color: #888;
  font-size: 12px;
}

.panel-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: #f3f4f6;
  font-size: 20px;
}

/* ================================
   STATUS
================================ */

.status-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.status-item {
  position: relative;
}

.status-item-top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 7px;
  font-size: 13px;
}

.status-name {
  display: flex;
  align-items: center;
  gap: 7px;
}

.status-name span {
  width: 25px;
  text-align: center;
}

.status-item-top strong {
  font-size: 13px;
}

.progress {
  height: 7px;
  overflow: hidden;
  border-radius: 20px;
  background: #eef0f3;
}

.progress-bar {
  height: 100%;
  border-radius: 20px;
  background: #2563eb;
  transition: width 0.5s ease;
}

.status-percent {
  margin-top: 4px;
  text-align: right;
  font-size: 11px;
  color: #888;
}

.completion-box {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 25px;
  padding: 15px;
  border-radius: 14px;
  background: #f8fafc;
}

.completion-icon {
  font-size: 25px;
}

.completion-info {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 3px;
}

.completion-info strong {
  font-size: 13px;
}

.completion-info span {
  font-size: 11px;
  color: #888;
}

.completion-number {
  font-size: 20px;
  font-weight: 800;
}

/* ================================
   CATEGORY
================================ */

.category-stat-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.category-stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.category-stat-icon {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 11px;
  background: #f3f4f6;
}

.category-stat-info {
  flex: 1;
}

.category-stat-title {
  display: flex;
  justify-content: space-between;
  margin-bottom: 7px;
  font-size: 13px;
}

.category-stat-title span {
  font-weight: 700;
}

/* ================================
   MONTHLY CHART
================================ */

.monthly-panel {
  margin-top: 22px;
}

.year-total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.year-total strong {
  font-size: 24px;
  line-height: 1;
}

.year-total span {
  margin-top: 4px;
  color: #888;
  font-size: 11px;
}

.monthly-chart {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 12px;
  min-height: 220px;
  align-items: end;
  padding-top: 20px;
}

.monthly-column {
  display: flex;
  height: 210px;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 7px;
}

.monthly-value {
  min-height: 16px;
  font-size: 11px;
  font-weight: 700;
  color: #555;
}

.monthly-bar-area {
  width: 100%;
  height: 160px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  border-bottom: 1px solid #e5e7eb;
}

.monthly-bar {
  width: min(32px, 70%);
  min-height: 4px;
  border-radius: 8px 8px 2px 2px;
  background: linear-gradient(180deg, #2563eb, #4f46e5);
  transition: height 0.4s ease;
}

.monthly-label {
  font-size: 11px;
  color: #888;
}

/* ================================
   DEADLINE
================================ */

.deadline-panel {
  margin-top: 22px;
}

.deadline-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.deadline-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 14px;
  background: #f8fafc;
}

.deadline-card-icon {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-weight: 800;
}

.deadline-card div:last-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.deadline-card strong {
  font-size: 20px;
}

.deadline-card span {
  font-size: 11px;
  color: #888;
}

.deadline-card-success .deadline-card-icon {
  background: #dcfce7;
  color: #15803d;
}

.deadline-card-active .deadline-card-icon {
  background: #dbeafe;
  color: #2563eb;
}

.deadline-card-late .deadline-card-icon {
  background: #fee2e2;
  color: #dc2626;
}

.deadline-card-none .deadline-card-icon {
  background: #f3f4f6;
  color: #777;
}

.deadline-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin-top: 18px;
  padding: 15px;
  border-radius: 14px;
  background: #f8fafc;
}

.deadline-summary div:first-child {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.deadline-summary strong {
  font-size: 13px;
}

.deadline-summary span {
  color: #888;
  font-size: 11px;
}

.deadline-summary-number {
  font-size: 22px;
  font-weight: 800;
}

/* ================================
   QUICK ACTION
================================ */

.quick-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin: 22px 0;
}

.quick-action-card {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 18px;
  border: 1px solid #edf0f3;
  border-radius: 16px;
  background: white;
  color: inherit;
  text-decoration: none;
  transition: 0.2s;
}

.quick-action-card:hover {
  transform: translateY(-3px);
  border-color: #dbe3f0;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
}

.quick-action-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 12px;
  background: #f3f6ff;
  font-size: 19px;
}

.quick-action-card div:nth-child(2) {
  flex: 1;
}

.quick-action-card strong {
  display: block;
  margin-bottom: 3px;
  font-size: 13px;
}

.quick-action-card span {
  display: block;
  font-size: 11px;
  color: #888;
}

.quick-action-card b {
  font-size: 18px;
  color: #999;
}

/* ================================
   TABLE
================================ */

.dashboard-recent {
  overflow: hidden;
}

.report-table-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.report-mini-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 11px;
  background: #f4f6f8;
  font-size: 18px;
}

.user-table-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.user-avatar-mini {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #eef2ff;
  color: #4f46e5;
  font-size: 12px;
  font-weight: 800;
}

.category-badge {
  display: inline-block;
  padding: 5px 9px;
  border-radius: 8px;
  background: #f5f6f8;
  font-size: 11px;
  font-weight: 600;
}

.deadline-badge {
  display: inline-block;
  padding: 5px 9px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 700;
  white-space: nowrap;
}

.deadline-completed {
  background: #dcfce7;
  color: #15803d;
}

.deadline-active {
  background: #dbeafe;
  color: #2563eb;
}

.deadline-late {
  background: #fee2e2;
  color: #dc2626;
}

.deadline-none {
  background: #f3f4f6;
  color: #777;
}

.date-text {
  color: #888;
  font-size: 12px;
  white-space: nowrap;
}

/* ================================
   LOADING
================================ */

.dashboard-loading {
  display: flex;
  min-height: 180px;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #888;
  font-size: 13px;
}

.loading-spinner-small {
  width: 22px;
  height: 22px;
  border: 3px solid #e5e7eb;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: dashboard-spin 0.7s linear infinite;
}

@keyframes dashboard-spin {
  to {
    transform: rotate(360deg);
  }
}

/* ================================
   EMPTY
================================ */

.dashboard-empty {
  padding: 30px;
  text-align: center;
  color: #888;
}

.dashboard-empty span,
.dashboard-empty > div {
  display: block;
  margin-bottom: 10px;
  font-size: 30px;
}

.dashboard-empty p {
  margin: 0;
  font-size: 13px;
}

.dashboard-empty.large {
  padding: 60px 20px;
}

.dashboard-empty.large h3 {
  margin: 5px 0;
  color: #444;
}

/* ================================
   RESPONSIVE
================================ */

@media (max-width: 1200px) {
  .dashboard-stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .deadline-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 1000px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .quick-actions {
    grid-template-columns: 1fr;
  }

  .monthly-chart {
    overflow-x: auto;
  }
}

@media (max-width: 700px) {
  .dashboard-welcome {
    flex-direction: column;
    align-items: flex-start;
  }

  .welcome-actions {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
  }

  .dashboard-main-button,
  .dashboard-refresh-button {
    text-align: center;
  }

  .dashboard-welcome h1 {
    font-size: 20px;
  }

  .dashboard-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .dashboard-panel {
    padding: 18px;
  }

  .deadline-grid {
    grid-template-columns: 1fr;
  }

  .monthly-chart {
    grid-template-columns: repeat(12, 55px);
    overflow-x: auto;
  }
}

@media (max-width: 450px) {
  .dashboard-stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>