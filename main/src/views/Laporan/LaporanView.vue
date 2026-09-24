<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import Navbar from '../../components/Navbar.vue'
import Footer from '../../components/Footer.vue'
import api from '../../services/api.js'

/* =====================================================
   ROUTE
===================================================== */

const route = useRoute()

/* =====================================================
   STATE
===================================================== */

const reports = ref([])
const kategoris = ref([])

const loadingReports = ref(true)
const loadingKategori = ref(true)

const reportError = ref('')
const search = ref('')

const activeCategory = ref('semua')
const activeStatus = ref('semua')

/* =====================================================
   BACKEND URL
===================================================== */

const BACKEND_URL = 'http://127.0.0.1:8000'

/* =====================================================
   CATEGORY DEFAULT
===================================================== */

const categoryMap = {
  jalan: {
    emoji: '🚧',
    label: 'Jalan Rusak',
    class: 'blue'
  },

  sampah: {
    emoji: '🗑️',
    label: 'Sampah',
    class: 'green'
  },

  lampu: {
    emoji: '💡',
    label: 'Lampu Jalan',
    class: 'yellow'
  },

  selokan: {
    emoji: '🌊',
    label: 'Selokan',
    class: 'cyan'
  },

  fasilitas: {
    emoji: '🏞️',
    label: 'Fasilitas Umum',
    class: 'purple'
  },

  lainnya: {
    emoji: '📋',
    label: 'Lainnya',
    class: ''
  }
}

/* =====================================================
   STATUS
===================================================== */

const statuses = [
  {
    key: 'semua',
    label: 'Semua Status'
  },

  {
    key: 'baru',
    label: '🟡 Menunggu'
  },

  {
    key: 'diproses',
    label: '🔵 Diproses'
  },

  {
    key: 'selesai',
    label: '🟢 Selesai'
  },

  {
    key: 'ditolak',
    label: '🔴 Ditolak'
  }
]

/* =====================================================
   CATEGORY LIST
===================================================== */

const categoryButtons = computed(() => {
  const list = [
    {
      id: null,
      key: 'semua',
      nama: 'Semua',
      icon: '📋'
    }
  ]

  kategoris.value.forEach((kategori) => {
    list.push({
      id: kategori.id,
      key: String(kategori.id),
      nama: kategori.nama,
      icon: kategori.icon || getCategoryInfo(kategori.nama).emoji
    })
  })

  return list
})

/* =====================================================
   CATEGORY INFO
===================================================== */

function getCategoryInfo(nama) {
  if (!nama) {
    return categoryMap.lainnya
  }

  const value = String(nama).toLowerCase()

  if (
    value.includes('jalan') ||
    value.includes('rusak')
  ) {
    return categoryMap.jalan
  }

  if (value.includes('sampah')) {
    return categoryMap.sampah
  }

  if (
    value.includes('lampu') ||
    value.includes('penerangan')
  ) {
    return categoryMap.lampu
  }

  if (
    value.includes('selokan') ||
    value.includes('drainase')
  ) {
    return categoryMap.selokan
  }

  if (
    value.includes('fasilitas') ||
    value.includes('umum') ||
    value.includes('taman')
  ) {
    return categoryMap.fasilitas
  }

  return categoryMap.lainnya
}

/* =====================================================
   GET CATEGORY NAME
===================================================== */

function getCategoryName(report) {
  return (
    report.kategori_relasi?.nama ||
    report.kategori ||
    'Lainnya'
  )
}

/* =====================================================
   GET CATEGORY ICON
===================================================== */

function getCategoryIcon(report) {
  return (
    report.kategori_relasi?.icon ||
    getCategoryInfo(getCategoryName(report)).emoji
  )
}

/* =====================================================
   GET CATEGORY CLASS
===================================================== */

function getCategoryClass(report) {
  return getCategoryInfo(
    getCategoryName(report)
  ).class
}

/* =====================================================
   STATUS INFO
===================================================== */

function getStatusInfo(status) {
  switch (status) {
    case 'baru':
      return {
        label: 'Menunggu',
        icon: '🟡',
        class: 'status-waiting'
      }

    case 'diproses':
      return {
        label: 'Diproses',
        icon: '🔵',
        class: 'status-processing'
      }

    case 'selesai':
      return {
        label: 'Selesai',
        icon: '🟢',
        class: 'status-success'
      }

    case 'ditolak':
      return {
        label: 'Ditolak',
        icon: '🔴',
        class: 'status-rejected'
      }

    default:
      return {
        label: 'Tidak diketahui',
        icon: '⚪',
        class: 'status-waiting'
      }
  }
}

