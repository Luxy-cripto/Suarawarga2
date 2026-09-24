<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const user = computed(() => {
  try {
    return JSON.parse(
      sessionStorage.getItem('user') || 'null'
    )
  } catch {
    return null
  }
})

const role = computed(() => user.value?.role || null)

const pageInfo = computed(() => {
  if (role.value === 'petugas') {
    return {
      icon: '🛡️',
      eyebrow: 'AKSES PETUGAS',
      title: 'Area administrator tidak tersedia.',
      description:
        'Akun petugas memiliki akses khusus untuk membantu menangani laporan masyarakat. Dashboard administrator hanya dapat diakses oleh akun admin.',
      label: 'Akun Petugas'
    }
  }

  if (role.value === 'warga') {
    return {
      icon: '🔐',
      eyebrow: 'AKSES TERBATAS',
      title: 'Halaman ini khusus administrator.',
      description:
        'Akun warga tidak memiliki izin untuk membuka area administrator. Kamu tetap bisa menggunakan layanan SUARAWARGA seperti membuat dan memantau laporan.',
      label: 'Akun Warga'
    }
  }

  return {
    icon: '🔒',
    eyebrow: 'AKSES DITOLAK',
    title: 'Kamu tidak memiliki izin.',
    description:
      'Halaman yang kamu coba buka membutuhkan hak akses tertentu. Silakan kembali ke halaman sebelumnya atau masuk menggunakan akun yang sesuai.',
    label: 'Akses Terbatas'
  }
})

function goHome() {
  router.push('/')
}

function goBack() {
  if (window.history.state?.back) {
    router.back()
  } else {
    router.push('/')
  }
}

function goLogin() {
  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')

  router.push('/login')
}
</script>

<template>
  <div class="unauthorized-page">

    <!-- BACKGROUND -->
    <div class="background-shape shape-one"></div>
    <div class="background-shape shape-two"></div>
    <div class="background-grid"></div>

    <!-- HEADER -->
    <header class="page-header">

      <button
        type="button"
        class="brand"
        @click="goHome"
      >
        <div class="brand-logo">
          📢
        </div>

        <div class="brand-text">
          <strong>SUARAWARGA</strong>
          <span>
            Suara masyarakat, perubahan nyata.
          </span>
        </div>
      </button>

      <div class="secure-badge">
        <span class="secure-dot"></span>
        Sistem aman
      </div>

    </header>

    <!-- MAIN -->
    <main class="main-content">

      <!-- LEFT -->
      <section class="message-section">

        <div class="eyebrow">
          <span class="eyebrow-icon">!</span>
          {{ pageInfo.eyebrow }}
        </div>

        <h1>
          {{ pageInfo.title }}
        </h1>

        <p class="description">
          {{ pageInfo.description }}
        </p>

        <!-- USER INFO -->
        <div
          v-if="user"
          class="user-card"
        >
          <div class="avatar">
            {{
              user.name?.charAt(0)?.toUpperCase() || 'U'
            }}
          </div>

          <div class="user-info">
            <span>Kamu masuk sebagai</span>

            <strong>
              {{ user.name || 'Pengguna' }}
            </strong>

            <small>
              {{ user.email || '-' }}
            </small>
          </div>

          <div class="role-badge">
            {{ pageInfo.label }}
          </div>
        </div>

        <!-- ACTION -->
        <div class="actions">

          <button
            type="button"
            class="button button-primary"
            @click="goHome"
          >
            <span>⌂</span>
            <span>Ke Beranda</span>
            <span class="arrow">→</span>
          </button>

          <button
            type="button"
            class="button button-secondary"
            @click="goBack"
          >
            <span>←</span>
            <span>Kembali</span>
          </button>

        </div>

        <!-- LOGIN -->
        <button
          v-if="!user"
          type="button"
          class="login-link"
          @click="goLogin"
        >
          🔐 Masuk dengan akun lain
        </button>

      </section>

      <!-- RIGHT ILLUSTRATION -->
      <section class="illustration-section">

        <div class="illustration-card">

          <!-- TOP LABEL -->
          <div class="card-label">
            <span class="card-label-dot"></span>
            ACCESS CONTROL
          </div>

          <!-- LOCK -->
          <div class="lock-scene">

            <div class="circle circle-back"></div>
            <div class="circle circle-middle"></div>
            <div class="circle circle-front"></div>

            <div class="lock-shadow"></div>

            <div class="lock">

              <div class="lock-shackle">
                <div class="shackle-inner"></div>
              </div>

              <div class="lock-body">

                <div class="lock-hole">
                  <span></span>
                </div>

              </div>

            </div>

            <!-- WARNING -->
            <div class="warning-card warning-left">
              <span>🔒</span>

              <div>
                <strong>Protected</strong>
                <small>Area terbatas</small>
              </div>
            </div>

            <div class="warning-card warning-right">
              <span>🛡️</span>

              <div>
                <strong>Secure</strong>
                <small>Akses terkontrol</small>
              </div>
            </div>

          </div>

          <!-- BOTTOM INFO -->
          <div class="security-info">

            <div class="security-icon">
              ✓
            </div>

            <div>
              <strong>
                Akses tetap aman
              </strong>

              <span>
                SUARAWARGA melindungi setiap
                area berdasarkan hak akses pengguna.
              </span>
            </div>

          </div>

        </div>

      </section>

    </main>

    <!-- FOOTER -->
    <footer class="footer">

      <div class="footer-brand">
        <span class="footer-dot"></span>
        <strong>SUARAWARGA</strong>
      </div>

      <span>
        © 2026 • Suara masyarakat, perubahan nyata.
      </span>

    </footer>

  </div>
