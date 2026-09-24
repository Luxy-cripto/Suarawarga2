<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

// ==========================================
// NAVIGATION
// ==========================================

const goHome = () => {
  router.push('/')
}

const goBack = () => {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}
</script>

<template>
  <div class="not-found-page">

    <!-- ========================================
         BACKGROUND
    ========================================= -->
    <div class="background-decoration">
      <div class="background-circle circle-one"></div>
      <div class="background-circle circle-two"></div>
      <div class="background-grid"></div>
    </div>

    <!-- ========================================
         HEADER
    ========================================= -->
    <header class="header">

      <button
        class="brand"
        type="button"
        @click="goHome"
      >
        <div class="brand-logo">
          <span>📢</span>
        </div>

        <div class="brand-text">
          <strong>SUARAWARGA</strong>

          <span>
            Suara masyarakat, perubahan nyata.
          </span>
        </div>
      </button>

      <div class="system-status">
        <span class="status-dot"></span>
        <span>Sistem aktif</span>
      </div>

    </header>

    <!-- ========================================
         MAIN CONTENT
    ========================================= -->
    <main class="main-content">

      <!-- ======================================
           ILLUSTRATION
      ======================================= -->
      <section class="illustration-section">

        <div class="illustration-glow"></div>

        <!-- Floating information card -->
        <div class="floating-card card-location">

          <div class="floating-icon">
            📍
          </div>

          <div class="floating-text">
            <strong>Lokasi</strong>
            <span>Tidak ditemukan</span>
          </div>

        </div>

        <!-- Floating search card -->
        <div class="floating-card card-search">

          <div class="floating-icon">
            🔎
          </div>

          <div class="floating-text">
            <strong>Pencarian</strong>
            <span>Halaman kosong</span>
          </div>

        </div>

        <!-- ====================================
             CITY SCENE
        ===================================== -->
        <div class="scene">

          <!-- Sky -->
          <div class="sun">
            <span>☀</span>
          </div>

          <div class="cloud cloud-left">
            <i></i>
            <i></i>
            <i></i>
          </div>

          <div class="cloud cloud-right">
            <i></i>
            <i></i>
            <i></i>
          </div>

          <!-- Hills -->
          <div class="hill hill-back"></div>
          <div class="hill hill-front"></div>

          <!-- ==================================
               BUILDINGS
          =================================== -->
          <div class="city">

            <div class="building building-small">

              <div class="building-roof"></div>

              <div class="building-windows">
                <i></i>
                <i></i>
                <i></i>
                <i></i>
              </div>

            </div>

            <div class="building building-tall">

              <div class="building-roof"></div>

              <div class="building-windows">
                <i></i>
                <i></i>
                <i></i>
                <i></i>
                <i></i>
                <i></i>
              </div>

            </div>

            <div class="building building-middle">

              <div class="building-roof"></div>

              <div class="building-windows">
                <i></i>
                <i></i>
                <i></i>
                <i></i>
              </div>

            </div>

            <div class="building building-last">

              <div class="building-roof"></div>

              <div class="building-windows">
                <i></i>
                <i></i>
                <i></i>
              </div>

            </div>

          </div>

          <!-- ==================================
               TREES
          =================================== -->
          <div class="tree tree-left">
            <div class="tree-leaves"></div>
            <div class="tree-trunk"></div>
          </div>

          <div class="tree tree-right">
            <div class="tree-leaves"></div>
            <div class="tree-trunk"></div>
          </div>

          <!-- ==================================
               ROAD
          =================================== -->
          <div class="road">
            <div class="road-line"></div>
          </div>

          <!-- ==================================
               CONFUSED PERSON
          =================================== -->
          <div class="person">

            <!-- Head -->
            <div class="person-head">

              <div class="hair"></div>

              <div class="ear ear-left"></div>
              <div class="ear ear-right"></div>

              <!-- Eyes -->
              <div class="eye eye-left"></div>
              <div class="eye eye-right"></div>

              <!-- Nose -->
              <div class="nose"></div>

              <!-- Mouth -->
              <div class="mouth"></div>

            </div>

            <!-- Body -->
            <div class="person-body">

              <div class="shirt"></div>

              <!-- Left arm -->
              <div class="arm arm-left">
                <span class="hand"></span>
              </div>

              <!-- Right arm -->
              <div class="arm arm-right">
                <span class="hand"></span>
              </div>

            </div>

            <!-- Legs -->
            <div class="person-leg leg-left"></div>
            <div class="person-leg leg-right"></div>

          </div>

          <!-- ==================================
               404 SIGN
          =================================== -->
          <div class="sign">

            <div class="sign-board">

              <div class="sign-number">
                404
              </div>

              <div class="sign-label">
                TIDAK DITEMUKAN
              </div>

            </div>

            <div class="sign-pole"></div>

          </div>

        </div>
      </section>

      <!-- ======================================
           MESSAGE
      ======================================= -->
      <section class="message-section">

        <!-- Big number -->
        <div class="big-number">
          <span>4</span>
          <span class="zero">0</span>
          <span>4</span>
        </div>

        <!-- Badge -->
        <div class="error-badge">
          <span class="badge-icon">!</span>
          Halaman tidak ditemukan
        </div>

        <!-- Heading -->
        <h1>
          Waduh...
          <br />

          Kamu
          <strong>tersesat.</strong>
        </h1>

        <!-- Description -->
        <p class="description">
          Halaman yang kamu cari tidak tersedia.
          Mungkin alamatnya salah, sudah dipindahkan,
          atau halaman tersebut memang sudah tidak ada.
        </p>

        <!-- ====================================
             ACTION BUTTONS
        ===================================== -->
        <div class="actions">

          <button
            class="button button-primary"
            type="button"
            @click="goHome"
          >
            <span class="button-home-icon">
              ⌂
            </span>

            <span>
              Ke Beranda
            </span>

            <span class="button-arrow">
              →
            </span>
          </button>

          <button
            class="button button-secondary"
            type="button"
            @click="goBack"
          >
            <span>
              ←
            </span>

            <span>
              Kembali
            </span>
          </button>

        </div>

        <!-- ====================================
             HELP CARD
        ===================================== -->
        <div class="help-card">

          <div class="help-icon">
            📢
          </div>

          <div class="help-text">

            <strong>
              Jangan khawatir!
            </strong>

            <span>
              Kamu masih bisa menjelajahi
              layanan SUARAWARGA dari halaman utama.
            </span>

          </div>

        </div>

      </section>

    </main>

    <!-- ========================================
         FOOTER
    ========================================= -->
    <footer class="footer">

      <div class="footer-brand">
        <span class="footer-dot"></span>

        <strong>
          SUARAWARGA
        </strong>
      </div>

      <span>
        © 2026 • Suara masyarakat, perubahan nyata.
      </span>

    </footer>

  </div>
