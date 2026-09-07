<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'

import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import api from '../services/api'

const router = useRouter()

/* =====================================================
   STATE
===================================================== */

const reports = ref([])
const kategoris = ref([])

const loadingReports = ref(true)
const loadingKategori = ref(true)

const reportError = ref('')
const searchQuery = ref('')

const user = ref(null)

/* =====================================================
   USER
===================================================== */

function loadUser() {
  try {
    user.value = JSON.parse(
      localStorage.getItem('user') || 'null'
    )
  } catch {
    user.value = null
  }
}

/* =====================================================
   CATEGORY MAP
===================================================== */

const categoryMap = {
  jalan: {
    emoji: '🚧',
    label: 'Jalan Rusak',
    class: 'orange',
    description: 'Jalan berlubang atau rusak'
  },

  sampah: {
    emoji: '🗑️',
    label: 'Sampah',
    class: 'green',
    description: 'Sampah menumpuk atau berserakan'
  },

  lampu: {
    emoji: '💡',
    label: 'Lampu Jalan',
    class: 'yellow',
    description: 'Lampu penerangan mati atau rusak'
  },

  selokan: {
    emoji: '🌊',
    label: 'Selokan',
    class: 'blue',
    description: 'Selokan tersumbat atau banjir'
  },

  fasilitas: {
    emoji: '🏞️',
    label: 'Fasilitas Umum',
    class: 'purple',
    description: 'Fasilitas umum rusak atau bermasalah'
  },

  keamanan: {
    emoji: '🛡️',
    label: 'Keamanan',
    class: 'red',
    description: 'Masalah keamanan lingkungan'
  },

  kebersihan: {
    emoji: '🧹',
    label: 'Kebersihan',
    class: 'teal',
    description: 'Masalah kebersihan lingkungan'
  },

  lainnya: {
    emoji: '📋',
    label: 'Lainnya',
    class: 'gray',
    description: 'Masalah lainnya'
  }
}

/* =====================================================
   STATUS MAP
===================================================== */

const statusMap = {
  baru: {
    label: 'Menunggu',
    icon: '🟡',
    class: 'waiting'
  },

  diproses: {
    label: 'Diproses',
    icon: '🔵',
    class: 'processing'
  },

  selesai: {
    label: 'Selesai',
    icon: '🟢',
    class: 'success'
  },

  ditolak: {
    label: 'Ditolak',
    icon: '🔴',
    class: 'rejected'
  }
}

/* =====================================================
   CATEGORY INFO
===================================================== */

function categoryInfo(kategori) {
  if (!kategori) {
    return categoryMap.lainnya
  }

  const key = String(kategori)
    .toLowerCase()
    .trim()

  if (
    key.includes('jalan') ||
    key.includes('rusak')
  ) {
    return categoryMap.jalan
  }

  if (
    key.includes('sampah') ||
    key.includes('limbah')
  ) {
    return categoryMap.sampah
  }

  if (
    key.includes('lampu') ||
    key.includes('penerangan')
  ) {
    return categoryMap.lampu
  }

  if (
    key.includes('selokan') ||
    key.includes('drainase') ||
    key.includes('banjir')
  ) {
    return categoryMap.selokan
  }

  if (
    key.includes('fasilitas') ||
    key.includes('taman') ||
    key.includes('umum')
  ) {
    return categoryMap.fasilitas
  }

  if (
    key.includes('aman') ||
    key.includes('keamanan')
  ) {
    return categoryMap.keamanan
  }

  if (
    key.includes('bersih') ||
    key.includes('kebersihan')
  ) {
    return categoryMap.kebersihan
  }

  return categoryMap.lainnya
}

/* =====================================================
   REPORT CATEGORY
===================================================== */

function getCategoryName(report) {
  return (
    report?.kategori_relasi?.nama ||
    report?.kategori ||
    'Lainnya'
  )
}

function getCategoryInfo(report) {
  return categoryInfo(
    getCategoryName(report)
  )
}

function getCategoryIcon(report) {
  return (
    report?.kategori_relasi?.icon ||
    getCategoryInfo(report).emoji
  )
}

/* =====================================================
   STATUS
===================================================== */

function statusInfo(status) {
  return (
    statusMap[status] || {
      label: status || 'Tidak diketahui',
      icon: '⚪',
      class: 'waiting'
    }
  )
}

/* =====================================================
   PHOTO URL
===================================================== */

function getPhotoUrl(report) {
  if (!report?.foto && !report?.foto_url) {
    return ''
  }

  if (report.foto_url) {
    return report.foto_url
  }

  if (
    typeof report.foto === 'string' &&
    report.foto.startsWith('http')
  ) {
    return report.foto
  }

  if (report.foto) {
    return `http://127.0.0.1:8000/storage/${report.foto}`
  }

  return ''
}

/* =====================================================
   RELATIVE TIME
===================================================== */

function formatRelativeTime(date) {
  if (!date) {
    return '-'
  }

  const now = new Date()
  const created = new Date(date)

  if (Number.isNaN(created.getTime())) {
    return '-'
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

    const data = Array.isArray(response.data)
      ? response.data
      : response.data?.data || []

    reports.value = Array.isArray(data)
      ? data
      : []


  } catch (error) {
    console.error(
      'ERROR LAPORAN:',
      error
    )

    reportError.value =
      error.response?.data?.message ||
      'Gagal mengambil laporan terbaru.'
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

    const data = Array.isArray(response.data)
      ? response.data
      : response.data?.data || []

    kategoris.value = Array.isArray(data)
      ? data
      : []
      
  } catch (error) {
    console.error(
      'ERROR KATEGORI:',
      error
    )

    kategoris.value = []
  } finally {
    loadingKategori.value = false
  }
}

/* =====================================================
   SEARCH
===================================================== */

const filteredReports = computed(() => {
  const keyword = searchQuery.value
    .trim()
    .toLowerCase()

  if (!keyword) {
    return reports.value.slice(0, 4)
  }

  return reports.value
    .filter((report) => {
      const kategori =
        report?.kategori_relasi?.nama ||
        report?.kategori ||
        ''

      return (
        report?.judul
          ?.toLowerCase()
          .includes(keyword) ||

        report?.deskripsi
          ?.toLowerCase()
          .includes(keyword) ||

        report?.lokasi
          ?.toLowerCase()
          .includes(keyword) ||

        kategori
          .toLowerCase()
          .includes(keyword)
      )
    })
    .slice(0, 10)
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
    ).length
  }
})

