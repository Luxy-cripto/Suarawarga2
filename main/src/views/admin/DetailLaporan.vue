<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '../../components/AdminLayout.vue'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()

const laporan = ref(null)
const petugasList = ref([])

const loading = ref(true)
const error = ref('')

const updating = ref(false)
const deleting = ref(false)
const assigning = ref(false)

const selectedPetugasId = ref('')

const showMap = ref(false)

const activeBeforeIndex = ref(0)
const activeAfterIndex = ref(0)

// =====================================================
// BACKEND URL
// =====================================================

const backendBaseUrl = computed(() => {
  let url =
    import.meta.env.VITE_API_URL ||
    'http://127.0.0.1:8000'

  url = url.replace(/\/+$/, '')
  url = url.replace(/\/api$/, '')

  return url
})

// =====================================================
// CATEGORY
// =====================================================

const categoryMap = {
  jalan_rusak: {
    emoji: '🚧',
    label: 'Jalan'
  },
  jalan: {
    emoji: '🚧',
    label: 'Jalan'
  },
  sampah: {
    emoji: '🗑️',
    label: 'Sampah'
  },
  lampu_mati: {
    emoji: '💡',
    label: 'Lampu'
  },
  lampu: {
    emoji: '💡',
    label: 'Lampu'
  },
  selokan: {
    emoji: '🌊',
    label: 'Selokan'
  },
  fasilitas: {
    emoji: '🏞️',
    label: 'Fasilitas'
  },
  keamanan: {
    emoji: '🛡️',
    label: 'Keamanan'
  },
  kebersihan: {
    emoji: '🧹',
    label: 'Kebersihan'
  },
  lainnya: {
    emoji: '📋',
    label: 'Lainnya'
  }
}

// =====================================================
// STATUS
// =====================================================

const statusMap = {
  baru: 'Menunggu',
  diproses: 'Diproses',
  selesai: 'Selesai',
  ditolak: 'Ditolak'
}

const statusClass = computed(() => {
  return laporan.value?.status || 'baru'
})

// =====================================================
// KATEGORI INFO
// =====================================================

const kategoriInfo = computed(() => {
  if (!laporan.value) {
    return {
      emoji: '📋',
      label: 'Lainnya'
    }
  }

  const kategori =
    laporan.value.kategori_relasi ||
    laporan.value.kategoriRelasi

  if (kategori) {
    const kode = kategori.kode

    return {
      emoji:
        kategori.emoji ||
        categoryMap[kode]?.emoji ||
        '📋',

      label:
        kategori.nama ||
        kategori.label ||
        categoryMap[kode]?.label ||
        'Lainnya'
    }
  }

  return (
    categoryMap[laporan.value.kategori] || {
      emoji: '📋',
      label: laporan.value.kategori || 'Lainnya'
    }
  )
})

// =====================================================
// FOTO
// =====================================================

const beforePhotos = computed(() => {
  const files = Array.isArray(
    laporan.value?.files
  )
    ? laporan.value.files
    : []

  return files.filter(
    file => file?.tipe === 'sebelum'
  )
})

const afterPhotos = computed(() => {
  const files = Array.isArray(
    laporan.value?.files
  )
    ? laporan.value.files
    : []

  return files.filter(
    file => file?.tipe === 'sesudah'
  )
})

const currentBeforePhoto = computed(() => {
  return (
    beforePhotos.value[
      activeBeforeIndex.value
    ] || null
  )
})

const currentAfterPhoto = computed(() => {
  return (
    afterPhotos.value[
      activeAfterIndex.value
    ] || null
  )
})

// =====================================================
// TIMELINE
// =====================================================

const timelineItems = computed(() => {
  if (!laporan.value) {
    return []
  }

  return [
    {
      icon: '📝',
      title: 'Laporan dibuat',
      date: laporan.value.created_at,
      done: Boolean(
        laporan.value.created_at
      )
    },
    {
      icon: '👷',
      title: 'Petugas ditugaskan',
      date: laporan.value.assigned_at,
      done: Boolean(
        laporan.value.assigned_at
      )
    },
    {
      icon: '🔧',
      title: 'Pengerjaan dimulai',
      date: laporan.value.started_at,
      done: Boolean(
        laporan.value.started_at
      )
    },
    {
      icon: '⏰',
      title: 'Deadline pengerjaan',
      date: laporan.value.deadline_at,
      done: Boolean(
        laporan.value.deadline_at
      )
    },
    {
      icon: '✅',
      title: 'Pengerjaan selesai',
      date: laporan.value.completed_at,
      done: Boolean(
        laporan.value.completed_at
      )
    }
  ]
})

// =====================================================
// DEADLINE
// =====================================================

const deadlineStatus = computed(() => {
  if (!laporan.value?.deadline_at) {
    return {
      class: 'deadline-none',
      label: 'Belum ada deadline',
      icon: '⏳'
    }
  }

  if (
    laporan.value.status === 'selesai'
  ) {
    return {
      class: 'deadline-done',
      label: 'Pekerjaan selesai',
      icon: '✅'
    }
  }

  const deadline = new Date(
    laporan.value.deadline_at
  )

  if (
    Number.isNaN(
      deadline.getTime()
    )
  ) {
    return {
      class: 'deadline-none',
      label: 'Deadline tidak valid',
      icon: '⏳'
    }
  }

  const now = new Date()

  if (now > deadline) {
    return {
      class: 'deadline-late',
      label: 'Terlambat',
      icon: '⚠️'
    }
  }

  return {
    class: 'deadline-active',
    label: 'Masih dalam waktu',
    icon: '⏰'
  }
})

// =====================================================
// LOG
// =====================================================

const logs = computed(() => {
  const items = Array.isArray(
    laporan.value?.logs
  )
    ? laporan.value.logs
    : []

  return [...items].sort(
    (a, b) =>
      new Date(b.created_at) -
      new Date(a.created_at)
  )
})

