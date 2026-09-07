<script setup>
import {
  ref,
  computed,
  onMounted,
  nextTick
} from 'vue'

import {
  useRoute,
  useRouter
} from 'vue-router'

import Navbar from '../../components/Navbar.vue'
import Footer from '../../components/Footer.vue'
import api from '../../services/api.js'

// =====================================================
// ROUTER
// =====================================================

const route = useRoute()
const router = useRouter()

// =====================================================
// STATE
// =====================================================

const laporan = ref(null)
const tanggapans = ref([])

const loading = ref(true)
const loadingTanggapan = ref(false)
const sending = ref(false)
const reacting = ref(null)

const error = ref('')

const komentar = ref('')
const replyTo = ref(null)
const replyText = ref('')

// =====================================================
// USER
// =====================================================

const user = ref(null)

function loadUser() {
  try {
    user.value = JSON.parse(
      localStorage.getItem('user') || 'null'
    )
  } catch {
    user.value = null
  }
}

loadUser()

// =====================================================
// STATUS MAP
// =====================================================

const statusMap = {
  baru: {
    label: 'Menunggu',
    class: 'status-waiting',
    icon: '🟡'
  },

  diproses: {
    label: 'Diproses',
    class: 'status-processing',
    icon: '🔵'
  },

  selesai: {
    label: 'Selesai',
    class: 'status-success',
    icon: '🟢'
  },

  ditolak: {
    label: 'Ditolak',
    class: 'status-rejected',
    icon: '🔴'
  }
}

// =====================================================
// COMPUTED
// =====================================================

const kategoriNama = computed(() => {
  return (
    laporan.value?.kategori_relasi?.nama ||
    laporan.value?.kategori ||
    'Lainnya'
  )
})

const kategoriIcon = computed(() => {
  if (laporan.value?.kategori_relasi?.icon) {
    return laporan.value.kategori_relasi.icon
  }

  const nama = kategoriNama.value.toLowerCase()

  if (nama.includes('jalan')) {
    return '🚧'
  }

  if (nama.includes('sampah')) {
    return '🗑️'
  }

  if (nama.includes('lampu')) {
    return '💡'
  }

  if (nama.includes('selokan')) {
    return '🌊'
  }

  if (nama.includes('fasilitas')) {
    return '🏞️'
  }

  return '📋'
})

const statusData = computed(() => {
  const status = laporan.value?.status

  return (
    statusMap[status] || {
      label: status || 'Tidak diketahui',
      class: 'status-waiting',
      icon: '⚪'
    }
  )
})

const isLoggedIn = computed(() => {
  return Boolean(
    localStorage.getItem('token')
  )
})

const totalKomentar = computed(() => {
  return tanggapans.value.length
})

// =====================================================
// TOKEN
// =====================================================

function getTokenExists() {
  return Boolean(
    localStorage.getItem('token')
  )
}

// =====================================================
// FORMAT DATE
// =====================================================

function formatDate(date) {
  if (!date) {
    return '-'
  }

  try {
    return new Date(date).toLocaleString(
      'id-ID',
      {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }
    )
  } catch {
    return '-'
  }
}

// =====================================================
// INITIAL
// =====================================================

function getInitial(name) {
  if (!name) {
    return 'W'
  }

  return name
    .trim()
    .charAt(0)
    .toUpperCase()
}

// =====================================================
// FOTO URL
// =====================================================

function getFotoUrl() {
  const foto = laporan.value?.foto_url

  if (!foto) {
    return null
  }

  // Jika API sudah memberikan URL lengkap
  if (
    foto.startsWith('http://') ||
    foto.startsWith('https://')
  ) {
    return foto
  }

  // Jika API memberikan path /storage/...
  if (foto.startsWith('/storage/')) {
    return `http://127.0.0.1:8000${foto}`
  }

  // Jika API memberikan storage/laporan-foto/...
  if (foto.startsWith('storage/')) {
    return `http://127.0.0.1:8000/${foto}`
  }

  // Fallback
  return `http://127.0.0.1:8000/storage/${foto}`
}