/* =====================================================
   NAVIGATION
===================================================== */

function goToCategory(kategori) {
  if (!kategori?.id) {
    router.push('/laporan')
    return
  }

  router.push({
    path: '/laporan',
    query: {
      kategori_id: kategori.id
    }
  })
}

function goToReports() {
  router.push('/laporan')
}

function goToCreateReport() {
  router.push('/laporan/buat')
}

/* =====================================================
   SEARCH
===================================================== */

function handleSearch() {
  const keyword = searchQuery.value.trim()

  if (!keyword) {
    return
  }

  document
    .getElementById('laporan')
    ?.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    })
}

function clearSearch() {
  searchQuery.value = ''
}

/* =====================================================
   INIT
===================================================== */

onMounted(() => {
  loadUser()
  fetchReports()
  fetchKategoris()
})
</script>

<template>
  <div class="home">

    <!-- =================================================
         NAVBAR
    ================================================== -->

    <Navbar />

    <!-- =================================================
         HERO
    ================================================== -->

    <section class="hero">

      <div class="hero-decoration hero-decoration-one"></div>
      <div class="hero-decoration hero-decoration-two"></div>
      <div class="hero-decoration hero-decoration-three"></div>

      <div class="container hero-container">

        <div class="hero-content">

          <div class="hero-badge">
            <span class="badge-dot"></span>
            <span>Platform layanan masyarakat</span>
          </div>

          <h1 class="hero-title">
            Suarakan masalah di
            <span class="brand">
              sekitarmu.
            </span>
          </h1>

          <p class="hero-description">
            Laporkan masalah lingkungan dan fasilitas
            umum dengan mudah. Pantau proses penanganannya
            secara transparan sampai selesai.
          </p>

          <div class="hero-actions">

            <button
              type="button"
              class="btn btn-primary hero-primary"
              @click="goToCreateReport"
            >
              <span>📢</span>
              Buat Laporan
            </button>

            <button
              type="button"
              class="btn btn-secondary hero-secondary"
              @click="goToReports"
            >
              <span>🔎</span>
              Lihat Laporan
            </button>

          </div>

          <div
            v-if="user"
            class="welcome-box"
          >
            <div class="welcome-avatar">
              {{ user.name?.charAt(0)?.toUpperCase() || '👤' }}
            </div>

            <div>
              <span>Selamat datang kembali</span>
              <strong>
                {{ user.name }} 👋
              </strong>
            </div>
          </div>

        </div>

        <!-- HERO VISUAL -->

        <div class="hero-visual">

          <div class="hero-card-main">

            <div class="hero-card-header">
              <div class="hero-card-icon">
                📢
              </div>

              <div>
                <span>Laporan warga</span>
                <strong>Terpantau</strong>
              </div>

              <div class="hero-check">
                ✓
              </div>
            </div>

            <div class="hero-progress">

              <div class="progress-label">
                <span>Proses penanganan</span>
                <strong>75%</strong>
              </div>

              <div class="progress-bar">
                <div class="progress-value"></div>
              </div>

            </div>

            <div class="hero-status-list">

              <div class="hero-status-item done">
                <span>✓</span>
                <div>
                  <strong>Laporan diterima</strong>
                  <small>Sudah diverifikasi</small>
                </div>
              </div>

              <div class="hero-status-item active">
                <span>⚙</span>
                <div>
                  <strong>Sedang diproses</strong>
                  <small>Dalam penanganan</small>
                </div>
              </div>

              <div class="hero-status-item">
                <span>○</span>
                <div>
                  <strong>Masalah selesai</strong>
                  <small>Menunggu penanganan</small>
                </div>
              </div>

            </div>

          </div>

          <div class="floating-card floating-card-top">
            <span>🟢</span>
            <div>
              <strong>Transparan</strong>
              <small>Status dapat dipantau</small>
            </div>
          </div>

          <div class="floating-card floating-card-bottom">
            <span>👥</span>
            <div>
              <strong>Untuk warga</strong>
              <small>Bersama membangun lingkungan</small>
            </div>
          </div>

        </div>

      </div>

    </section>

    <!-- =================================================
         SEARCH
    ================================================== -->

    <section class="search-section">

      <div class="container">

        <div class="search-card">

          <div class="search-heading">

            <div class="search-heading-icon">
              🔎
            </div>

            <div>
              <h3>
                Cari laporan
              </h3>

              <p>
                Temukan laporan berdasarkan judul,
                lokasi, atau kategori.
              </p>
            </div>

          </div>

          <div class="search-box">

            <span class="search-icon">
              🔎
            </span>

            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari laporan atau masalah..."
              @keyup.enter="handleSearch"
            />

            <button
              v-if="searchQuery"
              type="button"
              class="search-clear"
              @click="clearSearch"
            >
              ✕
            </button>

            <button
              type="button"
              class="search-button"
              @click="handleSearch"
            >
              Cari
            </button>

          </div>

        </div>

      </div>

    </section>

    <!-- =================================================
         CATEGORY
    ================================================== -->

    <section class="section category-section">

      <div class="container">

        <div class="section-header">

          <div class="section-eyebrow">
            Layanan SUARAWARGA
          </div>

          <h2 class="section-title">
            Ada masalah apa di sekitarmu?
          </h2>

          <p class="section-description">
            Pilih kategori masalah agar laporanmu
            lebih mudah diproses oleh pihak terkait.
          </p>

        </div>

        <!-- LOADING -->

        <div
          v-if="loadingKategori"
          class="category-grid"
        >

          <div
            v-for="i in 5"
            :key="i"
            class="category-card skeleton-card"
          >
            <div class="skeleton skeleton-icon"></div>
            <div class="skeleton skeleton-title"></div>
            <div class="skeleton skeleton-text"></div>
          </div>

        </div>

        <!-- DATA API -->

        <div
          v-else-if="kategoris.length"
          class="category-grid"
        >

          <button
            v-for="kategori in kategoris"
            :key="kategori.id"
            type="button"
            class="category-card"
            @click="goToCategory(kategori)"
          >

            <div
              class="category-icon"
              :class="
                categoryInfo(kategori.nama).class
              "
            >
              {{ kategori.icon || categoryInfo(kategori.nama).emoji }}
            </div>

            <div class="category-content">

              <h3>
                {{ kategori.nama }}
              </h3>

              <p>
                {{
                  kategori.deskripsi ||
                  categoryInfo(kategori.nama).description
                }}
              </p>

              <div class="category-footer">

                <span
                  v-if="kategori.laporans_count !== undefined"
                >
                  {{ kategori.laporans_count }} laporan
                </span>

                <span class="category-arrow">
                  →
                </span>

              </div>

            </div>

          </button>

        </div>

        <!-- FALLBACK -->

        <div
          v-else
          class="category-grid"
        >

          <button
            type="button"
            class="category-card"
            @click="goToReports"
          >
            <div class="category-icon orange">
              🚧
            </div>

            <div class="category-content">
              <h3>Jalan Rusak</h3>
              <p>
                Jalan berlubang atau mengalami kerusakan.
              </p>

              <div class="category-footer">
                <span>Laporkan masalah</span>
                <span class="category-arrow">→</span>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="category-card"
            @click="goToReports"
          >
            <div class="category-icon green">
              🗑️
            </div>

            <div class="category-content">
              <h3>Sampah</h3>
              <p>
                Sampah menumpuk atau belum terangkut.
              </p>

              <div class="category-footer">
                <span>Laporkan masalah</span>
                <span class="category-arrow">→</span>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="category-card"
            @click="goToReports"
          >
            <div class="category-icon yellow">
              💡
            </div>

            <div class="category-content">
              <h3>Lampu Jalan</h3>
              <p>
                Lampu penerangan mati atau rusak.
              </p>

              <div class="category-footer">
                <span>Laporkan masalah</span>
                <span class="category-arrow">→</span>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="category-card"
            @click="goToReports"
          >
            <div class="category-icon blue">
              🌊
            </div>

            <div class="category-content">
              <h3>Selokan</h3>
              <p>
                Selokan tersumbat atau menyebabkan genangan.
              </p>

              <div class="category-footer">
                <span>Laporkan masalah</span>
                <span class="category-arrow">→</span>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="category-card"
            @click="goToReports"
          >
            <div class="category-icon purple">
              🏞️
            </div>

            <div class="category-content">
              <h3>Fasilitas Umum</h3>
              <p>
                Fasilitas umum rusak atau bermasalah.
              </p>

              <div class="category-footer">
                <span>Laporkan masalah</span>
                <span class="category-arrow">→</span>
              </div>
            </div>
          </button>

        </div>

      </div>

    </section>

    <!-- =================================================
         STATISTICS
    ================================================== -->

    <section class="stats-section">

      <div class="container">

        <div class="stats-heading">

          <div>
            <div class="section-eyebrow">
              Transparansi
            </div>

            <h2>
              Perkembangan laporan masyarakat
            </h2>

            <p>
              Pantau bagaimana laporan warga
              ditangani secara terbuka.
            </p>
          </div>

          <div class="stats-live">
            <span></span>
            Data langsung
          </div>

        </div>

        <div class="stats-grid">

          <!-- TOTAL -->

          <div class="stat-card">

            <div class="stat-icon total">
              📋
            </div>

            <div class="stat-info">
              <span>Total Laporan</span>

              <strong>
                {{ statistics.total }}
              </strong>

              <small>
                Semua laporan masuk
              </small>
            </div>

          </div>

          <!-- BARU -->

          <div class="stat-card">

            <div class="stat-icon waiting">
              🕐
            </div>

            <div class="stat-info">
              <span>Menunggu</span>

              <strong>
                {{ statistics.baru }}
              </strong>

              <small>
                Menunggu penanganan
              </small>
            </div>

          </div>

          <!-- PROSES -->

          <div class="stat-card">

            <div class="stat-icon processing">
              ⚙️
            </div>

            <div class="stat-info">
              <span>Diproses</span>

              <strong>
                {{ statistics.diproses }}
              </strong>

              <small>
                Sedang ditangani
              </small>
            </div>

          </div>

          <!-- SELESAI -->

          <div class="stat-card">

            <div class="stat-icon success">
              ✓
            </div>

            <div class="stat-info">
              <span>Selesai</span>

              <strong>
                {{ statistics.selesai }}
              </strong>

              <small>
                Berhasil ditangani
              </small>
            </div>

          </div>

        </div>

      </div>

    </section>

    <!-- =================================================
         LAPORAN TERBARU
    ================================================== -->

    <section
      id="laporan"
      class="section reports-section"
    >

      <div class="container">

        <div class="reports-header">

          <div>
            <div class="section-eyebrow">
              Laporan Masyarakat
            </div>

            <h2 class="section-title">
              Laporan terbaru
            </h2>

            <p class="section-description">
              Lihat laporan yang sedang ditangani
              di lingkungan sekitar.
            </p>
          </div>

          <RouterLink
            to="/laporan"
            class="view-all-link"
          >
            Lihat semua
            <span>→</span>
          </RouterLink>

        </div>

        <div class="reports-list">

          <!-- LOADING -->

          <div
            v-if="loadingReports"
            class="report-loading"
          >

            <div class="loading-spinner"></div>

            <span>
              Memuat laporan terbaru...
            </span>

          </div>

          <!-- ERROR -->

          <div
            v-else-if="reportError"
            class="report-state"
          >

            <div class="state-icon">
              ⚠️
            </div>

            <h3>
              Gagal memuat laporan
            </h3>

            <p>
              {{ reportError }}
            </p>

            <button
              type="button"
              class="btn btn-secondary"
              @click="fetchReports"
            >
              🔄 Coba Lagi
            </button>

          </div>

          <!-- SEARCH EMPTY -->

          <div
            v-else-if="
              searchQuery &&
              filteredReports.length === 0
            "
            class="report-state"
          >

            <div class="state-icon">
              🔎
            </div>

            <h3>
              Laporan tidak ditemukan
            </h3>

            <p>
              Tidak ada laporan yang cocok dengan
              "<strong>{{ searchQuery }}</strong>".
            </p>

            <button
              type="button"
              class="btn btn-secondary"
              @click="clearSearch"
            >
              Reset Pencarian
            </button>

          </div>

          <!-- REPORT DATA -->

          <template
            v-else-if="filteredReports.length"
          >

            <article
              v-for="report in filteredReports"
              :key="report.id"
              class="report-card"
            >

              <!-- LEFT -->

              <div class="report-left">

                <div class="report-visual">

                  <img
                    v-if="getPhotoUrl(report)"
                    :src="getPhotoUrl(report)"
                    alt="Foto laporan"
                    class="report-photo"
                    @error="
                      $event.target.style.display = 'none'
                    "
                  />

                  <div
                    v-else
                    class="report-icon"
                    :class="
                      getCategoryInfo(report).class
                    "
                  >
                    {{ getCategoryIcon(report) }}
                  </div>

                </div>

                <div class="report-info">

                  <div class="report-category">
                    {{ getCategoryName(report) }}
                  </div>

                  <h3>
                    {{ report.judul }}
                  </h3>

                  <p class="report-description">
                    {{
                      report.deskripsi ||
                      'Tidak ada deskripsi laporan.'
                    }}
                  </p>

                  <div class="report-meta">

                    <span>
                      📍
                      {{
                        report.lokasi ||
                        'Lokasi tidak tersedia'
                      }}
                    </span>

                    <span class="meta-dot">
                      •
                    </span>

                    <span>
                      🕒
                      {{ formatRelativeTime(report.created_at) }}
                    </span>

                  </div>

                </div>

              </div>

              <!-- RIGHT -->

              <div class="report-right">

                <span
                  class="status"
                  :class="
                    `status-${statusInfo(report.status).class}`
                  "
                >
                  {{ statusInfo(report.status).icon }}
                  {{ statusInfo(report.status).label }}
                </span>

                <RouterLink
                  :to="`/laporan/${report.id}`"
                  class="report-detail"
                >
                  Lihat detail
                  <span>→</span>
                </RouterLink>

              </div>

            </article>

          </template>

          <!-- NO DATA -->

          <div
            v-else
            class="report-state"
          >

            <div class="state-icon">
              📋
            </div>

            <h3>
              Belum ada laporan
            </h3>

            <p>
              Jadilah warga pertama yang menyampaikan
              masalah di lingkungan sekitar.
            </p>

            <button
              type="button"
              class="btn btn-primary"
              @click="goToCreateReport"
            >
              📢 Buat Laporan
            </button>

          </div>

        </div>

      </div>

    </section>

    <!-- =================================================
         CTA
    ================================================== -->

    <section class="cta-section">

      <div class="container">

        <div class="cta-card">

          <div class="cta-decoration"></div>

          <div class="cta-content">

            <div class="cta-icon">
              📢
            </div>

            <div>

              <span class="cta-label">
                Suara kamu penting
              </span>

              <h2>
                {{
                  user
                    ? 'Ada masalah di sekitar kamu?'
                    : 'Mari ikut menjaga lingkungan bersama.'
                }}
              </h2>

              <p>
                Laporkan masalah di lingkunganmu
                dan bantu menciptakan tempat tinggal
                yang lebih nyaman untuk semua.
              </p>

            </div>

          </div>

          <button
            type="button"
            class="btn btn-white"
            @click="goToCreateReport"
          >
            📢 Buat Laporan
            <span>→</span>
          </button>

        </div>

      </div>

    </section>

    <!-- =================================================
         FOOTER
    ================================================== -->

    <Footer />

  </div>
