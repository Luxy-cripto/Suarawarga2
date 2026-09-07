<script setup>
import { ref, computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'

const search = ref('')
const activeCategory = ref('semua')
const activeStatus = ref('semua')

const categories = [
  { key: 'semua', label: 'Semua' },
  { key: 'jalan', label: '🚧 Jalan' },
  { key: 'sampah', label: '🗑️ Sampah' },
  { key: 'lampu', label: '💡 Lampu' },
  { key: 'selokan', label: '🌊 Selokan' },
  { key: 'fasilitas', label: '🏞️ Fasilitas' },
]

const statuses = [
  { key: 'semua', label: 'Semua Status' },
  { key: 'waiting', label: 'Menunggu' },
  { key: 'processing', label: 'Diproses' },
  { key: 'success', label: 'Selesai' },
]

const reports = ref([
  {
    id: 1,
    title: 'Jalan berlubang di Jalan Melati',
    location: 'Jalan Melati',
    time: 'Dilaporkan 2 jam lalu',
    category: 'jalan',
    icon: '🚧',
    iconClass: '',
    status: 'processing',
    statusLabel: '🔵 Diproses',
  },
  {
    id: 2,
    title: 'Sampah menumpuk di sekitar pasar',
    location: 'Pasar Warga',
    time: 'Dilaporkan kemarin',
    category: 'sampah',
    icon: '🗑️',
    iconClass: 'green',
    status: 'waiting',
    statusLabel: '🟡 Menunggu',
  },
  {
    id: 3,
    title: 'Lampu penerangan jalan mati',
    location: 'Jalan Kenanga',
    time: 'Dilaporkan 2 hari lalu',
    category: 'lampu',
    icon: '💡',
    iconClass: 'yellow',
    status: 'success',
    statusLabel: '🟢 Selesai',
  },
  {
    id: 4,
    title: 'Selokan tersumbat menyebabkan genangan',
    location: 'Jalan Anggrek',
    time: 'Dilaporkan 3 hari lalu',
    category: 'selokan',
    icon: '🌊',
    iconClass: '',
    status: 'processing',
    statusLabel: '🔵 Diproses',
  },
  {
    id: 5,
    title: 'Taman bermain rusak dan berbahaya',
    location: 'Taman RW 05',
    time: 'Dilaporkan 5 hari lalu',
    category: 'fasilitas',
    icon: '🏞️',
    iconClass: '',
    status: 'waiting',
    statusLabel: '🟡 Menunggu',
  },
])

const filteredReports = computed(() => {
  return reports.value.filter((r) => {
    const matchSearch = r.title.toLowerCase().includes(search.value.toLowerCase())
    const matchCategory = activeCategory.value === 'semua' || r.category === activeCategory.value
    const matchStatus = activeStatus.value === 'semua' || r.status === activeStatus.value
    return matchSearch && matchCategory && matchStatus
  })
})
</script>

<template>
  <div class="reports-page">

    <!-- NAVBAR -->
    <Navbar />

    <!-- HEADER -->
    <section class="page-header">
      <div class="container">
        <p class="section-eyebrow">Laporan Masyarakat</p>
        <h1 class="page-title">Daftar Laporan</h1>
        <p class="page-description">
          Lihat semua laporan warga dan pantau status penanganannya.
        </p>
      </div>
    </section>

    <!-- FILTER -->
    <section class="filter-section">
      <div class="container">

        <div class="search-box">
          <span class="search-icon">🔎</span>
          <input
            v-model="search"
            type="text"
            placeholder="Cari laporan atau masalah..."
          />
        </div>

        <div class="filter-row">

          <div class="filter-tabs">
            <button
              v-for="cat in categories"
              :key="cat.key"
              class="filter-tab"
              :class="{ active: activeCategory === cat.key }"
              @click="activeCategory = cat.key"
            >
              {{ cat.label }}
            </button>
          </div>

          <select v-model="activeStatus" class="filter-select">
            <option v-for="s in statuses" :key="s.key" :value="s.key">
              {{ s.label }}
            </option>
          </select>

        </div>

      </div>
    </section>

    <!-- LIST LAPORAN -->
    <section class="section" style="padding-top: 30px;">
      <div class="container">

        <p class="results-count">
          Menampilkan {{ filteredReports.length }} laporan
        </p>

        <div class="reports-list" v-if="filteredReports.length">

          <div
            v-for="report in filteredReports"
            :key="report.id"
            class="report-card card"
          >
            <div class="report-left">
              <div class="report-icon" :class="report.iconClass">
                {{ report.icon }}
              </div>

              <div>
                <h3 class="report-card-title">{{ report.title }}</h3>
                <p class="report-location">📍 {{ report.location }}</p>
                <p class="report-time">{{ report.time }}</p>
              </div>
            </div>

            <div class="report-right">
              <span class="status" :class="`status-${report.status}`">
                {{ report.statusLabel }}
              </span>

              <button class="report-detail">Lihat detail →</button>
            </div>
          </div>

        </div>

        <!-- EMPTY STATE -->
        <div class="empty-state" v-else>
          <div class="empty-icon">🔍</div>
          <h3>Laporan tidak ditemukan</h3>
          <p>Coba ubah kata kunci atau filter pencarianmu.</p>
        </div>

        <!-- PAGINATION -->
        <div class="pagination" v-if="filteredReports.length">
          <button class="page-btn" disabled>←</button>
          <button class="page-btn active">1</button>
          <button class="page-btn">2</button>
          <button class="page-btn">3</button>
          <button class="page-btn">→</button>
        </div>

      </div>
    </section>

   <Footer />

  </div>
</template>