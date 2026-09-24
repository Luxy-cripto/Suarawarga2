<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
  watch,
} from 'vue'
import { useRoute } from 'vue-router'

import Navbar from '../../components/Navbar.vue'
import Footer from '../../components/Footer.vue'
import api from '../../services/api'

const MAX_FOTO = 5
const MAX_UKURAN = 2 * 1024 * 1024

const TIPE_DIIZINKAN = [
  'image/jpeg',
  'image/png',
  'image/jpg',
  'image/webp',
]

const statusMap = {
  baru: {
    label: 'Menunggu',
    class: 'status-waiting',
    icon: '🟡',
  },
  diproses: {
    label: 'Diproses',
    class: 'status-processing',
    icon: '🔵',
  },
  selesai: {
    label: 'Selesai',
    class: 'status-success',
    icon: '🟢',
  },
  ditolak: {
    label: 'Ditolak',
    class: 'status-rejected',
    icon: '🔴',
  },
}

const laporans = ref([])
const loading = ref(true)
const refreshing = ref(false)
const errorMessage = ref('')
const selected = ref(null)

const statusBaru = ref('diproses')
const catatan = ref('')
const alasanDitolak = ref('')
const fotoItems = ref([])

const saving = ref(false)
const saveError = ref('')
const saveSuccess = ref('')

const route = useRoute()

const laporanDariNotifikasi = computed(() => {
  const id = route.query.laporan

  if (!id) {
    return null
  }

  const parsedId = Number(id)

  return Number.isNaN(parsedId) ? null : parsedId
})

function statusData(status) {
  return (
    statusMap[status] || {
      label: status || '-',
      class: 'status-waiting',
      icon: '⚪',
    }
  )
}

function formatDate(date) {
  if (!date) return '-'

  try {
    const hasil = new Date(date)

    if (Number.isNaN(hasil.getTime())) {
      return '-'
    }

    return hasil.toLocaleString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return '-'
  }
}

function formatShortDate(date) {
  if (!date) return '-'

  try {
    const hasil = new Date(date)

    if (Number.isNaN(hasil.getTime())) {
      return '-'
    }

    return hasil.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return '-'
  }
}

function getImageUrl(url) {
  if (!url) return ''

  if (
    url.startsWith('http://') ||
    url.startsWith('https://') ||
    url.startsWith('blob:')
  ) {
    return url
  }

  const baseUrl = (
    import.meta.env.VITE_API_URL ||
    'http://127.0.0.1:8000'
  ).replace(/\/$/, '')

  if (url.startsWith('/')) {
    return `${baseUrl}${url}`
  }

  return `${baseUrl}/${url}`
}

function getKategori(laporan) {
  return (
    laporan?.kategoriRelasi?.nama ||
    laporan?.kategori_relasi?.nama ||
    laporan?.kategori ||
    'Lainnya'
  )
}

function getUserName(laporan) {
  return laporan?.user?.name || 'Warga'
}

function getDeadlineClass(laporan) {
  if (!laporan?.deadline_at) {
    return 'deadline-none'
  }

  if (laporan.status === 'selesai') {
    return 'deadline-success'
  }

  const deadline = new Date(laporan.deadline_at)

  if (Number.isNaN(deadline.getTime())) {
    return 'deadline-none'
  }

  return deadline < new Date()
    ? 'deadline-danger'
    : 'deadline-active'
}

function getDeadlineText(laporan) {
  if (!laporan?.deadline_at) {
    return 'Tidak ada deadline'
  }

  if (laporan.status === 'selesai') {
    return `Selesai • ${formatShortDate(
      laporan.completed_at || laporan.deadline_at
    )}`
  }

  const deadline = new Date(laporan.deadline_at)

  if (Number.isNaN(deadline.getTime())) {
    return 'Deadline tidak valid'
  }

  if (deadline < new Date()) {
    return `Terlambat • ${formatShortDate(
      laporan.deadline_at
    )}`
  }

  return `Deadline • ${formatShortDate(
    laporan.deadline_at
  )}`
}

/* =========================================================
   STATISTIK
========================================================= */

const totalTugas = computed(() => laporans.value.length)

const totalMenunggu = computed(() =>
  laporans.value.filter(
    (laporan) => laporan.status === 'baru'
  ).length
)

const totalDiproses = computed(() =>
  laporans.value.filter(
    (laporan) => laporan.status === 'diproses'
  ).length
)

const totalSelesai = computed(() =>
  laporans.value.filter(
    (laporan) => laporan.status === 'selesai'
  ).length
)

const totalDitolak = computed(() =>
  laporans.value.filter(
    (laporan) => laporan.status === 'ditolak'
  ).length
)

const totalTerlambat = computed(() =>
  laporans.value.filter((laporan) => {
    if (
      !laporan.deadline_at ||
      laporan.status === 'selesai'
    ) {
      return false
    }

    const deadline = new Date(laporan.deadline_at)

    return (
      !Number.isNaN(deadline.getTime()) &&
      deadline < new Date()
    )
  }).length
)