/* =====================================================
   PHOTO URL
===================================================== */

function getPhotoUrl(report) {
  if (!report) {
    return ''
  }

  // Kalau API sudah mengirim foto_url
  if (report.foto_url) {
    return report.foto_url
  }

  // Kalau API mengirim URL lengkap
  if (
    report.foto &&
    (
      report.foto.startsWith('http://') ||
      report.foto.startsWith('https://')
    )
  ) {
    return report.foto
  }

  // Kalau hanya path storage
  if (report.foto) {
    return `${BACKEND_URL}/storage/${String(report.foto).replace(/^\/+/, '')}`
  }

  return ''
}

/* =====================================================
   FORMAT RELATIVE TIME
===================================================== */

function formatRelativeTime(date) {
  if (!date) {
    return 'Waktu tidak tersedia'
  }

  const now = new Date()
  const created = new Date(date)

  if (Number.isNaN(created.getTime())) {
    return 'Waktu tidak tersedia'
  }

  const diff = now - created

  if (diff < 0) {
    return 'Baru saja'
  }

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

  return created.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

/* =====================================================
   FETCH LAPORAN
===================================================== */

async function fetchReports() {
  loadingReports.value = true
  reportError.value = ''

  try {
    const response = await api.get('/laporans')

    let data = []

    if (Array.isArray(response.data)) {
      data = response.data
    } else if (Array.isArray(response.data?.data)) {
      data = response.data.data
    }

    reports.value = data

  } catch (error) {
    console.error('ERROR LAPORAN:', error)

    reportError.value =
      error.response?.data?.message ||
      'Gagal mengambil data laporan.'
  } finally {
    loadingReports.value = false
  }
}

/* =====================================================
   FETCH KATEGORI
===================================================== */

async function fetchKategoris() {
  loadingKategori.value = true

  try {
    const response = await api.get('/kategoris')

    let data = []

    if (Array.isArray(response.data)) {
      data = response.data
    } else if (Array.isArray(response.data?.data)) {
      data = response.data.data
    }

    kategoris.value = data

  } catch (error) {
    console.error('ERROR KATEGORI:', error)
  } finally {
    loadingKategori.value = false
  }
}

/* =====================================================
   FILTER LAPORAN
===================================================== */

const filteredReports = computed(() => {
  const keyword = search.value
    .trim()
    .toLowerCase()

  return reports.value.filter((report) => {

    /* -----------------------------
       SEARCH
    ----------------------------- */

    const categoryName =
      getCategoryName(report)

    const matchSearch =
      !keyword ||
      String(report.judul || '')
        .toLowerCase()
        .includes(keyword) ||

      String(report.deskripsi || '')
        .toLowerCase()
        .includes(keyword) ||

      String(report.lokasi || '')
        .toLowerCase()
        .includes(keyword) ||

      String(categoryName)
        .toLowerCase()
        .includes(keyword)

    /* -----------------------------
       CATEGORY
    ----------------------------- */

    const matchCategory =
      activeCategory.value === 'semua' ||
      String(report.kategori_id) ===
        String(activeCategory.value)

    /* -----------------------------
       STATUS
    ----------------------------- */

    const matchStatus =
      activeStatus.value === 'semua' ||
      report.status === activeStatus.value

    return (
      matchSearch &&
      matchCategory &&
      matchStatus
    )
  })
})

/* =====================================================
   STATISTICS
===================================================== */

const statistics = computed(() => {
  return {
    total: reports.value.length,

    baru: reports.value.filter(
      report => report.status === 'baru'
    ).length,

    diproses: reports.value.filter(
      report => report.status === 'diproses'
    ).length,

    selesai: reports.value.filter(
      report => report.status === 'selesai'
    ).length,

    ditolak: reports.value.filter(
      report => report.status === 'ditolak'
    ).length
  }
})

/* =====================================================
   SELECT CATEGORY
===================================================== */

function selectCategory(category) {
  activeCategory.value = category.key
}

/* =====================================================
   RESET FILTER
===================================================== */

function resetFilter() {
  search.value = ''
  activeCategory.value = 'semua'
  activeStatus.value = 'semua'
}

/* =====================================================
   CHECK QUERY CATEGORY
===================================================== */

function checkQueryCategory() {
  const kategoriId = route.query.kategori_id

  if (kategoriId) {
    activeCategory.value = String(kategoriId)
  }
}

/* =====================================================
   INIT
===================================================== */

onMounted(async () => {
  await Promise.all([
    fetchReports(),
    fetchKategoris()
  ])

  checkQueryCategory()
})
</script>

<template>
  <div class="reports-page">

    <!-- =================================================
         NAVBAR
    ================================================== -->

    <Navbar />

    <!-- =================================================
         HEADER
    ================================================== -->

    <section class="reports-header">

      <div class="reports-header-bg"></div>

      <div class="reports-container header-content">

        <div class="header-badge">
          📢
          <span>Laporan Masyarakat</span>
        </div>

        <h1>
          Daftar
          <span>Laporan Warga</span>
        </h1>

        <p>
          Lihat laporan masyarakat dan pantau perkembangan
          penanganan masalah di lingkungan sekitar.
        </p>

      </div>

    </section>

    <!-- =================================================
         FILTER
    ================================================== -->

    <section class="filter-wrapper">

      <div class="reports-container">

        <div class="filter-card">

          <!-- SEARCH -->

          <div class="search-wrapper">

            <span class="search-icon">
              🔎
            </span>

            <input
              v-model="search"
              type="text"
              class="search-input"
              placeholder="Cari laporan, lokasi, atau masalah..."
            />

            <button
              v-if="search"
              type="button"
              class="clear-search"
              @click="search = ''"
            >
              ✕
            </button>

          </div>

          <!-- FILTER CONTENT -->

          <div class="filter-content">

            <!-- KATEGORI -->

            <div class="category-wrapper">

              <div class="filter-title">
                Kategori
              </div>

              <div
                v-if="loadingKategori"
                class="category-list"
              >

                <button
                  v-for="i in 5"
                  :key="i"
                  type="button"
                  class="category-button"
                  disabled
                >
                  📋 Memuat...
                </button>

              </div>

              <div
                v-else
                class="category-list"
              >

                <button
                  v-for="category in categoryButtons"
                  :key="category.key"
                  type="button"
                  class="category-button"
                  :class="{
                    active:
                      activeCategory === category.key
                  }"
                  @click="selectCategory(category)"
                >
                  {{ category.icon }}
                  {{ category.nama }}
                </button>

              </div>

            </div>

            <!-- STATUS -->

            <div class="status-wrapper">

              <label
                for="status"
                class="filter-title"
              >
                Status
              </label>

              <select
                id="status"
                v-model="activeStatus"
                class="status-select"
              >

                <option
                  v-for="status in statuses"
                  :key="status.key"
                  :value="status.key"
                >
                  {{ status.label }}
                </option>

              </select>

            </div>

          </div>

        </div>

      </div>

    </section>

    <!-- =================================================
         MAIN
    ================================================== -->

    <main class="reports-main">

      <div class="reports-container">

        <!-- =================================================
             RESULT HEADER
        ================================================== -->

        <div class="result-header">

          <div>

            <h2>
              Semua Laporan
            </h2>

            <p>
              Menampilkan
              <strong>
                {{ filteredReports.length }}
              </strong>
              laporan
            </p>

          </div>

          <button
            v-if="
              search ||
              activeCategory !== 'semua' ||
              activeStatus !== 'semua'
            "
            type="button"
            class="reset-button"
            @click="resetFilter"
          >
            ↻ Reset Filter
          </button>

        </div>

        <!-- =================================================
             LOADING
        ================================================== -->

        <div
          v-if="loadingReports"
          class="loading-state"
        >

          <div class="loading-icon">
            ⏳
          </div>

          <h3>
            Memuat laporan...
          </h3>

          <p>
            Sedang mengambil laporan masyarakat.
          </p>

        </div>

        <!-- =================================================
             ERROR
        ================================================== -->

        <div
          v-else-if="reportError"
          class="empty-state error-state"
        >

          <div class="empty-icon">
            ⚠️
          </div>

          <h3>
            Gagal Memuat Laporan
          </h3>

          <p>
            {{ reportError }}
          </p>

          <button
            type="button"
            class="reset-empty-button"
            @click="fetchReports"
          >
            🔄 Coba Lagi
          </button>

        </div>

        <!-- =================================================
             REPORT LIST
        ================================================== -->

        <div
          v-else-if="filteredReports.length"
          class="reports-list"
        >

          <article
            v-for="report in filteredReports"
            :key="report.id"
            class="report-card"
          >

            <!-- LEFT -->

            <div class="report-main">

              <!-- FOTO -->

              <img
                v-if="getPhotoUrl(report)"
                :src="getPhotoUrl(report)"
                :alt="`Foto ${report.judul}`"
                class="report-photo"
                @error="$event.target.style.display = 'none'"
              />

              <!-- ICON JIKA TIDAK ADA FOTO -->

              <div
                v-else
                class="report-icon"
                :class="getCategoryClass(report)"
              >
                {{ getCategoryIcon(report) }}
              </div>

              <!-- INFO -->

              <div class="report-info">

                <div class="report-category">

                  {{ getCategoryIcon(report) }}

                  {{ getCategoryName(report) }}

                </div>

                <h3>
                  {{ report.judul }}
                </h3>

                <p class="report-description">
                  {{ report.deskripsi }}
                </p>

                <div class="report-meta">

                  <span>
                    📍
                    {{
                      report.lokasi ||
                      'Lokasi tidak tersedia'
                    }}
                  </span>

                  <span class="meta-separator">
                    •
                  </span>

                  <span>
                    🕒
                    Dilaporkan
                    {{ formatRelativeTime(report.created_at) }}
                  </span>

                </div>

              </div>

            </div>

            <!-- RIGHT -->

            <div class="report-action">

              <span
                class="status-badge"
                :class="
                  getStatusInfo(report.status).class
                "
              >
                {{
                  getStatusInfo(report.status).icon
                }}

                {{
                  getStatusInfo(report.status).label
                }}
              </span>

              <RouterLink
                :to="`/laporan/${report.id}`"
                class="detail-button"
              >
                <span>
                  Lihat detail
                </span>

                <span>
                  →
                </span>
              </RouterLink>

            </div>

          </article>

        </div>

        <!-- =================================================
             EMPTY
        ================================================== -->

        <div
          v-else
          class="empty-state"
        >

          <div class="empty-icon">
            🔍
          </div>

          <h3>
            Laporan tidak ditemukan
          </h3>

          <p>
            Tidak ada laporan yang sesuai dengan
            pencarian atau filter yang kamu pilih.
          </p>

          <button
            type="button"
            class="reset-empty-button"
            @click="resetFilter"
          >
            ↻ Reset Filter
          </button>

        </div>

        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div
          v-if="
            !loadingReports &&
            !reportError &&
            reports.length
          "
          class="report-statistics"
        >

          <div class="stat-item">

            <span class="stat-icon">
              📋
            </span>

            <div>
              <strong>
                {{ statistics.total }}
              </strong>

              <small>
                Total
              </small>
            </div>

          </div>

          <div class="stat-item">

            <span class="stat-icon waiting">
              🟡
            </span>

            <div>
              <strong>
                {{ statistics.baru }}
              </strong>

              <small>
                Menunggu
              </small>
            </div>

          </div>

          <div class="stat-item">

            <span class="stat-icon processing">
              🔵
            </span>

            <div>
              <strong>
                {{ statistics.diproses }}
              </strong>

              <small>
                Diproses
              </small>
            </div>

          </div>

          <div class="stat-item">

            <span class="stat-icon success">
              🟢
            </span>

            <div>
              <strong>
                {{ statistics.selesai }}
              </strong>

              <small>
                Selesai
              </small>
            </div>

          </div>

        </div>

      </div>

    </main>

    <!-- =================================================
         FOOTER
    ================================================== -->

    <Footer />

  </div>
