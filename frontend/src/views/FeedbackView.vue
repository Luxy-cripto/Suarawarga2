<template>
  <div>
    <Navbar />

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="container">
        <router-link
          to="/"
          class="auth-link-small"
          style="display: inline-block; margin-bottom: 8px;"
        >
          ← Kembali ke Beranda
        </router-link>

        <h1 class="page-title">Beri Masukan</h1>

        <p class="page-description">
          Nilai aplikasi kami atau laporkan jika ada bug/masalah teknis
        </p>
      </div>
    </div>

    <div
      class="container"
      style="padding: 32px 24px; max-width: 700px;"
    >

      <!-- ========================= -->
      <!-- FORM -->
      <!-- ========================= -->
      <div
        v-if="!submitted"
        class="card feedback-card"
      >

        <h3 class="feedback-title">
          Formulir Masukan
        </h3>

        <p v-if="error" class="auth-error">
          {{ error }}
        </p>

        <form @submit.prevent="handleSubmit">

          <!-- RATING -->
          <div class="form-group">
            <label class="form-label">
              Bagaimana pengalaman kamu menggunakan aplikasi ini?
            </label>

            <div class="rating-wrapper">

              <span
                v-for="star in 5"
                :key="star"
                class="star"
                :class="{ active: star <= form.rating }"
                @click="form.rating = star"
              >
                ⭐
              </span>

            </div>

            <p
              v-if="form.rating"
              class="rating-text"
            >
              {{ ratingText }}
            </p>
          </div>

          <!-- KATEGORI -->
          <div class="form-group">
            <label class="form-label">
              Jenis Masukan
            </label>

            <select
              v-model="form.kategori"
              class="form-control filter-select"
              style="width: 100%;"
              required
            >
              <option value="" disabled>
                Pilih jenis masukan
              </option>

              <option value="bug">
                🐛 Bug / Error
              </option>

              <option value="saran">
                💡 Saran
              </option>

              <option value="pujian">
                😊 Pujian
              </option>

              <option value="lainnya">
                📝 Lainnya
              </option>
            </select>
          </div>

          <!-- PESAN -->
          <div class="form-group">
            <label class="form-label">
              Ceritakan lebih detail
            </label>

            <textarea
              v-model="form.pesan"
              class="form-control"
              placeholder="Contoh: Tombol 'Simpan' di halaman profil tidak berfungsi saat diklik di HP Android..."
              required
            ></textarea>
          </div>

          <!-- BUTTON -->
          <button
            type="submit"
            class="btn btn-primary submit-button"
            :disabled="submitting || !form.rating"
          >

            <span
              v-if="submitting"
              class="auth-spinner"
            ></span>

            <span v-else>
              💬 Kirim Masukan
            </span>

          </button>

        </form>
      </div>


      <!-- ========================= -->
      <!-- SUCCESS ANIMATION -->
      <!-- ========================= -->
      <Transition name="success">

        <div
          v-if="submitted"
          class="success-card"
        >

          <!-- ANIMATED CHECK -->
          <div class="success-icon-wrapper">

            <div class="success-circle">

              <svg
                class="check-icon"
                viewBox="0 0 52 52"
              >
                <circle
                  class="check-circle"
                  cx="26"
                  cy="26"
                  r="25"
                  fill="none"
                />

                <path
                  class="check-path"
                  fill="none"
                  d="M14 27l8 8 16-18"
                />
              </svg>

            </div>

          </div>


          <!-- EMOJI -->
          <div class="success-emoji">
            🎉
          </div>


          <!-- TITLE -->
          <h2 class="success-title">
            Terima Kasih! ❤️
          </h2>


          <!-- DESCRIPTION -->
          <p class="success-message">
            Terima kasih sudah memberikan masukan
            atau melaporkan bug di aplikasi ini.
          </p>

          <p class="success-message secondary">
            Masukan kamu sangat berarti untuk membantu
            kami membuat <strong>Suarawarga</strong>
            menjadi lebih baik. 🚀
          </p>


          <!-- DECORATION -->
          <div class="success-stars">
            ✨ &nbsp; 💙 &nbsp; ✨
          </div>


          <!-- BUTTON -->
          <button
            class="btn btn-secondary success-button"
            @click="resetForm"
          >
            💬 Kirim Masukan Lain
          </button>

          <router-link
            to="/"
            class="back-home"
          >
            ← Kembali ke Beranda
          </router-link>

        </div>

      </Transition>

    </div>

    <Footer />
  </div>
