<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../../services/api'
import AdminLayout from '../../components/AdminLayout.vue'

const laporans = ref([])
const loading = ref(true)

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
    value: laporans.value.filter(l => l.status === 'baru').length,
    icon: '🕐',
    variant: 'waiting'
  },
  {
    label: 'Diproses',
    value: laporans.value.filter(l => l.status === 'diproses').length,
    icon: '⚙️',
    variant: 'processing'
  },
  {
    label: 'Selesai',
    value: laporans.value.filter(l => l.status === 'selesai').length,
    icon: '✓',
    variant: 'success'
  }
])

const recentLaporans = computed(() => {
  return laporans.value.slice(0, 5)
})

const statusStats = computed(() => {
  const total = laporans.value.length || 1

  return [
    {
      label: 'Menunggu',
      key: 'baru',
      icon: '🕐',
      value: laporans.value.filter(l => l.status === 'baru').length,
      percent: Math.round(
        (laporans.value.filter(l => l.status === 'baru').length / total) * 100
      )
    },
    {
      label: 'Diproses',
      key: 'diproses',
      icon: '⚙️',
      value: laporans.value.filter(l => l.status === 'diproses').length,
      percent: Math.round(
        (laporans.value.filter(l => l.status === 'diproses').length / total) * 100
      )
    },
    {
      label: 'Selesai',
      key: 'selesai',
      icon: '✓',
      value: laporans.value.filter(l => l.status === 'selesai').length,
      percent: Math.round(
        (laporans.value.filter(l => l.status === 'selesai').length / total) * 100
      )
    },
    {
      label: 'Ditolak',
      key: 'ditolak',
      icon: '✕',
      value: laporans.value.filter(l => l.status === 'ditolak').length,
      percent: Math.round(
        (laporans.value.filter(l => l.status === 'ditolak').length / total) * 100
      )
    }
  ]
})

const completionRate = computed(() => {
  if (!laporans.value.length) return 0

  const selesai = laporans.value.filter(
    l => l.status === 'selesai'
  ).length

  return Math.round(
    (selesai / laporans.value.length) * 100
  )
})

const kategoriStats = computed(() => {
  const result = {}

  laporans.value.forEach(laporan => {
    const nama =
      laporan.kategori_relasi?.nama ||
      laporan.kategori ||
      'Lainnya'

    result[nama] = (result[nama] || 0) + 1
  })

  return Object.entries(result)
    .map(([nama, jumlah]) => ({
      nama,
      jumlah
    }))
    .sort((a, b) => b.jumlah - a.jumlah)
    .slice(0, 5)
})

const totalKategori = computed(() => {
  return laporans.value.length || 1
})

function kategoriPercent(jumlah) {
  return Math.round(
    (jumlah / totalKategori.value) * 100
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

function statusLabel(status) {
  const map = {
    baru: 'Menunggu',
    diproses: 'Diproses',
    selesai: 'Selesai',
    ditolak: 'Ditolak'
  }

  return map[status] || status || '-'
}

function statusIcon(status) {
  const map = {
    baru: '🕐',
    diproses: '⚙️',
    selesai: '✓',
    ditolak: '✕'
  }

  return map[status] || '•'
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
  const kategori = (
    laporan.kategori_relasi?.nama ||
    laporan.kategori ||
    ''
  ).toLowerCase()

  if (kategori.includes('jalan')) return '🚧'
  if (kategori.includes('sampah')) return '🗑️'
  if (kategori.includes('lampu')) return '💡'
  if (kategori.includes('selokan')) return '🌊'
  if (kategori.includes('fasilitas')) return '🏞️'

  return '📋'
}

async function fetchLaporans() {
  loading.value = true

  try {
    const res = await api.get('/laporans')

    laporans.value = Array.isArray(res.data)
      ? res.data
      : res.data.data || []
  } catch (err) {
    console.error('Gagal mengambil laporan:', err)
    laporans.value = []
  } finally {
    loading.value = false
  }
}

async function updateStatus(laporan, newStatus) {
  const oldStatus = laporan.status

  try {
    await api.put(
      `/laporans/${laporan.id}`,
      {
        status: newStatus
      }
    )

    laporan.status = newStatus
  } catch (err) {
    laporan.status = oldStatus

    console.error(
      'Gagal update status:',
      err
    )
  }
}

onMounted(fetchLaporans)
</script>

<template>
  <AdminLayout
    page-title="Dashboard"
    page-description="Ringkasan aktivitas SUARAWARGA hari ini"
  >

    <!-- =========================
         STATISTIK UTAMA
    ========================== -->

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

      <RouterLink
        to="/admin/laporan"
        class="dashboard-main-button"
      >
        📋 Kelola Laporan
      </RouterLink>
    </div>

    <div class="admin-stats-grid">

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

    <!-- =========================
         CONTENT GRID
    ========================== -->

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
              📋
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

    <!-- =========================
         QUICK ACTION
    ========================== -->

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

    <!-- =========================
         LAPORAN TERBARU
    ========================== -->

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
                  {{
                    laporan.kategori_relasi?.nama ||
                    laporan.kategori ||
                    'Lainnya'
                  }}
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
  background: linear-gradient(
    135deg,
    #2563eb,
    #4f46e5
  );
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

.dashboard-main-button {
  padding: 12px 18px;
  border-radius: 12px;
  background: white;
  color: #2563eb;
  text-decoration: none;
  font-weight: 700;
  white-space: nowrap;
  transition: 0.2s;
}

.dashboard-main-button:hover {
  transform: translateY(-2px);
}

/* ================================
   STAT
================================ */

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

@media (max-width: 1000px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .quick-actions {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 700px) {
  .dashboard-welcome {
    flex-direction: column;
    align-items: flex-start;
  }

  .dashboard-welcome h1 {
    font-size: 20px;
  }

  .admin-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .dashboard-panel {
    padding: 18px;
  }
}
</style>