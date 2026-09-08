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
             BANTUAN
        ========================== -->
        <div class="footer-col">
          <h3>
            Laporkan Masalah atau Beri Masukan
          </h3>

          <p>
            Jika kamu menemukan bug, error, atau ingin memberikan saran
            perbaikan, silakan gunakan tautan di bawah ini.
          </p>

          <ul class="footer-links">
            <li>
              <RouterLink
                to="/feedback"
                class="footer-report-link"
              >
                💬 Beri Masukan
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

/* ================================
   FOOTER COLUMN - LAPORAN & MASUKAN
================================ */

.footer-col {
  flex: 1;
  min-width: 250px;
}

.footer-col h3 {
  margin: 0 0 14px;
  color: #ffffff;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.4;
}

.footer-col p {
  margin: 0 0 18px;
  color: #b8c1d1;
  font-size: 14px;
  line-height: 1.7;
  max-width: 360px;
}

/* ================================
   FOOTER LINKS
================================ */

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin: 0;
  padding: 0;
}

/* ================================
   LINK BERI MASUKAN
================================ */

.footer-report-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;

  padding: 10px 16px;

  color: #ffffff;
  background: rgba(255, 255, 255, 0.08);

  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;

  text-decoration: none;
  font-size: 14px;
  font-weight: 600;

  transition:
    background 0.25s ease,
    border-color 0.25s ease,
    transform 0.25s ease,
    box-shadow 0.25s ease;
}

/* Hover */

.footer-report-link:hover {
  color: #ffffff;

  background: rgba(255, 255, 255, 0.15);

  border-color: rgba(255, 255, 255, 0.25);

  transform: translateY(-2px);

  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
}

/* Saat diklik */

.footer-report-link:active {
  transform: translateY(0);
}

/* ================================
   RESPONSIVE
================================ */

@media (max-width: 768px) {
  .footer-col {
    min-width: 100%;
  }

  .footer-col h3 {
    font-size: 17px;
  }

  .footer-col p {
    max-width: 100%;
    font-size: 13px;
  }

  .footer-report-link {
    padding: 9px 14px;
    font-size: 13px;
  }
}
</style>