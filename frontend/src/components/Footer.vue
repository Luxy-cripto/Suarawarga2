<template>
  <footer class="footer">
    <div class="container">

      <!-- =========================
           FOOTER GRID
      ========================== -->
      <div class="footer-grid">

        <!-- =========================
             BRAND
        ========================== -->
        <div class="footer-brand">

          <RouterLink
            to="/"
            class="footer-brand-link"
          >
            <h2 class="brand-footer">
              SUARAWARGA
            </h2>
          </RouterLink>

          <p class="footer-tagline">
            Suara masyarakat, perubahan nyata.
            Platform pelaporan masalah lingkungan
            dan fasilitas umum untuk warga.
          </p>

          <!-- SOCIAL MEDIA -->
          <div class="social-links">

            <!-- Instagram -->
            <a
              href="https://instagram.com/"
              target="_blank"
              rel="noopener noreferrer"
              class="social-icon"
              aria-label="Instagram"
              title="Instagram"
            >
              📷
            </a>

            <!-- X / Twitter -->
            <a
              href="https://x.com/"
              target="_blank"
              rel="noopener noreferrer"
              class="social-icon"
              aria-label="Twitter/X"
              title="Twitter / X"
            >
              🐦
            </a>

            <!-- Facebook -->
            <a
              href="https://facebook.com/"
              target="_blank"
              rel="noopener noreferrer"
              class="social-icon"
              aria-label="Facebook"
              title="Facebook"
            >
              📘
            </a>

            <!-- Email -->
            <a
              href="mailto:suarawarga@com.id"
              class="social-icon"
              aria-label="Email"
              title="Kirim Email"
            >
              ✉️
            </a>

          </div>

        </div>


        <!-- =========================
             NAVIGASI
        ========================== -->
        <div class="footer-col">

          <h3 class="footer-heading">
            Navigasi
          </h3>

          <ul class="footer-links">

            <li>
              <RouterLink to="/">
                🏠 Beranda
              </RouterLink>
            </li>

            <li>
              <RouterLink to="/laporan">
                📋 Laporan
              </RouterLink>
            </li>

            <li>
              <RouterLink to="/about">
                ℹ️ Tentang
              </RouterLink>
            </li>

            <li>
              <RouterLink to="/login">
                🔐 Masuk
              </RouterLink>
            </li>

          </ul>

        </div>


        <!-- =========================
             LAYANAN
        ========================== -->
        <div class="footer-col">

          <h3 class="footer-heading">
            Layanan
          </h3>

          <ul class="footer-links">

            <!-- Jalan Rusak -->
            <li>
              <RouterLink
                to="/laporan?kategori_id=1"
              >
                🛣️ Jalan Rusak
              </RouterLink>
            </li>

            <!-- Sampah -->
            <li>
              <RouterLink
                to="/laporan?kategori_id=2"
              >
                🗑️ Sampah
              </RouterLink>
            </li>

            <!-- Lampu Jalan -->
            <li>
              <RouterLink
                to="/laporan?kategori_id=3"
              >
                💡 Lampu Jalan
              </RouterLink>
            </li>

            <!-- Fasilitas Umum -->
            <li>
              <RouterLink
                to="/laporan?kategori_id=4"
              >
                🏢 Fasilitas Umum
              </RouterLink>
            </li>

            <!-- Buat Laporan -->
            <li>
              <RouterLink
                to="/laporan"
                class="footer-report-link"
              >
                📢 Buat Laporan
              </RouterLink>
            </li>

          </ul>

        </div>


        <!-- =========================
             KONTAK
        ========================== -->
        <div class="footer-col">

          <h3 class="footer-heading">
            Kontak
          </h3>

          <ul class="footer-links">

            <!-- Lokasi -->
            <li>
              <a
                href="https://www.google.com/maps/search/?api=1&query=Bandung,Indonesia"
                target="_blank"
                rel="noopener noreferrer"
                title="Lihat lokasi di Google Maps"
              >
                📍 Bandung, Indonesia
              </a>
            </li>

            <!-- Email -->
            <li>
              <a
                href="mailto:suarawarga@com.id"
                title="Kirim Email"
              >
                ✉️ suarawarga@com.id
              </a>
            </li>

            <!-- Telepon -->
            <li>
              <a
                href="tel:+62211234567"
                title="Hubungi SuaraWarga"
              >
                📞 (021) 123-4567
              </a>
            </li>

            <!-- Jam -->
            <li>
              <span>
                🕐 Senin–Jumat, 08.00–17.00
              </span>
            </li>

          </ul>

        </div>

      </div>


      <!-- =========================
           FOOTER BOTTOM
      ========================== -->
      <div class="footer-bottom">

        <p>
          © {{ currentYear }} SUARAWARGA.
          Semua hak dilindungi.
        </p>

        <div class="footer-bottom-links">

          <RouterLink to="/privacy">
            Kebijakan Privasi
          </RouterLink>

          <RouterLink to="/terms">
            Syarat & Ketentuan
          </RouterLink>

        </div>

      </div>

    </div>
  </footer>
</template>


<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

/*
|--------------------------------------------------------------------------
| Tahun otomatis
|--------------------------------------------------------------------------
| Jadi tidak perlu mengubah 2026 secara manual setiap tahun.
*/
const currentYear = computed(() => {
  return new Date().getFullYear()
})
</script>


<style scoped>
/* =========================================
   FOOTER BRAND LINK
========================================= */

.footer-brand-link {
  text-decoration: none;
  display: inline-block;
}

.brand-footer {
  transition: all 0.3s ease;
}

.footer-brand-link:hover .brand-footer {
  transform: translateY(-2px);
}


/* =========================================
   SOCIAL ICON
========================================= */

.social-icon {
  text-decoration: none;
  transition:
    transform 0.3s ease,
    background 0.3s ease,
    box-shadow 0.3s ease;
}

.social-icon:hover {
  transform: translateY(-4px) scale(1.05);
}


/* =========================================
   FOOTER LINKS
========================================= */

.footer-links a {
  transition:
    color 0.25s ease,
    transform 0.25s ease;
}

.footer-links a:hover {
  transform: translateX(4px);
}


/* =========================================
   REPORT LINK
========================================= */

.footer-report-link {
  font-weight: 700;
}


/* =========================================
   BOTTOM LINKS
========================================= */

.footer-bottom-links a {
  transition: color 0.25s ease;
}

.footer-bottom-links a:hover {
  color: #0d9488;
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 768px) {

  .footer-grid {
    grid-template-columns: 1fr;
    gap: 30px;
  }

  .footer-bottom {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }

  .footer-bottom-links {
    justify-content: center;
    flex-wrap: wrap;
  }

}
</style>