// =====================================================
// TANGGAPAN
// =====================================================

const tanggapans = computed(() => {
  return Array.isArray(
    laporan.value?.tanggapans
  )
    ? laporan.value.tanggapans
    : []
})

function tanggapanText(item) {
  return (
    item?.isi ||
    item?.komentar ||
    item?.pesan ||
    item?.tanggapan ||
    item?.content ||
    '-'
  )
}

// =====================================================
// FORMAT DATE
// =====================================================

function formatDate(date) {
  if (!date) {
    return '-'
  }

  const parsedDate = new Date(date)

  if (
    Number.isNaN(
      parsedDate.getTime()
    )
  ) {
    return '-'
  }

  return parsedDate.toLocaleString(
    'id-ID',
    {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
  )
}

function formatShortDate(date) {
  if (!date) {
    return '-'
  }

  const parsedDate = new Date(date)

  if (
    Number.isNaN(
      parsedDate.getTime()
    )
  ) {
    return '-'
  }

  return parsedDate.toLocaleDateString(
    'id-ID',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    }
  )
}

// =====================================================
// PHOTO URL
// =====================================================

function getPhotoUrl(file) {
  if (!file) {
    return ''
  }

  const value =
    file.url ||
    file.path ||
    ''

  if (!value) {
    return ''
  }

  if (
    value.startsWith('http://') ||
    value.startsWith('https://') ||
    value.startsWith('//')
  ) {
    return value
  }

  if (
    value.startsWith('/storage/')
  ) {
    return `${backendBaseUrl.value}${value}`
  }

  if (
    value.startsWith('storage/')
  ) {
    return `${backendBaseUrl.value}/${value}`
  }

  if (
    value.startsWith('/uploads/')
  ) {
    return `${backendBaseUrl.value}${value}`
  }

  if (
    value.startsWith('uploads/')
  ) {
    return `${backendBaseUrl.value}/${value}`
  }

  return `${backendBaseUrl.value}/storage/${value.replace(/^\/+/, '')}`
}

// =====================================================
// AVATAR URL
// =====================================================

function getAvatarUrl(user) {
  if (!user) {
    return ''
  }

  const avatar =
    user.avatar ||
    user.google_avatar ||
    user.foto ||
    ''

  if (!avatar) {
    return ''
  }

  if (
    avatar.startsWith('http://') ||
    avatar.startsWith('https://') ||
    avatar.startsWith('//')
  ) {
    return avatar
  }

  if (
    avatar.startsWith('/storage/')
  ) {
    return `${backendBaseUrl.value}${avatar}`
  }

  if (
    avatar.startsWith('storage/')
  ) {
    return `${backendBaseUrl.value}/${avatar}`
  }

  return `${backendBaseUrl.value}/storage/${avatar.replace(/^\/+/, '')}`
}

// =====================================================
// FETCH DETAIL
// =====================================================

async function fetchDetail() {
  loading.value = true
  error.value = ''

  activeBeforeIndex.value = 0
  activeAfterIndex.value = 0

  try {
    const response =
      await api.get(
        `/laporans/${route.params.id}`
      )

    const data =
      response.data?.data ??
      response.data

    if (!data) {
      throw new Error(
        'Data laporan tidak ditemukan.'
      )
    }

    laporan.value = data

    selectedPetugasId.value =
      laporan.value.petugas_id ||
      laporan.value.petugas?.id ||
      ''

    showMap.value = false
  } catch (err) {
    console.error(
      'Gagal mengambil detail laporan:',
      err
    )

    error.value =
      err.response?.data?.message ||
      'Gagal mengambil detail laporan.'
  } finally {
    loading.value = false
  }
}

// =====================================================
// FETCH PETUGAS
// =====================================================

async function fetchPetugas() {
  try {
    const response =
      await api.get(
        '/users/petugas'
      )

    const data =
      response.data?.data ??
      response.data ??
      []

    petugasList.value =
      Array.isArray(data)
        ? data
        : []
  } catch (err) {
    console.error(
      'Gagal mengambil data petugas:',
      err
    )

    petugasList.value = []
  }
}

// =====================================================
// ASSIGN PETUGAS
// =====================================================

async function assignPetugas() {
  if (
    !laporan.value ||
    !selectedPetugasId.value
  ) {
    return
  }

  assigning.value = true

  try {
    await api.put(
      `/laporans/${laporan.value.id}/assign`,
      {
        petugas_id: Number(
          selectedPetugasId.value
        )
      }
    )

    await fetchDetail()

    alert(
      'Petugas berhasil ditugaskan.'
    )
  } catch (err) {
    console.error(
      'Gagal menugaskan petugas:',
      err
    )

    alert(
      err.response?.data?.message ||
      'Gagal menugaskan petugas.'
    )
  } finally {
    assigning.value = false
  }
}

// =====================================================
// UPDATE STATUS
// =====================================================

async function updateStatus(event) {
  const newStatus =
    event.target.value

  if (
    !laporan.value ||
    !newStatus ||
    updating.value
  ) {
    return
  }

  const oldStatus =
    laporan.value.status

  if (
    newStatus === oldStatus
  ) {
    return
  }

  updating.value = true

  try {
    await api.put(
      `/laporans/${laporan.value.id}`,
      {
        status: newStatus
      }
    )

    await fetchDetail()

    alert(
      'Status laporan berhasil diperbarui.'
    )
  } catch (err) {
    console.error(
      'Gagal memperbarui status:',
      err
    )

    alert(
      err.response?.data?.message ||
      'Gagal memperbarui status laporan.'
    )
  } finally {
    updating.value = false
  }
}

// =====================================================
// DELETE LAPORAN
// =====================================================