</template>


<script setup>
import { ref, computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import api from '@/services/api'

const submitting = ref(false)
const submitted = ref(false)
const error = ref('')

const form = ref({
  rating: 0,
  kategori: '',
  pesan: '',
})


/*
|--------------------------------------------------------------------------
| TEXT RATING
|--------------------------------------------------------------------------
*/

const ratingText = computed(() => {
  const texts = {
    1: '😞 Kurang puas',
    2: '😕 Masih perlu banyak perbaikan',
    3: '🙂 Cukup baik',
    4: '😊 Sangat baik',
    5: '🤩 Sangat puas!',
  }

  return texts[form.value.rating] || ''
})


/*
|--------------------------------------------------------------------------
| RESET FORM
|--------------------------------------------------------------------------
*/

function resetForm() {
  form.value = {
    rating: 0,
    kategori: '',
    pesan: '',
  }

  submitted.value = false
  error.value = ''
}


/*
|--------------------------------------------------------------------------
| SUBMIT
|--------------------------------------------------------------------------
*/

async function handleSubmit() {

  if (!form.value.rating) {
    error.value = 'Silakan beri rating dulu ya.'
    return
  }

  if (!form.value.kategori) {
    error.value = 'Silakan pilih jenis masukan.'
    return
  }

  if (!form.value.pesan.trim()) {
    error.value = 'Silakan tuliskan pesan atau masukan kamu.'
    return
  }

  submitting.value = true
  error.value = ''

  try {

    await api.post('/feedbacks', form.value)

    submitted.value = true

  } catch (err) {

    console.error('Gagal mengirim feedback:', err)

    error.value =
      err.response?.data?.message ||
      'Gagal mengirim masukan. Silakan coba lagi.'

  } finally {

    submitting.value = false

  }
}
</script>


<style scoped>

/* =========================================
   FORM CARD
========================================= */

.feedback-card {
  padding: 28px;
  border-radius: 18px;
  animation: fadeUp 0.5s ease;
}

.feedback-title {
  margin-bottom: 20px;
}


/* =========================================
   RATING
========================================= */

.rating-wrapper {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

.star {
  font-size: 34px;
  cursor: pointer;
  filter: grayscale(100%);
  opacity: 0.35;
  transition:
    transform 0.2s ease,
    filter 0.2s ease,
    opacity 0.2s ease;
}

.star:hover {
  transform: scale(1.2);
  filter: grayscale(0%);
  opacity: 1;
}

.star.active {
  filter: grayscale(0%);
  opacity: 1;
  animation: starPop 0.25s ease;
}

.rating-text {
  margin-top: 8px;
  font-size: 14px;
  font-weight: 600;
  animation: fadeIn 0.25s ease;
}


/* =========================================
   BUTTON
========================================= */

.submit-button {
  min-width: 160px;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.submit-button:hover:not(:disabled) {
  transform: translateY(-2px);
}


/* =========================================
   SUCCESS CARD
========================================= */

.success-card {
  position: relative;
  overflow: hidden;

  padding: 50px 35px;
  text-align: center;

  border-radius: 24px;

  background: var(--card-bg, #ffffff);

  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.10);

  animation: successAppear 0.7s cubic-bezier(.2,.8,.2,1);
}


/* =========================================
   SUCCESS ICON
========================================= */

.success-icon-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 10px;
}

.success-circle {
  width: 90px;
  height: 90px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #e9f9ef;

  animation:
    circlePop 0.6s ease,
    successPulse 2s ease-in-out 0.8s infinite;
}

.check-icon {
  width: 65px;
  height: 65px;
}

.check-circle {
  stroke: #22c55e;
  stroke-width: 2;
  stroke-dasharray: 157;
  stroke-dashoffset: 157;

  animation: drawCircle 0.7s ease forwards;
}

.check-path {
  stroke: #22c55e;
  stroke-width: 4;
  stroke-linecap: round;
  stroke-linejoin: round;

  stroke-dasharray: 50;
  stroke-dashoffset: 50;

  animation: drawCheck 0.5s ease 0.5s forwards;
}


/* =========================================
   EMOJI
========================================= */

.success-emoji {
  font-size: 42px;
  margin-top: 4px;

  animation:
    emojiBounce 0.8s ease 0.4s both;
}


/* =========================================
   SUCCESS TEXT
========================================= */

.success-title {
  margin: 10px 0 12px;

  font-size: 30px;
  font-weight: 800;

  animation: textUp 0.6s ease 0.35s both;
}

.success-message {
  max-width: 500px;
  margin: 0 auto 10px;

  line-height: 1.7;
  font-size: 16px;

  animation: textUp 0.6s ease 0.5s both;
}

.success-message.secondary {
  font-size: 14px;
  opacity: 0.75;

  animation-delay: 0.65s;
}


/* =========================================
   DECORATION
========================================= */

.success-stars {
  margin: 20px 0;

  font-size: 20px;

  animation:
    fadeIn 0.8s ease 0.8s both,
    floating 2.5s ease-in-out 1.2s infinite;
}


/* =========================================
   SUCCESS BUTTON
========================================= */

.success-button {
  margin-top: 8px;

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.success-button:hover {
  transform: translateY(-2px);
}


/* =========================================
   BACK HOME
========================================= */

.back-home {
  display: block;

  margin-top: 18px;

  font-size: 14px;
  text-decoration: none;

  opacity: 0.7;

  transition: opacity 0.2s ease;
}

.back-home:hover {
  opacity: 1;
}


/* =========================================
   TRANSITION
========================================= */

.success-enter-active {
  animation: successAppear 0.7s ease;
}

.success-leave-active {
  animation: successDisappear 0.3s ease;
}

.success-enter-from,
.success-leave-to {
  opacity: 0;
}


/* =========================================
   ANIMATIONS
========================================= */

@keyframes successAppear {

  0% {
    opacity: 0;
    transform: translateY(30px) scale(0.94);
  }

  60% {
    transform: translateY(-5px) scale(1.01);
  }

  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }

}


@keyframes successDisappear {

  from {
    opacity: 1;
    transform: scale(1);
  }

  to {
    opacity: 0;
    transform: scale(0.95);
  }

}


@keyframes circlePop {

  0% {
    transform: scale(0);
  }

  60% {
    transform: scale(1.15);
  }

  100% {
    transform: scale(1);
  }

}


@keyframes successPulse {

  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.06);
  }

}