// =====================================================
// LOGIN
// =====================================================

function goLogin() {
  router.push({
    path: '/login',
    query: {
      redirect: route.fullPath
    }
  })
}

// =====================================================
// KEMBALI KE DAFTAR LAPORAN
// =====================================================

function kembali() {
  router.push('/laporan')
}

// =====================================================
// FETCH LAPORAN
// =====================================================

async function fetchLaporan() {
  loading.value = true
  error.value = ''
  laporan.value = null

  const id = route.params.id

  // ===================================================
  // VALIDASI ID
  // ===================================================

  if (!id || !/^\d+$/.test(String(id))) {
    error.value =
      'ID laporan tidak valid.'

    loading.value = false
    return
  }

  try {
    console.log(
      'Mengambil laporan ID:',
      id
    )

    const response = await api.get(
      `/laporans/${id}`
    )

    console.log(
      'Response detail laporan:',
      response.data
    )

    // Laravel bisa mengembalikan:
    // { data: {...} }
    // atau langsung {...}

    const data =
      response.data?.data ??
      response.data

    if (!data || !data.id) {
      error.value =
        'Laporan tidak ditemukan.'

      return
    }

    laporan.value = data

    await fetchTanggapans()

  } catch (err) {
    console.error(
      'Gagal mengambil detail laporan:',
      err
    )

    if (err.response?.status === 404) {
      error.value =
        'Laporan dengan ID tersebut tidak ditemukan.'
    } else if (err.response?.status === 401) {
      error.value =
        'Kamu tidak memiliki akses ke laporan ini.'
    } else {
      error.value =
        err.response?.data?.message ||
        'Gagal mengambil detail laporan.'
    }

  } finally {
    loading.value = false
  }
}

// =====================================================
// FETCH TANGGAPAN
// =====================================================

async function fetchTanggapans() {
  if (!laporan.value?.id) {
    return
  }

  loadingTanggapan.value = true

  try {
    const response = await api.get(
      `/laporans/${laporan.value.id}/tanggapans`
    )

    console.log(
      'Response tanggapan:',
      response.data
    )

    const data =
      response.data?.data ??
      response.data

    tanggapans.value =
      Array.isArray(data)
        ? data
        : []

    // Pastikan struktur reply aman
    tanggapans.value =
      tanggapans.value.map((item) => ({
        ...item,

        replies: Array.isArray(
          item.replies
        )
          ? item.replies
          : [],

        likes_count:
          item.likes_count ?? 0,

        dislikes_count:
          item.dislikes_count ?? 0,

        user_reaction:
          item.user_reaction ?? null
      }))

  } catch (err) {
    console.error(
      'Gagal mengambil tanggapan:',
      err
    )

    // Fallback jika tanggapan sudah ikut
    // dikirim dari endpoint detail
    tanggapans.value =
      Array.isArray(
        laporan.value?.tanggapans
      )
        ? laporan.value.tanggapans
        : []

  } finally {
    loadingTanggapan.value = false
  }
}

// =====================================================
// KIRIM KOMENTAR
// =====================================================

async function kirimKomentar() {
  const pesan =
    komentar.value.trim()

  if (!pesan) {
    return
  }

  if (!getTokenExists()) {
    alert(
      'Silakan login terlebih dahulu untuk memberikan komentar.'
    )

    goLogin()
    return
  }

  sending.value = true

  try {
    const response = await api.post(
      `/laporans/${laporan.value.id}/tanggapans`,
      {
        pesan
      }
    )

    console.log(
      'Response komentar:',
      response.data
    )

    const data =
      response.data?.data ??
      response.data

    if (!data) {
      throw new Error(
        'Data komentar tidak ditemukan.'
      )
    }

    const komentarBaru = {
      ...data,

      replies: Array.isArray(
        data.replies
      )
        ? data.replies
        : [],

      likes_count:
        data.likes_count ?? 0,

      dislikes_count:
        data.dislikes_count ?? 0,

      user_reaction:
        data.user_reaction ?? null
    }

    tanggapans.value.unshift(
      komentarBaru
    )

    komentar.value = ''

  } catch (err) {
    console.error(
      'Gagal mengirim komentar:',
      err
    )

    if (
      err.response?.status === 401
    ) {
      alert(
        'Sesi login kamu sudah berakhir. Silakan login kembali.'
      )

      localStorage.removeItem('token')
      localStorage.removeItem('user')

      user.value = null

      goLogin()

      return
    }

    alert(
      err.response?.data?.message ||
      'Gagal mengirim komentar.'
    )

  } finally {
    sending.value = false
  }
}