</template>

<style scoped>

/* =========================================================
   RESET
========================================================= */

* {
  box-sizing: border-box;
}

button {
  font-family: inherit;
}

/* =========================================================
   PAGE
========================================================= */

.not-found-page {
  position: relative;

  min-height: 100vh;

  display: flex;
  flex-direction: column;

  overflow: hidden;

  background:
    linear-gradient(
      135deg,
      #ffffff 0%,
      #f9fcff 45%,
      #eef6ff 100%
    );

  color: #172033;

  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

/* =========================================================
   BACKGROUND
========================================================= */

.background-decoration {
  position: absolute;
  inset: 0;

  overflow: hidden;

  pointer-events: none;
}

.background-circle {
  position: absolute;

  border-radius: 50%;
}

.circle-one {
  width: 520px;
  height: 520px;

  top: -300px;
  right: -180px;

  background: rgba(37, 99, 235, 0.07);
}

.circle-two {
  width: 430px;
  height: 430px;

  bottom: -300px;
  left: -180px;

  background: rgba(59, 130, 246, 0.055);
}

.background-grid {
  position: absolute;
  inset: 0;

  opacity: 0.3;

  background-image:
    linear-gradient(
      rgba(37, 99, 235, 0.035) 1px,
      transparent 1px
    ),
    linear-gradient(
      90deg,
      rgba(37, 99, 235, 0.035) 1px,
      transparent 1px
    );

  background-size: 42px 42px;

  mask-image:
    linear-gradient(
      to bottom,
      black,
      transparent 80%
    );
}

/* =========================================================
   HEADER
========================================================= */

.header {
  position: relative;
  z-index: 20;

  width: 100%;

  padding: 24px 6vw;

  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand {
  display: flex;
  align-items: center;

  gap: 12px;

  padding: 0;

  border: none;

  background: transparent;

  cursor: pointer;

  text-align: left;
}

.brand-logo {
  width: 46px;
  height: 46px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 14px;

  background: #2563eb;

  box-shadow:
    0 12px 28px rgba(37, 99, 235, 0.2);

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.brand:hover .brand-logo {
  transform: translateY(-2px) rotate(-3deg);

  box-shadow:
    0 15px 32px rgba(37, 99, 235, 0.25);
}

.brand-logo span {
  font-size: 21px;
}

.brand-text {
  display: flex;
  flex-direction: column;

  gap: 3px;
}

.brand-text strong {
  color: #172033;

  font-size: 15px;
  font-weight: 850;

  letter-spacing: 0.9px;
}

.brand-text span {
  color: #8491a5;

  font-size: 10px;
  font-weight: 500;
}

.system-status {
  display: flex;
  align-items: center;

  gap: 8px;

  padding: 8px 13px;

  border: 1px solid #e2e8f0;
  border-radius: 100px;

  background: rgba(255, 255, 255, 0.72);

  color: #64748b;

  font-size: 11px;
  font-weight: 650;

  backdrop-filter: blur(12px);
}

.status-dot {
  width: 7px;
  height: 7px;

  border-radius: 50%;

  background: #22c55e;

  box-shadow:
    0 0 0 4px rgba(34, 197, 94, 0.1);

  animation:
    statusPulse 2s ease-in-out infinite;
}

/* =========================================================
   MAIN
========================================================= */

.main-content {
  position: relative;
  z-index: 5;

  flex: 1;

  width: min(1200px, 90%);

  margin: 0 auto;

  display: grid;

  grid-template-columns:
    1.08fr
    0.92fr;

  align-items: center;

  gap: 60px;

  padding:
    10px
    0
    45px;
}

/* =========================================================
   ILLUSTRATION SECTION
========================================================= */

.illustration-section {
  position: relative;

  min-height: 500px;

  display: flex;
  align-items: center;
  justify-content: center;
}

.illustration-glow {
  position: absolute;

  width: 390px;
  height: 390px;

  border-radius: 50%;

  background:
    rgba(59, 130, 246, 0.09);

  filter: blur(55px);
}

.scene {
  position: relative;

  width: 550px;
  height: 450px;

  max-width: 100%;
}

/* =========================================================
   SUN
========================================================= */

.sun {
  position: absolute;

  top: 30px;
  right: 65px;

  width: 65px;
  height: 65px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #fbbf24;

  box-shadow:
    0 15px 35px rgba(251, 191, 36, 0.2);

  animation:
    sunFloat 5s ease-in-out infinite;
}

.sun span {
  color: white;

  font-size: 29px;
}

/* =========================================================
   CLOUD
========================================================= */

.cloud {
  position: absolute;

  width: 95px;
  height: 25px;

  border-radius: 30px;

  background: white;

  box-shadow:
    0 8px 25px rgba(37, 99, 235, 0.05);
}

.cloud i {
  position: absolute;

  display: block;

  border-radius: 50%;

  background: white;
}

.cloud i:nth-child(1) {
  width: 40px;
  height: 40px;

  left: 13px;
  bottom: 5px;
}

.cloud i:nth-child(2) {
  width: 32px;
  height: 32px;

  left: 45px;
  bottom: 4px;
}

.cloud i:nth-child(3) {
  width: 24px;
  height: 24px;

  right: 8px;
  bottom: 1px;
}

.cloud-left {
  top: 90px;
  left: 40px;

  animation:
    cloudMove 8s ease-in-out infinite;
}

.cloud-right {
  top: 145px;
  right: 115px;

  transform: scale(0.65);

  opacity: 0.8;

  animation:
    cloudMove 10s ease-in-out infinite reverse;
}

/* =========================================================
   HILLS
========================================================= */

.hill {
  position: absolute;

  bottom: 112px;

  border-radius:
    50% 50% 0 0;
}

.hill-back {
  left: 20px;

  width: 510px;
  height: 110px;

  background: #dbeafe;
}

.hill-front {
  left: -20px;

  width: 585px;
  height: 82px;

  background: #bfdbfe;

  opacity: 0.65;
}

/* =========================================================
   CITY
========================================================= */

.city {
  position: absolute;

  left: 45px;
  right: 45px;

  bottom: 110px;

  height: 150px;

  display: flex;
  align-items: flex-end;
  justify-content: center;

  gap: 8px;
}

.building {
  position: relative;

  display: flex;
  flex-direction: column;
  align-items: center;

  border:
    1px solid
    #cfe0f5;

  border-radius:
    5px 5px 0 0;

  background: #eff6ff;

  box-shadow:
    0 8px 20px rgba(30, 64, 175, 0.04);
}

.building-roof {
  position: absolute;

  top: -9px;

  width: 75%;
  height: 10px;

  border-radius: 5px 5px 0 0;

  background: #93c5fd;
}

.building-small {
  width: 72px;
  height: 95px;
}

.building-tall {
  width: 90px;
  height: 138px;
}

.building-middle {
  width: 74px;
  height: 115px;
}

.building-last {
  width: 63px;
  height: 83px;
}

.building-windows {
  display: grid;

  grid-template-columns:
    repeat(2, 12px);

  gap: 9px;

  margin-top: 19px;
}

.building-windows i {
  width: 12px;
  height: 14px;

  border-radius: 2px;

  background: #93c5fd;
}

/* =========================================================
   TREES
========================================================= */

.tree {
  position: absolute;

  bottom: 109px;

  z-index: 4;
}

.tree-left {
  left: 37px;
}

.tree-right {
  right: 32px;

  transform: scale(0.75);
}

.tree-leaves {
  width: 60px;
  height: 65px;

  border-radius: 50%;

  background: #60a5fa;

  box-shadow:
    18px 8px 0 #93c5fd,
    -17px 13px 0 #bfdbfe;
}

.tree-trunk {
  width: 12px;
  height: 45px;

  margin:
    -3px
    auto
    0;

  border-radius: 5px;

  background: #94a3b8;
}

/* =========================================================
   ROAD
========================================================= */

.road {
  position: absolute;

  left: 0;
  right: 0;

  bottom: 62px;

  height: 68px;

  background: #e2e8f0;

  clip-path:
    polygon(
      38% 0,
      62% 0,
      100% 100%,
      0 100%
    );
}

.road-line {
  position: absolute;

  top: 9px;
  left: 50%;

  width: 7px;
  height: 40px;

  border-radius: 10px;

  background: white;

  transform:
    translateX(-50%);
}

/* =========================================================
   PERSON
========================================================= */

.person {
  position: absolute;

  left: 168px;
  bottom: 77px;

  width: 78px;
  height: 175px;

  z-index: 8;

  transform-origin:
    center bottom;

  animation:
    confusedBody
    8s
    ease-in-out
    infinite;
}

/* -------------------------
   HEAD
-------------------------- */

.person-head {
  position: absolute;

  top: 0;
  left: 22px;

  width: 37px;
  height: 37px;

  border-radius: 50%;

  background: #f4c7a1;

  transform-origin:
    center bottom;

  animation:
    confusedHead
    8s
    ease-in-out
    infinite;
}

/* -------------------------
   HAIR
-------------------------- */

.hair {
  position: absolute;

  top: -2px;
  left: 3px;

  width: 32px;
  height: 19px;

  border-radius:
    20px
    20px
    7px
    7px;

  background: #334155;
}

/* -------------------------
   EARS
-------------------------- */

.ear {
  position: absolute;

  top: 14px;

  width: 7px;
  height: 10px;

  border-radius: 50%;

  background: #eab38e;
}

.ear-left {
  left: -4px;
}

.ear-right {
  right: -4px;
}

/* -------------------------
   EYES
-------------------------- */

.eye {
  position: absolute;

  top: 17px;

  width: 5px;
  height: 5px;

  border-radius: 50%;

  background: #172033;

  animation:
    eyeLook
    8s
    ease-in-out
    infinite;
}

.eye-left {
  left: 9px;
}

.eye-right {
  right: 9px;
}

/* -------------------------
   NOSE
-------------------------- */

.nose {
  position: absolute;

  left: 50%;
  top: 20px;

  width: 5px;
  height: 5px;

  border-radius: 50%;

  background: #d89b78;

  transform:
    translateX(-50%);
}

/* -------------------------
   MOUTH
-------------------------- */

.mouth {
  position: absolute;

  left: 50%;
  bottom: 7px;

  width: 11px;
  height: 5px;

  border-bottom:
    2px solid
    #9f624d;

  border-radius: 0 0 10px 10px;

  transform:
    translateX(-50%);

  animation:
    confusedMouth
    8s
    ease-in-out
    infinite;
}

/* -------------------------
   BODY
-------------------------- */

.person-body {
  position: absolute;

  top: 36px;
  left: 13px;

  width: 52px;
  height: 70px;
}

/* -------------------------
   SHIRT
-------------------------- */

.shirt {
  width: 100%;
  height: 100%;

  border-radius:
    18px
    18px
    9px
    9px;

  background: #2563eb;
}

/* -------------------------
   ARMS
-------------------------- */

.arm {
  position: absolute;

  top: 9px;

  width: 14px;
  height: 55px;

  border-radius: 10px;

  background: #f4c7a1;

  transform-origin:
    top center;
}

.arm-left {
  left: -10px;

  animation:
    leftArm
    8s
    ease-in-out
    infinite;
}

.arm-right {
  right: -10px;

  animation:
    rightArm
    8s
    ease-in-out
    infinite;
}

.hand {
  position: absolute;

  left: 0;
  top: -6px;

  width: 14px;
  height: 14px;

  border-radius: 50%;

  background: #f4c7a1;
}

/* =========================================================
   LEGS
========================================================= */

.person-leg {
  position: absolute;

  bottom: 0;

  width: 15px;
  height: 66px;

  border-radius: 10px;

  background: #1e3a8a;

  transform-origin:
    top center;
}

.leg-left {
  left: 21px;

  animation:
    leftLeg
    8s
    ease-in-out
    infinite;
}

.leg-right {
  right: 21px;

  animation:
    rightLeg
    8s
    ease-in-out
    infinite;
}

/* =========================================================
   SIGN
========================================================= */

.sign {
  position: absolute;

  right: 83px;
  bottom: 80px;

  width: 150px;
  height: 215px;

  z-index: 9;
}

.sign-board {
  position: absolute;

  top: 0;
  left: 0;

  width: 150px;
  height: 108px;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  border:
    4px solid
    #2563eb;

  border-radius: 16px;

  background: white;

  box-shadow:
    0 18px 38px
    rgba(37, 99, 235, 0.13);

  transform:
    rotate(-4deg);
}

.sign-number {
  color: #2563eb;

  font-size: 44px;
  font-weight: 900;

  line-height: 1;
}

.sign-label {
  margin-top: 9px;

  color: #64748b;

  font-size: 9px;
  font-weight: 850;

  letter-spacing: 1.3px;
}

.sign-pole {
  position: absolute;

  top: 98px;
  left: 70px;

  width: 9px;
  height: 117px;

  border-radius: 10px;

  background: #64748b;
}

/* =========================================================
   FLOATING CARDS
========================================================= */

.floating-card {
  position: absolute;

  z-index: 15;

  display: flex;
  align-items: center;

  gap: 10px;

  padding:
    11px
    14px;

  border:
    1px solid
    rgba(226, 232, 240, 0.9);

  border-radius: 14px;

  background:
    rgba(255, 255, 255, 0.88);

  box-shadow:
    0 15px 35px
    rgba(30, 64, 175, 0.08);

  backdrop-filter: blur(14px);
}

.card-location {
  top: 68px;
  left: 5px;

  animation:
    cardFloat
    5s
    ease-in-out
    infinite;
}

.card-search {
  right: 0;
  bottom: 83px;

  animation:
    cardFloat
    5s
    ease-in-out
    infinite
    1.5s;
}

.floating-icon {
  width: 31px;
  height: 31px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 9px;

  background: #eff6ff;

  font-size: 14px;
}

.floating-text {
  display: flex;
  flex-direction: column;

  gap: 2px;
}

.floating-text strong {
  color: #334155;

  font-size: 11px;
}

.floating-text span {
  color: #94a3b8;

  font-size: 9px;
}

/* =========================================================
   MESSAGE
========================================================= */

.message-section {
  max-width: 490px;
}

/* =========================================================
   BIG 404
========================================================= */

.big-number {
  display: flex;

  align-items: baseline;

  margin-bottom: 12px;

  color: #dbeafe;

  font-size:
    clamp(
      78px,
      9vw,
      125px
    );

  font-weight: 950;

  letter-spacing: -10px;

  line-height: 0.82;
}

.big-number .zero {
  color: #2563eb;
}

/* =========================================================
   BADGE
========================================================= */

.error-badge {
  width: fit-content;

  display: flex;
  align-items: center;

  gap: 7px;

  padding:
    7px
    11px;

  margin-bottom: 18px;

  border:
    1px solid
    #dbeafe;

  border-radius: 100px;

  background: #eff6ff;

  color: #2563eb;

  font-size: 10px;
  font-weight: 800;

  text-transform: uppercase;

  letter-spacing: 0.4px;
}

.badge-icon {
  width: 16px;
  height: 16px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #2563eb;

  color: white;

  font-size: 10px;
}

/* =========================================================
   HEADING
========================================================= */

.message-section h1 {
  margin: 0;

  color: #172033;

  font-size:
    clamp(
      38px,
      4vw,
      55px
    );

  font-weight: 850;

  line-height: 1.06;

  letter-spacing: -2px;
}

.message-section h1 strong {
  color: #2563eb;
}

/* =========================================================
   DESCRIPTION
========================================================= */

.description {
  max-width: 445px;

  margin:
    20px
    0
    29px;

  color: #64748b;

  font-size: 15px;

  line-height: 1.75;
}

/* =========================================================
   ACTIONS
========================================================= */

.actions {
  display: flex;
  align-items: center;

  gap: 11px;

  flex-wrap: wrap;
}

.button {
  height: 50px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  gap: 9px;

  padding:
    0
    18px;

  border-radius: 12px;

  font-size: 13px;
  font-weight: 750;

  cursor: pointer;

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
}

.button:hover {
  transform:
    translateY(-2px);
}

.button-primary {
  border: none;

  background: #2563eb;

  color: white;

  box-shadow:
    0 12px 25px
    rgba(37, 99, 235, 0.2);
}

.button-primary:hover {
  background: #1d4ed8;

  box-shadow:
    0 16px 32px
    rgba(37, 99, 235, 0.25);
}

.button-secondary {
  border:
    1px solid
    #dbe4ef;

  background: white;

  color: #475569;
}

.button-secondary:hover {
  background: #f8fafc;

  border-color: #cbd5e1;
}

.button-home-icon {
  font-size: 18px;
}

.button-arrow {
  margin-left: 5px;

  font-size: 17px;

  transition:
    transform 0.2s ease;
}

.button-primary:hover .button-arrow {
  transform:
    translateX(4px);
}

/* =========================================================
   HELP CARD
========================================================= */

.help-card {
  max-width: 425px;

  display: flex;
  align-items: center;

  gap: 12px;

  margin-top: 30px;

  padding:
    13px
    15px;

  border:
    1px solid
    #e4edf7;

  border-radius: 14px;

  background:
    rgba(255, 255, 255, 0.65);

  backdrop-filter: blur(10px);
}

.help-icon {
  width: 39px;
  height: 39px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 10px;

  background: #eff6ff;

  font-size: 17px;
}

.help-text {
  display: flex;
  flex-direction: column;

  gap: 3px;
}

.help-text strong {
  color: #334155;

  font-size: 11px;
}

.help-text span {
  color: #94a3b8;

  font-size: 10px;

  line-height: 1.5;
}

/* =========================================================
   FOOTER
========================================================= */

.footer {
  position: relative;
  z-index: 10;

  width: 100%;

  display: flex;
  align-items: center;
  justify-content: space-between;

  padding:
    18px
    6vw;

  border-top:
    1px solid
    rgba(226, 232, 240, 0.7);

  color: #94a3b8;

  font-size: 10px;
}

.footer-brand {
  display: flex;
  align-items: center;

  gap: 7px;

  color: #64748b;
}

.footer-dot {
  width: 6px;
  height: 6px;

  border-radius: 50%;

  background: #2563eb;
}

/* =========================================================
   ANIMATIONS
========================================================= */

/*
   Karakter:
   0 - 15%   : normal
   15 - 30%  : lihat kiri
   30 - 45%  : kembali
   45 - 60%  : lihat kanan
   60 - 70%  : lihat papan
   70 - 82%  : garuk kepala
   82 - 90%  : angkat bahu
   90 - 100% : kembali normal
*/

/* -------------------------
   BODY
-------------------------- */

@keyframes confusedBody {

  0%,
  10% {
    transform:
      rotate(0deg)
      translateY(0);
  }

  18% {
    transform:
      rotate(-3deg)
      translateY(0);
  }

  28% {
    transform:
      rotate(-3deg)
      translateY(0);
  }

  36% {
    transform:
      rotate(0deg)
      translateY(0);
  }

  45% {
    transform:
      rotate(3deg)
      translateY(0);
  }

  56% {
    transform:
      rotate(3deg)
      translateY(0);
  }

  63% {
    transform:
      rotate(2deg)
      translateY(-1px);
  }

  70% {
    transform:
      rotate(-2deg)
      translateY(0);
  }

  76% {
    transform:
      rotate(2deg)
      translateY(-2px);
  }

  82% {
    transform:
      rotate(-2deg)
      translateY(0);
  }

  88% {
    transform:
      rotate(1deg)
      translateY(0);
  }

  100% {
    transform:
      rotate(0deg)
      translateY(0);
  }
}

/* -------------------------
   HEAD
-------------------------- */

@keyframes confusedHead {

  0%,
  10% {
    transform:
      rotate(0deg);
  }

  /* lihat kiri */
  18%,
  28% {
    transform:
      rotate(-15deg);
  }

  /* kembali */
  36% {
    transform:
      rotate(0deg);
  }

  /* lihat kanan */
  45%,
  56% {
    transform:
      rotate(15deg);
  }

  /* lihat papan */
  63% {
    transform:
      rotate(9deg);
  }

  /* bingung */
  70% {
    transform:
      rotate(-7deg);
  }

  76% {
    transform:
      rotate(7deg);
  }

  /* kembali */
  88%,
  100% {
    transform:
      rotate(0deg);
  }
}

/* -------------------------
   EYES
-------------------------- */

@keyframes eyeLook {

  0%,
  10% {
    transform:
      translateX(0);
  }

  /* kiri */
  18%,
  28% {
    transform:
      translateX(-5px);
  }

  /* tengah */
  36% {
    transform:
      translateX(0);
  }

  /* kanan */
  45%,
  56% {
    transform:
      translateX(5px);
  }

  /* papan */
  63%,
  70% {
    transform:
      translateX(3px);
  }

  /* bingung */
  76% {
    transform:
      translateX(-3px);
  }

  82% {
    transform:
      translateX(3px);
  }

  90%,
  100% {
    transform:
      translateX(0);
  }
}

/* -------------------------
   MOUTH
-------------------------- */

@keyframes confusedMouth {

  0%,
  10% {
    width: 11px;
    height: 5px;
  }

  18%,
  28% {
    width: 8px;
    height: 4px;
  }

  45%,
  56% {
    width: 8px;
    height: 4px;
  }

  /* mulut sedikit terbuka */
  63% {
    width: 12px;
    height: 8px;
  }

  /* bingung */
  70%,
  82% {
    width: 10px;
    height: 5px;
  }

  90%,
  100% {
    width: 11px;
    height: 5px;
  }
}

/* -------------------------
   LEFT ARM
-------------------------- */

@keyframes leftArm {

  0%,
  10% {
    transform:
      rotate(13deg);
  }

  /* lihat kiri */
  18%,
  28% {
    transform:
      rotate(7deg);
  }

  36% {
    transform:
      rotate(13deg);
  }

  /* lihat kanan */
  45%,
  56% {
    transform:
      rotate(18deg);
  }

  /* bingung */
  63% {
    transform:
      rotate(25deg);
  }

  70% {
    transform:
      rotate(15deg);
  }

  /* bahu */
  76%,
  82% {
    transform:
      rotate(28deg);
  }

  90%,
  100% {
    transform:
      rotate(13deg);
  }
}

/* -------------------------
   RIGHT ARM
-------------------------- */

@keyframes rightArm {

  0%,
  56% {
    transform:
      rotate(-15deg);
  }

  /* mulai naik */
  63% {
    transform:
      rotate(-45deg);
  }

  /* menuju kepala */
  70% {
    transform:
      rotate(-85deg);
  }

  /* garuk kepala */
  76%,
  82% {
    transform:
      rotate(-125deg);
  }

  /* selesai garuk */
  86% {
    transform:
      rotate(-95deg);
  }

  90% {
    transform:
      rotate(-55deg);
  }

  100% {
    transform:
      rotate(-15deg);
  }
}

/* -------------------------
   LEFT LEG
-------------------------- */

@keyframes leftLeg {

  0%,
  60% {
    transform:
      rotate(4deg);
  }

  70% {
    transform:
      rotate(7deg);
  }

  78% {
    transform:
      rotate(2deg);
  }

  88% {
    transform:
      rotate(5deg);
  }

  100% {
    transform:
      rotate(4deg);
  }
}

/* -------------------------
   RIGHT LEG
-------------------------- */

@keyframes rightLeg {

  0%,
  60% {
    transform:
      rotate(-4deg);
  }

  70% {
    transform:
      rotate(-7deg);
  }

  78% {
    transform:
      rotate(-2deg);
  }

  88% {
    transform:
      rotate(-5deg);
  }

  100% {
    transform:
      rotate(-4deg);
  }
}

/* -------------------------
   CARDS
-------------------------- */

@keyframes cardFloat {

  0%,
  100% {
    transform:
      translateY(0);
  }

  50% {
    transform:
      translateY(-9px);
  }
}

/* -------------------------
   SUN
-------------------------- */

@keyframes sunFloat {

  0%,
  100% {
    transform:
      translateY(0);
  }

  50% {
    transform:
      translateY(-7px);
  }
}

/* -------------------------
   CLOUD
-------------------------- */

@keyframes cloudMove {

  0%,
  100% {
    margin-left: 0;
  }

  50% {
    margin-left: 13px;
  }
}

/* -------------------------
   STATUS
-------------------------- */

@keyframes statusPulse {

  0%,
  100% {
    box-shadow:
      0 0 0 4px
      rgba(34, 197, 94, 0.1);
  }

  50% {
    box-shadow:
      0 0 0 7px
      rgba(34, 197, 94, 0.04);
  }
}

/* =========================================================
   TABLET
========================================================= */

@media (max-width: 950px) {

  .main-content {
    grid-template-columns: 1fr;

    gap: 5px;

    padding-top: 0;
  }

  .illustration-section {
    min-height: 420px;
  }

  .scene {
    transform:
      scale(0.86);
  }

  .message-section {
    max-width: 620px;

    margin: 0 auto;

    text-align: center;
  }

  .error-badge {
    margin-left: auto;
    margin-right: auto;
  }

  .description {
    margin-left: auto;
    margin-right: auto;
  }

  .actions {
    justify-content: center;
  }

  .help-card {
    margin-left: auto;
    margin-right: auto;

    text-align: left;
  }
}

/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

  .header {
    padding:
      18px
      5%;
  }

  .brand-logo {
    width: 42px;
    height: 42px;

    border-radius: 12px;
  }

  .brand-text span {
    display: none;
  }

  .system-status {
    padding:
      7px
      10px;
  }

  .system-status span:last-child {
    display: none;
  }

  .main-content {
    width: 92%;

    padding-bottom: 25px;
  }

  .illustration-section {
    min-height: 285px;
  }

  .scene {
    transform:
      scale(0.59);
  }

  .floating-card {
    transform:
      scale(0.8);
  }

  .card-location {
    left: -15px;
    top: 45px;
  }

  .card-search {
    right: -15px;
    bottom: 35px;
  }

  .big-number {
    font-size: 82px;

    letter-spacing: -6px;
  }

  .message-section h1 {
    font-size: 36px;

    letter-spacing: -1.5px;
  }

  .description {
    font-size: 14px;

    line-height: 1.7;
  }

  .actions {
    flex-direction: column;

    width: 100%;
  }

  .button {
    width: 100%;
  }

  .help-card {
    margin-top: 23px;
  }

  .footer {
    flex-direction: column;

    gap: 8px;

    text-align: center;
  }
}

/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 400px) {

  .illustration-section {
    min-height: 245px;
  }

  .scene {
    transform:
      scale(0.49);
  }

  .floating-card {
    display: none;
  }

  .big-number {
    font-size: 70px;
  }

  .message-section h1 {
    font-size: 31px;
  }

  .help-card {
    padding: 11px;
  }
}

/* =========================================================
   REDUCED MOTION
========================================================= */

@media (prefers-reduced-motion: reduce) {

  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    scroll-behavior: auto !important;
  }
}

</style>