async function deleteLaporan() {
  if (
    !laporan.value ||
    deleting.value
  ) {
    return
  }

  const yakin = window.confirm(
    `Yakin ingin menghapus laporan "${laporan.value.judul}"?`
  )

  if (!yakin) {
    return
  }

  deleting.value = true

  try {
    await api.delete(
      `/laporans/${laporan.value.id}`
    )

    alert(
      'Laporan berhasil dihapus.'
    )

    router.push(
      '/admin/laporan'
    )
  } catch (err) {
    console.error(
      'Gagal menghapus laporan:',
      err
    )

    alert(
      err.response?.data?.message ||
      'Gagal menghapus laporan.'
    )
  } finally {
    deleting.value = false
  }
}

// =====================================================
// NAVIGASI
// =====================================================

function kembali() {
  router.push(
    '/admin/laporan'
  )
}

// =====================================================
// FOTO SEBELUM
// =====================================================

function nextBefore() {
  if (
    !beforePhotos.value.length
  ) {
    return
  }

  activeBeforeIndex.value =
    (
      activeBeforeIndex.value +
      1
    ) %
    beforePhotos.value.length
}

function prevBefore() {
  if (
    !beforePhotos.value.length
  ) {
    return
  }

  activeBeforeIndex.value =
    (
      activeBeforeIndex.value -
      1 +
      beforePhotos.value.length
    ) %
    beforePhotos.value.length
}

// =====================================================
// FOTO SESUDAH
// =====================================================

function nextAfter() {
  if (
    !afterPhotos.value.length
  ) {
    return
  }

  activeAfterIndex.value =
    (
      activeAfterIndex.value +
      1
    ) %
    afterPhotos.value.length
}

function prevAfter() {
  if (
    !afterPhotos.value.length
  ) {
    return
  }

  activeAfterIndex.value =
    (
      activeAfterIndex.value -
      1 +
      afterPhotos.value.length
    ) %
    afterPhotos.value.length
}

// =====================================================
// GOOGLE MAPS
// =====================================================

function bukaGoogleMaps() {
  const latitude =
    Number(
      laporan.value?.latitude
    )

  const longitude =
    Number(
      laporan.value?.longitude
    )

  if (
    !Number.isFinite(latitude) ||
    !Number.isFinite(longitude)
  ) {
    return
  }

  const url =
    `https://www.google.com/maps?q=${latitude},${longitude}`

  window.open(
    url,
    '_blank',
    'noopener,noreferrer'
  )
}

function toggleMap() {
  showMap.value =
    !showMap.value
}

// =====================================================
// LIFECYCLE
// =====================================================

onMounted(async () => {
  await Promise.all([
    fetchDetail(),
    fetchPetugas()
  ])
})
</script>

