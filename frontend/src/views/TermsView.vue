<template>
  <div class="page">

    <Navbar />

    <main class="legal-page">
      <div class="container">

        <h1>Syarat & Ketentuan</h1>

        <p class="intro">
          Dengan menggunakan platform SUARAWARGA, pengguna
          dianggap telah memahami dan menyetujui syarat dan
          ketentuan penggunaan layanan.
        </p>

        <section>
          <h2>1. Penggunaan Layanan</h2>

          <p>
            SUARAWARGA digunakan sebagai platform untuk
            menyampaikan laporan mengenai masalah lingkungan,
            fasilitas umum, dan kondisi pelayanan masyarakat.
          </p>
        </section>

        <section>
          <h2>2. Laporan Pengguna</h2>

          <p>
            Pengguna bertanggung jawab atas informasi yang
            disampaikan dalam laporan. Laporan harus dibuat
            berdasarkan informasi yang benar dan tidak digunakan
            untuk menyebarkan informasi palsu atau merugikan
            pihak lain.
          </p>
        </section>

        <section>
          <h2>3. Larangan</h2>

          <p>
            Pengguna dilarang menggunakan platform untuk
            menyebarkan spam, penghinaan, ancaman, konten
            ilegal, atau informasi yang sengaja dibuat untuk
            merugikan pihak lain.
          </p>
        </section>

        <section>
          <h2>4. Pengelolaan Laporan</h2>

          <p>
            Setiap laporan dapat melalui proses pemeriksaan
            sebelum ditindaklanjuti oleh pihak yang berwenang.
          </p>
        </section>

        <section>
          <h2>5. Perubahan Ketentuan</h2>

          <p>
            Syarat dan ketentuan dapat diperbarui apabila
            diperlukan untuk meningkatkan keamanan dan kualitas
            layanan SUARAWARGA.
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