// =====================================================
// MULAI BALAS
// =====================================================

function mulaiBalas(tanggapan) {
  if (!getTokenExists()) {
    alert(
      'Silakan login terlebih dahulu untuk membalas komentar.'
    )

    goLogin()
    return
  }

  replyTo.value = tanggapan
  replyText.value = ''

  nextTick(() => {
    document
      .getElementById('reply-input')
      ?.focus()
  })
}

// =====================================================
// BATAL BALAS
// =====================================================

function batalBalas() {
  replyTo.value = null
  replyText.value = ''
}

// =====================================================
// KIRIM BALASAN
// =====================================================

async function kirimBalasan() {
  const pesan =
    replyText.value.trim()

  if (
    !pesan ||
    !replyTo.value
  ) {
    return
  }

  if (!getTokenExists()) {
    goLogin()
    return
  }

  sending.value = true

  try {
    const response = await api.post(
      `/laporans/${laporan.value.id}/tanggapans`,
      {
        pesan,
        parent_id:
          replyTo.value.id
      }
    )

    console.log(
      'Response balasan:',
      response.data
    )

    const data =
      response.data?.data ??
      response.data

    if (!data) {
      throw new Error(
        'Data balasan tidak ditemukan.'
      )
    }

    const balasanBaru = {
      ...data,

      replies: [],

      likes_count:
        data.likes_count ?? 0,

      dislikes_count:
        data.dislikes_count ?? 0,

      user_reaction:
        data.user_reaction ?? null
    }

    if (
      !Array.isArray(
        replyTo.value.replies
      )
    ) {
      replyTo.value.replies = []
    }

    replyTo.value.replies.unshift(
      balasanBaru
    )

    replyText.value = ''
    replyTo.value = null

  } catch (err) {
    console.error(
      'Gagal mengirim balasan:',
      err
    )

    if (
      err.response?.status === 401
    ) {
      alert(
        'Sesi login kamu sudah berakhir. Silakan login kembali.'
      )

      localStorage.removeItem('token')
      localStorage.removeItem('user')

      user.value = null

      goLogin()

      return
    }

    alert(
      err.response?.data?.message ||
      'Gagal mengirim balasan.'
    )

  } finally {
    sending.value = false
  }
}

// =====================================================
// REACTION
// =====================================================

async function reactKomentar(
  tanggapan,
  type
) {
  if (!getTokenExists()) {
    alert(
      'Silakan login terlebih dahulu untuk memberikan reaction.'
    )

    goLogin()
    return
  }

  if (
    reacting.value === tanggapan.id
  ) {
    return
  }

  reacting.value =
    tanggapan.id

  try {
    const response = await api.post(
      `/tanggapans/${tanggapan.id}/reaction`,
      {
        type
      }
    )

    console.log(
      'Response reaction:',
      response.data
    )

    const data =
      response.data?.data ??
      response.data

    tanggapan.likes_count =
      data?.likes_count ?? 0

    tanggapan.dislikes_count =
      data?.dislikes_count ?? 0

    tanggapan.user_reaction =
      data?.user_reaction ?? null

  } catch (err) {
    console.error(
      'Gagal memberikan reaction:',
      err
    )

    if (
      err.response?.status === 401
    ) {
      alert(
        'Sesi login kamu sudah berakhir. Silakan login kembali.'
      )

      localStorage.removeItem('token')
      localStorage.removeItem('user')

      user.value = null

      goLogin()

      return
    }

    alert(
      err.response?.data?.message ||
      'Gagal memberikan reaction.'
    )

  } finally {
    reacting.value = null
  }
}