</template>

<style scoped>

/* =====================================================
   BASE
===================================================== */

.reports-page {
  min-height: 100vh;
  background: #f7fafc;
  color: #172b4d;
  overflow-x: hidden;
}

.reports-container {
  width: min(1120px, calc(100% - 40px));
  margin: 0 auto;
  box-sizing: border-box;
}

/* =====================================================
   HEADER
===================================================== */

.reports-header {
  position: relative;
  min-height: 340px;

  display: flex;
  align-items: center;

  padding: 70px 0 120px;

  overflow: hidden;

  background:
    linear-gradient(
      135deg,
      #123c56 0%,
      #105568 50%,
      #1196a2 100%
    );
}

.reports-header-bg::before,
.reports-header-bg::after {
  content: "";

  position: absolute;

  border-radius: 50%;

  pointer-events: none;
}

.reports-header-bg::before {
  width: 520px;
  height: 520px;

  top: -280px;
  right: -130px;

  background: rgba(255, 255, 255, 0.055);
}

.reports-header-bg::after {
  width: 380px;
  height: 380px;

  bottom: -270px;
  left: -130px;

  background: rgba(102, 238, 220, 0.05);
}

.header-content {
  position: relative;
  z-index: 2;
  text-align: center;
}

.header-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;

  padding: 9px 17px;

  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;

  background: rgba(255, 255, 255, 0.08);

  color: #73e7dc;

  font-size: 12px;
  font-weight: 700;
}

