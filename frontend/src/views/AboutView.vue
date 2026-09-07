<script setup>
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
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
            <span class="brand">dibangun oleh warga.</span>
          </h1>

          <p class="hero-description" style="margin-left: auto; margin-right: auto;">
            SUARAWARGA hadir untuk menjembatani suara masyarakat dengan
            pihak yang berwenang, agar setiap masalah di lingkungan sekitar
            bisa ditangani lebih cepat dan transparan.
          </p>

        </div>

      </div>
    </section>


    <!-- MISI & VISI -->
    <section class="section">
      <div class="container">

        <div class="mission-grid">

          <div class="mission-card card">
            <div class="mission-icon">🎯</div>
            <h3>Misi Kami</h3>
            <p>
              Memudahkan setiap warga melaporkan masalah lingkungan
              dan fasilitas umum, serta memastikan laporan tersebut
              diproses dengan cepat dan transparan oleh pihak terkait.
            </p>
          </div>

          <div class="mission-card card">
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


    <!-- CARA KERJA -->
    <section class="section" style="background: var(--surface-soft);">
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
    <section class="section">
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

        <div class="stats-grid">

          <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div>
              <div class="stat-number">128</div>
              <div class="stat-label">Total Laporan</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon success">✓</div>
            <div>
              <div class="stat-number">73</div>
              <div class="stat-label">Laporan Selesai</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon processing">⚙️</div>
            <div>
              <div class="stat-number">31</div>
              <div class="stat-label">Sedang Diproses</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon waiting">🏘️</div>
            <div>
              <div class="stat-number">15+</div>
              <div class="stat-label">Wilayah Terjangkau</div>
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

          <button class="btn btn-white">
            📢 Buat Laporan
          </button>

        </div>

      </div>
    </section>


    <!-- FOOTER -->
    <Footer />

  </div>
</template>