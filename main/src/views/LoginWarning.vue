<template>
  <div class="warning-page">

    <!-- Background -->
    <div class="warning-background">
      <div class="warning-blob warning-blob-1"></div>
      <div class="warning-blob warning-blob-2"></div>
    </div>

    <main class="warning-container">

      <div class="warning-card">

        <!-- Logo -->
        <RouterLink
          to="/"
          class="warning-logo"
        >
          <div class="warning-logo-icon">
            📢
          </div>

          <span>SUARAWARGA</span>
        </RouterLink>

        <!-- Icon -->
        <div class="warning-icon-wrapper">
          <div class="warning-icon">
            🔐
          </div>
        </div>

        <!-- Badge -->
        <div class="warning-badge">
          ⚠️ Akses dibatasi
        </div>

        <!-- Title -->
        <h1>
          Akun administrator terdeteksi
        </h1>

        <!-- Description -->
        <p class="warning-description">
          Akun yang kamu gunakan merupakan
          <strong>akun administrator</strong>.
          Akun admin tidak dapat digunakan melalui
          halaman login warga.
        </p>

        <!-- Information -->
        <div class="warning-info">

          <div class="warning-info-icon">
            🛡️
          </div>

          <div>
            <strong>
              Demi keamanan sistem
            </strong>

            <p>
              Silakan gunakan akses khusus
              administrator untuk masuk ke
              dashboard admin.
            </p>
          </div>

        </div>

        <!-- Back to login -->
        <button
          class="warning-button"
          @click="backToLogin"
        >
          <span>←</span>
          Kembali ke Login
        </button>

        <!-- Home -->
        <RouterLink
          to="/"
          class="warning-home"
        >
          Kembali ke Beranda
        </RouterLink>

        <!-- Footer -->
        <div class="warning-footer">
          <span>📢</span>
          SUARAWARGA
          <span>•</span>
          Layanan pengaduan masyarakat
        </div>

      </div>

    </main>

  </div>
</template>

<script setup>

import { useRouter } from 'vue-router'

const router = useRouter()

function backToLogin() {

  router.replace({
    name: 'login'
  })

}

</script>

<style scoped>

* {
  box-sizing: border-box;
}

.warning-page {
  min-height: 100vh;
  position: relative;
  overflow: hidden;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 30px 20px;

  background:
    linear-gradient(
      135deg,
      #f8fafc 0%,
      #f0fdfa 50%,
      #ecfeff 100%
    );

  font-family:
    Inter,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

/* =========================================
   BACKGROUND
========================================= */

.warning-background {
  position: absolute;
  inset: 0;

  pointer-events: none;
}

.warning-blob {
  position: absolute;

  border-radius: 50%;

  filter: blur(2px);
}

.warning-blob-1 {
  width: 420px;
  height: 420px;

  top: -180px;
  right: -120px;

  background: rgba(20, 184, 166, 0.12);
}

.warning-blob-2 {
  width: 360px;
  height: 360px;

  bottom: -180px;
  left: -120px;

  background: rgba(14, 165, 233, 0.10);
}

/* =========================================
   CONTAINER
========================================= */

.warning-container {
  width: 100%;
  max-width: 520px;

  position: relative;
  z-index: 2;
}

/* =========================================
   CARD
========================================= */

.warning-card {
  padding: 42px 42px 34px;

  background: rgba(255, 255, 255, 0.94);

  border: 1px solid rgba(226, 232, 240, 0.9);

  border-radius: 28px;

  box-shadow:
    0 25px 70px rgba(15, 23, 42, 0.10),
    0 8px 25px rgba(15, 23, 42, 0.05);

  text-align: center;

  backdrop-filter: blur(16px);

  animation: cardIn 0.45s ease;
}

/* =========================================
   LOGO
========================================= */

.warning-logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;

  margin-bottom: 28px;

  color: #0f766e;

  text-decoration: none;

  font-size: 17px;
  font-weight: 800;

  letter-spacing: 0.5px;
}