<template>
  <AdminLayout>
    <div class="detail-page">

      <!-- HEADER -->
      <div class="page-header">
        <button
          type="button"
          class="back-button"
          @click="kembali"
        >
          ← Kembali
        </button>

        <div>
          <h1>Detail Laporan</h1>

          <p>
            Lihat informasi lengkap dan
            proses pengerjaan laporan.
          </p>
        </div>
      </div>

      <!-- LOADING -->
      <div
        v-if="loading"
        class="state-card"
      >
        <div class="spinner"></div>

        <p>
          Memuat detail laporan...
        </p>
      </div>

      <!-- ERROR -->
      <div
        v-else-if="error"
        class="state-card error-state"
      >
        <div class="state-icon">
          ⚠️
        </div>

        <h3>
          Terjadi Kesalahan
        </h3>

        <p>
          {{ error }}
        </p>

        <button
          type="button"
          class="retry-button"
          @click="fetchDetail"
        >
          Coba Lagi
        </button>
      </div>

      <!-- DETAIL -->
      <div
        v-else-if="laporan"
        class="content"
      >

        <!-- REPORT HEADER -->
        <section
          class="card report-header"
        >
          <div class="report-main">

            <div class="report-meta">
              <span
                :class="[
                  'status-badge',
                  statusClass
                ]"
              >
                {{
                  statusMap[
                    laporan.status
                  ] ||
                  laporan.status
                }}
              </span>

              <span class="report-id">
                #{{ laporan.id }}
              </span>

              <span
                class="category-badge"
              >
                {{ kategoriInfo.emoji }}
                {{ kategoriInfo.label }}
              </span>
            </div>

            <h2>
              {{ laporan.judul }}
            </h2>

            <p class="created-info">
              Dibuat
              {{
                formatDate(
                  laporan.created_at
                )
              }}
            </p>
          </div>

          <div class="report-person">
            <div
              v-if="
                getAvatarUrl(
                  laporan.user
                )
              "
              class="avatar"
            >
              <img
                :src="
                  getAvatarUrl(
                    laporan.user
                  )
                "
                :alt="
                  laporan.user?.name ||
                  'Warga'
                "
              />
            </div>

            <div
              v-else
              class="avatar avatar-placeholder"
            >
              {{
                (
                  laporan.user?.name ||
                  'W'
                )
                  .charAt(0)
                  .toUpperCase()
              }}
            </div>

            <div>
              <span>
                Dilaporkan oleh
              </span>

              <strong>
                {{
                  laporan.user?.name ||
                  'Warga'
                }}
              </strong>
            </div>
          </div>
        </section>

        <!-- PENUGASAN -->
        <section
          class="card assignment-card"
        >
          <div class="section-heading">
            <div>
              <span class="section-icon">
                👷
              </span>

              <div>
                <h3>
                  Penugasan Petugas
                </h3>

                <p>
                  Pilih petugas yang akan
                  menangani laporan ini.
                </p>
              </div>
            </div>
          </div>

          <div class="assignment-content">

            <div class="current-petugas">
              <span class="label">
                Petugas saat ini
              </span>

              <strong>
                {{
                  laporan.petugas?.name ||
                  'Belum ditugaskan'
                }}
              </strong>

              <small
                v-if="
                  laporan.assigned_at
                "
              >
                Ditugaskan
                {{
                  formatDate(
                    laporan.assigned_at
                  )
                }}
              </small>
            </div>

            <div class="assignment-form">
              <select
                v-model="selectedPetugasId"
                :disabled="assigning"
              >
                <option value="">
                  Pilih petugas
                </option>

                <option
                  v-for="
                    petugas in petugasList
                  "
                  :key="petugas.id"
                  :value="petugas.id"
                >
                  {{ petugas.name }}
                </option>
              </select>

              <button
                type="button"
                class="assign-button"
                :disabled="
                  !selectedPetugasId ||
                  assigning
                "
                @click="assignPetugas"
              >
                <span
                  v-if="assigning"
                  class="button-spinner"
                ></span>

                <span v-else>
                  👷
                </span>

                {{
                  assigning
                    ? 'Menyimpan...'
                    : 'Tugaskan'
                }}
              </button>
            </div>
          </div>

          <div
            v-if="laporan.deadline_at"
            :class="[
              'deadline-box',
              deadlineStatus.class
            ]"
          >
            <span>
              {{ deadlineStatus.icon }}
            </span>

            <div>
              <strong>
                {{
                  deadlineStatus.label
                }}
              </strong>

              <small>
                Deadline:
                {{
                  formatDate(
                    laporan.deadline_at
                  )
                }}
              </small>
            </div>
          </div>
        </section>

        <!-- FOTO SEBELUM -->
        <section class="card">
          <div class="section-heading">
            <div>
              <span class="section-icon">
                📷
              </span>

              <div>
                <h3>
                  Foto Sebelum
                </h3>

                <p>
                  Dokumentasi kondisi
                  saat laporan dibuat.
                </p>
              </div>
            </div>

            <span class="photo-count">
              {{ beforePhotos.length }}/5
            </span>
          </div>

          <div
            v-if="currentBeforePhoto"
            class="gallery"
          >
            <div class="main-photo">
              <img
                :src="
                  getPhotoUrl(
                    currentBeforePhoto
                  )
                "
                alt="Foto sebelum"
              />

              <button
                v-if="
                  beforePhotos.length > 1
                "
                type="button"
                class="gallery-arrow left"
                @click="prevBefore"
              >
                ‹
              </button>

              <button
                v-if="
                  beforePhotos.length > 1
                "
                type="button"
                class="gallery-arrow right"
                @click="nextBefore"
              >
                ›
              </button>

              <span class="photo-position">
                {{
                  activeBeforeIndex + 1
                }}
                /
                {{ beforePhotos.length }}
              </span>
            </div>

            <div
              v-if="
                beforePhotos.length > 1
              "
              class="thumbnails"
            >
              <button
                v-for="(
                  photo,
                  index
                ) in beforePhotos"
                :key="
                  photo.id || index
                "
                type="button"
                :class="[
                  'thumbnail',
                  {
                    active:
                      index ===
                      activeBeforeIndex
                  }
                ]"
                @click="
                  activeBeforeIndex =
                    index
                "
              >
                <img
                  :src="
                    getPhotoUrl(
                      photo
                    )
                  "
                  alt="Thumbnail"
                />
              </button>
            </div>
          </div>

          <div
            v-else
            class="empty-photo"
          >
            <span>📷</span>

            <p>
              Belum ada foto sebelum.
            </p>
          </div>
        </section>

        <!-- FOTO SESUDAH -->
        <section class="card">
          <div class="section-heading">
            <div>
              <span class="section-icon">
                ✨
              </span>

              <div>
                <h3>
                  Foto Sesudah
                </h3>

                <p>
                  Dokumentasi hasil
                  pengerjaan petugas.
                </p>
              </div>
            </div>

            <span class="photo-count">
              {{ afterPhotos.length }}/5
            </span>
          </div>

          <div
            v-if="currentAfterPhoto"
            class="gallery"
          >
            <div class="main-photo">
              <img
                :src="
                  getPhotoUrl(
                    currentAfterPhoto
                  )
                "
                alt="Foto sesudah"
              />

              <button
                v-if="
                  afterPhotos.length > 1
                "
                type="button"
                class="gallery-arrow left"
                @click="prevAfter"
              >
                ‹
              </button>

              <button
                v-if="
                  afterPhotos.length > 1
                "
                type="button"
                class="gallery-arrow right"
                @click="nextAfter"
              >
                ›
              </button>

              <span class="photo-position">
                {{
                  activeAfterIndex + 1
                }}
                /
                {{ afterPhotos.length }}
              </span>
            </div>

            <div
              v-if="
                afterPhotos.length > 1
              "
              class="thumbnails"
            >
              <button
                v-for="(
                  photo,
                  index
                ) in afterPhotos"
                :key="
                  photo.id || index
                "
                type="button"
                :class="[
                  'thumbnail',
                  {
                    active:
                      index ===
                      activeAfterIndex
                  }
                ]"
                @click="
                  activeAfterIndex =
                    index
                "
              >
                <img
                  :src="
                    getPhotoUrl(
                      photo
                    )
                  "
                  alt="Thumbnail"
                />
              </button>
            </div>
          </div>

          <div
            v-else
            class="empty-photo"
          >
            <span>✨</span>

            <p>
              Belum ada foto sesudah.
            </p>
          </div>
        </section>

        <!-- DESKRIPSI -->
        <section class="card">
          <div class="section-heading">
            <div>
              <span class="section-icon">
                📝
              </span>

              <div>
                <h3>
                  Deskripsi Laporan
                </h3>

                <p>
                  Informasi masalah yang
                  dilaporkan warga.
                </p>
              </div>
            </div>
          </div>

          <div class="description">
            {{
              laporan.deskripsi ||
              'Tidak ada deskripsi.'
            }}
          </div>
        </section>

        <!-- LOKASI -->
        <section class="card">
          <div class="section-heading">
            <div>
              <span class="section-icon">
                📍
              </span>

              <div>
                <h3>
                  Lokasi
                </h3>

                <p>
                  Lokasi tempat laporan
                  dibuat.
                </p>
              </div>
            </div>
          </div>

          <div class="location-info">
            <div class="location-text">
              <strong>
                {{
                  laporan.lokasi ||
                  'Lokasi tidak tersedia'
                }}
              </strong>

              <span
                v-if="
                  laporan.latitude !==
                    null &&
                  laporan.latitude !==
                    undefined &&
                  laporan.longitude !==
                    null &&
                  laporan.longitude !==
                    undefined
                "
              >
                Koordinat:
                {{ laporan.latitude }},
                {{ laporan.longitude }}
              </span>

              <span v-else>
                Koordinat tidak tersedia
              </span>
            </div>

            <div
              v-if="
                laporan.latitude !==
                  null &&
                laporan.latitude !==
                  undefined &&
                laporan.longitude !==
                  null &&
                laporan.longitude !==
                  undefined
              "
              class="map-actions"
            >
              <button
                type="button"
                class="map-button"
                @click="
                  bukaGoogleMaps
                "
              >
                🗺️ Buka Google Maps
              </button>

              <button
                type="button"
                class="map-preview-button"
                @click="
                  toggleMap
                "
              >
                {{
                  showMap
                    ? '✕ Sembunyikan Peta'
                    : '👁️ Tampilkan Peta'
                }}
              </button>
            </div>
          </div>

          <!-- GOOGLE MAP PREVIEW -->
          <div
            v-if="
              showMap &&
              laporan.latitude !==
                null &&
              laporan.latitude !==
                undefined &&
              laporan.longitude !==
                null &&
              laporan.longitude !==
                undefined
            "
            class="map-container"
          >
            <iframe
              :src="
                `https://www.google.com/maps?q=${laporan.latitude},${laporan.longitude}&output=embed`
              "
              loading="lazy"
              title="Lokasi laporan"
            ></iframe>
          </div>
        </section>

        <!-- INFORMASI ORANG -->
        <div class="two-columns">

          <!-- PELAPOR -->
          <section class="card">
            <div class="section-heading">
              <div>
                <span class="section-icon">
                  👤
                </span>

                <div>
                  <h3>
                    Pelapor
                  </h3>

                  <p>
                    Informasi warga yang
                    membuat laporan.
                  </p>
                </div>
              </div>
            </div>

            <div class="person-card">
              <div
                v-if="
                  getAvatarUrl(
                    laporan.user
                  )
                "
                class="large-avatar"
              >
                <img
                  :src="
                    getAvatarUrl(
                      laporan.user
                    )
                  "
                  :alt="
                    laporan.user?.name ||
                    'Warga'
                  "
                />
              </div>

              <div
                v-else
                class="
                  large-avatar
                  avatar-placeholder
                "
              >
                {{
                  (
                    laporan.user?.name ||
                    'W'
                  )
                    .charAt(0)
                    .toUpperCase()
                }}
              </div>

              <div>
                <strong>
                  {{
                    laporan.user?.name ||
                    'Tidak diketahui'
                  }}
                </strong>

                <span>
                  {{
                    laporan.user?.email ||
                    '-'
                  }}
                </span>
              </div>
            </div>
          </section>

          <!-- PETUGAS -->
          <section class="card">
            <div class="section-heading">
              <div>
                <span class="section-icon">
                  👷
                </span>

                <div>
                  <h3>
                    Petugas
                  </h3>

                  <p>
                    Petugas yang menangani
                    laporan.
                  </p>
                </div>
              </div>
            </div>

            <div
              v-if="laporan.petugas"
              class="person-card"
            >
              <div
                v-if="
                  getAvatarUrl(
                    laporan.petugas
                  )
                "
                class="large-avatar"
              >
                <img
                  :src="
                    getAvatarUrl(
                      laporan.petugas
                    )
                  "
                  :alt="
                    laporan.petugas.name
                  "
                />
              </div>

              <div
                v-else
                class="
                  large-avatar
                  petugas-avatar
                "
              >
                {{
                  laporan.petugas.name
                    ?.charAt(0)
                    .toUpperCase()
                }}
              </div>

              <div>
                <strong>
                  {{
                    laporan.petugas.name
                  }}
                </strong>

                <span>
                  {{
                    laporan.petugas.email ||
                    '-'
                  }}
                </span>
              </div>
            </div>

            <div
              v-else
              class="not-assigned"
            >
              <span>👷</span>

              <p>
                Belum ada petugas
                yang ditugaskan.
              </p>
            </div>
          </section>
        </div>

        <!-- TIMELINE -->
        <section class="card">
          <div class="section-heading">
            <div>
              <span class="section-icon">
                🕒
              </span>

              <div>
                <h3>
                  Timeline Pengerjaan
                </h3>

                <p>
                  Perjalanan laporan dari
                  dibuat sampai selesai.
                </p>
              </div>
            </div>
          </div>

          <div class="timeline">
            <div
              v-for="
                item in timelineItems
              "
              :key="item.title"
              :class="[
                'timeline-item',
                {
                  completed:
                    item.done
                }
              ]"
            >
              <div class="timeline-icon">
                {{ item.icon }}
              </div>

              <div
                class="
                  timeline-content
                "
              >
                <strong>
                  {{ item.title }}
                </strong>

                <span
                  v-if="item.date"
                >
                  {{
                    formatDate(
                      item.date
                    )
                  }}
                </span>

                <span
                  v-else
                  class="not-yet"
                >
                  Belum dilakukan
                </span>
              </div>
            </div>
          </div>

          <div class="time-summary">
            <div>
              <span>
                Dibuat
              </span>

              <strong>
                {{
                  formatShortDate(
                    laporan.created_at
                  )
                }}
              </strong>
            </div>

            <div>
              <span>
                Diperbarui
              </span>

              <strong>
                {{
                  formatShortDate(
                    laporan.updated_at
                  )
                }}
              </strong>
            </div>

            <div>
              <span>
                Deadline
              </span>

              <strong>
                {{
                  formatShortDate(
                    laporan.deadline_at
                  )
                }}
              </strong>
            </div>
          </div>
        </section>

        <!-- RIWAYAT -->
        <section class="card">
          <div class="section-heading">
            <div>
              <span class="section-icon">
                📋
              </span>

              <div>
                <h3>
                  Riwayat Aktivitas
                </h3>

                <p>
                  Catatan perubahan dan
                  aktivitas laporan.
                </p>
              </div>
            </div>
          </div>

          <div
            v-if="logs.length"
            class="activity-list"
          >
            <div
              v-for="log in logs"
              :key="log.id"
              class="activity-item"
            >
              <div
                class="activity-dot"
              ></div>

              <div
                class="activity-body"
              >
                <div
                  class="activity-top"
                >
                  <strong>
                    {{
                      log.tipe ||
                      'Aktivitas'
                    }}
                  </strong>

                  <span>
                    {{
                      formatDate(
                        log.created_at
                      )
                    }}
                  </span>
                </div>

                <p>
                  {{
                    log.keterangan ||
                    '-'
                  }}
                </p>

                <small
                  v-if="log.user"
                >
                  oleh
                  {{
                    log.user.name
                  }}
                </small>
              </div>
            </div>
          </div>

          <div
            v-else
            class="empty-state"
          >
            <span>📋</span>

            <p>
              Belum ada riwayat
              aktivitas.
            </p>
          </div>
        </section>

        <!-- TANGGAPAN -->
        <section class="card">
          <div class="section-heading">
            <div>
              <span class="section-icon">
                💬
              </span>

              <div>
                <h3>
                  Tanggapan
                </h3>

                <p>
                  Tanggapan yang berkaitan
                  dengan laporan.
                </p>
              </div>
            </div>
          </div>

          <div
            v-if="tanggapans.length"
            class="comment-list"
          >
            <div
              v-for="
                item in tanggapans
              "
              :key="item.id"
              class="comment-item"
            >
              <div
                class="comment-avatar"
              >
                {{
                  (
                    item.user?.name ||
                    item.nama ||
                    'W'
                  )
                    .charAt(0)
                    .toUpperCase()
                }}
              </div>

              <div
                class="comment-body"
              >
                <div
                  class="
                    comment-header
                  "
                >
                  <strong>
                    {{
                      item.user?.name ||
                      item.nama ||
                      'Pengguna'
                    }}
                  </strong>

                  <span>
                    {{
                      formatDate(
                        item.created_at
                      )
                    }}
                  </span>
                </div>

                <p>
                  {{
                    tanggapanText(
                      item
                    )
                  }}
                </p>
              </div>
            </div>
          </div>

          <div
            v-else
            class="empty-state"
          >
            <span>💬</span>

            <p>
              Belum ada tanggapan.
            </p>
          </div>
        </section>

        <!-- ALASAN DITOLAK -->
        <section
          v-if="
            laporan.status ===
              'ditolak' &&
            laporan.alasan_ditolak
          "
          class="
            card
            rejection-card
          "
        >
          <div class="section-heading">
            <div>
              <span class="section-icon">
                ⚠️
              </span>

              <div>
                <h3>
                  Alasan Laporan Ditolak
                </h3>

                <p>
                  Alasan yang diberikan
                  saat laporan ditolak.
                </p>
              </div>
            </div>
          </div>

          <div
            class="
              rejection-content
            "
          >
            {{
              laporan.alasan_ditolak
            }}
          </div>
        </section>

        <!-- KELOLA -->
        <section
          class="
            card
            manage-card
          "
        >
          <div class="section-heading">
            <div>
              <span class="section-icon">
                ⚙️
              </span>

              <div>
                <h3>
                  Kelola Laporan
                </h3>

                <p>
                  Ubah status atau hapus
                  laporan.
                </p>
              </div>
            </div>
          </div>

          <div class="manage-content">
            <div
              class="status-control"
            >
              <label
                for="status"
              >
                Status Laporan
              </label>

              <select
                id="status"
                :value="laporan.status"
                :disabled="updating"
                @change="updateStatus"
              >
                <option value="baru">
                  Menunggu
                </option>

                <option value="diproses">
                  Diproses
                </option>

                <option value="selesai">
                  Selesai
                </option>

                <option value="ditolak">
                  Ditolak
                </option>
              </select>
            </div>

            <button
              type="button"
              class="delete-button"
              :disabled="deleting"
              @click="deleteLaporan"
            >
              <span
                v-if="deleting"
                class="button-spinner delete-spinner"
              ></span>

              <span v-else>
                🗑️
              </span>

              {{
                deleting
                  ? 'Menghapus...'
                  : 'Hapus Laporan'
              }}
            </button>
          </div>
        </section>

      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.detail-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