// =====================================================
// MOUNTED
// =====================================================

onMounted(() => {
  fetchLaporan()
})
</script>

<template>
  <div class="report-detail-page">

    <!-- =================================================
         NAVBAR
    ================================================== -->

    <Navbar />

    <!-- =================================================
         LOADING
    ================================================== -->

    <section
      v-if="loading"
      class="section"
    >
      <div class="container">

        <div class="card loading-card">

          <div class="loading-icon">
            ⏳
          </div>

          <h3>
            Memuat detail laporan...
          </h3>

          <p class="text-muted">
            Mohon tunggu sebentar.
          </p>

        </div>

      </div>
    </section>

    <!-- =================================================
         ERROR
    ================================================== -->

    <section
      v-else-if="error"
      class="section"
    >
      <div class="container">

        <div class="card error-card">

          <div class="error-icon">
            ⚠️
          </div>

          <h2>
            Laporan Tidak Ditemukan
          </h2>

          <p class="text-muted">
            {{ error }}
          </p>

          <button
            type="button"
            class="btn btn-primary"
            @click="kembali"
          >
            ← Kembali ke Laporan
          </button>

        </div>

      </div>
    </section>

    <!-- =================================================
         DETAIL
    ================================================== -->

    <section
      v-else-if="laporan"
      class="section"
    >

      <div class="container">

        <!-- BACK -->

        <button
          type="button"
          class="btn btn-secondary back-button"
          @click="kembali"
        >
          ← Kembali ke Laporan
        </button>

        <div class="dashboard-grid">

          <!-- =================================================
               CONTENT
          ================================================== -->

          <div>

            <!-- =================================================
                 DETAIL LAPORAN
            ================================================== -->

            <div class="card report-detail-card">

              <!-- FOTO -->

              <div
                v-if="getFotoUrl()"
                class="report-photo"
              >
                <img
                  :src="getFotoUrl()"
                  alt="Foto laporan"
                  @error="$event.target.style.display = 'none'"
                >
              </div>

              <!-- CONTENT -->

              <div class="report-content">

                <!-- TOP -->

                <div class="report-top">

                  <span class="report-category">
                    {{ kategoriIcon }}
                    {{ kategoriNama }}
                  </span>

                  <span
                    class="status"
                    :class="statusData.class"
                  >
                    {{ statusData.icon }}
                    {{ statusData.label }}
                  </span>

                </div>

                <!-- TITLE -->

                <h1 class="report-title">
                  {{ laporan.judul }}
                </h1>

                <!-- META -->

                <div class="report-meta">

                  <span>
                    👤
                    {{ laporan.user?.name || 'Warga' }}
                  </span>

                  <span>
                    📅
                    {{ formatDate(laporan.created_at) }}
                  </span>

                  <span>
                    📍
                    {{ laporan.lokasi || 'Lokasi tidak tersedia' }}
                  </span>

                </div>

                <!-- DESCRIPTION -->

                <div class="description">

                  <h3>
                    Deskripsi Laporan
                  </h3>

                  <p>
                    {{ laporan.deskripsi }}
                  </p>

                </div>

              </div>

            </div>

            <!-- =================================================
                 KOMENTAR
            ================================================== -->

            <div class="card comments-card">

              <!-- HEADER -->

              <div class="comments-header">

                <div>

                  <h2>
                    💬 Komentar Warga
                  </h2>

                  <p class="text-muted">
                    Diskusikan dan berikan informasi tambahan.
                  </p>

                </div>

                <span class="comment-count">
                  {{ totalKomentar }}
                </span>

              </div>

              <!-- =================================================
                   FORM KOMENTAR
              ================================================== -->

              <div
                v-if="isLoggedIn"
                class="comment-form"
              >

                <textarea
                  v-model="komentar"
                  class="form-control"
                  rows="3"
                  maxlength="1000"
                  placeholder="Tulis komentar atau informasi tambahan..."
                ></textarea>

                <div class="form-footer">

                  <small class="text-muted">
                    {{ komentar.length }}/1000
                  </small>

                  <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="
                      sending ||
                      !komentar.trim()
                    "
                    @click="kirimKomentar"
                  >
                    {{
                      sending
                        ? 'Mengirim...'
                        : '💬 Kirim Komentar'
                    }}
                  </button>

                </div>

              </div>

              <!-- =================================================
                   BELUM LOGIN
              ================================================== -->

              <div
                v-else
                class="login-comment"
              >

                <div class="login-icon">
                  🔐
                </div>

                <p>
                  Login untuk ikut berdiskusi
                  dengan warga lainnya.
                </p>

                <button
                  type="button"
                  class="btn btn-primary"
                  @click="goLogin"
                >
                  Login untuk Berkomentar
                </button>

              </div>

              <!-- =================================================
                   LOADING KOMENTAR
              ================================================== -->

              <div
                v-if="loadingTanggapan"
                class="empty-comments"
              >
                ⏳ Memuat komentar...
              </div>

              <!-- =================================================
                   KOSONG
              ================================================== -->

              <div
                v-else-if="!tanggapans.length"
                class="empty-comments"
              >

                <div>
                  💬
                </div>

                <p>
                  Belum ada komentar.
                </p>

                <small class="text-muted">
                  Jadilah warga pertama yang memberikan komentar.
                </small>

              </div>

              <!-- =================================================
                   KOMENTAR
              ================================================== -->

              <div
                v-else
                class="comment-list"
              >

                <div
                  v-for="tg in tanggapans"
                  :key="tg.id"
                  class="comment-item"
                >

                  <!-- AVATAR -->

                  <div class="avatar">
                    {{ getInitial(tg.user?.name) }}
                  </div>

                  <div class="comment-body">

                    <!-- HEADER KOMENTAR -->

                    <div class="comment-head">

                      <strong>
                        {{ tg.user?.name || 'Warga' }}
                      </strong>

                      <small class="text-muted">
                        {{ formatDate(tg.created_at) }}
                      </small>

                    </div>

                    <!-- TEXT -->

                    <p class="comment-text">
                      {{ tg.pesan }}
                    </p>

                    <!-- ACTION -->

                    <div class="comment-actions">

                      <!-- LIKE -->

                      <button
                        type="button"
                        class="comment-action"
                        :class="{
                          active:
                            tg.user_reaction === 'like'
                        }"
                        :disabled="
                          reacting === tg.id
                        "
                        @click="
                          reactKomentar(
                            tg,
                            'like'
                          )
                        "
                      >
                        👍
                        {{ tg.likes_count || 0 }}
                      </button>

                      <!-- DISLIKE -->

                      <button
                        type="button"
                        class="comment-action"
                        :class="{
                          active:
                            tg.user_reaction === 'dislike'
                        }"
                        :disabled="
                          reacting === tg.id
                        "
                        @click="
                          reactKomentar(
                            tg,
                            'dislike'
                          )
                        "
                      >
                        👎
                        {{ tg.dislikes_count || 0 }}
                      </button>

                      <!-- BALAS -->

                      <button
                        type="button"
                        class="comment-action"
                        @click="
                          mulaiBalas(tg)
                        "
                      >
                        💬 Balas
                      </button>

                    </div>

                    <!-- =================================================
                         REPLIES
                    ================================================== -->

                    <div
                      v-if="
                        tg.replies &&
                        tg.replies.length
                      "
                      class="replies"
                    >

                      <div
                        v-for="reply in tg.replies"
                        :key="reply.id"
                        class="reply-item"
                      >

                        <div
                          class="avatar reply-avatar"
                        >
                          {{
                            getInitial(
                              reply.user?.name
                            )
                          }}
                        </div>

                        <div class="comment-body">

                          <div class="comment-head">

                            <strong>
                              {{
                                reply.user?.name ||
                                'Warga'
                              }}
                            </strong>

                            <small class="text-muted">
                              {{
                                formatDate(
                                  reply.created_at
                                )
                              }}
                            </small>

                          </div>

                          <p class="comment-text">
                            {{ reply.pesan }}
                          </p>

                          <div class="comment-actions">

                            <button
                              type="button"
                              class="comment-action"
                              :class="{
                                active:
                                  reply.user_reaction ===
                                  'like'
                              }"
                              :disabled="
                                reacting === reply.id
                              "
                              @click="
                                reactKomentar(
                                  reply,
                                  'like'
                                )
                              "
                            >
                              👍
                              {{
                                reply.likes_count ||
                                0
                              }}
                            </button>

                            <button
                              type="button"
                              class="comment-action"
                              :class="{
                                active:
                                  reply.user_reaction ===
                                  'dislike'
                              }"
                              :disabled="
                                reacting === reply.id
                              "
                              @click="
                                reactKomentar(
                                  reply,
                                  'dislike'
                                )
                              "
                            >
                              👎
                              {{
                                reply.dislikes_count ||
                                0
                              }}
                            </button>

                          </div>

                        </div>

                      </div>

                    </div>

                    <!-- =================================================
                         FORM BALAS
                    ================================================== -->

                    <div
                      v-if="
                        replyTo &&
                        replyTo.id === tg.id
                      "
                      class="reply-form"
                    >

                      <div class="reply-title">

                        💬 Membalas

                        <strong>
                          {{
                            tg.user?.name ||
                            'Warga'
                          }}
                        </strong>

                        <button
                          type="button"
                          class="close-reply"
                          @click="batalBalas"
                        >
                          ✕
                        </button>

                      </div>

                      <textarea
                        id="reply-input"
                        v-model="replyText"
                        class="form-control"
                        rows="2"
                        maxlength="1000"
                        placeholder="Tulis balasan..."
                      ></textarea>

                      <div class="form-footer">

                        <small class="text-muted">
                          {{ replyText.length }}/1000
                        </small>

                        <button
                          type="button"
                          class="btn btn-primary"
                          :disabled="
                            sending ||
                            !replyText.trim()
                          "
                          @click="
                            kirimBalasan
                          "
                        >
                          {{
                            sending
                              ? 'Mengirim...'
                              : 'Kirim Balasan'
                          }}
                        </button>

                      </div>

                    </div>

                  </div>

                </div>

              </div>

            </div>

          </div>

          <!-- =================================================
               SIDEBAR
          ================================================== -->

          <div>

            <div class="card sidebar-card">

              <h3>
                📋 Informasi Laporan
              </h3>

              <div class="info-list">

                <!-- STATUS -->

                <div>

                  <small>
                    Status
                  </small>

                  <span
                    class="status"
                    :class="statusData.class"
                  >
                    {{ statusData.icon }}
                    {{ statusData.label }}
                  </span>

                </div>

                <!-- KATEGORI -->

                <div>

                  <small>
                    Kategori
                  </small>

                  <strong>
                    {{ kategoriIcon }}
                    {{ kategoriNama }}
                  </strong>

                </div>

                <!-- PELAPOR -->

                <div>

                  <small>
                    Pelapor
                  </small>

                  <strong>
                    {{
                      laporan.user?.name ||
                      'Warga'
                    }}
                  </strong>

                </div>

                <!-- LOKASI -->

                <div>

                  <small>
                    Lokasi
                  </small>

                  <strong>
                    📍
                    {{
                      laporan.lokasi ||
                      'Lokasi tidak tersedia'
                    }}
                  </strong>

                </div>

                <!-- CREATED -->

                <div>

                  <small>
                    Dibuat
                  </small>

                  <strong>
                    {{
                      formatDate(
                        laporan.created_at
                      )
                    }}
                  </strong>

                </div>

                <!-- KOMENTAR -->

                <div>

                  <small>
                    Total Komentar
                  </small>

                  <strong>
                    💬
                    {{ totalKomentar }}
                  </strong>

                </div>

              </div>

            </div>

          </div>

        </div>

      </div>

    </section>

    <!-- =================================================
         FOOTER
    ================================================== -->

    <Footer />

  </div>