@keyframes drawCircle {

  to {
    stroke-dashoffset: 0;
  }

}


@keyframes drawCheck {

  to {
    stroke-dashoffset: 0;
  }

}


@keyframes emojiBounce {

  0% {
    opacity: 0;
    transform: translateY(15px) scale(0.5);
  }

  60% {
    transform: translateY(-8px) scale(1.15);
  }

  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }

}


@keyframes textUp {

  from {
    opacity: 0;
    transform: translateY(15px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }

}


@keyframes fadeIn {

  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }

}


@keyframes floating {

  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-5px);
  }

}


@keyframes starPop {

  0% {
    transform: scale(0.7);
  }

  70% {
    transform: scale(1.2);
  }

  100% {
    transform: scale(1);
  }

}


@keyframes fadeUp {

  from {
    opacity: 0;
    transform: translateY(15px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }

}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 600px) {

  .feedback-card {
    padding: 22px;
  }

  .success-card {
    padding: 40px 22px;
    border-radius: 20px;
  }

  .success-title {
    font-size: 25px;
  }

  .success-message {
    font-size: 15px;
  }

  .success-circle {
    width: 78px;
    height: 78px;
  }

  .check-icon {
    width: 58px;
    height: 58px;
  }

  .success-emoji {
    font-size: 36px;
  }

  .star {
    font-size: 29px;
  }

}

</style>