.header-content h1 {
  margin: 20px 0 15px;

  color: #ffffff;

  font-size: clamp(36px, 5vw, 54px);

  line-height: 1.08;

  font-weight: 800;

  letter-spacing: -1.5px;
}

.header-content h1 span {
  color: #70e8dc;
}

.header-content p {
  max-width: 650px;

  margin: 0 auto;

  color: rgba(255, 255, 255, 0.78);

  font-size: 15px;
  line-height: 1.7;
}

/* =====================================================
   FILTER
===================================================== */

.filter-wrapper {
  position: relative;
  z-index: 5;

  margin-top: -70px;
}

.filter-card {
  padding: 24px;

  border: 1px solid #e3ebf0;

  border-radius: 20px;

  background: #ffffff;

  box-shadow:
    0 18px 45px rgba(25, 55, 75, 0.1),
    0 3px 10px rgba(25, 55, 75, 0.04);
}

.search-wrapper {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;

  top: 50%;
  left: 16px;

  transform: translateY(-50%);

  font-size: 17px;
}

.search-input {
  width: 100%;
  height: 52px;

  padding: 0 45px;

  box-sizing: border-box;

  border: 1px solid #dce5ec;
  border-radius: 12px;

  outline: none;

  background: #f9fbfc;

  color: #172b4d;

  font-family: inherit;
  font-size: 14px;

  transition: 0.2s ease;
}

