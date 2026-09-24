<script setup>
import { ref, onMounted, computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import api from '@/services/api'

const openFaq = ref(null)
const laporans = ref([])
const loading = ref(true)
const testimonials = ref([])

function toggleFaq(index) {
  openFaq.value = openFaq.value === index ? null : index
}

const totalLaporan = computed(() => laporans.value.length)
const laporanSelesai = computed(() => laporans.value.filter(l => l.status === 'selesai').length)
const laporanDiproses = computed(() => laporans.value.filter(l => l.status === 'diproses').length)
const wilayahTerjangkau = computed(() => {
  const lokasiUnik = new Set(laporans.value.map(l => l.lokasi).filter(Boolean))
  return lokasiUnik.size
})

async function fetchLaporans() {
  loading.value = true
  try {
    const res = await api.get('/laporans')
    laporans.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    laporans.value = []
  } finally {
    loading.value = false
  }
}

async function fetchTestimonials() {
  try {
    const res = await api.get('/feedbacks/public')
    testimonials.value = res.data
  } catch (err) {
    testimonials.value = []
  }
}

const faqs = [
  {
    q: 'Apakah SUARAWARGA gratis digunakan?',
    a: 'Ya, sepenuhnya gratis. Warga cukup daftar akun dan bisa langsung melaporkan masalah di sekitarnya.',
  },
  {
    q: 'Berapa lama laporan saya diproses?',
    a: 'Waktu proses bervariasi tergantung jenis dan tingkat urgensi laporan, namun sebagian besar mulai ditindaklanjuti dalam 1-3 hari kerja.',
  },
  {
    q: 'Apakah identitas saya sebagai pelapor dirahasiakan?',
    a: 'Ya, data pribadi pelapor dijaga kerahasiaannya dan hanya digunakan untuk keperluan verifikasi internal.',
  },
]

onMounted(() => {
  fetchLaporans()
  fetchTestimonials()
})
</script>

<template>
  <div class="about">

    <!-- NAVBAR -->
    <Navbar />

    <!-- HERO -->
    <section class="hero">
      <div class="container">

        <div class="hero-content text-center" style="margin: 0 auto;">

          <div class="hero-badge" style="margin-left: auto; margin-right: auto;">
            💙
            <span>Tentang kami</span>
          </div>

          <h1 class="hero-title">
            Platform untuk warga,
            <span class="brand-gradient">dibangun oleh warga.</span>
          </h1>

          <p class="hero-description" style="margin-left: auto; margin-right: auto;">
            SUARAWARGA hadir untuk menjembatani suara masyarakat dengan
            pihak yang berwenang, agar setiap masalah di lingkungan sekitar
            bisa ditangani lebih cepat dan transparan.
          </p>

          <div class="hero-actions" style="justify-content: center;">
            <router-link to="/laporan" class="btn btn-primary">📢 Mulai Melapor</router-link>
            <router-link to="/register" class="btn btn-secondary">Daftar Gratis</router-link>
          </div>

        </div>

      </div>
    </section>


    <!-- MISI & VISI -->
    <section class="section">
      <div class="container">

        <div class="section-header">
          <p class="section-eyebrow">Fondasi Kami</p>
          <h2 class="section-title">Misi & Visi</h2>
        </div>

        <div class="mission-grid">

          <div class="mission-card card fade-in">
            <div class="mission-icon">🎯</div>
            <h3>Misi Kami</h3>
            <p>
              Memudahkan setiap warga melaporkan masalah lingkungan
              dan fasilitas umum, serta memastikan laporan tersebut
              diproses dengan cepat dan transparan oleh pihak terkait.
            </p>
          </div>

          <div class="mission-card card fade-in">
            <div class="mission-icon">🌱</div>
            <h3>Visi Kami</h3>
            <p>
              Menjadi platform partisipasi warga yang terpercaya,
              membantu menciptakan lingkungan yang lebih layak, aman,
              dan nyaman untuk ditinggali bersama.
            </p>
          </div>

        </div>

      </div>
    </section>


    <!-- PERJALANAN KAMI (Timeline) -->
    <section class="section" style="background: var(--surface-soft);">
      <div class="container">

        <div class="section-header">
          <p class="section-eyebrow">Perjalanan Kami</p>
          <h2 class="section-title">Dari ide sederhana, jadi gerakan bersama</h2>
        </div>

        <div class="steps-grid">

          <div class="step-card card float-in">
            <div class="step-number">🌱</div>
            <h3>Berawal dari Keresahan</h3>
            <p>
              Banyak laporan warga soal jalan rusak dan sampah yang
              lambat ditindaklanjuti karena tidak ada saluran yang jelas.
            </p>
          </div>

          <div class="step-card card float-in" style="animation-delay: 0.1s;">
            <div class="step-number">🚀</div>
            <h3>Lahirnya SUARAWARGA</h3>
            <p>
              Kami membangun platform digital agar warga bisa melapor
              dengan mudah dan pihak berwenang bisa merespons lebih cepat.
            </p>
          </div>

          <div class="step-card card float-in" style="animation-delay: 0.2s;">
            <div class="step-number">🤝</div>
            <h3>Terus Berkembang Bersama</h3>
            <p>
              Kini semakin banyak warga dan wilayah yang bergabung,
              menciptakan lingkungan yang lebih baik bersama-sama.
            </p>
          </div>

        </div>

      </div>
    </section>


    <!-- CARA KERJA -->
    <section class="section">
      <div class="container">

        <div class="section-header">
          <p class="section-eyebrow">Cara Kerja</p>
          <h2 class="section-title">Tiga langkah mudah</h2>
          <p class="section-description">
            Melaporkan masalah di sekitarmu gak perlu ribet.
          </p>
        </div>

        <div class="steps-grid">

          <div class="step-card card">
            <div class="step-number">1</div>
            <h3>Buat Laporan</h3>
            <p>
              Foto masalahnya, tulis lokasi dan deskripsi singkat,
              lalu kirim laporanmu lewat aplikasi.
            </p>
          </div>

          <div class="step-card card">
            <div class="step-number">2</div>
            <h3>Diverifikasi & Diproses</h3>
            <p>
              Tim terkait akan meninjau laporanmu dan meneruskannya
              ke pihak yang berwenang menangani.
            </p>
          </div>

          <div class="step-card card">
            <div class="step-number">3</div>
            <h3>Pantau Progresnya</h3>
            <p>
              Kamu bisa memantau status laporan secara real-time
              sampai masalahnya benar-benar selesai ditangani.
            </p>
          </div>

        </div>

      </div>
    </section>


    <!-- NILAI-NILAI -->
    <section class="section" style="background: var(--surface-soft);">
      <div class="container">

        <div class="section-header">
          <p class="section-eyebrow">Nilai Kami</p>
          <h2 class="section-title">Apa yang kami pegang teguh</h2>
        </div>

        <div class="category-grid">

          <div class="category-card">
            <div class="category-icon category-blue">🔍</div>
            <h3>Transparansi</h3>
            <p>Setiap laporan bisa dipantau prosesnya secara terbuka.</p>
          </div>

          <div class="category-card">
            <div class="category-icon category-green">⚡</div>
            <h3>Responsif</h3>
            <p>Laporan ditindaklanjuti secepat mungkin oleh pihak terkait.</p>
          </div>

          <div class="category-card">
            <div class="category-icon category-purple">🤝</div>
            <h3>Kolaboratif</h3>
            <p>Kami percaya perubahan nyata lahir dari kerja sama warga.</p>
          </div>

          <div class="category-card">
            <div class="category-icon category-orange">🔒</div>
            <h3>Aman & Terpercaya</h3>
            <p>Data dan identitas pelapor dijaga kerahasiaannya.</p>
          </div>

        </div>

      </div>
    </section>


    <!-- STATISTIK DAMPAK -->
    <section class="stats-section">
      <div class="container">

        <div class="section-header">
          <p class="section-eyebrow">Dampak</p>
          <h2 class="section-title">SUARAWARGA dalam angka</h2>
        </div>

        <p v-if="loading" class="text-center text-muted">Memuat data...</p>

        <div v-else class="stats-grid">

          <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div>
              <div class="stat-number">{{ totalLaporan }}</div>
              <div class="stat-label">Total Laporan</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon success">✓</div>
            <div>
              <div class="stat-number">{{ laporanSelesai }}</div>
              <div class="stat-label">Laporan Selesai</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon processing">⚙️</div>
            <div>
              <div class="stat-number">{{ laporanDiproses }}</div>
              <div class="stat-label">Sedang Diproses</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon waiting">🏘️</div>
            <div>
              <div class="stat-number">{{ wilayahTerjangkau }}</div>
              <div class="stat-label">Wilayah Terjangkau</div>
            </div>
          </div>

        </div>

      </div>
    </section>


    <!-- TESTIMONI -->
    <section class="section" v-if="testimonials.length > 0">
      <div class="container">

        <div class="section-header">
          <p class="section-eyebrow">Kata Mereka</p>
          <h2 class="section-title">Apa kata warga tentang kami</h2>
        </div>

        <div class="category-grid">

          <div v-for="fb in testimonials" :key="fb.id" class="card" style="padding: 28px; text-align: left;">
            <div style="font-size: 24px; margin-bottom: 12px; letter-spacing: 2px;">{{ '⭐'.repeat(fb.rating) }}</div>
            <p style="margin-bottom: 20px; font-style: italic;">"{{ fb.pesan }}"</p>
            <div style="display: flex; align-items: center; gap: 12px;">
              <div class="admin-avatar" style="width: 40px; height: 40px; font-size: 14px;">
                {{ fb.user?.name?.charAt(0).toUpperCase() }}
              </div>
              <div>
                <div style="font-weight: 700; font-size: 14px;">{{ fb.user?.name }}</div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </section>


    <!-- FAQ -->
    <section class="section" style="background: var(--surface-soft);">
      <div class="container" style="max-width: 800px;">

        <div class="section-header">
          <p class="section-eyebrow">Pertanyaan Umum</p>
          <h2 class="section-title">Yang sering ditanyakan</h2>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">

          <div
            v-for="(faq, i) in faqs"
            :key="i"
            class="card"
            style="padding: 0; cursor: pointer; overflow: hidden;"
            @click="toggleFaq(i)"
          >
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px;">
              <h3 style="font-size: 16px; margin: 0;">{{ faq.q }}</h3>
              <span style="font-size: 18px; color: var(--accent-dark); flex-shrink: 0; margin-left: 16px; transition: transform 0.2s ease;" :style="openFaq === i ? 'transform: rotate(45deg);' : ''">
                ＋
              </span>
            </div>
            <div v-if="openFaq === i" style="padding: 0 24px 20px;">
              <p class="text-muted" style="margin: 0;">{{ faq.a }}</p>
            </div>
          </div>

        </div>

      </div>
    </section>


    <!-- CTA -->
    <section class="cta-section">
      <div class="container">

        <div class="cta">

          <div>
            <h2>Yuk, jadi bagian dari perubahan.</h2>
            <p>
              Suaramu penting. Laporkan masalah di sekitarmu dan
              bantu ciptakan lingkungan yang lebih baik bersama kami.
            </p>
          </div>

          <router-link to="/laporan" class="btn btn-white">
            📢 Buat Laporan
          </router-link>

        </div>

      </div>
    </section>


    <!-- FOOTER -->
    <Footer />

  </div>
</template>