.page-header {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 24px;
}

.page-header h1 {
  margin: 0;
  color: #172033;
  font-size: 28px;
}

.page-header p {
  margin: 6px 0 0;
  color: #718096;
}

.back-button {
  border: 1px solid #dbe2ea;
  background: white;
  color: #334155;
  border-radius: 10px;
  padding: 10px 16px;
  cursor: pointer;
  font-weight: 600;
  transition: .2s ease;
}

.back-button:hover {
  background: #f8fafc;
}

.content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.card {
  background: white;
  border: 1px solid #e6eaf0;
  border-radius: 16px;
  padding: 24px;
  box-shadow:
    0 4px 16px
      rgba(15, 23, 42, .04);
}

.report-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.report-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.report-header h2 {
  margin: 14px 0 6px;
  color: #172033;
  font-size: 26px;
}

.created-info {
  margin: 0;
  color: #718096;
  font-size: 14px;
}

.status-badge,
.category-badge,
.report-id {
  padding: 7px 11px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.status-badge.baru {
  background: #fff7ed;
  color: #c2410c;
}

.status-badge.diproses {
  background: #eff6ff;
  color: #1d4ed8;
}

.status-badge.selesai {
  background: #ecfdf5;
  color: #047857;
}

.status-badge.ditolak {
  background: #fef2f2;
  color: #dc2626;
}

.category-badge {
  background: #f0fdfa;
  color: #0f766e;
}

.report-id {
  background: #f1f5f9;
  color: #64748b;
}

.report-person {
  display: flex;
  align-items: center;
  gap: 12px;
}

.report-person span,
.report-person strong {
  display: block;
}

.report-person span {
  color: #94a3b8;
  font-size: 12px;
}

.report-person strong {
  margin-top: 3px;
  color: #334155;
}

.avatar,
.large-avatar {
  flex-shrink: 0;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e2e8f0;
  color: #475569;
  font-weight: 800;
}

.avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
}