.search-input:focus {
  border-color: #0d9d98;
  background: #ffffff;

  box-shadow:
    0 0 0 4px rgba(13, 157, 152, 0.08);
}

.clear-search {
  position: absolute;

  top: 50%;
  right: 14px;

  width: 28px;
  height: 28px;

  transform: translateY(-50%);

  border: 0;
  border-radius: 50%;

  background: #e9eff3;

  color: #65758b;

  cursor: pointer;
}

/* =====================================================
   FILTER CONTENT
===================================================== */

.filter-content {
  display: flex;

  align-items: flex-end;

  justify-content: space-between;

  gap: 30px;

  margin-top: 22px;
}

.category-wrapper {
  flex: 1;
  min-width: 0;
}

.filter-title {
  display: block;

  margin-bottom: 9px;

  color: #52657d;

  font-size: 12px;
  font-weight: 800;
}

.category-list {
  display: flex;
  flex-wrap: wrap;

  gap: 8px;
}

.category-button {
  min-height: 36px;

  padding: 0 13px;

  border: 1px solid #dfe8ed;
  border-radius: 9px;

  background: #ffffff;

  color: #617289;

  font-family: inherit;

  font-size: 12px;
  font-weight: 600;

  cursor: pointer;

  transition: all 0.2s ease;
}

.category-button:hover {
  border-color: #0d9d98;
  color: #0d8f8a;
}

.category-button.active {
  border-color: #0d9d98;

  background: #0d9d98;

  color: #ffffff;

  box-shadow:
    0 5px 13px rgba(13, 157, 152, 0.2);
}

.category-button:disabled {
  cursor: wait;
  opacity: 0.6;
}

.status-wrapper {
  width: 190px;
  flex-shrink: 0;
}

.status-select {
  width: 100%;
  height: 38px;

  padding: 0 12px;

  border: 1px solid #dfe8ed;
  border-radius: 9px;

  outline: none;

  background: #ffffff;

  color: #52657d;

  font-family: inherit;
  font-size: 12px;

  cursor: pointer;
}

/* =====================================================
   MAIN
===================================================== */

.reports-main {
  padding: 42px 0 80px;
}

.result-header {
  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 20px;

  margin-bottom: 20px;
}

.result-header h2 {
  margin: 0;

  color: #172b4d;

  font-size: 22px;
  font-weight: 800;
}

.result-header p {
  margin: 5px 0 0;

  color: #8796a9;

  font-size: 13px;
}