</template>

<style scoped>

/* =====================================================
   RESET
===================================================== */

.home {
  min-height: 100vh;
  background: #f7fafc;
  color: #172b4d;
  overflow-x: hidden;
}

.container {
  width: min(1120px, calc(100% - 40px));
  margin: 0 auto;
}

/* =====================================================
   HERO
===================================================== */

.hero {
  position: relative;
  overflow: hidden;
  padding: 90px 0 125px;

  background:
    linear-gradient(
      135deg,
      #10394f 0%,
      #0d5867 48%,
      #0ca29f 100%
    );
}

.hero-container {
  position: relative;
  z-index: 2;

  display: grid;
  grid-template-columns: 1.05fr 0.95fr;

  align-items: center;

  gap: 70px;
}

.hero-decoration {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

.hero-decoration-one {
  width: 520px;
  height: 520px;

  top: -300px;
  right: -150px;

  background: rgba(255,255,255,0.06);
}

.hero-decoration-two {
  width: 400px;
  height: 400px;

  bottom: -270px;
  left: -160px;

  background: rgba(100,238,221,0.05);
}

.hero-decoration-three {
  width: 180px;
  height: 180px;

  top: 25%;
  right: 42%;

  border: 1px solid rgba(255,255,255,0.08);
}

.hero-content {
  position: relative;
  z-index: 3;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 9px;

  padding: 9px 16px;

  border: 1px solid rgba(255,255,255,0.16);
  border-radius: 999px;

  background: rgba(255,255,255,0.08);

  color: #8af1e7;

  font-size: 12px;
  font-weight: 700;

  backdrop-filter: blur(10px);
}

.badge-dot {
  width: 7px;
  height: 7px;

  border-radius: 50%;

  background: #6ce5d9;

  box-shadow:
    0 0 0 5px rgba(108,229,217,0.12);
}

.hero-title {
  max-width: 700px;

  margin: 22px 0 18px;

  color: #ffffff;

  font-size: clamp(42px, 5.2vw, 64px);

  line-height: 1.04;

  letter-spacing: -2.5px;

  font-weight: 850;
}

.hero-title .brand {
  color: #73e8dc;
}

.hero-description {
  max-width: 600px;

  margin: 0;

  color: rgba(255,255,255,0.76);

  font-size: 15px;
  line-height: 1.8;
}

.hero-actions {
  display: flex;
  align-items: center;

  gap: 12px;

  margin-top: 30px;
}

.btn {
  display: inline-flex;

  align-items: center;
  justify-content: center;

  gap: 9px;

  min-height: 44px;

  padding: 0 19px;

  border-radius: 10px;

  border: 0;

  font-family: inherit;

  font-size: 12px;
  font-weight: 750;

  text-decoration: none;

  cursor: pointer;

  transition:
    transform .2s ease,
    box-shadow .2s ease,
    background .2s ease;
}

.btn:hover {
  transform: translateY(-2px);
}

.btn-primary {
  background: #0d9d98;
  color: #ffffff;

  box-shadow:
    0 9px 22px rgba(13,157,152,.2);
}

.btn-primary:hover {
  background: #0b8f8a;
}

.hero-primary {
  min-width: 150px;
}

.btn-secondary {
  border: 1px solid #dce7eb;

  background: #ffffff;

  color: #0d8f8a;
}

.hero-secondary {
  border-color: rgba(255,255,255,.2);

  background: rgba(255,255,255,.08);

  color: #ffffff;

  backdrop-filter: blur(8px);
}

.hero-secondary:hover {
  background: rgba(255,255,255,.14);
}

.welcome-box {
  display: inline-flex;

  align-items: center;

  gap: 11px;

  margin-top: 22px;

  padding: 9px 14px 9px 9px;

  border: 1px solid rgba(255,255,255,.12);
  border-radius: 13px;

  background: rgba(255,255,255,.07);

  color: rgba(255,255,255,.72);

  backdrop-filter: blur(8px);
}

.welcome-avatar {
  display: flex;

  align-items: center;
  justify-content: center;

  width: 35px;
  height: 35px;

  border-radius: 10px;

  background: rgba(255,255,255,.15);

  color: #ffffff;

  font-size: 13px;
  font-weight: 800;
}

.welcome-box div:last-child {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.welcome-box span {
  font-size: 10px;
}

.welcome-box strong {
  color: #ffffff;
  font-size: 12px;
}

/* =====================================================
   HERO VISUAL
===================================================== */

.hero-visual {
  position: relative;

  min-height: 400px;

  display: flex;

  align-items: center;
  justify-content: center;
}

.hero-card-main {
  position: relative;
  z-index: 2;

  width: min(380px, 100%);

  padding: 23px;

  border: 1px solid rgba(255,255,255,.16);
  border-radius: 23px;

  background:
    linear-gradient(
      145deg,
      rgba(255,255,255,.14),
      rgba(255,255,255,.055)
    );

  box-shadow:
    0 28px 70px rgba(0,0,0,.18);

  backdrop-filter: blur(18px);
}

.hero-card-header {
  display: flex;
  align-items: center;

  gap: 12px;
}

.hero-card-icon {
  display: flex;

  align-items: center;
  justify-content: center;

  width: 47px;
  height: 47px;

  border-radius: 13px;

  background: rgba(255,255,255,.13);

  font-size: 21px;
}

.hero-card-header > div:nth-child(2) {
  display: flex;
  flex-direction: column;

  gap: 2px;
}

.hero-card-header span {
  color: rgba(255,255,255,.58);
  font-size: 10px;
}

.hero-card-header strong {
  color: #ffffff;
  font-size: 14px;
}

.hero-check {
  display: flex;

  align-items: center;
  justify-content: center;

  width: 27px;
  height: 27px;

  margin-left: auto;

  border-radius: 50%;

  background: rgba(109,232,220,.15);

  color: #73e8dc;

  font-size: 12px;
  font-weight: 800;
}

.hero-progress {
  margin-top: 27px;
}

.progress-label {
  display: flex;

  justify-content: space-between;

  margin-bottom: 9px;

  color: rgba(255,255,255,.65);

  font-size: 10px;
}

.progress-label strong {
  color: #73e8dc;
}

.progress-bar {
  width: 100%;
  height: 7px;

  overflow: hidden;

  border-radius: 999px;

  background: rgba(255,255,255,.1);
}

.progress-value {
  width: 75%;
  height: 100%;

  border-radius: inherit;

  background:
    linear-gradient(
      90deg,
      #4fd8cb,
      #7aeee3
    );
}

.hero-status-list {
  display: flex;
  flex-direction: column;

  gap: 16px;

  margin-top: 26px;
}

.hero-status-item {
  display: flex;

  align-items: center;

  gap: 11px;

  opacity: .5;
}

.hero-status-item > span {
  display: flex;

  align-items: center;
  justify-content: center;

  width: 27px;
  height: 27px;

  border-radius: 50%;

  background: rgba(255,255,255,.08);

  color: #ffffff;

  font-size: 10px;
}

.hero-status-item div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.hero-status-item strong {
  color: #ffffff;
  font-size: 10px;
}

.hero-status-item small {
  color: rgba(255,255,255,.45);
  font-size: 9px;
}

.hero-status-item.done,
.hero-status-item.active {
  opacity: 1;
}

.hero-status-item.done > span {
  background: rgba(82,210,148,.15);
  color: #67df9e;
}

.hero-status-item.active > span {
  background: rgba(92,179,255,.15);
  color: #70bfff;
}

.floating-card {
  position: absolute;
  z-index: 3;

  display: flex;

  align-items: center;

  gap: 9px;

  padding: 11px 13px;

  border: 1px solid rgba(255,255,255,.13);
  border-radius: 13px;

  background: rgba(255,255,255,.1);

  box-shadow:
    0 15px 35px rgba(0,0,0,.14);

  backdrop-filter: blur(13px);
}

.floating-card > span {
  font-size: 17px;
}

.floating-card div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.floating-card strong {
  color: #ffffff;
  font-size: 10px;
}

.floating-card small {
  color: rgba(255,255,255,.5);
  font-size: 8px;
}

.floating-card-top {
  top: 30px;
  right: -10px;
}

.floating-card-bottom {
  bottom: 35px;
  left: -15px;
}

/* =====================================================
   SEARCH
===================================================== */

.search-section {
  position: relative;
  z-index: 10;

  margin-top: -48px;
}

.search-card {
  display: flex;

  align-items: center;

  gap: 35px;

  padding: 21px 24px;

  border: 1px solid #e4ebef;
  border-radius: 17px;

  background: #ffffff;

  box-shadow:
    0 18px 45px rgba(24,54,74,.09);
}

.search-heading {
  display: flex;
  align-items: center;

  gap: 11px;

  min-width: 265px;
}

.search-heading-icon {
  display: flex;

  align-items: center;
  justify-content: center;

  width: 40px;
  height: 40px;

  border-radius: 11px;

  background: #eaf9f8;

  font-size: 17px;
}

.search-heading h3 {
  margin: 0;

  color: #1b3049;

  font-size: 13px;
  font-weight: 800;
}

.search-heading p {
  margin: 3px 0 0;

  color: #8a99a9;

  font-size: 9px;
  line-height: 1.4;
}

.search-box {
  position: relative;

  display: flex;

  align-items: center;

  flex: 1;
}

.search-icon {
  position: absolute;

  left: 15px;

  color: #8b9aaa;

  font-size: 15px;

  pointer-events: none;
}

.search-box input {
  width: 100%;
  height: 45px;

  padding: 0 105px 0 42px;

  box-sizing: border-box;

  border: 1px solid #dce6eb;
  border-radius: 10px;

  outline: none;

  background: #f9fbfc;

  color: #253a52;

  font-family: inherit;

  font-size: 11px;

  transition: .2s ease;
}

.search-box input:focus {
  border-color: #0d9d98;

  background: #ffffff;

  box-shadow:
    0 0 0 4px rgba(13,157,152,.07);
}

.search-clear {
  position: absolute;

  right: 77px;

  width: 24px;
  height: 24px;

  border: 0;
  border-radius: 50%;

  background: #e9eff2;

  color: #728398;

  cursor: pointer;
}

.search-button {
  position: absolute;

  right: 5px;

  height: 35px;

  padding: 0 14px;

  border: 0;
  border-radius: 8px;

  background: #0d9d98;

  color: #ffffff;

  font-family: inherit;

  font-size: 10px;
  font-weight: 750;

  cursor: pointer;
}

/* =====================================================
   SECTION
===================================================== */

.section {
  padding: 85px 0;
}

.section-header {
  margin-bottom: 38px;

  text-align: center;
}

.section-header-left {
  text-align: left;
}

.section-eyebrow {
  margin-bottom: 9px;

  color: #0d9d98;

  font-size: 10px;
  font-weight: 850;

  letter-spacing: 1.2px;

  text-transform: uppercase;
}

.section-title {
  margin: 0;

  color: #172b4d;

  font-size: clamp(25px, 3vw, 34px);

  line-height: 1.15;

  letter-spacing: -.8px;

  font-weight: 850;
}

.section-description {
  max-width: 580px;

  margin: 10px auto 0;

  color: #8796a8;

  font-size: 12px;

  line-height: 1.7;
}

/* =====================================================
   CATEGORY
===================================================== */

.category-section {
  background: #ffffff;
}

.category-grid {
  display: grid;

  grid-template-columns:
    repeat(5, minmax(0, 1fr));

  gap: 13px;
}

.category-card {
  display: flex;

  flex-direction: column;

  min-width: 0;

  padding: 19px;

  border: 1px solid #e5ecef;
  border-radius: 16px;

  background: #ffffff;

  color: inherit;

  text-align: left;

  cursor: pointer;

  box-shadow:
    0 5px 18px rgba(20,45,65,.035);

  transition:
    transform .2s ease,
    border-color .2s ease,
    box-shadow .2s ease;
}

.category-card:hover {
  transform: translateY(-5px);

  border-color: #c9e7e5;

  box-shadow:
    0 15px 30px rgba(20,45,65,.08);
}

.category-icon {
  display: flex;

  align-items: center;
  justify-content: center;

  width: 49px;
  height: 49px;

  margin-bottom: 16px;

  border-radius: 13px;

  background: #edf8fa;

  font-size: 22px;
}

.category-icon.orange {
  background: #fff2e9;
}

.category-icon.green {
  background: #edf9f0;
}

.category-icon.yellow {
  background: #fff9e7;
}

.category-icon.blue {
  background: #edf6ff;
}

.category-icon.purple {
  background: #f5efff;
}

.category-icon.red {
  background: #fff0f0;
}

.category-icon.teal {
  background: #e9faf8;
}

.category-icon.gray {
  background: #f1f4f6;
}

.category-content {
  min-width: 0;
}

.category-card h3 {
  margin: 0 0 7px;

  color: #243852;

  font-size: 13px;
  font-weight: 800;
}

.category-card p {
  min-height: 38px;

  margin: 0;

  color: #8997a7;

  font-size: 10px;

  line-height: 1.55;
}

.category-footer {
  display: flex;

  align-items: center;
  justify-content: space-between;

  margin-top: 16px;
  padding-top: 11px;

  border-top: 1px solid #edf1f3;

  color: #93a0ae;

  font-size: 9px;
}

.category-arrow {
  color: #0d9d98;

  font-size: 15px;
  font-weight: 800;

  transition: transform .2s ease;
}

.category-card:hover .category-arrow {
  transform: translateX(4px);
}

/* =====================================================
   SKELETON
===================================================== */

.skeleton-card {
  cursor: default;
}

.skeleton {
  border-radius: 8px;

  background:
    linear-gradient(
      90deg,
      #f0f3f5 25%,
      #e7ecef 50%,
      #f0f3f5 75%
    );

  background-size: 200% 100%;

  animation: skeleton 1.5s infinite;
}

.skeleton-icon {
  width: 49px;
  height: 49px;
}

.skeleton-title {
  width: 70%;
  height: 13px;

  margin-bottom: 10px;
}

.skeleton-text {
  width: 100%;
  height: 30px;
}

@keyframes skeleton {
  from {
    background-position: 200% 0;
  }

  to {
    background-position: -200% 0;
  }
}

/* =====================================================
   STATISTICS
===================================================== */

.stats-section {
  padding: 75px 0;

  background:
    linear-gradient(
      180deg,
      #f5fafb 0%,
      #eef7f8 100%
    );
}

.stats-heading {
  display: flex;

  align-items: flex-end;
  justify-content: space-between;

  gap: 30px;

  margin-bottom: 28px;
}

.stats-heading h2 {
  margin: 0;

  color: #172b4d;

  font-size: 27px;

  font-weight: 850;

  letter-spacing: -.7px;
}

.stats-heading p {
  margin: 7px 0 0;

  color: #8494a5;

  font-size: 11px;
}

.stats-live {
  display: flex;

  align-items: center;

  gap: 7px;

  padding: 7px 11px;

  border: 1px solid #d7e8e7;
  border-radius: 999px;

  background: #ffffff;

  color: #0d918c;

  font-size: 9px;
  font-weight: 750;
}

.stats-live span {
  width: 6px;
  height: 6px;

  border-radius: 50%;

  background: #35c48a;

  box-shadow:
    0 0 0 4px rgba(53,196,138,.1);
}

.stats-grid {
  display: grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap: 13px;
}

.stat-card {
  display: flex;

  align-items: center;

  gap: 14px;

  padding: 19px;

  border: 1px solid #e0e9ec;
  border-radius: 15px;

  background: #ffffff;

  box-shadow:
    0 5px 18px rgba(25,55,75,.035);
}

.stat-icon {
  display: flex;

  align-items: center;
  justify-content: center;

  width: 47px;
  height: 47px;

  flex-shrink: 0;

  border-radius: 13px;

  background: #edf8f8;

  font-size: 19px;
}

.stat-icon.waiting {
  background: #fff8e6;
}

.stat-icon.processing {
  background: #edf5ff;
}

.stat-icon.success {
  background: #ebfaf2;
}

.stat-info {
  display: flex;

  flex-direction: column;

  gap: 2px;
}

.stat-info > span {
  color: #8493a4;

  font-size: 9px;
}

.stat-number {
  color: #20364f;

  font-size: 24px;
  font-weight: 850;

  line-height: 1.1;
}

.stat-info small {
  color: #a1adb9;

  font-size: 8px;
}

/* =====================================================
   REPORTS
===================================================== */

.reports-section {
  background: #ffffff;
}

.reports-header {
  display: flex;

  align-items: flex-end;
  justify-content: space-between;

  gap: 30px;

  margin-bottom: 28px;
}

.reports-header .section-description {
  margin-left: 0;
}

.view-all-link {
  display: inline-flex;

  align-items: center;

  gap: 7px;

  color: #0d928d;

  font-size: 11px;
  font-weight: 750;

  text-decoration: none;

  white-space: nowrap;
}

.view-all-link span {
  font-size: 15px;

  transition: transform .2s ease;
}

.view-all-link:hover span {
  transform: translateX(4px);
}

.reports-list {
  display: flex;
  flex-direction: column;

  gap: 11px;
}

.report-card {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 25px;

  padding: 17px 19px;

  border: 1px solid #e5ecef;
  border-radius: 15px;

  background: #ffffff;

  box-shadow:
    0 4px 16px rgba(20,45,65,.03);

  transition:
    transform .2s ease,
    box-shadow .2s ease,
    border-color .2s ease;
}

.report-card:hover {
  transform: translateY(-2px);

  border-color: #d0e5e5;

  box-shadow:
    0 11px 25px rgba(20,45,65,.065);
}

.report-left {
  display: flex;

  align-items: center;

  gap: 14px;

  min-width: 0;

  flex: 1;
}

.report-visual {
  flex-shrink: 0;
}

.report-photo,
.report-icon {
  width: 57px;
  height: 57px;

  border-radius: 13px;
}

.report-photo {
  display: block;

  object-fit: cover;
}

.report-icon {
  display: flex;

  align-items: center;
  justify-content: center;

  background: #edf8fa;

  font-size: 23px;
}

.report-icon.orange {
  background: #fff2e9;
}

.report-icon.green {
  background: #edf9f0;
}

.report-icon.yellow {
  background: #fff9e7;
}

.report-icon.blue {
  background: #edf6ff;
}

.report-icon.purple {
  background: #f5efff;
}

.report-icon.red {
  background: #fff0f0;
}

.report-icon.teal {
  background: #e9faf8;
}

.report-icon.gray {
  background: #f1f4f6;
}

.report-info {
  min-width: 0;
}

.report-category {
  margin-bottom: 4px;

  color: #0d9d98;

  font-size: 8px;
  font-weight: 850;

  text-transform: uppercase;

  letter-spacing: .9px;
}

.report-info h3 {
  overflow: hidden;

  margin: 0 0 5px;

  color: #233850;

  font-size: 13px;
  font-weight: 800;

  line-height: 1.4;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.report-description {
  max-width: 650px;

  overflow: hidden;

  margin: 0 0 6px;

  color: #8997a6;

  font-size: 9px;

  line-height: 1.5;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.report-meta {
  display: flex;

  align-items: center;

  flex-wrap: wrap;

  gap: 6px;

  color: #98a4b0;

  font-size: 9px;
}

.meta-dot {
  color: #ccd4da;
}

.report-right {
  display: flex;

  align-items: center;

  gap: 17px;

  flex-shrink: 0;
}

.status {
  display: inline-flex;

  align-items: center;

  gap: 4px;

  min-height: 27px;

  padding: 0 9px;

  border-radius: 999px;

  font-size: 9px;
  font-weight: 750;

  white-space: nowrap;
}

.status-waiting {
  background: #fff8e3;
  color: #a67b0a;
}

.status-processing {
  background: #edf5ff;
  color: #3e79c1;
}

.status-success {
  background: #eaf9f1;
  color: #25825d;
}

.status-rejected {
  background: #fff0f0;
  color: #b54a4a;
}

.report-detail {
  display: inline-flex;

  align-items: center;

  gap: 5px;

  padding: 9px 12px;

  border: 1px solid #dce7ea;
  border-radius: 8px;

  color: #0d918c;

  font-size: 9px;
  font-weight: 750;

  text-decoration: none;

  transition: .2s ease;
}

.report-detail:hover {
  background: #effaf9;

  border-color: #bde1df;
}

.report-detail span {
  font-size: 13px;
}

/* =====================================================
   REPORT STATES
===================================================== */

.report-loading,
.report-state {
  display: flex;

  flex-direction: column;

  align-items: center;
  justify-content: center;

  min-height: 190px;

  padding: 30px;

  border: 1px solid #e5ecef;
  border-radius: 16px;

  background: #ffffff;

  text-align: center;
}

.report-loading {
  flex-direction: row;

  gap: 10px;

  color: #8493a3;

  font-size: 11px;
}

.loading-spinner {
  width: 17px;
  height: 17px;

  border: 2px solid #dcebea;
  border-top-color: #0d9d98;

  border-radius: 50%;

  animation: spin .8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.state-icon {
  margin-bottom: 11px;

  font-size: 34px;
}

.report-state h3 {
  margin: 0 0 6px;

  color: #31455e;

  font-size: 15px;
}

.report-state p {
  max-width: 440px;

  margin: 0 0 17px;

  color: #8c9aa9;

  font-size: 10px;

  line-height: 1.6;
}

/* =====================================================
   CTA
===================================================== */

.cta-section {
  padding: 0 0 85px;

  background: #ffffff;
}

.cta-card {
  position: relative;

  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 35px;

  overflow: hidden;

  padding: 35px 40px;

  border-radius: 22px;

  background:
    linear-gradient(
      135deg,
      #103c53,
      #0b7378
    );

  box-shadow:
    0 20px 45px rgba(14,75,82,.13);
}

.cta-decoration {
  position: absolute;

  width: 300px;
  height: 300px;

  right: -100px;
  top: -170px;

  border-radius: 50%;

  background: rgba(255,255,255,.06);
}

.cta-content {
  position: relative;
  z-index: 2;

  display: flex;

  align-items: center;

  gap: 17px;
}

.cta-icon {
  display: flex;

  align-items: center;
  justify-content: center;

  width: 55px;
  height: 55px;

  flex-shrink: 0;

  border-radius: 15px;

  background: rgba(255,255,255,.11);

  font-size: 24px;
}

.cta-label {
  display: block;

  margin-bottom: 5px;

  color: #72e4da;

  font-size: 9px;
  font-weight: 850;

  text-transform: uppercase;

  letter-spacing: 1px;
}

.cta-card h2 {
  margin: 0 0 7px;

  color: #ffffff;

  font-size: 23px;

  font-weight: 850;

  letter-spacing: -.5px;
}

.cta-card p {
  max-width: 540px;

  margin: 0;

  color: rgba(255,255,255,.67);

  font-size: 10px;

  line-height: 1.6;
}

.btn-white {
  position: relative;
  z-index: 2;

  flex-shrink: 0;

  background: #ffffff;

  color: #0c8f8a;

  box-shadow:
    0 10px 25px rgba(0,0,0,.1);
}

.btn-white:hover {
  background: #f4fffe;
}

.btn-white span {
  font-size: 14px;
}

/* =====================================================
   TABLET
===================================================== */

@media (max-width: 1000px) {

  .hero-container {
    grid-template-columns: 1fr;

    gap: 45px;
  }

  .hero-content {
    text-align: center;
  }

  .hero-description {
    margin: 0 auto;
  }

  .hero-actions,
  .welcome-box {
    justify-content: center;
  }

  .hero-visual {
    min-height: 370px;
  }

  .category-grid {
    grid-template-columns:
      repeat(3, 1fr);
  }

  .stats-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .search-card {
    display: block;
  }

  .search-heading {
    margin-bottom: 15px;
  }
}

/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 700px) {

  .container {
    width: calc(100% - 24px);
  }

  .hero {
    padding: 65px 0 95px;
  }

  .hero-title {
    font-size: 39px;

    letter-spacing: -1.7px;
  }

  .hero-description {
    font-size: 12px;
  }

  .hero-actions {
    flex-direction: column;

    width: 100%;
  }

  .hero-actions .btn {
    width: 100%;
  }

  .hero-visual {
    min-height: 320px;
  }

  .hero-card-main {
    width: calc(100% - 30px);

    padding: 18px;
  }

  .floating-card {
    transform: scale(.82);
  }

  .floating-card-top {
    right: -30px;
  }

  .floating-card-bottom {
    left: -35px;
  }

  .search-section {
    margin-top: -35px;
  }

  .search-card {
    padding: 17px;
  }

  .search-heading {
    min-width: 0;
  }

  .search-heading p {
    font-size: 9px;
  }

  .section {
    padding: 65px 0;
  }

  .section-title {
    font-size: 25px;
  }

  .section-description {
    font-size: 11px;
  }

  .category-grid {
    display: flex;

    overflow-x: auto;

    gap: 10px;

    padding-bottom: 7px;

    scrollbar-width: none;
  }

  .category-grid::-webkit-scrollbar {
    display: none;
  }

  .category-card {
    min-width: 205px;
  }

  .stats-section {
    padding: 60px 0;
  }

  .stats-heading {
    align-items: flex-start;

    flex-direction: column;

    gap: 13px;
  }

  .stats-heading h2 {
    font-size: 23px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    padding: 16px;
  }

  .reports-header {
    align-items: flex-start;

    flex-direction: column;

    gap: 15px;
  }

  .report-card {
    align-items: flex-start;

    flex-direction: column;

    padding: 16px;
  }

  .report-left {
    align-items: flex-start;

    width: 100%;
  }

  .report-info h3 {
    white-space: normal;
  }

  .report-description {
    white-space: normal;
  }

  .report-right {
    width: 100%;

    justify-content: space-between;

    padding-top: 13px;

    border-top: 1px solid #edf1f3;
  }

  .cta-card {
    align-items: flex-start;

    flex-direction: column;

    padding: 27px 23px;
  }

  .cta-content {
    align-items: flex-start;
  }

  .cta-card h2 {
    font-size: 20px;
  }

  .cta-card .btn {
    width: 100%;
  }
}

/* =====================================================
   SMALL MOBILE
===================================================== */

@media (max-width: 430px) {

  .hero-title {
    font-size: 34px;
  }

  .hero-visual {
    min-height: 280px;
  }

  .hero-card-main {
    width: 100%;
  }

  .floating-card {
    display: none;
  }

  .search-box input {
    padding-right: 75px;
  }

  .search-button {
    padding: 0 10px;
  }

  .report-photo,
  .report-icon {
    width: 48px;
    height: 48px;
  }

  .report-meta {
    display: block;
  }

  .meta-dot {
    display: none;
  }

  .report-right {
    gap: 8px;
  }

  .report-detail {
    padding: 8px 9px;
  }

  .cta-content {
    gap: 12px;
  }

  .cta-icon {
    width: 45px;
    height: 45px;

    font-size: 19px;
  }

}
</style>