</template>

<style scoped>
/* =====================================================
   RESET
===================================================== */

* {
  box-sizing: border-box;
}

button {
  font-family: inherit;
}

/* =====================================================
   PAGE
===================================================== */

.unauthorized-page {
  position: relative;
  min-height: 100vh;
  overflow: hidden;

  background:
    radial-gradient(
      circle at 85% 15%,
      rgba(255, 190, 120, 0.13),
      transparent 28%
    ),
    radial-gradient(
      circle at 10% 85%,
      rgba(19, 151, 143, 0.08),
      transparent 28%
    ),
    #f7f8f6;

  color: #26352f;
}

/* =====================================================
   BACKGROUND
===================================================== */

.background-shape {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

.shape-one {
  width: 480px;
  height: 480px;

  top: -300px;
  right: -170px;

  background:
    rgba(222, 174, 104, 0.08);
}

.shape-two {
  width: 420px;
  height: 420px;

  bottom: -280px;
  left: -190px;

  background:
    rgba(19, 151, 143, 0.06);
}

.background-grid {
  position: absolute;
  inset: 0;

  opacity: 0.25;

  background-image:
    linear-gradient(
      rgba(40, 55, 48, 0.035) 1px,
      transparent 1px
    ),
    linear-gradient(
      90deg,
      rgba(40, 55, 48, 0.035) 1px,
      transparent 1px
    );

  background-size: 45px 45px;

  mask-image:
    linear-gradient(
      to bottom,
      black,
      transparent 85%
    );

  pointer-events: none;
}

/* =====================================================
   HEADER
===================================================== */

.page-header {
  position: relative;
  z-index: 5;

  display: flex;
  align-items: center;
  justify-content: space-between;

  width: min(
    1180px,
    calc(100% - 48px)
  );

  margin: 0 auto;
  padding: 26px 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: 11px;

  padding: 0;

  border: 0;
  background: transparent;

  color: inherit;

  text-align: left;
  cursor: pointer;
}

.brand-logo {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 43px;
  height: 43px;

  border-radius: 13px;

  background:
    linear-gradient(
      145deg,
      #143e3b,
      #176d66
    );

  color: white;

  font-size: 19px;

  box-shadow:
    0 9px 22px rgba(20, 70, 65, 0.15);
}

.brand-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.brand-text strong {
  color: #26352f;

  font-size: 13px;
  font-weight: 900;

  letter-spacing: 1px;
}

.brand-text span {
  color: #8a968f;

  font-size: 8px;
}

/* =====================================================
   SYSTEM BADGE
===================================================== */

.secure-badge {
  display: flex;
  align-items: center;
  gap: 8px;

  padding: 8px 13px;

  border: 1px solid #e3e8e4;
  border-radius: 999px;

  background: rgba(255, 255, 255, 0.72);

  color: #68766f;

  font-size: 9px;
  font-weight: 750;

  backdrop-filter: blur(10px);
}

.secure-dot {
  width: 7px;
  height: 7px;

  border-radius: 50%;

  background: #39b879;

  box-shadow:
    0 0 0 4px rgba(57, 184, 121, 0.1);
}

/* =====================================================
   MAIN
===================================================== */

.main-content {
  position: relative;
  z-index: 2;

  display: grid;
  grid-template-columns:
    0.9fr 1.1fr;

  align-items: center;

  gap: 70px;

  width: min(
    1080px,
    calc(100% - 48px)
  );

  min-height: calc(100vh - 145px);

  margin: 0 auto;
  padding: 25px 0 65px;
}

/* =====================================================
   MESSAGE
===================================================== */

.message-section {
  position: relative;
  z-index: 3;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;

  padding: 8px 12px;

  border: 1px solid #eadfcf;
  border-radius: 999px;

  background: #fffaf2;

  color: #9b713d;

  font-size: 9px;
  font-weight: 850;

  letter-spacing: 0.9px;
}

.eyebrow-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 17px;
  height: 17px;

  border-radius: 50%;

  background: #e6b56c;

  color: white;

  font-size: 10px;
}