</template>

<style scoped>
/* =====================================================
   BASE
===================================================== */

.report-detail-page {
  min-height: 100vh;
  background: var(--background, #f7fafc);
}


/* =====================================================
   LOADING & ERROR
===================================================== */

.loading-card,
.error-card {
  padding: 60px 25px;
  text-align: center;
  border-radius: 20px;
}

.loading-icon,
.error-icon {
  font-size: 52px;
  margin-bottom: 15px;
}

.loading-card h3,
.error-card h2 {
  margin-bottom: 8px;
}

.error-card p {
  max-width: 500px;
  margin: 0 auto 20px;
}


/* =====================================================
   BACK
===================================================== */

.back-button {
  margin-bottom: 22px;
}


/* =====================================================
   DETAIL CARD
===================================================== */

.report-detail-card {
  overflow: hidden;
  padding: 0;
  border-radius: 18px;
}


/* =====================================================
   PHOTO
===================================================== */

.report-photo {
  width: 100%;
  max-height: 450px;
  overflow: hidden;
  background: #f1f5f9;
}

.report-photo img {
  width: 100%;
  max-height: 450px;
  object-fit: cover;
  display: block;
}


/* =====================================================
   REPORT CONTENT
===================================================== */

.report-content {
  padding: 30px;
}

.report-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.report-category {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 13px;
  border-radius: 999px;
  background: var(--surface-soft, #f1f5f9);
  color: var(--text, #172b4d);
  font-weight: 600;
  font-size: 14px;
}

.report-title {
  font-size: 32px;
  line-height: 1.3;
  margin: 0 0 14px;
  color: var(--text, #172b4d);
  word-break: break-word;
}

.report-meta {
  display: flex;
  gap: 18px;
  flex-wrap: wrap;
  color: var(--text-secondary, #64748b);
  font-size: 14px;
  margin-bottom: 28px;
}

.report-meta span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}


/* =====================================================
   DESCRIPTION
===================================================== */

.description {
  border-top: 1px solid var(--border, #e2e8f0);
  padding-top: 22px;
}

.description h3 {
  margin-bottom: 12px;
  color: var(--text, #172b4d);
}

.description p {
  line-height: 1.8;
  white-space: pre-line;
  color: var(--text-secondary, #64748b);
  margin: 0;
}


/* =====================================================
   COMMENTS
===================================================== */

.comments-card {
  padding: 30px;
  margin-top: 24px;
  border-radius: 18px;
}

.comments-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  margin-bottom: 22px;
}

.comments-header h2 {
  margin: 0 0 5px;
  color: var(--text, #172b4d);
}

.comments-header p {
  margin: 0;
}

.comment-count {
  min-width: 40px;
  height: 40px;
  padding: 0 12px;
  border-radius: 50px;
  background: var(--primary, #0d9d98);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}


/* =====================================================
   FORM
===================================================== */

.comment-form {
  margin-bottom: 25px;
}

.form-control {
  resize: vertical;
}

.form-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-top: 10px;
}


/* =====================================================
   LOGIN
===================================================== */

.login-comment {
  padding: 28px;
  border-radius: 15px;
  background: var(--surface-soft, #f1f5f9);
  margin-bottom: 25px;
  text-align: center;
}

.login-icon {
  font-size: 40px;
  margin-bottom: 8px;
}

.login-comment p {
  margin-bottom: 15px;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-comments {
  text-align: center;
  padding: 40px 10px;
  color: var(--text-secondary, #64748b);
}

.empty-comments > div {
  font-size: 42px;
  margin-bottom: 8px;
}

.empty-comments p {
  margin-bottom: 5px;
}


/* =====================================================
   COMMENT LIST
===================================================== */

.comment-list {
  border-top: 1px solid var(--border, #e2e8f0);
}

.comment-item {
  display: flex;
  gap: 13px;
  padding: 20px 0;
  border-bottom: 1px solid var(--border, #e2e8f0);
}

.avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--primary, #0d9d98);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  flex-shrink: 0;
}

.reply-avatar {
  width: 37px;
  height: 37px;
}

.comment-body {
  flex: 1;
  min-width: 0;
}

.comment-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.comment-text {
  margin: 7px 0;
  color: var(--text-secondary, #64748b);
  white-space: pre-line;
  line-height: 1.65;
}


/* =====================================================
   COMMENT ACTION
===================================================== */

.comment-actions {
  display: flex;
  align-items: center;
  gap: 5px;
  flex-wrap: wrap;
}

.comment-action {
  border: none;
  background: transparent;
  padding: 7px 10px;
  border-radius: 8px;
  cursor: pointer;
  color: var(--text-secondary, #64748b);
  font-size: 13px;
  transition: 0.2s ease;
}

.comment-action:hover {
  background: var(--surface-soft, #f1f5f9);
}

.comment-action.active {
  background: var(--surface-soft, #f1f5f9);
  color: var(--primary, #0d9d98);
  font-weight: 700;
}

.comment-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}


/* =====================================================
   REPLIES
===================================================== */

.replies {
  margin-top: 16px;
  margin-left: 8px;
  padding-left: 18px;
  border-left: 2px solid var(--border, #e2e8f0);
}

.reply-item {
  display: flex;
  gap: 10px;
  padding: 13px 0;
}


/* =====================================================
   REPLY FORM
===================================================== */

.reply-form {
  margin-top: 16px;
  padding: 16px;
  border-radius: 12px;
  background: var(--surface-soft, #f1f5f9);
  border: 1px solid var(--border, #e2e8f0);
}

.reply-title {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-bottom: 10px;
  color: var(--text, #172b4d);
}

.close-reply {
  margin-left: auto;
  border: none;
  background: transparent;
  cursor: pointer;
  color: var(--text-secondary, #64748b);
  font-size: 16px;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar-card {
  padding: 24px;
  position: sticky;
  top: 90px;
  border-radius: 18px;
}

.sidebar-card h3 {
  margin-bottom: 20px;
  color: var(--text, #172b4d);
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.info-list > div {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--border, #e2e8f0);
}

.info-list > div:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.info-list small {
  color: var(--text-secondary, #64748b);
  font-size: 13px;
}

.info-list strong {
  color: var(--text, #172b4d);
  line-height: 1.5;
}


/* =====================================================
   STATUS
===================================================== */

.status {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  width: fit-content;
  padding: 7px 11px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}

.status-waiting {
  background: #fff7d6;
  color: #9a7200;
}

.status-processing {
  background: #e0efff;
  color: #1769aa;
}

.status-success {
  background: #dcfce7;
  color: #16803c;
}

.status-rejected {
  background: #fee2e2;
  color: #b42318;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 992px) {

  .sidebar-card {
    position: static;
    margin-top: 24px;
  }

}


@media (max-width: 768px) {

  .report-content {
    padding: 22px;
  }

  .comments-card {
    padding: 22px;
  }

  .report-title {
    font-size: 25px;
  }

  .report-meta {
    flex-direction: column;
    gap: 9px;
  }

  .report-top {
    align-items: flex-start;
    flex-direction: column;
  }

  .comment-head {
    align-items: flex-start;
    flex-direction: column;
    gap: 3px;
  }

  .form-footer {
    align-items: flex-start;
    flex-direction: column;
  }

  .form-footer .btn {
    width: 100%;
  }

  .login-comment {
    padding: 22px 15px;
  }

  .login-comment .btn {
    width: 100%;
  }

  .replies {
    margin-left: 0;
    padding-left: 12px;
  }

  .avatar {
    width: 39px;
    height: 39px;
  }

  .reply-avatar {
    width: 34px;
    height: 34px;
  }

}


@media (max-width: 480px) {

  .report-title {
    font-size: 22px;
  }

  .report-content,
  .comments-card,
  .sidebar-card {
    padding: 18px;
  }

  .comment-item {
    gap: 9px;
  }

  .comment-action {
    padding: 6px 7px;
  }

}
</style>