.result-header strong {
  color: #0d9d98;
}

.reset-button {
  padding: 9px 14px;

  border: 1px solid #dce8eb;
  border-radius: 9px;

  background: #ffffff;

  color: #0d8f8a;

  font-family: inherit;

  font-size: 12px;
  font-weight: 700;

  cursor: pointer;
}

/* =====================================================
   LOADING
===================================================== */

.loading-state {
  padding: 65px 25px;

  text-align: center;

  border: 1px solid #e4ebf0;
  border-radius: 18px;

  background: #ffffff;
}

.loading-icon {
  font-size: 40px;
}

.loading-state h3 {
  margin: 15px 0 7px;

  color: #31445d;

  font-size: 18px;
}

.loading-state p {
  margin: 0;

  color: #8a99aa;

  font-size: 13px;
}

/* =====================================================
   REPORT LIST
===================================================== */

.reports-list {
  display: flex;

  flex-direction: column;

  gap: 12px;
}

.report-card {
  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 25px;

  width: 100%;

  min-height: 115px;

  padding: 20px 22px;

  box-sizing: border-box;

  border: 1px solid #e4ebf0;
  border-radius: 16px;

  background: #ffffff;

  box-shadow:
    0 5px 18px rgba(20, 45, 65, 0.045);

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    border-color 0.2s ease;
}

.report-card:hover {
  transform: translateY(-2px);

  border-color: #d3e7e8;

  box-shadow:
    0 12px 28px rgba(20, 45, 65, 0.08);
}

.report-main {
  display: flex;

  align-items: center;

  gap: 17px;

  min-width: 0;

  flex: 1;
}

/* =====================================================
   PHOTO
===================================================== */

.report-photo {
  width: 72px;
  height: 72px;

  flex-shrink: 0;

  object-fit: cover;

  border-radius: 15px;

  border: 1px solid #e3ebf0;

  background: #f1f5f7;
}

/* =====================================================
   ICON
===================================================== */

.report-icon {
  display: flex;

  align-items: center;

  justify-content: center;

  width: 58px;
  height: 58px;

  flex-shrink: 0;

  border-radius: 15px;

  background: #edf5ff;

  font-size: 26px;
}

.report-icon.green {
  background: #effaf2;
}

.report-icon.yellow {
  background: #fff9e9;
}

.report-icon.cyan {
  background: #eafafd;
}

.report-icon.purple {
  background: #f5efff;
}

.report-icon.blue {
  background: #edf5ff;
}

/* =====================================================
   INFO
===================================================== */

.report-info {
  min-width: 0;
}

.report-category {
  margin-bottom: 4px;

  color: #0d9d98;

  font-size: 10px;

  font-weight: 800;

  text-transform: uppercase;

  letter-spacing: 0.8px;
}

.report-info h3 {
  margin: 0 0 7px;

  color: #1c304b;

  font-size: 15px;

  font-weight: 750;

  line-height: 1.4;

  word-break: break-word;
}

.report-description {
  max-width: 650px;

  margin: 0 0 8px;

  display: -webkit-box;

  line-clamp: 2;

  -webkit-line-clamp: 2;

  -webkit-box-orient: vertical;

  overflow: hidden;

  color: #8799ab;

  font-size: 12px;

  line-height: 1.5;
}

.report-meta {
  display: flex;

  align-items: center;

  flex-wrap: wrap;

  gap: 7px;

  color: #8796a9;

  font-size: 11px;
}

.meta-separator {
  color: #c1ccd5;
}

/* =====================================================
   ACTION
===================================================== */

.report-action {
  display: flex;

  align-items: center;

  gap: 18px;

  flex-shrink: 0;
}

.status-badge {
  display: inline-flex;

  align-items: center;

  min-height: 30px;

  padding: 0 11px;

  border-radius: 999px;

  font-size: 11px;

  font-weight: 700;
}

.status-processing {
  background: #edf5ff;
  color: #3978c5;
}

.status-waiting {
  background: #fff8df;
  color: #a87a00;
}

.status-success {
  background: #eafaf2;
  color: #25805c;
}

.status-rejected {
  background: #fff0f0;
  color: #c54545;
}

/* =====================================================
   DETAIL
===================================================== */