.large-avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
}

.avatar img,
.large-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  background: #f1f5f9;
  color: #64748b;
}

.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 20px;
}

.section-heading > div {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border-radius: 10px;
  font-size: 19px;
  flex-shrink: 0;
}

.section-heading h3 {
  margin: 0;
  color: #1e293b;
  font-size: 17px;
}

.section-heading p {
  margin: 4px 0 0;
  color: #94a3b8;
  font-size: 13px;
}

.photo-count {
  background: #f1f5f9;
  color: #64748b;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
}

.gallery {
  width: 100%;
}

.main-photo {
  position: relative;
  height: 430px;
  overflow: hidden;
  border-radius: 14px;
  background: #f1f5f9;
}

.main-photo img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  background: #f8fafc;
}

.gallery-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 42px;
  height: 42px;
  border: none;
  border-radius: 50%;
  background: rgba(15, 23, 42, .65);
  color: white;
  font-size: 30px;
  line-height: 1;
  cursor: pointer;
  z-index: 2;
}

.gallery-arrow.left {
  left: 16px;
}

.gallery-arrow.right {
  right: 16px;
}

.gallery-arrow:hover {
  background: rgba(15, 23, 42, .82);
}

.photo-position {
  position: absolute;
  right: 14px;
  bottom: 14px;
  padding: 6px 10px;
  border-radius: 8px;
  background: rgba(15, 23, 42, .7);
  color: white;
  font-size: 12px;
}