/* =========================================================
   PILIH LAPORAN DARI NOTIFIKASI
========================================================= */

function bukaLaporanDariNotifikasi() {
  const id = laporanDariNotifikasi.value

  if (!id || !laporans.value.length) {
    return
  }

  const laporan = laporans.value.find(
    (item) => Number(item.id) === id
  )

  if (laporan) {
    pilihLaporan(laporan)
  }
}

/* =========================================================
   AMBIL TUGAS PETUGAS
========================================================= */

async function fetchTugas(isRefresh = false) {
  if (isRefresh) {
    refreshing.value = true
  } else {
    loading.value = true
  }

  errorMessage.value = ''

  try {
    const response = await api.get(
      '/laporans/tugas-saya'
    )

    const data = Array.isArray(response.data)
      ? response.data
      : response.data?.data || []

    laporans.value = Array.isArray(data)
      ? data
      : []

    if (selected.value) {
      const laporanTerbaru = laporans.value.find(
        (laporan) =>
          laporan.id === selected.value.id
      )

      if (laporanTerbaru) {
        selected.value = laporanTerbaru
      }
    }

    bukaLaporanDariNotifikasi()
  } catch (err) {
    console.error(
      'ERROR TUGAS PETUGAS:',
      err
    )

    errorMessage.value =
      err.response?.data?.message ||
      'Gagal mengambil daftar tugas.'
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

async function refreshTugas() {
  await fetchTugas(true)
}

/* =========================================================
   PILIH LAPORAN
========================================================= */

function pilihLaporan(laporan) {
  selected.value = laporan

  statusBaru.value =
    laporan.status === 'baru'
      ? 'diproses'
      : laporan.status

  catatan.value = ''

  alasanDitolak.value =
    laporan.alasan_ditolak || ''

  bersihkanFotoItems()

  saveError.value = ''
  saveSuccess.value = ''

  window.scrollTo({
    top: 0,
    behavior: 'smooth',
  })
}

function tutupDetail() {
  selected.value = null

  bersihkanFotoItems()

  saveError.value = ''
  saveSuccess.value = ''
}

/* =========================================================
   FOTO
========================================================= */

function bersihkanFotoItems() {
  for (const item of fotoItems.value) {
    if (item.url?.startsWith('blob:')) {
      URL.revokeObjectURL(item.url)
    }
  }

  fotoItems.value = []
}

function handleFotoChange(event) {
  const files = Array.from(
    event.target.files || []
  )

  event.target.value = ''

  if (!files.length) {
    return
  }

  saveError.value = ''

  const sisaSlot =
    MAX_FOTO - fotoItems.value.length

  if (sisaSlot <= 0) {
    saveError.value =
      `Maksimal ${MAX_FOTO} foto.`
    return
  }

  const valid = []

  for (const file of files) {
    if (!TIPE_DIIZINKAN.includes(file.type)) {
      saveError.value =
        `Foto "${file.name}" harus JPG, PNG, atau WEBP.`
      continue
    }

    if (file.size > MAX_UKURAN) {
      saveError.value =
        `Foto "${file.name}" lebih dari 2 MB.`
      continue
    }

    valid.push(file)
  }

  const fotoYangDitambahkan =
    valid.slice(0, sisaSlot)

  for (const file of fotoYangDitambahkan) {
    fotoItems.value.push({
      file,
      url: URL.createObjectURL(file),
    })
  }

  if (valid.length > sisaSlot) {
    saveError.value =
      `Hanya ${sisaSlot} foto yang dapat ditambahkan. Maksimal ${MAX_FOTO} foto.`
  }
}

function hapusFoto(index) {
  const item = fotoItems.value[index]

  if (item?.url?.startsWith('blob:')) {
    URL.revokeObjectURL(item.url)
  }

  fotoItems.value.splice(index, 1)
}

/* =========================================================
   UPDATE PROGRESS
========================================================= */

async function submitProgress() {
  if (!selected.value || saving.value) {
    return
  }

  saveError.value = ''
  saveSuccess.value = ''

  if (
    statusBaru.value === 'ditolak' &&
    !alasanDitolak.value.trim()
  ) {
    saveError.value =
      'Alasan ditolak wajib diisi.'
    return
  }

  saving.value = true

  try {
    const formData = new FormData()

    formData.append(
      '_method',
      'PUT'
    )

    formData.append(
      'status',
      statusBaru.value
    )

    if (catatan.value.trim()) {
      formData.append(
        'catatan',
        catatan.value.trim()
      )
    }

    if (statusBaru.value === 'ditolak') {
      formData.append(
        'alasan_ditolak',
        alasanDitolak.value.trim()
      )
    }

    for (const item of fotoItems.value) {
      formData.append(
        'fotos[]',
        item.file
      )
    }

    const response = await api.post(
      `/laporans/${selected.value.id}/progress`,
      formData,
      {
        headers: {
          'Content-Type':
            'multipart/form-data',
        },
      }
    )

    const updated =
      response.data?.data

    const index =
      laporans.value.findIndex(
        (laporan) =>
          laporan.id === selected.value.id
      )

    if (index !== -1 && updated) {
      laporans.value[index] = updated
    }

    if (updated) {
      selected.value = updated
    }

    saveSuccess.value =
      response.data?.message ||
      'Progress berhasil diperbarui.'

    bersihkanFotoItems()

    await fetchTugas(true)
  } catch (err) {
    console.error(
      'ERROR UPDATE PROGRESS:',
      err
    )

    saveError.value =
      err.response?.data?.message ||
      'Gagal memperbarui progress laporan.'
  } finally {
    saving.value = false
  }
}

/* =========================================================
   WATCH & LIFECYCLE
========================================================= */

watch(
  () => route.query.laporan,
  () => {
    bukaLaporanDariNotifikasi()
  }
)

onMounted(() => {
  fetchTugas()
})

onBeforeUnmount(() => {
  bersihkanFotoItems()
})
</script>

<template>
  <div class="petugas-page">
    <Navbar />

    <section class="section">
      <div class="container">

        <!-- HEADER -->
        <div class="page-header">
          <div>
            <span class="page-kicker">
              PANEL PETUGAS
            </span>

            <h1>🔧 Tugas Saya</h1>

            <p class="text-muted">
              Kelola laporan yang ditugaskan kepada kamu dan
              perbarui progress pengerjaannya.
            </p>
          </div>

          <button
            type="button"
            class="refresh-btn"
            :disabled="refreshing"
            @click="refreshTugas"
          >
            <span
              :class="{
                'refresh-spin': refreshing
              }"
            >
              ↻
            </span>

            {{ refreshing ? 'Memuat...' : 'Refresh' }}
          </button>
        </div>

        <!-- STATISTIK -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon stat-icon-total">
              📋
            </div>

            <div>
              <span class="stat-label">
                Total Tugas
              </span>

              <strong class="stat-number">
                {{ totalTugas }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon-waiting">
              🟡
            </div>

            <div>
              <span class="stat-label">
                Menunggu
              </span>

              <strong class="stat-number">
                {{ totalMenunggu }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon-processing">
              🔵
            </div>

            <div>
              <span class="stat-label">
                Diproses
              </span>

              <strong class="stat-number">
                {{ totalDiproses }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon-success">
              🟢
            </div>

            <div>
              <span class="stat-label">
                Selesai
              </span>

              <strong class="stat-number">
                {{ totalSelesai }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon-danger">
              🔴
            </div>

            <div>
              <span class="stat-label">
                Ditolak
              </span>

              <strong class="stat-number">
                {{ totalDitolak }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon-late">
              ⏰
            </div>

            <div>
              <span class="stat-label">
                Terlambat
              </span>

              <strong class="stat-number">
                {{ totalTerlambat }}
              </strong>
            </div>
          </div>
        </div>

        <!-- LOADING -->
        <div
          v-if="loading"
          class="loading-card"
        >
          <div class="spinner"></div>

          <p>
            Memuat tugas kamu...
          </p>
        </div>

        <!-- ERROR -->
        <div
          v-else-if="errorMessage"
          class="error-card"
        >
          <div class="error-icon">
            ⚠️
          </div>

          <div>
            <strong>
              Gagal memuat tugas
            </strong>

            <p>
              {{ errorMessage }}
            </p>
          </div>

          <button
            type="button"
            class="retry-btn"
            @click="fetchTugas()"
          >
            Coba Lagi
          </button>
        </div>

        <!-- CONTENT -->
        <div
          v-else
          class="petugas-grid"
        >

          <!-- DAFTAR TUGAS -->
          <div class="card tugas-list-card">
            <div class="tugas-list-header">
              <div>
                <h2>
                  Daftar Laporan
                </h2>

                <p>
                  {{ laporans.length }}
                  laporan ditugaskan
                </p>
              </div>

              <span class="total-badge">
                {{ laporans.length }}
              </span>
            </div>

            <!-- KOSONG -->
            <div
              v-if="!laporans.length"
              class="empty-state"
            >
              <div class="empty-icon">
                📭
              </div>

              <h3>
                Belum ada tugas
              </h3>

              <p>
                Saat admin memberikan laporan kepada kamu,
                laporan tersebut akan muncul di sini.
              </p>

              <button
                type="button"
                class="retry-btn"
                @click="refreshTugas"
              >
                ↻ Refresh
              </button>
            </div>

            <!-- LIST -->
            <div
              v-else
              class="tugas-items"
            >
              <button
                v-for="laporan in laporans"
                :key="laporan.id"
                type="button"
                class="tugas-item"
                :class="{
                  active:
                    selected?.id === laporan.id
                }"
                @click="pilihLaporan(laporan)"
              >
                <div class="tugas-item-head">
                  <strong>
                    {{ laporan.judul }}
                  </strong>

                  <span
                    class="status"
                    :class="
                      statusData(
                        laporan.status
                      ).class
                    "
                  >
                    {{
                      statusData(
                        laporan.status
                      ).icon
                    }}

                    {{
                      statusData(
                        laporan.status
                      ).label
                    }}
                  </span>
                </div>

                <p class="tugas-item-sub">
                  📍
                  {{
                    laporan.lokasi ||
                    'Lokasi tidak tersedia'
                  }}
                </p>

                <p class="tugas-item-sub">
                  👤
                  {{ getUserName(laporan) }}
                </p>

                <div class="tugas-item-footer">
                  <span>
                    📅
                    {{
                      formatShortDate(
                        laporan.created_at
                      )
                    }}
                  </span>

                  <span
                    class="deadline-mini"
                    :class="
                      getDeadlineClass(
                        laporan
                      )
                    "
                  >
                    {{
                      getDeadlineText(
                        laporan
                      )
                    }}
                  </span>
                </div>
              </button>
            </div>
          </div>

          <!-- DETAIL -->
          <div class="card tugas-detail-card">

            <!-- BELUM PILIH -->
            <div
              v-if="!selected"
              class="empty-detail"
            >
              <div class="empty-detail-icon">
                👈
              </div>

              <h3>
                Pilih laporan
              </h3>

              <p>
                Pilih salah satu laporan di sebelah kiri untuk
                melihat detail dan memperbarui progress.
              </p>
            </div>

            <!-- DETAIL LAPORAN -->
            <div v-else>

              <!-- HEADER DETAIL -->
              <div class="detail-header">
                <div class="detail-title">
                  <span class="detail-kategori">
                    {{ getKategori(selected) }}
                  </span>

                  <h2>
                    {{ selected.judul }}
                  </h2>

                  <div class="detail-status-row">
                    <span
                      class="status"
                      :class="
                        statusData(
                          selected.status
                        ).class
                      "
                    >
                      {{
                        statusData(
                          selected.status
                        ).icon
                      }}

                      {{
                        statusData(
                          selected.status
                        ).label
                      }}
                    </span>

                    <span
                      class="deadline-badge"
                      :class="
                        getDeadlineClass(
                          selected
                        )
                      "
                    >
                      ⏰
                      {{
                        getDeadlineText(
                          selected
                        )
                      }}
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  class="detail-close"
                  aria-label="Tutup detail"
                  @click="tutupDetail"
                >
                  ✕
                </button>
              </div>

              <!-- DESKRIPSI -->
              <div class="detail-section">
                <h3>
                  📝 Deskripsi Laporan
                </h3>

                <p class="detail-deskripsi">
                  {{
                    selected.deskripsi ||
                    'Tidak ada deskripsi.'
                  }}
                </p>
              </div>

              <!-- META -->
              <div class="detail-meta">
                <div class="meta-card">
                  <span>
                    👤 Pelapor
                  </span>

                  <strong>
                    {{ getUserName(selected) }}
                  </strong>
                </div>

                <div class="meta-card">
                  <span>
                    📍 Lokasi
                  </span>

                  <strong>
                    {{
                      selected.lokasi ||
                      'Lokasi tidak tersedia'
                    }}
                  </strong>
                </div>

                <div class="meta-card">
                  <span>
                    📅 Dibuat
                  </span>

                  <strong>
                    {{
                      formatDate(
                        selected.created_at
                      )
                    }}
                  </strong>
                </div>

                <div class="meta-card">
                  <span>
                    ⏰ Deadline
                  </span>

                  <strong>
                    {{
                      selected.deadline_at
                        ? formatDate(
                            selected.deadline_at
                          )
                        : 'Tidak ada deadline'
                    }}
                  </strong>
                </div>
              </div>

              <!-- ALASAN DITOLAK -->
              <div
                v-if="
                  selected.status === 'ditolak' &&
                  selected.alasan_ditolak
                "
                class="alasan-ditolak-box"
              >
                <strong>
                  🔴 Alasan Ditolak
                </strong>

                <p>
                  {{ selected.alasan_ditolak }}
                </p>
              </div>

              <!-- FOTO SEBELUM -->
              <div
                v-if="
                  selected.files?.some(
                    (file) =>
                      file.tipe === 'sebelum'
                  )
                "
                class="foto-section"
              >
                <div class="section-title-row">
                  <h3>
                    📷 Foto dari Warga
                  </h3>

                  <span>
                    Sebelum
                  </span>
                </div>

                <div class="foto-grid">
                  <a
                    v-for="file in selected.files.filter(
                      (file) =>
                        file.tipe === 'sebelum'
                    )"
                    :key="file.id"
                    :href="
                      getImageUrl(file.url)
                    "
                    target="_blank"
                    rel="noopener noreferrer"
                    class="foto-link"
                  >
                    <img
                      :src="
                        getImageUrl(
                          file.url
                        )
                      "
                      alt="Foto laporan"
                      loading="lazy"
                    />
                  </a>
                </div>
              </div>

              <!-- FOTO SESUDAH -->
              <div
                v-if="
                  selected.files?.some(
                    (file) =>
                      file.tipe === 'sesudah'
                  )
                "
                class="foto-section"
              >
                <div class="section-title-row">
                  <h3>
                    ✅ Foto Bukti Pengerjaan
                  </h3>

                  <span>
                    Sesudah
                  </span>
                </div>

                <div class="foto-grid">
                  <a
                    v-for="file in selected.files.filter(
                      (file) =>
                        file.tipe === 'sesudah'
                    )"
                    :key="file.id"
                    :href="
                      getImageUrl(file.url)
                    "
                    target="_blank"
                    rel="noopener noreferrer"
                    class="foto-link"
                  >
                    <img
                      :src="
                        getImageUrl(
                          file.url
                        )
                      "
                      alt="Foto bukti pengerjaan"
                      loading="lazy"
                    />
                  </a>
                </div>
              </div>

              <!-- UPDATE PROGRESS -->
              <div class="progress-form">
                <div class="progress-heading">
                  <div>
                    <span class="section-kicker">
                      TINDAK LANJUT
                    </span>

                    <h3>
                      Update Progress
                    </h3>
                  </div>
                </div>

                <!-- ERROR -->
                <div
                  v-if="saveError"
                  class="form-message form-error"
                >
                  ⚠️
                  {{ saveError }}
                </div>

                <!-- SUCCESS -->
                <div
                  v-if="saveSuccess"
                  class="form-message form-success"
                >
                  ✅
                  {{ saveSuccess }}
                </div>

                <!-- STATUS -->
                <div class="form-group">
                  <label class="form-label">
                    Status
                  </label>

                  <select
                    v-model="statusBaru"
                    class="form-control"
                    :disabled="saving"
                  >
                    <option value="diproses">
                      🔵 Diproses
                    </option>

                    <option value="selesai">
                      🟢 Selesai
                    </option>

                    <option value="ditolak">
                      🔴 Ditolak
                    </option>
                  </select>
                </div>

                <!-- CATATAN -->
                <div class="form-group">
                  <label class="form-label">
                    Catatan untuk warga

                    <span class="optional">
                      Opsional
                    </span>
                  </label>

                  <textarea
                    v-model="catatan"
                    class="form-control"
                    rows="4"
                    maxlength="1000"
                    :disabled="saving"
                    placeholder="Contoh: Jalan sudah diperbaiki dan sekarang sudah bisa dilalui kembali."
                  ></textarea>

                  <small class="char-count">
                    {{ catatan.length }}/1000
                  </small>
                </div>

                <!-- ALASAN DITOLAK -->
                <div
                  v-if="
                    statusBaru === 'ditolak'
                  "
                  class="form-group"
                >
                  <label class="form-label">
                    Alasan Ditolak

                    <span class="required-mark">
                      *
                    </span>
                  </label>

                  <textarea
                    v-model="alasanDitolak"
                    class="form-control"
                    rows="4"
                    maxlength="1000"
                    :disabled="saving"
                    placeholder="Jelaskan alasan laporan ditolak."
                  ></textarea>

                  <small class="char-count">
                    {{ alasanDitolak.length }}/1000
                  </small>
                </div>

                <!-- FOTO -->
                <div class="form-group">
                  <label class="form-label">
                    Foto Bukti Sesudah

                    <span class="optional">
                      Maks. 5 foto, 2 MB/foto
                    </span>
                  </label>

                  <div class="upload-box">
                    <input
                      id="foto-progress"
                      type="file"
                      accept="image/jpeg,image/png,image/jpg,image/webp"
                      multiple
                      :disabled="
                        saving ||
                        fotoItems.length >= MAX_FOTO
                      "
                      @change="
                        handleFotoChange
                      "
                    />

                    <label
                      for="foto-progress"
                      class="upload-label"
                    >
                      <span class="upload-icon">
                        📸
                      </span>

                      <strong>
                        Pilih Foto
                      </strong>

                      <small>
                        JPG, PNG, WEBP
                      </small>
                    </label>
                  </div>

                  <!-- PREVIEW -->
                  <div
                    v-if="fotoItems.length"
                    class="preview-wrapper"
                  >
                    <div class="preview-header">
                      <span>
                        Foto dipilih
                      </span>

                      <strong>
                        {{ fotoItems.length }}/{{ MAX_FOTO }}
                      </strong>
                    </div>

                    <div class="preview-grid">
                      <div
                        v-for="(
                          item,
                          index
                        ) in fotoItems"
                        :key="item.url"
                        class="preview-item"
                      >
                        <img
                          :src="item.url"
                          :alt="
                            `Preview foto ${
                              index + 1
                            }`
                          "
                        />

                        <button
                          type="button"
                          class="remove-photo"
                          :disabled="saving"
                          @click="
                            hapusFoto(index)
                          "
                        >
                          ✕
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- SIMPAN -->
                <button
                  type="button"
                  class="btn-save"
                  :disabled="saving"
                  @click="submitProgress"
                >
                  <span
                    v-if="saving"
                    class="button-spinner"
                  ></span>

                  {{
                    saving
                      ? 'Menyimpan...'
                      : '💾 Simpan Progress'
                  }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <Footer />
  </div>
</template>

<style scoped>
.petugas-page {
  min-height: 100vh;
  background: var(--background, #f7fafc);
}

.section {
  padding: 32px 0 56px;
}

.container {
  max-width: 1240px;
  margin: 0 auto;
  padding: 0 20px;
}

.text-muted {
  color: var(--text-secondary, #64748b);
}

/* =========================================================
   HEADER
========================================================= */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 20px;
  margin-bottom: 24px;
}

.page-kicker,
.section-kicker {
  display: block;
  margin-bottom: 6px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--primary, #0d9d98);
}

.page-header h1 {
  margin: 0 0 6px;
  font-size: 26px;
  line-height: 1.25;
  color: var(--text, #172b4d);
}

.page-header p {
  margin: 0;
  font-size: 14px;
  line-height: 1.6;
}

.refresh-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  border: 1px solid var(--border, #e2e8f0);
  background: var(--surface, #fff);
  color: var(--text, #172b4d);
  border-radius: 10px;
  padding: 10px 14px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.15s ease;
}

.refresh-btn:hover:not(:disabled) {
  border-color: var(--primary, #0d9d98);
  color: var(--primary, #0d9d98);
}

.refresh-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.refresh-spin {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

/* =========================================================
   STATISTIK
========================================================= */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 11px;
  min-width: 0;
  padding: 15px;
  background: var(--surface, #fff);
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 14px;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 38px;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  font-size: 18px;
}

.stat-icon-total {
  background: #eef2ff;
}

.stat-icon-waiting {
  background: #fff7d6;
}

.stat-icon-processing {
  background: #e0efff;
}

.stat-icon-success {
  background: #dcfce7;
}

.stat-icon-danger {
  background: #fee2e2;
}

.stat-icon-late {
  background: #ffedd5;
}

.stat-label {
  display: block;
  margin-bottom: 3px;
  color: var(--text-secondary, #64748b);
  font-size: 10px;
  font-weight: 600;
}

.stat-number {
  display: block;
  color: var(--text, #172b4d);
  font-size: 21px;
}

/* =========================================================
   CARD
========================================================= */

.card {
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e2e8f0);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  border-radius: 16px;
}

/* =========================================================
   LOADING / ERROR
========================================================= */

.loading-card,
.error-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 180px;
  padding: 30px;
  background: var(--surface, #fff);
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 16px;
}

.loading-card p {
  margin: 0;
  color: var(--text-secondary, #64748b);
  font-size: 13px;
}

.spinner,
.button-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #dbe5e5;
  border-top-color: var(--primary, #0d9d98);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

.error-card {
  justify-content: flex-start;
  color: #991b1b;
  background: #fef2f2;
  border-color: #fecaca;
}

.error-icon {
  font-size: 24px;
}

.error-card strong {
  display: block;
  margin-bottom: 3px;
  font-size: 14px;
}

.error-card p {
  margin: 0;
  font-size: 12px;
}

.retry-btn {
  margin-left: auto;
  flex-shrink: 0;
  border: 1px solid var(--border, #e2e8f0);
  background: var(--surface, #fff);
  color: var(--text, #172b4d);
  border-radius: 9px;
  padding: 8px 12px;
  font-family: inherit;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

/* =========================================================
   GRID UTAMA
========================================================= */

.petugas-grid {
  display: grid;
  grid-template-columns: 370px minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}

/* =========================================================
   DAFTAR TUGAS
========================================================= */

.tugas-list-card {
  padding: 18px;
  position: sticky;
  top: 85px;
}

.tugas-list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border, #e2e8f0);
}

.tugas-list-header h2 {
  margin: 0 0 3px;
  font-size: 16px;
  color: var(--text, #172b4d);
}

.tugas-list-header p {
  margin: 0;
  font-size: 11px;
  color: var(--text-secondary, #64748b);
}

.total-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 8px;
  border-radius: 999px;
  background: rgba(13, 157, 152, 0.1);
  color: var(--primary, #0d9d98);
  font-size: 12px;
  font-weight: 800;
}

.tugas-items {
  display: flex;
  flex-direction: column;
  gap: 9px;
  margin-top: 14px;
  max-height: 680px;
  overflow-y: auto;
  padding-right: 2px;
}

.tugas-item {
  text-align: left;
  width: 100%;
  padding: 14px;
  border-radius: 12px;
  border: 1px solid var(--border, #e2e8f0);
  background: var(--surface-soft, #f8fafc);
  cursor: pointer;
  font-family: inherit;
  transition:
    border-color 0.15s ease,
    background 0.15s ease,
    transform 0.15s ease;
}

.tugas-item:hover {
  border-color: var(--primary, #0d9d98);
  transform: translateY(-1px);
}

.tugas-item.active {
  border-color: var(--primary, #0d9d98);
  background: rgba(13, 157, 152, 0.06);
}

.tugas-item-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 8px;
}

.tugas-item-head strong {
  min-width: 0;
  font-size: 13.5px;
  line-height: 1.4;
  color: var(--text, #172b4d);
}

.tugas-item-sub {
  margin: 4px 0 0;
  font-size: 11.5px;
  color: var(--text-secondary, #64748b);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tugas-item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
  padding-top: 9px;
  border-top: 1px solid var(--border, #e2e8f0);
  font-size: 10.5px;
  color: var(--text-secondary, #64748b);
}

.deadline-mini {
  font-weight: 700;
}

.deadline-active {
  color: #1769aa;
}

.deadline-success {
  color: #16803c;
}

.deadline-danger {
  color: #b42318;
}

.deadline-none {
  color: #64748b;
}

/* =========================================================
   EMPTY
========================================================= */

.empty-state {
  text-align: center;
  padding: 45px 15px 30px;
  color: var(--text-secondary, #64748b);
}

.empty-icon {
  font-size: 34px;
  margin-bottom: 10px;
}

.empty-state h3 {
  margin: 0 0 6px;
  color: var(--text, #172b4d);
  font-size: 15px;
}

.empty-state p {
  max-width: 280px;
  margin: 0 auto 15px;
  font-size: 12px;
  line-height: 1.6;
}

.empty-state .retry-btn {
  margin: 0;
}

.empty-detail {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 450px;
  padding: 30px;
  text-align: center;
  color: var(--text-secondary, #64748b);
}

.empty-detail-icon {
  margin-bottom: 12px;
  font-size: 42px;
}

.empty-detail h3 {
  margin: 0 0 6px;
  color: var(--text, #172b4d);
  font-size: 18px;
}

.empty-detail p {
  max-width: 350px;
  margin: 0;
  font-size: 13px;
  line-height: 1.7;
}

/* =========================================================
   DETAIL
========================================================= */

.tugas-detail-card {
  min-height: 500px;
  padding: 26px;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 22px;
}

.detail-title {
  min-width: 0;
}

.detail-kategori {
  display: inline-block;
  margin-bottom: 6px;
  color: var(--primary, #0d9d98);
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-header h2 {
  margin: 0 0 10px;
  font-size: 21px;
  line-height: 1.35;
  color: var(--text, #172b4d);
}

.detail-status-row {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  align-items: center;
}

.detail-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 32px;
  width: 32px;
  height: 32px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 9px;
  background: var(--surface, #fff);
  color: var(--text-secondary, #64748b);
  cursor: pointer;
  transition: 0.15s ease;
}

.detail-close:hover {
  border-color: #ef4444;
  color: #ef4444;
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 9px;
  border-radius: 999px;
  font-size: 10.5px;
  font-weight: 700;
  white-space: nowrap;
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

.deadline-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 9px;
  border-radius: 999px;
  background: #f1f5f9;
  font-size: 10.5px;
  font-weight: 700;
}

/* =========================================================
   DETAIL SECTION
========================================================= */

.detail-section {
  margin-bottom: 20px;
}

.detail-section h3,
.foto-section h3 {
  margin: 0;
  font-size: 13.5px;
  color: var(--text, #172b4d);
}

.detail-deskripsi {
  margin: 9px 0 0;
  color: var(--text-secondary, #64748b);
  line-height: 1.7;
  font-size: 13px;
  white-space: pre-line;
}

.detail-meta {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 9px;
  margin-bottom: 20px;
}

.meta-card {
  min-width: 0;
  padding: 11px 13px;
  border-radius: 10px;
  background: var(--surface-soft, #f8fafc);
  border: 1px solid var(--border, #e2e8f0);
}

.meta-card span {
  display: block;
  margin-bottom: 4px;
  color: var(--text-secondary, #64748b);
  font-size: 10.5px;
}

.meta-card strong {
  display: block;
  color: var(--text, #172b4d);
  font-size: 12px;
  line-height: 1.4;
  word-break: break-word;
}

/* =========================================================
   DITOLAK
========================================================= */

.alasan-ditolak-box {
  padding: 13px 15px;
  margin-bottom: 20px;
  border-radius: 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.alasan-ditolak-box strong {
  display: block;
  margin-bottom: 5px;
  font-size: 12.5px;
}

.alasan-ditolak-box p {
  margin: 0;
  font-size: 12.5px;
  line-height: 1.6;
}

/* =========================================================
   FOTO
========================================================= */

.foto-section {
  margin-bottom: 22px;
}

.section-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.section-title-row > span {
  padding: 4px 8px;
  border-radius: 7px;
  background: #f1f5f9;
  color: #64748b;
  font-size: 10px;
  font-weight: 700;
}

.foto-grid {
  display: grid;
  grid-template-columns: repeat(
    auto-fill,
    minmax(110px, 1fr)
  );
  gap: 10px;
}

.foto-link {
  display: block;
  overflow: hidden;
  border-radius: 10px;
  border: 1px solid var(--border, #e2e8f0);
  background: #f8fafc;
}

.foto-grid img {
  display: block;
  width: 100%;
  height: 110px;
  object-fit: cover;
  transition: transform 0.2s ease;
}

.foto-link:hover img {
  transform: scale(1.04);
}

/* =========================================================
   PROGRESS FORM
========================================================= */

.progress-form {
  padding-top: 22px;
  border-top: 1px solid var(--border, #e2e8f0);
}

.progress-heading {
  margin-bottom: 17px;
}

.progress-heading h3 {
  margin: 0;
  color: var(--text, #172b4d);
  font-size: 17px;
}

.form-group {
  position: relative;
  margin-bottom: 17px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 7px;
  font-size: 12.5px;
  font-weight: 700;
  color: var(--text, #172b4d);
}

.optional {
  color: var(--text-secondary, #94a3b8);
  font-size: 10px;
  font-weight: 500;
}

.required-mark {
  color: #e54848;
}

.form-control {
  width: 100%;
  box-sizing: border-box;
  padding: 11px 12px;
  border-radius: 10px;
  border: 1px solid var(--border, #e2e8f0);
  background: var(--surface, #fff);
  color: var(--text, #172b4d);
  font-family: inherit;
  font-size: 13px;
  outline: none;
  transition: 0.15s ease;
}

.form-control:focus {
  border-color: var(--primary, #0d9d98);
  box-shadow:
    0 0 0 3px rgba(13, 157, 152, 0.08);
}

.form-control:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

textarea.form-control {
  resize: vertical;
  min-height: 95px;
}

.char-count {
  display: block;
  margin-top: 4px;
  text-align: right;
  color: #94a3b8;
  font-size: 10px;
}

/* =========================================================
   MESSAGE
========================================================= */

.form-message {
  margin-bottom: 14px;
  padding: 10px 13px;
  border-radius: 10px;
  font-size: 12.5px;
  line-height: 1.5;
}

.form-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.form-success {
  background: #effbf5;
  border: 1px solid #cceede;
  color: #27815e;
}

/* =========================================================
   UPLOAD
========================================================= */

.upload-box {
  position: relative;
  overflow: hidden;
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
}

.upload-box input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
}

.upload-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 105px;
  padding: 15px;
  cursor: pointer;
  text-align: center;
  transition: 0.15s ease;
}

.upload-label:hover {
  background: rgba(13, 157, 152, 0.04);
}

.upload-icon {
  margin-bottom: 5px;
  font-size: 25px;
}

.upload-label strong {
  color: var(--text, #172b4d);
  font-size: 12px;
}

.upload-label small {
  margin-top: 3px;
  color: var(--text-secondary, #64748b);
  font-size: 10px;
}

/* =========================================================
   PREVIEW
========================================================= */

.preview-wrapper {
  margin-top: 12px;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  color: var(--text-secondary, #64748b);
  font-size: 11px;
}

.preview-header strong {
  color: var(--text, #172b4d);
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(
    auto-fill,
    minmax(90px, 1fr)
  );
  gap: 8px;
}

.preview-item {
  position: relative;
  overflow: hidden;
  border-radius: 9px;
}

.preview-item img {
  display: block;
  width: 100%;
  height: 90px;
  object-fit: cover;
  border-radius: 9px;
  border: 1px solid var(--border, #e2e8f0);
}

.remove-photo {
  position: absolute;
  top: 5px;
  right: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: rgba(15, 23, 42, 0.72);
  color: #fff;
  font-size: 10px;
  cursor: pointer;
}

.remove-photo:hover:not(:disabled) {
  background: #dc2626;
}

.remove-photo:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* =========================================================
   BUTTON
========================================================= */

.btn-save {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  min-height: 43px;
  padding: 10px 16px;
  border: none;
  border-radius: 10px;
  background: var(--primary, #0d9d98);
  color: #fff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  transition: 0.15s ease;
}

.btn-save:hover:not(:disabled) {
  filter: brightness(0.95);
  transform: translateY(-1px);
}

.btn-save:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
}

.button-spinner {
  width: 15px;
  height: 15px;
  border-color: rgba(255, 255, 255, 0.35);
  border-top-color: #fff;
}

/* =========================================================
   ANIMATION
========================================================= */

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1150px) {
  .stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 900px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .refresh-btn {
    align-self: flex-start;
  }

  .petugas-grid {
    grid-template-columns: 1fr;
  }

  .tugas-list-card {
    position: static;
  }

  .tugas-items {
    max-height: none;
  }
}

@media (max-width: 650px) {
  .section {
    padding: 22px 0 40px;
  }

  .container {
    padding: 0 14px;
  }

  .page-header h1 {
    font-size: 22px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .stat-card {
    padding: 12px;
  }

  .stat-icon {
    flex-basis: 34px;
    width: 34px;
    height: 34px;
    font-size: 15px;
  }

  .stat-number {
    font-size: 18px;
  }

  .tugas-detail-card {
    padding: 18px;
  }

  .detail-meta {
    grid-template-columns: 1fr;
  }

  .detail-header h2 {
    font-size: 18px;
  }

  .tugas-item-head {
    flex-direction: column;
  }

  .tugas-item-footer {
    align-items: flex-start;
    flex-direction: column;
  }

  .error-card {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .retry-btn {
    margin-left: 0;
  }
}

@media (max-width: 420px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }

  .stat-card {
    gap: 8px;
    padding: 10px;
  }

  .stat-label {
    font-size: 9px;
  }

  .stat-number {
    font-size: 17px;
  }

  .tugas-list-card,
  .tugas-detail-card {
    border-radius: 12px;
  }
}
</style>