.message-section h1 {
  max-width: 600px;

  margin: 20px 0 16px;

  color: #26352f;

  font-size: clamp(
    39px,
    5vw,
    59px
  );

  line-height: 1.02;

  letter-spacing: -2.5px;

  font-weight: 900;
}

.description {
  max-width: 510px;

  margin: 0;

  color: #7b8982;

  font-size: 13px;

  line-height: 1.8;
}

/* =====================================================
   USER CARD
===================================================== */

.user-card {
  display: flex;
  align-items: center;
  gap: 11px;

  max-width: 500px;

  margin-top: 25px;
  padding: 11px 13px;

  border: 1px solid #e3e8e4;
  border-radius: 14px;

  background: rgba(
    255,
    255,
    255,
    0.75
  );

  box-shadow:
    0 8px 25px rgba(
      40,
      55,
      48,
      0.045
    );

  backdrop-filter: blur(10px);
}

.avatar {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 39px;
  height: 39px;

  flex-shrink: 0;

  border-radius: 11px;

  background:
    linear-gradient(
      145deg,
      #e4b56d,
      #c99143
    );

  color: white;

  font-size: 13px;
  font-weight: 900;
}

.user-info {
  display: flex;
  flex-direction: column;

  min-width: 0;

  flex: 1;
}

.user-info span {
  color: #9aa59f;

  font-size: 8px;
}