.thumbnails {
  display: flex;
  gap: 10px;
  margin-top: 12px;
  overflow-x: auto;
}

.thumbnail {
  width: 76px;
  height: 58px;
  flex-shrink: 0;
  padding: 0;
  overflow: hidden;
  border: 2px solid transparent;
  border-radius: 8px;
  background: #f1f5f9;
  cursor: pointer;
}

.thumbnail.active {
  border-color: #0d9488;
}

.thumbnail img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.empty-photo,
.empty-state {
  min-height: 140px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #94a3b8;
  text-align: center;
}

.empty-photo span,
.empty-state span {
  font-size: 30px;
}

.empty-photo p,
.empty-state p {
  margin: 8px 0 0;
}

.description {
  color: #475569;
  line-height: 1.8;
  white-space: pre-line;
}

.location-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.location-text strong,
.location-text span {
  display: block;
}

.location-text strong {
  color: #334155;
}

.location-text span {
  margin-top: 5px;
  color: #94a3b8;
  font-size: 13px;
}

.map-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.map-button,
.map-preview-button {
  border: none;
  border-radius: 9px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: .2s ease;
}

.map-button {
  background: #eff6ff;
  color: #1d4ed8;
}

.map-button:hover {
  background: #dbeafe;
}

.map-preview-button {
  background: #f1f5f9;
  color: #475569;
}

.map-preview-button:hover {
  background: #e2e8f0;
}

.map-container {
  height: 320px;
  margin-top: 16px;
  overflow: hidden;
  border-radius: 12px;
  background: #f1f5f9;
}

.map-container iframe {
  width: 100%;
  height: 100%;
  border: 0;
}

.two-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.person-card {
  display: flex;
  align-items: center;
  gap: 14px;
}

.person-card strong,
.person-card span {
  display: block;
}

.person-card strong {
  color: #334155;
}

.person-card span {
  margin-top: 5px;
  color: #94a3b8;
  font-size: 13px;
}

.petugas-avatar {
  background: #ecfdf5;
  color: #047857;
}

.not-assigned {
  min-height: 80px;
  display: flex;
  align-items: center;
  gap: 12px;
  color: #94a3b8;
}

.not-assigned span {
  font-size: 26px;
}

.not-assigned p {
  margin: 0;
}

.assignment-content {
  display: grid;
  grid-template-columns: 1fr 1.4fr;
  gap: 20px;
  align-items: end;
}

.current-petugas {
  padding: 16px;
  border-radius: 12px;
  background: #f8fafc;
}

.current-petugas .label {
  display: block;
  margin-bottom: 6px;
  color: #94a3b8;
  font-size: 12px;
}

.current-petugas strong {
  display: block;
  color: #334155;
}

.current-petugas small {
  display: block;
  margin-top: 5px;
  color: #94a3b8;
}

.assignment-form {
  display: flex;
  gap: 10px;
}

.assignment-form select,
.status-control select {
  width: 100%;
  border: 1px solid #dbe2ea;
  border-radius: 10px;
  padding: 11px 12px;
  background: white;
  color: #334155;
  outline: none;
}

.assignment-form select:focus,
.status-control select:focus {
  border-color: #0d9488;
}