.warning-logo-icon {
  width: 40px;
  height: 40px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 12px;

  background: #f0fdfa;

  font-size: 21px;
}

/* =========================================
   WARNING ICON
========================================= */

.warning-icon-wrapper {
  width: 92px;
  height: 92px;

  margin: 0 auto 20px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #fff7ed;

  border: 8px solid #fffbeb;
}

.warning-icon {
  font-size: 38px;

  animation: pulse 2s infinite;
}

/* =========================================
   BADGE
========================================= */

.warning-badge {
  display: inline-flex;
  align-items: center;

  padding: 7px 13px;

  margin-bottom: 16px;

  border-radius: 999px;

  background: #fff7ed;

  color: #c2410c;

  font-size: 13px;
  font-weight: 700;
}

/* =========================================
   TITLE
========================================= */

.warning-card h1 {
  margin: 0;

  color: #0f172a;

  font-size: 28px;
  line-height: 1.25;
  font-weight: 800;
}

/* =========================================
   DESCRIPTION
========================================= */

.warning-description {
  max-width: 420px;

  margin: 14px auto 24px;

  color: #64748b;

  font-size: 15px;
  line-height: 1.7;
}

.warning-description strong {
  color: #334155;
}

/* =========================================
   INFO BOX
========================================= */

.warning-info {
  display: flex;
  gap: 14px;

  padding: 17px;

  margin-bottom: 24px;

  text-align: left;

  background: #f8fafc;

  border: 1px solid #e2e8f0;

  border-radius: 16px;
}

.warning-info-icon {
  width: 40px;
  height: 40px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 11px;

  background: #f0fdfa;

  font-size: 19px;
}

.warning-info strong {
  display: block;

  margin-bottom: 4px;

  color: #334155;

  font-size: 14px;
}

.warning-info p {
  margin: 0;

  color: #64748b;

  font-size: 13px;
  line-height: 1.55;
}

/* =========================================
   BUTTON
========================================= */

.warning-button {
  width: 100%;

  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;

  min-height: 50px;

  padding: 12px 20px;

  border: none;
  border-radius: 14px;

  background: #0f766e;

  color: white;

  font-size: 14px;
  font-weight: 700;

  cursor: pointer;

  transition:
    transform 0.2s ease,
    background 0.2s ease,
    box-shadow 0.2s ease;
}

.warning-button:hover {
  background: #115e59;

  transform: translateY(-2px);

  box-shadow:
    0 10px 25px rgba(15, 118, 110, 0.20);
}

.warning-button:active {
  transform: translateY(0);
}

.warning-button span {
  font-size: 18px;
}

/* =========================================
   HOME LINK
========================================= */

.warning-home {
  display: inline-block;

  margin-top: 17px;

  color: #64748b;

  text-decoration: none;

  font-size: 13px;
  font-weight: 600;

  transition: color 0.2s ease;
}

.warning-home:hover {
  color: #0f766e;
}

/* =========================================
   FOOTER
========================================= */

.warning-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;

  margin-top: 30px;

  color: #94a3b8;

  font-size: 11px;
}

/* =========================================
   ANIMATION
========================================= */

@keyframes cardIn {

  from {
    opacity: 0;
    transform: translateY(20px) scale(0.98);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }

}

@keyframes pulse {

  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.08);
  }

}

/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 600px) {

  .warning-page {
    padding: 20px 14px;
  }

  .warning-card {
    padding: 32px 22px 27px;
    border-radius: 22px;
  }

  .warning-card h1 {
    font-size: 23px;
  }

  .warning-description {
    font-size: 14px;
  }

  .warning-footer {
    flex-wrap: wrap;
  }

}

/* =========================================
   REDUCED MOTION
========================================= */

@media (prefers-reduced-motion: reduce) {

  .warning-card,
  .warning-icon {
    animation: none;
  }

  .warning-button {
    transition: none;
  }

}

</style>