```vue
<template>
  <div class="auth-page">

    <!-- Background decoration -->
    <div class="auth-blob auth-blob-1"></div>
    <div class="auth-blob auth-blob-2"></div>

    <div class="auth-wrapper">

      <div class="auth-card card fade-in">

        <!-- LOGO -->
        <RouterLink to="/" class="auth-logo">
          <div class="auth-logo-icon">📢</div>
          <span>SUARAWARGA</span>
        </RouterLink>

        <!-- HEADER -->
        <div class="auth-header">
          <h1>Buat Akun Warga</h1>
          <p>
            Gabung dan mulai suarakan masalah di sekitarmu.
          </p>
        </div>

        <!-- FORM -->
        <form
          class="auth-form"
          @submit.prevent="handleRegister"
        >

          <!-- ERROR -->
          <transition name="shake">
            <div
              v-if="errorMessage"
              class="auth-error"
            >
              ⚠️ {{ errorMessage }}
            </div>
          </transition>


          <!-- ========================= -->
          <!-- NAMA -->
          <!-- ========================= -->

          <div class="form-group">

            <label
              class="form-label"
              for="name"
            >
              Nama Lengkap
            </label>

            <div class="input-icon-field">

              <span class="input-icon">
                👤
              </span>

              <input
                id="name"
                v-model="name"
                type="text"
                class="form-control has-icon"
                placeholder="Nama kamu"
                autocomplete="name"
                required
              />

            </div>

          </div>


          <!-- ========================= -->
          <!-- EMAIL -->
          <!-- ========================= -->

          <div class="form-group">

            <label
              class="form-label"
              for="email"
            >
              Email
            </label>

            <div class="input-icon-field">

              <span class="input-icon">
                ✉️
              </span>

              <input
                id="email"
                v-model="email"
                type="email"
                class="form-control has-icon"
                placeholder="nama@email.com"
                autocomplete="email"
                required
              />

            </div>

          </div>


          <!-- ========================= -->
          <!-- PASSWORD -->
          <!-- ========================= -->

          <div class="form-group">

            <label
              class="form-label"
              for="password"
            >
              Kata Sandi
            </label>

            <div class="input-icon-field">

              <span class="input-icon">
                🔒
              </span>

              <input
                id="password"
                v-model="password"
                :type="
                  showPassword
                    ? 'text'
                    : 'password'
                "
                class="form-control has-icon"
                placeholder="Minimal 8 karakter"
                autocomplete="new-password"
                required
                minlength="8"
              />

              <button
                type="button"
                class="password-toggle"
                @click="
                  showPassword = !showPassword
                "
              >
                {{
                  showPassword
                    ? '🙈'
                    : '👁️'
                }}
              </button>

            </div>

          </div>


          <!-- ========================= -->
          <!-- KONFIRMASI PASSWORD -->
          <!-- ========================= -->

          <div class="form-group">

            <label
              class="form-label"
              for="confirm-password"
            >
              Konfirmasi Kata Sandi
            </label>

            <div class="input-icon-field">

              <span class="input-icon">
                🔒
              </span>

              <input
                id="confirm-password"
                v-model="confirmPassword"
                :type="
                  showConfirmPassword
                    ? 'text'
                    : 'password'
                "
                class="form-control has-icon"
                placeholder="Ulangi kata sandi"
                autocomplete="new-password"
                required
                minlength="8"
              />

              <button
                type="button"
                class="password-toggle"
                @click="
                  showConfirmPassword =
                    !showConfirmPassword
                "
              >
                {{
                  showConfirmPassword
                    ? '🙈'
                    : '👁️'
                }}
              </button>

            </div>

          </div>


          <!-- ========================= -->
          <!-- CHECK PASSWORD -->
          <!-- ========================= -->

          <div
            v-if="
              confirmPassword &&
              password !== confirmPassword
            "
            class="password-warning"
          >
            ⚠️ Konfirmasi kata sandi tidak sama.
          </div>


          <!-- ========================= -->
          <!-- TERMS -->
          <!-- ========================= -->

          <label class="auth-checkbox">

            <input
              type="checkbox"
              v-model="agreeTerms"
            />

            <span>
              Saya menyetujui

              <a
                href="#"
                @click.prevent
              >
                Syarat & Ketentuan
              </a>

              dan

              <a
                href="#"
                @click.prevent
              >
                Kebijakan Privasi
              </a>

            </span>

          </label>


          <!-- ========================= -->
          <!-- REGISTER BUTTON -->
          <!-- ========================= -->

          <button
            type="submit"
            class="btn btn-primary auth-submit"
            :disabled="isLoading"
          >

            <span
              v-if="isLoading"
              class="auth-spinner"
            ></span>

            {{
              isLoading
                ? 'Membuat akun...'
                : 'Daftar Sekarang'
            }}

          </button>

        </form>


        <!-- ========================= -->
        <!-- DIVIDER -->
        <!-- ========================= -->

        <div class="auth-divider">
          <span>atau</span>
        </div>


        <!-- ========================= -->
        <!-- GOOGLE -->
        <!-- ========================= -->

        <button
          type="button"
          class="btn btn-secondary auth-submit auth-google"
          @click="registerGoogle"
        >

          <span class="auth-google-icon">
            G
          </span>

          Daftar dengan Google

        </button>


        <!-- ========================= -->
        <!-- LOGIN -->
        <!-- ========================= -->

        <p class="auth-footer-text">

          Sudah punya akun?

          <RouterLink to="/login">
            Masuk di sini
          </RouterLink>

        </p>

      </div>

    </div>

  </div>
</template>


<script setup>

import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'


// ==============================
// ROUTER
// ==============================

const router = useRouter()


// ==============================
// FORM STATE
// ==============================

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')


// ==============================
// UI STATE
// ==============================

const isLoading = ref(false)
const errorMessage = ref('')

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const agreeTerms = ref(false)


// ==============================
// REGISTER
// ==============================

async function handleRegister() {

  // Reset error
  errorMessage.value = ''


  // ==============================
  // VALIDASI NAMA
  // ==============================

  if (!name.value.trim()) {

    errorMessage.value =
      'Nama lengkap wajib diisi.'

    return
  }


  // ==============================
  // VALIDASI EMAIL
  // ==============================

  if (!email.value.trim()) {

    errorMessage.value =
      'Email wajib diisi.'

    return
  }


  // ==============================
  // VALIDASI PASSWORD
  // ==============================

  if (password.value.length < 8) {

    errorMessage.value =
      'Kata sandi minimal 8 karakter.'

    return
  }


  // ==============================
  // KONFIRMASI PASSWORD
  // ==============================

  if (
    password.value !==
    confirmPassword.value
  ) {

    errorMessage.value =
      'Konfirmasi kata sandi tidak sama.'

    return
  }


  // ==============================
  // TERMS
  // ==============================

  if (!agreeTerms.value) {

    errorMessage.value =
      'Kamu harus menyetujui Syarat & Ketentuan.'

    return
  }


  // ==============================
  // MULAI LOADING
  // ==============================

  isLoading.value = true


  try {

    // Data yang dikirim ke Laravel
    const registerData = {
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation:
        confirmPassword.value
    }


    console.log(
      'REGISTER DATA:',
      registerData
    )


    // ==============================
    // REQUEST KE LARAVEL
    // ==============================

    const response = await api.post(
      '/register',
      registerData
    )


    console.log(
      'REGISTER RESPONSE:',
      response.data
    )


    // ==============================
    // AMBIL TOKEN
    // ==============================

    const token =
      response.data.token ||
      response.data.access_token


    if (!token) {

      throw new Error(
        'Token register tidak ditemukan dari server.'
      )

    }


    // ==============================
    // SIMPAN TOKEN
    // ==============================

    localStorage.setItem(
      'token',
      token
    )


    // ==============================
    // SIMPAN USER
    // ==============================

    if (response.data.user) {

      localStorage.setItem(
        'user',
        JSON.stringify(
          response.data.user
        )
      )

    }


    // ==============================
    // BERHASIL
    // ==============================

    console.log(
      'REGISTER BERHASIL'
    )


    // Redirect ke Home
    await router.push('/')


  } catch (err) {

    console.error(
      'REGISTER ERROR:',
      err
    )


    // ==============================
    // ERROR DARI SERVER
    // ==============================

    if (err.response) {

      console.error(
        'STATUS:',
        err.response.status
      )

      console.error(
        'DATA:',
        err.response.data
      )


      // ==============================
      // VALIDATION ERROR 422
      // ==============================

      if (
        err.response.status === 422
      ) {

        const errors =
          err.response.data.errors


        if (errors) {

          const firstError =
            Object.values(errors)[0]


          if (
            Array.isArray(firstError)
          ) {

            errorMessage.value =
              firstError[0]

          } else {

            errorMessage.value =
              firstError

          }

        } else {

          errorMessage.value =
            err.response.data.message ||
            'Data yang dimasukkan tidak valid.'

        }

      }


      // ==============================
      // SERVER ERROR 500
      // ==============================

      else if (
        err.response.status >= 500
      ) {

        errorMessage.value =
          'Terjadi kesalahan pada server Laravel. Cek terminal Laravel.'

      }


      // ==============================
      // ERROR LAIN
      // ==============================

      else {

        errorMessage.value =
          err.response.data.message ||
          'Gagal membuat akun.'

      }

    }


    // ==============================
    // SERVER TIDAK TERHUBUNG
    // ==============================

    else if (err.request) {

      errorMessage.value =
        'Tidak dapat terhubung ke server Laravel. Pastikan php artisan serve sedang berjalan.'

    }


    // ==============================
    // ERROR LAIN
    // ==============================

    else {

      errorMessage.value =
        err.message ||
        'Terjadi kesalahan saat membuat akun.'

    }

  } finally {

    isLoading.value = false

  }

}


// ==============================
// GOOGLE REGISTER
// ==============================

function registerGoogle() {

  window.location.href =
    'http://127.0.0.1:8000/auth/google'

}

</script>


<style scoped>

/*
 * Kalau class berikut belum ada di CSS utama,
 * bagian ini memberikan style sederhana
 * untuk pesan password tidak sama.
 */

.password-warning {
  margin-top: -10px;
  margin-bottom: 15px;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 13px;
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffe69c;
}

</style>
```