.assign-button {
  min-height: 42px;
  border: none;
  border-radius: 10px;
  padding: 0 18px;
  background: #0d9488;
  color: white;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.assign-button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.deadline-box {
  margin-top: 18px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-radius: 12px;
}

.deadline-box > span {
  font-size: 22px;
}

.deadline-box strong,
.deadline-box small {
  display: block;
}

.deadline-box small {
  margin-top: 4px;
}

.deadline-active {
  background: #eff6ff;
  color: #1d4ed8;
}

.deadline-done {
  background: #ecfdf5;
  color: #047857;
}

.deadline-late {
  background: #fef2f2;
  color: #dc2626;
}

.deadline-none {
  background: #f8fafc;
  color: #64748b;
}

.timeline {
  position: relative;
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.timeline::before {
  content: '';
  position: absolute;
  top: 20px;
  left: 40px;
  right: 40px;
  height: 2px;
  background: #e2e8f0;
}

.timeline-item {
  position: relative;
  z-index: 1;
  flex: 1;
  text-align: center;
}

.timeline-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 10px;
  border: 3px solid white;
  border-radius: 50%;
  background: #f1f5f9;
  box-shadow:
    0 0 0 1px #e2e8f0;
  filter: grayscale(1);
}

.timeline-item.completed
  .timeline-icon {
  background: #ecfdf5;
  box-shadow:
    0 0 0 1px #10b981;
  filter: none;
}

.timeline-content strong,
.timeline-content span {
  display: block;
}

.timeline-content strong {
  color: #334155;
  font-size: 13px;
}

.timeline-content span {
  margin-top: 4px;
  color: #64748b;
  font-size: 11px;
}

.timeline-content .not-yet {
  color: #cbd5e1;
}

.time-summary {
  display: grid;
  grid-template-columns:
    repeat(3, 1fr);
  gap: 12px;
  margin-top: 24px;
}

.time-summary > div {
  padding: 14px;
  border-radius: 10px;
  background: #f8fafc;
}

.time-summary span,
.time-summary strong {
  display: block;
}

.time-summary span {
  color: #94a3b8;
  font-size: 12px;
}

.time-summary strong {
  margin-top: 5px;
  color: #334155;
  font-size: 13px;
}

.activity-list,
.comment-list {
  display: flex;
  flex-direction: column;
}

.activity-item {
  display: flex;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid #eef2f7;
}

.activity-item:last-child,
.comment-item:last-child {
  border-bottom: none;
}

.activity-dot {
  width: 10px;
  height: 10px;
  flex-shrink: 0;
  margin-top: 6px;
  border-radius: 50%;
  background: #0d9488;
}

.activity-body {
  flex: 1;
}

.activity-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.activity-top strong {
  color: #334155;
}

.activity-top span {
  color: #94a3b8;
  font-size: 12px;
}

.activity-body p {
  margin: 5px 0;
  color: #64748b;
  line-height: 1.6;
}

.activity-body small {
  color: #94a3b8;
}

.comment-item {
  display: flex;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid #eef2f7;
}

.comment-avatar {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #f1f5f9;
  color: #475569;
  font-weight: 800;
}

.comment-body {
  flex: 1;
  min-width: 0;
}

.comment-header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.comment-header strong {
  color: #334155;
}

.comment-header span {
  color: #94a3b8;
  font-size: 12px;
}

.comment-body p {
  margin: 6px 0 0;
  color: #64748b;
  line-height: 1.6;
  white-space: pre-line;
}

.rejection-card {
  border-color: #fecaca;
  background: #fffafa;
}

.rejection-content {
  padding: 16px;
  border-radius: 10px;
  background: #fef2f2;
  color: #991b1b;
  line-height: 1.7;
  white-space: pre-line;
}

.manage-content {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 20px;
}

.status-control {
  width: 300px;
}

.status-control label {
  display: block;
  margin-bottom: 7px;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
}

.delete-button {
  min-height: 42px;
  border: none;
  border-radius: 10px;
  padding: 0 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: #fee2e2;
  color: #b91c1c;
  font-weight: 700;
  cursor: pointer;
}

.delete-button:hover:not(:disabled) {
  background: #fecaca;
}

.delete-button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.state-card {
  min-height: 300px;
  background: white;
  border: 1px solid #e6eaf0;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  color: #64748b;
}

.state-card h3 {
  margin: 10px 0 5px;
  color: #334155;
}

.state-card p {
  margin: 0;
}

.state-icon {
  font-size: 40px;
}

.retry-button {
  margin-top: 18px;
  border: none;
  border-radius: 9px;
  padding: 10px 16px;
  background: #0d9488;
  color: white;
  font-weight: 700;
  cursor: pointer;
}

.retry-button:hover {
  background: #0f766e;
}

.spinner {
  width: 38px;
  height: 38px;
  border: 4px solid #e2e8f0;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation:
    spin .8s linear infinite;
}

.button-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,.4);
  border-top-color: white;
  border-radius: 50%;
  animation:
    spin .7s linear infinite;
  vertical-align: middle;
}

.delete-spinner {
  border-color: rgba(185,28,28,.25);
  border-top-color: #b91c1c;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 900px) {
  .two-columns {
    grid-template-columns: 1fr;
  }

  .assignment-content {
    grid-template-columns: 1fr;
  }

  .timeline {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .timeline::before {
    top: 20px;
    bottom: 20px;
    left: 20px;
    width: 2px;
    height: auto;
  }

  .timeline-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    text-align: left;
  }

  .timeline-icon {
    margin: 0;
    flex-shrink: 0;
  }

  .time-summary {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 700px) {
  .detail-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .report-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .assignment-form {
    flex-direction: column;
  }

  .assign-button {
    width: 100%;
  }

  .main-photo {
    height: 280px;
  }

  .location-info {
    flex-direction: column;
    align-items: flex-start;
  }

  .map-actions {
    width: 100%;
  }

  .map-button,
  .map-preview-button {
    flex: 1;
  }

  .manage-content {
    flex-direction: column;
    align-items: stretch;
  }

  .status-control {
    width: 100%;
  }

  .delete-button {
    width: 100%;
  }

  .activity-top,
  .comment-header {
    flex-direction: column;
    gap: 4px;
  }
}
</style>