.detail-button {
  display: inline-flex;

  align-items: center;

  gap: 7px;

  padding: 10px 14px;

  border: 1px solid #dce8eb;

  border-radius: 9px;

  background: #ffffff;

  color: #0d8f8a;

  font-family: inherit;

  font-size: 11px;
  font-weight: 700;

  text-decoration: none;

  cursor: pointer;

  transition: 0.2s ease;
}

.detail-button:hover {
  background: #effaf9;

  border-color: #bde3e1;

  color: #0d8f8a;

  transform: translateY(-1px);
}

/* =====================================================
   EMPTY
===================================================== */

.empty-state {
  padding: 65px 25px;

  text-align: center;

  border: 1px dashed #d8e4e9;
  border-radius: 18px;

  background: #ffffff;
}

.empty-icon {
  font-size: 42px;
}

.empty-state h3 {
  margin: 15px 0 7px;

  color: #31445d;

  font-size: 18px;
}

.empty-state p {
  max-width: 500px;

  margin: 0 auto 18px;

  color: #8a99aa;

  font-size: 13px;

  line-height: 1.6;
}

.reset-empty-button {
  padding: 10px 17px;

  border: 0;
  border-radius: 9px;

  background: #0d9d98;

  color: #ffffff;

  font-family: inherit;

  font-size: 12px;
  font-weight: 700;

  cursor: pointer;
}

/* =====================================================
   STATISTICS
===================================================== */

.report-statistics {
  display: grid;

  grid-template-columns: repeat(4, 1fr);

  gap: 12px;

  margin-top: 22px;
}

.stat-item {
  display: flex;

  align-items: center;

  gap: 12px;

  padding: 15px;

  border: 1px solid #e4ebf0;

  border-radius: 13px;

  background: #ffffff;
}

.stat-icon {
  font-size: 22px;
}

.stat-item strong {
  display: block;

  color: #172b4d;

  font-size: 18px;
}

.stat-item small {
  display: block;

  margin-top: 2px;

  color: #8796a9;

  font-size: 11px;
}

/* =====================================================
   TABLET
===================================================== */

@media (max-width: 900px) {

  .reports-container {
    width: min(100% - 30px, 760px);
  }

  .filter-content {
    display: block;
  }

  .status-wrapper {
    width: 100%;
    margin-top: 18px;
  }

  .status-select {
    height: 42px;
  }

  .report-card {
    align-items: flex-start;
  }

  .report-action {
    flex-direction: column;

    align-items: flex-end;

    gap: 10px;
  }

  .report-statistics {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 600px) {

  .reports-container {
    width: calc(100% - 24px);
  }

  .reports-header {
    min-height: 350px;

    padding: 45px 0 100px;
  }

  .header-content h1 {
    font-size: 36px;

    letter-spacing: -1px;
  }

  .header-content p {
    font-size: 13px;
  }

  .filter-wrapper {
    margin-top: -55px;
  }

  .filter-card {
    padding: 17px;

    border-radius: 16px;
  }

  .category-list {
    flex-wrap: nowrap;

    overflow-x: auto;

    padding-bottom: 5px;

    scrollbar-width: none;
  }

  .category-list::-webkit-scrollbar {
    display: none;
  }

  .category-button {
    flex-shrink: 0;
  }

  .reports-main {
    padding-top: 30px;
  }

  .result-header {
    align-items: flex-start;

    flex-direction: column;

    margin-bottom: 16px;
  }

  .result-header h2 {
    font-size: 20px;
  }

  .reset-button {
    width: 100%;
  }

  .report-card {
    display: block;

    padding: 17px;

    border-radius: 15px;
  }

  .report-main {
    align-items: flex-start;

    gap: 12px;
  }

  .report-photo {
    width: 52px;
    height: 52px;

    border-radius: 12px;
  }

  .report-icon {
    width: 48px;
    height: 48px;

    border-radius: 12px;

    font-size: 21px;
  }

  .report-info h3 {
    font-size: 14px;
  }

  .report-description {
    font-size: 11px;
  }

  .report-meta {
    display: block;

    line-height: 1.7;
  }

  .meta-separator {
    display: none;
  }

  .report-action {
    display: flex;

    align-items: center;

    justify-content: space-between;

    flex-direction: row;

    margin-top: 15px;

    padding-top: 13px;

    border-top: 1px solid #edf1f4;
  }

  .detail-button {
    padding: 9px 12px;
  }

  .report-statistics {
    grid-template-columns: 1fr 1fr;
  }

  .stat-item {
    padding: 12px;
  }
}

</style>