.user-info strong {
  overflow: hidden;

  margin-top: 2px;

  color: #35443d;

  font-size: 11px;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-info small {
  overflow: hidden;

  color: #9ba6a0;

  font-size: 8px;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.role-badge {
  padding: 6px 9px;

  border-radius: 7px;

  background: #f3eee5;

  color: #947244;

  font-size: 8px;
  font-weight: 800;

  white-space: nowrap;
}

/* =====================================================
   ACTION
===================================================== */

.actions {
  display: flex;
  align-items: center;
  gap: 10px;

  margin-top: 25px;
}

.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;

  min-height: 43px;

  padding: 0 16px;

  border-radius: 10px;

  font-size: 10px;
  font-weight: 800;

  cursor: pointer;

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
}

.button:hover {
  transform: translateY(-2px);
}

.button-primary {
  border: 0;

  background:
    linear-gradient(
      135deg,
      #173f3b,
      #176c65
    );

  color: white;

  box-shadow:
    0 10px 23px rgba(
      23,
      108,
      101,
      0.16
    );
}

.button-primary:hover {
  box-shadow:
    0 14px 28px rgba(
      23,
      108,
      101,
      0.22
    );
}

.button-primary .arrow {
  margin-left: 5px;

  font-size: 14px;
}

.button-secondary {
  border: 1px solid #dfe5e1;

  background: white;

  color: #65736c;
}

.button-secondary:hover {
  background: #f8faf9;
}

/* =====================================================
   LOGIN LINK
===================================================== */

.login-link {
  display: block;

  margin-top: 17px;
  padding: 0;

  border: 0;

  background: transparent;

  color: #9a7441;

  font-family: inherit;

  font-size: 9px;
  font-weight: 750;

  cursor: pointer;
}

.login-link:hover {
  text-decoration: underline;
}

/* =====================================================
   ILLUSTRATION
===================================================== */

.illustration-section {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;
}

.illustration-card {
  position: relative;

  width: min(
    510px,
    100%
  );

  padding: 22px;

  border: 1px solid rgba(
    255,
    255,
    255,
    0.85
  );

  border-radius: 28px;

  background:
    linear-gradient(
      145deg,
      rgba(255,255,255,0.96),
      rgba(246,248,245,0.92)
    );

  box-shadow:
    0 35px 80px rgba(
      39,
      56,
      48,
      0.10
    );

  backdrop-filter: blur(15px);
}

/* =====================================================
   CARD LABEL
===================================================== */

.card-label {
  display: inline-flex;
  align-items: center;
  gap: 7px;

  padding: 7px 10px;

  border-radius: 8px;

  background: #f1f4f1;

  color: #89948e;

  font-size: 8px;
  font-weight: 850;

  letter-spacing: 0.9px;
}

.card-label-dot {
  width: 5px;
  height: 5px;

  border-radius: 50%;

  background: #d6a45c;
}

/* =====================================================
   LOCK SCENE
===================================================== */

.lock-scene {
  position: relative;

  height: 350px;

  display: flex;
  align-items: center;
  justify-content: center;

  overflow: hidden;
}

/* =====================================================
   CIRCLES
===================================================== */

.circle {
  position: absolute;

  border-radius: 50%;
}

.circle-back {
  width: 300px;
  height: 300px;

  background:
    rgba(226, 188, 128, 0.08);

  animation:
    breathe 5s ease-in-out infinite;
}

.circle-middle {
  width: 220px;
  height: 220px;

  border: 1px solid
    rgba(214, 164, 92, 0.13);
}

.circle-front {
  width: 145px;
  height: 145px;

  background:
    rgba(214, 164, 92, 0.08);

  border: 1px solid
    rgba(214, 164, 92, 0.12);
}

/* =====================================================
   LOCK
===================================================== */

.lock {
  position: relative;

  z-index: 5;

  width: 125px;
  height: 142px;

  filter:
    drop-shadow(
      0 20px 20px
      rgba(51, 55, 47, 0.15)
    );

  animation:
    lockFloat 4s ease-in-out infinite;
}

.lock-shackle {
  position: absolute;

  left: 29px;
  top: 0;

  width: 67px;
  height: 75px;

  border: 15px solid #d39c50;

  border-bottom: 0;

  border-radius:
    40px 40px 0 0;
}

.shackle-inner {
  position: absolute;

  left: -4px;
  top: 10px;

  width: 38px;
  height: 48px;

  border-radius:
    20px 20px 0 0;

  border:
    4px solid rgba(
      255,
      255,
      255,
      0.4
    );

  border-bottom: 0;
}

.lock-body {
  position: absolute;

  left: 0;
  bottom: 0;

  width: 125px;
  height: 92px;

  border-radius: 17px;

  background:
    linear-gradient(
      145deg,
      #d9aa68,
      #b87d32
    );

  box-shadow:
    inset 0 2px 0
      rgba(255,255,255,.3);
}

.lock-hole {
  position: absolute;

  left: 50%;
  top: 50%;

  transform:
    translate(-50%, -35%);

  width: 24px;
  height: 24px;

  border-radius: 50%;

  background: #7f5729;

  box-shadow:
    0 3px 5px
      rgba(0,0,0,.12);
}

.lock-hole span {
  position: absolute;

  left: 50%;
  top: 17px;

  width: 7px;
  height: 16px;

  transform:
    translateX(-50%);

  border-radius: 5px;

  background: #7f5729;
}

/* =====================================================
   SHADOW
===================================================== */

.lock-shadow {
  position: absolute;

  bottom: 50px;

  width: 150px;
  height: 20px;

  border-radius: 50%;

  background:
    rgba(50, 58, 51, 0.10);

  filter: blur(8px);

  animation:
    shadowFloat 4s ease-in-out infinite;
}

/* =====================================================
   WARNING CARDS
===================================================== */

.warning-card {
  position: absolute;

  z-index: 8;

  display: flex;
  align-items: center;
  gap: 9px;

  padding: 10px 12px;

  border: 1px solid #e8ece8;

  border-radius: 12px;

  background:
    rgba(255,255,255,.92);

  box-shadow:
    0 15px 30px
      rgba(45, 55, 49, .08);

  backdrop-filter: blur(10px);

  animation:
    cardFloat 5s ease-in-out infinite;
}

.warning-card > span {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 31px;
  height: 31px;

  border-radius: 9px;

  background: #f6eee1;

  font-size: 14px;
}

.warning-card div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.warning-card strong {
  color: #44534c;

  font-size: 9px;
}

.warning-card small {
  color: #9aa49f;

  font-size: 7px;
}

.warning-left {
  left: 15px;
  top: 105px;
}

.warning-right {
  right: 15px;
  bottom: 90px;

  animation-delay: -2s;
}

/* =====================================================
   SECURITY INFO
===================================================== */

.security-info {
  display: flex;
  align-items: center;
  gap: 11px;

  padding: 13px;

  border-radius: 13px;

  background: #f4f7f4;
}

.security-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 35px;
  height: 35px;

  flex-shrink: 0;

  border-radius: 10px;

  background: #e2f4e9;

  color: #36a86f;

  font-size: 13px;
  font-weight: 900;
}

