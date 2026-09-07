<template>
  <div class="page">

    <Navbar />

    <main class="legal-page">
      <div class="container">

        <h1>Kebijakan Privasi</h1>

        <p class="intro">
          SUARAWARGA menghargai privasi setiap pengguna.
          Halaman ini menjelaskan bagaimana informasi pengguna
          digunakan dalam platform SUARAWARGA.
        </p>

        <section>
          <h2>1. Informasi yang Dikumpulkan</h2>

          <p>
            Kami dapat mengumpulkan informasi yang diberikan
            pengguna ketika membuat akun dan mengirimkan laporan,
            seperti nama, email, nomor telepon, serta informasi
            yang berkaitan dengan laporan.
          </p>
        </section>

        <section>
          <h2>2. Penggunaan Informasi</h2>

          <p>
            Informasi digunakan untuk mengelola akun, memproses
            laporan masyarakat, memberikan notifikasi, dan
            meningkatkan kualitas layanan SUARAWARGA.
          </p>
        </section>

        <section>
          <h2>3. Keamanan Data</h2>

          <p>
            Kami berupaya menjaga keamanan informasi pengguna
            dan mencegah akses yang tidak sah.
          </p>
        </section>

        <section>
          <h2>4. Kontak</h2>

          <p>
            Jika memiliki pertanyaan mengenai kebijakan privasi,
            silakan hubungi SUARAWARGA melalui informasi kontak
            yang tersedia di halaman website.
          </p>
        </section>

      </div>
    </main>

    <Footer />

  </div>
</template>

<script setup>
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
</script>

<style scoped>
.legal-page {
  padding: 80px 20px;
  min-height: 70vh;
}

.container {
  max-width: 900px;
  margin: auto;
}

h1 {
  font-size: 36px;
  font-weight: 800;
  margin-bottom: 20px;
}

h2 {
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 12px;
}

p {
  line-height: 1.8;
  color: #64748b;
}

section {
  margin-top: 35px;
}

.intro {
  font-size: 17px;
}
</style>