.security-info div:last-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.security-info strong {
  color: #536159;

  font-size: 9px;
}

.security-info span {
  color: #96a19b;

  font-size: 7px;

  line-height: 1.5;
}

/* =====================================================
   FOOTER
===================================================== */

.footer {
  position: absolute;
  z-index: 5;

  bottom: 20px;
  left: 50%;

  transform:
    translateX(-50%);

  display: flex;
  align-items: center;
  justify-content: space-between;

  width: min(
    1080px,
    calc(100% - 48px)
  );

  color: #9aa49e;

  font-size: 8px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 6px;
}

.footer-brand strong {
  color: #65726b;

  font-size: 8px;

  letter-spacing: .7px;
}

.footer-dot {
  width: 5px;
  height: 5px;

  border-radius: 50%;

  background: #d2a15b;
}

/* =====================================================
   ANIMATION
===================================================== */

@keyframes lockFloat {
  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-8px);
  }
}

@keyframes shadowFloat {
  0%,
  100% {
    transform: scale(1);
    opacity: .8;
  }

  50% {
    transform: scale(.86);
    opacity: .5;
  }
}

@keyframes cardFloat {
  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-7px);
  }
}

@keyframes breathe {
  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.05);
  }
}

/* =====================================================
   TABLET
===================================================== */

@media (max-width: 950px) {

  .main-content {
    grid-template-columns: 1fr;

    gap: 45px;

    min-height: auto;

    padding-top: 35px;
    padding-bottom: 100px;
  }

  .message-section {
    text-align: center;
  }

  .description {
    margin-left: auto;
    margin-right: auto;
  }

  .user-card {
    margin-left: auto;
    margin-right: auto;

    text-align: left;
  }

  .actions {
    justify-content: center;
  }

  .login-link {
    margin-left: auto;
    margin-right: auto;
  }

  .illustration-section {
    max-width: 560px;
    width: 100%;

    margin: 0 auto;
  }

  .footer {
    position: relative;

    bottom: auto;

    width: min(
      1080px,
      calc(100% - 48px)
    );

    margin: -70px auto 20px;

    transform: none;
  }
}

/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 600px) {

  .page-header {
    width: calc(100% - 28px);

    padding: 18px 0;
  }

  .brand-logo {
    width: 38px;
    height: 38px;

    border-radius: 11px;

    font-size: 17px;
  }

  .brand-text strong {
    font-size: 11px;
  }

  .brand-text span {
    display: none;
  }

  .secure-badge {
    padding: 7px 9px;

    font-size: 8px;
  }

  .main-content {
    width: calc(100% - 28px);

    padding-top: 25px;

    gap: 35px;
  }

  .message-section h1 {
    font-size: 38px;

    letter-spacing: -1.8px;
  }

  .description {
    font-size: 11px;

    line-height: 1.7;
  }

  .user-card {
    align-items: flex-start;
  }

  .role-badge {
    display: none;
  }

  .actions {
    flex-direction: column;

    width: 100%;
  }

  .button {
    width: 100%;
  }

  .illustration-card {
    padding: 15px;

    border-radius: 21px;
  }

  .lock-scene {
    height: 285px;
  }

  .circle-back {
    width: 240px;
    height: 240px;
  }

  .circle-middle {
    width: 175px;
    height: 175px;
  }

  .circle-front {
    width: 120px;
    height: 120px;
  }

  .warning-left {
    left: 3px;
    top: 80px;
  }

  .warning-right {
    right: 3px;
    bottom: 55px;
  }

  .warning-card {
    transform: scale(.82);
  }

  .security-info span {
    font-size: 7px;
  }

  .footer {
    flex-direction: column;
    gap: 7px;

    text-align: center;
  }
}

/* =====================================================
   SMALL MOBILE
===================================================== */

@media (max-width: 390px) {

  .secure-badge {
    display: none;
  }

  .message-section h1 {
    font-size: 34px;
  }

  .illustration-card {
    padding: 12px;
  }

  .lock-scene {
    height: 255px;
  }

  .lock {
    transform: scale(.85);
  }

  .warning-card {
    transform: scale(.7);
  }
}

/* =====================================================
   REDUCED MOTION
===================================================== */

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