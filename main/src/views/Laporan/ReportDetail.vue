<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Navbar from '../../components/Navbar.vue'
import Footer from '../../components/Footer.vue'
import api from '../../services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const route = useRoute()
const router = useRouter()

const laporan = ref(null)
const tanggapans = ref([])
const loading = ref(true)
const loadingTanggapan = ref(false)
const error = ref('')
const tanggapanError = ref('')
const komentar = ref('')
const submittingKomentar = ref(false)

const currentPhoto = ref(null)
const showPhotoModal = ref(false)

const currentTime = ref(Date.now())
const activeFotoIndex = ref(0)

const replyTo = ref(null)
const replyText = ref('')

const reacting = ref(null)
const statusJustUpdated = ref(false)

const mapContainer = ref(null)

let statusTimer = null
let deadlineTimer = null
let map = null
let marker = null

const id = route.params.id

// =====================================================
// STATUS
// =====================================================

const statusMap = {
  baru: {
    label: 'Menunggu',
    class: 'status-baru',
    icon: '⏳'
  },

  diproses: {
    label: 'Diproses',
    class: 'status-diproses',
    icon: '🔧'
  },

  selesai: {
    label: 'Selesai',
    class: 'status-selesai',
    icon: '✅'
  },

  ditolak: {
    label: 'Ditolak',
    class: 'status-ditolak',
    icon: '❌'
  }
}

const currentStatus = computed(() => {
  const status = laporan.value?.status || 'baru'

  return (
    statusMap[status] || {
      label: status,
      class: '',
      icon: '📌'
    }
  )
})

const statusData = computed(() => {
  return currentStatus.value
})

// =====================================================
// KATEGORI
// =====================================================

const kategoriIconMap = {
  jalan: '🚧',
  sampah: '🗑️',
  lampu: '💡',
  selokan: '🌊',
  fasilitas: '🏞️',
  keamanan: '🛡️',
  kebersihan: '🧹',
  lainnya: '📋'
}

const kategoriNama = computed(() => {
  const kategori = laporan.value?.kategoriRelasi

  return (
    kategori?.nama ||
    kategori?.name ||
    laporan.value?.kategori ||
    'Lainnya'
  )
})

const kategoriIcon = computed(() => {
  const nama = kategoriNama.value.toLowerCase()

  for (const key in kategoriIconMap) {
    if (nama.includes(key)) {
      return kategoriIconMap[key]
    }
  }

  return '📋'
})

// =====================================================
// FOTO
// =====================================================

const semuaFoto = computed(() => {
  if (!laporan.value) {
    return []
  }

  const files = Array.isArray(laporan.value.files)
    ? laporan.value.files
    : []

  const fotoFiles = files
    .filter(file => file?.url)
    .map(file => ({
      id: file.id,
      url: file.url,
      tipe: file.tipe || 'sebelum'
    }))

  if (fotoFiles.length) {
    return fotoFiles
  }

  if (laporan.value.foto_url) {
    return [
      {
        id: 'utama',
        url: laporan.value.foto_url,
        tipe: 'sebelum'
      }
    ]
  }

  return []
})

const fotoList = computed(() => {
  return semuaFoto.value
    .filter(foto => foto.tipe === 'sebelum')
    .map(foto => foto.url)
})

const fotoSesudahList = computed(() => {
  return semuaFoto.value
    .filter(foto => foto.tipe === 'sesudah')
    .map(foto => foto.url)
})

const activeFoto = computed(() => {
  return (
    fotoList.value[activeFotoIndex.value] ||
    fotoList.value[0] ||
    ''
  )
})

const fotoSebelum = computed(() => {
  return semuaFoto.value.filter(
    foto => foto.tipe === 'sebelum'
  )
})

const fotoSesudah = computed(() => {
  return semuaFoto.value.filter(
    foto => foto.tipe === 'sesudah'
  )
})

// =====================================================
// INFO PETUGAS
// =====================================================

const namaPetugas = computed(() => {
  return laporan.value?.petugas?.name || ''
})

const alasanDitolak = computed(() => {
  return laporan.value?.alasan_ditolak || ''
})

// =====================================================
// LOKASI
// =====================================================

const hasCoordinates = computed(() => {
  if (!laporan.value) {
    return false
  }

  const latitude = Number(
    laporan.value.latitude
  )

  const longitude = Number(
    laporan.value.longitude
  )

  return (
    Number.isFinite(latitude) &&
    Number.isFinite(longitude)
  )
})

// =====================================================
// LOGIN
// =====================================================

const isLoggedIn = computed(() => {
  return Boolean(
    sessionStorage.getItem('token')
  )
})

// =====================================================
// KOMENTAR
// =====================================================

const totalKomentar = computed(() => {
  return Array.isArray(tanggapans.value)
    ? tanggapans.value.length
    : 0
})

// =====================================================
// TIMELINE
// =====================================================

const logIconMap = {
  dibuat: '📝',
  diassign: '🔧',
  status_berubah: '🔄',
  foto_ditambahkan: '📷'
}

const timelineLogs = computed(() => {
  const logs = laporan.value?.logs

  return Array.isArray(logs)
    ? logs
    : []
})

// =====================================================
// DEADLINE
// =====================================================

const deadlineInfo = computed(() => {
  if (!laporan.value?.deadline_at) {
    return null
  }

  const deadline = new Date(
    laporan.value.deadline_at
  )

  const now = new Date(
    currentTime.value
  )

  if (
    Number.isNaN(
      deadline.getTime()
    )
  ) {
    return null
  }

  if (
    laporan.value.status === 'selesai'
  ) {
    return {
      type: 'selesai',
      label: 'Pengerjaan selesai',
      text: laporan.value.completed_at
        ? `Selesai ${formatDate(
            laporan.value.completed_at
          )}`
        : 'Laporan sudah selesai'
    }
  }

  const diff =
    deadline.getTime() -
    now.getTime()

  if (diff <= 0) {
    return {
      type: 'terlambat',
      label: 'Melewati batas pengerjaan',
      text:
        'Deadline pengerjaan sudah terlewati.'
    }
  }

  const totalMinutes =
    Math.floor(diff / 60000)

  const days =
    Math.floor(
      totalMinutes / 1440
    )

  const hours =
    Math.floor(
      (totalMinutes % 1440) / 60
    )

  const minutes =
    totalMinutes % 60

  let text = ''

  if (days > 0) {
    text =
      `${days} hari ${hours} jam lagi`
  } else if (hours > 0) {
    text =
      `${hours} jam ${minutes} menit lagi`
  } else {
    text =
      `${minutes} menit lagi`
  }

  return {
    type: 'aktif',
    label: 'Batas pengerjaan',
    text
  }
})

// =====================================================
// HELPER
// =====================================================

function formatDate(date) {
  if (!date) {
    return '-'
  }

  const parsedDate =
    new Date(date)

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

function logIcon(tipe) {
  return (
    logIconMap[tipe] ||
    '📌'
  )
}

function pilihFoto(index) {
  activeFotoIndex.value =
    index
}

function getInitial(name) {
  if (!name) {
    return 'W'
  }

  return name
    .trim()
    .charAt(0)
    .toUpperCase()
}

function goLogin() {
  router.push({
    path: '/login',
    query: {
      redirect: route.fullPath
    }
  })
}

function mulaiBalas(tanggapan) {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }

  replyTo.value =
    tanggapan

  replyText.value = ''

  nextTick(() => {
    document
      .getElementById(
        'reply-input'
      )
      ?.focus()
  })
}

function batalBalas() {
  replyTo.value = null
  replyText.value = ''
}

// =====================================================
// FETCH LAPORAN
// =====================================================

async function fetchLaporan() {
  loading.value = true
  error.value = ''

  try {
    const response =
      await api.get(
        `/laporans/${id}`
      )

    const data =
      response.data?.data ??
      response.data

    if (!data) {
      throw new Error(
        'Data laporan tidak ditemukan.'
      )
    }

    laporan.value =
      data

    activeFotoIndex.value = 0

    loading.value = false

    await nextTick()

    if (hasCoordinates.value) {
      initMap()
    }
  } catch (err) {
    console.error(
      'Gagal mengambil laporan:',
      err
    )

    error.value =
      err.response?.data?.message ||
      'Laporan tidak dapat ditemukan.'

    loading.value = false
  }
}

// =====================================================
// REFRESH STATUS
// =====================================================

async function refreshStatus() {
  if (!laporan.value) {
    return
  }

  try {
    const response =
      await api.get(
        `/laporans/${id}`
      )

    const data =
      response.data?.data ??
      response.data

    if (!data) {
      return
    }

    const oldStatus =
      laporan.value.status

    const oldLatitude =
      Number(
        laporan.value.latitude
      )

    const oldLongitude =
      Number(
        laporan.value.longitude
      )

    const newLatitude =
      Number(
        data.latitude ??
        laporan.value.latitude
      )

    const newLongitude =
      Number(
        data.longitude ??
        laporan.value.longitude
      )

    laporan.value = {
      ...laporan.value,
      status:
        data.status ??
        laporan.value.status,

      alasan_ditolak:
        data.alasan_ditolak ??
        laporan.value.alasan_ditolak,

      petugas:
        data.petugas ??
        laporan.value.petugas,

      assigned_at:
        data.assigned_at ??
        laporan.value.assigned_at,

      started_at:
        data.started_at ??
        laporan.value.started_at,

      deadline_at:
        data.deadline_at ??
        laporan.value.deadline_at,

      completed_at:
        data.completed_at ??
        laporan.value.completed_at,

      files:
        data.files ??
        laporan.value.files,

      logs:
        data.logs ??
        laporan.value.logs,

      latitude:
        data.latitude ??
        laporan.value.latitude,

      longitude:
        data.longitude ??
        laporan.value.longitude
    }

    if (
      oldStatus !== data.status
    ) {
      statusJustUpdated.value = true

      setTimeout(() => {
        statusJustUpdated.value =
          false
      }, 2000)
    }

    const koordinatBerubah =
      oldLatitude !==
        newLatitude ||
      oldLongitude !==
        newLongitude

    if (!hasCoordinates.value) {
      destroyMap()
      return
    }

    await nextTick()

    if (!map) {
      initMap()
    } else if (koordinatBerubah) {
      updateMapPosition()
    }
  } catch (err) {
    console.error(
      'Gagal memperbarui status:',
      err
    )
  }
}

function startStatusPolling() {
  stopStatusPolling()

  statusTimer =
    setInterval(
      refreshStatus,
      15000
    )
}

function stopStatusPolling() {
  if (statusTimer) {
    clearInterval(
      statusTimer
    )

    statusTimer = null
  }
}

function startDeadlineTimer() {
  stopDeadlineTimer()

  deadlineTimer =
    setInterval(() => {
      currentTime.value =
        Date.now()
    }, 30000)
}

function stopDeadlineTimer() {
  if (deadlineTimer) {
    clearInterval(
      deadlineTimer
    )

    deadlineTimer = null
  }
}

function handleVisibilityChange() {
  if (
    document.visibilityState !==
    'visible'
  ) {
    return
  }

  currentTime.value =
    Date.now()

  refreshStatus()

  if (map) {
    requestAnimationFrame(() => {
      map.invalidateSize()
    })
  }
}

// =====================================================
// FETCH KOMENTAR
// =====================================================

async function fetchTanggapans() {
  loadingTanggapan.value = true
  tanggapanError.value = ''

  try {
    const response =
      await api.get(
        `/laporans/${id}/tanggapans`
      )

    const data =
      response.data?.data ??
      response.data ??
      []

    tanggapans.value =
      (
        Array.isArray(data)
          ? data
          : []
      ).map(item => ({
        ...item,

        replies:
          Array.isArray(
            item.replies
          )
            ? item.replies.map(
                reply => ({
                  ...reply,
                  likes_count:
                    Number(
                      reply.likes_count ??
                      0
                    ),
                  dislikes_count:
                    Number(
                      reply.dislikes_count ??
                      0
                    ),
                  user_reaction:
                    reply.user_reaction ??
                    null
                })
              )
            : [],

        likes_count:
          Number(
            item.likes_count ??
            0
          ),

        dislikes_count:
          Number(
            item.dislikes_count ??
            0
          ),

        user_reaction:
          item.user_reaction ??
          null
      }))
  } catch (err) {
    console.error(
      'Gagal mengambil tanggapan:',
      err
    )

    tanggapanError.value =
      err.response?.data?.message ||
      'Gagal mengambil komentar.'

    tanggapans.value = []
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

  if (
    !pesan ||
    submittingKomentar.value
  ) {
    return
  }

  if (!isLoggedIn.value) {
    goLogin()
    return
  }

  submittingKomentar.value =
    true

  try {
    await api.post(
      `/laporans/${id}/tanggapans`,
      {
        isi: pesan,
        pesan: pesan
      }
    )

    komentar.value = ''

    await fetchTanggapans()
  } catch (err) {
    console.error(
      'Gagal mengirim komentar:',
      err
    )

    if (
      err.response?.status ===
      401
    ) {
      sessionStorage.removeItem(
        'token'
      )

      sessionStorage.removeItem(
        'user'
      )

      goLogin()
      return
    }

    alert(
      err.response?.data?.message ||
      'Komentar gagal dikirim.'
    )
  } finally {
    submittingKomentar.value =
      false
  }
}

// =====================================================
// KIRIM BALASAN
// =====================================================

async function kirimBalasan() {
  const pesan =
    replyText.value.trim()

  if (
    !pesan ||
    !replyTo.value ||
    submittingKomentar.value
  ) {
    return
  }

  if (!isLoggedIn.value) {
    goLogin()
    return
  }

  submittingKomentar.value =
    true

  try {
    await api.post(
      `/laporans/${id}/tanggapans`,
      {
        isi: pesan,
        pesan: pesan,
        parent_id:
          replyTo.value.id
      }
    )

    replyText.value = ''
    replyTo.value = null

    await fetchTanggapans()
  } catch (err) {
    console.error(
      'Gagal mengirim balasan:',
      err
    )

    if (
      err.response?.status ===
      401
    ) {
      sessionStorage.removeItem(
        'token'
      )

      sessionStorage.removeItem(
        'user'
      )

      goLogin()
      return
    }

    alert(
      err.response?.data?.message ||
      'Balasan gagal dikirim.'
    )
  } finally {
    submittingKomentar.value =
      false
  }
}

// =====================================================
// LIKE / DISLIKE
// =====================================================

async function reactKomentar(
  tanggapan,
  type
) {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }

  if (
    !tanggapan?.id ||
    !['like', 'dislike'].includes(
      type
    ) ||
    reacting.value ===
      tanggapan.id
  ) {
    return
  }

  reacting.value =
    tanggapan.id

  try {
    console.log(
      'REACTION REQUEST:',
      {
        tanggapan_id:
          tanggapan.id,
        type
      }
    )

    const response =
      await api.post(
        `/tanggapans/${tanggapan.id}/reaction`,
        {
          type
        }
      )

    console.log(
      'REACTION RESPONSE:',
      response.data
    )

    const responseData =
      response.data

    const data =
      responseData?.data ??
      responseData

    const updated =
      data?.tanggapan ??
      data

    if (
      updated &&
      typeof updated ===
        'object'
    ) {
      if (
        updated.likes_count !==
        undefined
      ) {
        tanggapan.likes_count =
          Number(
            updated.likes_count
          )
      }

      if (
        updated.dislikes_count !==
        undefined
      ) {
        tanggapan.dislikes_count =
          Number(
            updated.dislikes_count
          )
      }

      if (
        updated.user_reaction !==
        undefined
      ) {
        tanggapan.user_reaction =
          updated.user_reaction
      }
    }

    // Ambil ulang komentar agar
    // jumlah like/dislike selalu sinkron.
    await fetchTanggapans()
  } catch (err) {
    console.error(
      'REACTION ERROR:',
      err.response?.status,
      err.response?.data ||
        err
    )

    if (
      err.response?.status ===
      401
    ) {
      alert(
        'Sesi login kamu sudah tidak valid. Silakan login kembali.'
      )

      sessionStorage.removeItem(
        'token'
      )

      sessionStorage.removeItem(
        'user'
      )

      goLogin()
      return
    }

    if (
      err.response?.status ===
      422
    ) {
      console.error(
        'VALIDATION ERROR:',
        err.response?.data
      )

      alert(
        err.response?.data?.message ||
        'Data reaction tidak valid.'
      )

      return
    }

    alert(
      err.response?.data?.message ||
      'Reaksi gagal diproses.'
    )
  } finally {
    reacting.value =
      null
  }
}

// =====================================================
// FOTO MODAL
// =====================================================

function openPhoto(photo) {
  currentPhoto.value =
    photo

  showPhotoModal.value =
    true
}

function closePhoto() {
  currentPhoto.value =
    null

  showPhotoModal.value =
    false
}

// =====================================================
// MAP
// =====================================================

function initMap() {
  if (
    !mapContainer.value ||
    !hasCoordinates.value
  ) {
    return
  }

  const latitude =
    Number(
      laporan.value.latitude
    )

  const longitude =
    Number(
      laporan.value.longitude
    )

  if (
    !Number.isFinite(latitude) ||
    !Number.isFinite(longitude)
  ) {
    return
  }

  if (map) {
    updateMapPosition()
    return
  }

  map = L.map(
    mapContainer.value,
    {
      center: [
        latitude,
        longitude
      ],
      zoom: 16,
      zoomControl: true,
      scrollWheelZoom: false,
      attributionControl: true
    }
  )

  L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
      maxZoom: 19,
      updateWhenZooming: false,
      updateWhenIdle: true,
      attribution:
        '&copy; OpenStreetMap contributors'
    }
  ).addTo(map)

  marker =
    L.marker([
      latitude,
      longitude
    ])
      .addTo(map)
      .bindPopup(
        `<strong>${
          laporan.value.judul ||
          'Lokasi laporan'
        }</strong>`
      )

  marker.openPopup()

  requestAnimationFrame(() => {
    if (!map) {
      return
    }

    map.invalidateSize(false)

    map.setView(
      [
        latitude,
        longitude
      ],
      16,
      {
        animate: false
      }
    )
  })
}

function updateMapPosition() {
  if (
    !map ||
    !laporan.value ||
    !hasCoordinates.value
  ) {
    return
  }

  const latitude =
    Number(
      laporan.value.latitude
    )

  const longitude =
    Number(
      laporan.value.longitude
    )

  if (
    !Number.isFinite(latitude) ||
    !Number.isFinite(longitude)
  ) {
    return
  }

  const position = [
    latitude,
    longitude
  ]

  if (marker) {
    marker.setLatLng(
      position
    )

    marker.setPopupContent(
      `<strong>${
        laporan.value.judul ||
        'Lokasi laporan'
      }</strong>`
    )
  } else {
    marker =
      L.marker(position)
        .addTo(map)
        .bindPopup(
          `<strong>${
            laporan.value.judul ||
            'Lokasi laporan'
          }</strong>`
        )
  }

  map.setView(
    position,
    16,
    {
      animate: false
    }
  )

  requestAnimationFrame(() => {
    if (map) {
      map.invalidateSize(false)
    }
  })
}

function destroyMap() {
  if (map) {
    map.remove()
    map = null
  }

  marker = null
}

// =====================================================
// NAVIGASI
// =====================================================

function kembali() {
  router.back()
}

// =====================================================
// LIFECYCLE
// =====================================================

onMounted(async () => {
  await fetchLaporan()

  await fetchTanggapans()

  startStatusPolling()
  startDeadlineTimer()

  document.addEventListener(
    'visibilitychange',
    handleVisibilityChange
  )

  await nextTick()

  if (map) {
    requestAnimationFrame(() => {
      map.invalidateSize(false)
    })
  }
})

onUnmounted(() => {
  stopStatusPolling()
  stopDeadlineTimer()

  document.removeEventListener(
    'visibilitychange',
    handleVisibilityChange
  )

  destroyMap()
})
</script>

<template>
  <div class="report-detail-page">
    <Navbar />

    <!-- LOADING -->
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

    <!-- ERROR -->
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

    <!-- DETAIL -->
    <section
      v-else-if="laporan"
      class="section"
    >
      <div class="container">
        <button
          type="button"
          class="btn btn-secondary back-button"
          @click="kembali"
        >
          ← Kembali ke Laporan
        </button>

        <div class="dashboard-grid">

          <!-- MAIN -->
          <div class="main-column">

            <!-- DETAIL LAPORAN -->
            <div class="card report-detail-card">

              <div
                v-if="fotoList.length"
                class="report-photo"
              >
                <img
                  :src="activeFoto"
                  alt="Foto laporan"
                  @click="openPhoto(activeFoto)"
                  @error="
                    $event.target.style.display =
                      'none'
                  "
                />

                <span
                  class="report-category floating"
                >
                  {{ kategoriIcon }}
                  {{ kategoriNama }}
                </span>

                <span
                  class="status floating"
                  :class="[
                    statusData.class,
                    {
                      'status-pulse':
                        statusJustUpdated
                    }
                  ]"
                >
                  {{ statusData.icon }}
                  {{ statusData.label }}
                </span>

                <div
                  v-if="
                    fotoList.length > 1
                  "
                  class="foto-thumbs"
                >
                  <button
                    v-for="(
                      foto,
                      idx
                    ) in fotoList"
                    :key="idx"
                    type="button"
                    class="foto-thumb"
                    :class="{
                      active:
                        idx ===
                        activeFotoIndex
                    }"
                    @click="
                      pilihFoto(idx)
                    "
                  >
                    <img
                      :src="foto"
                      :alt="
                        `Foto ${
                          idx + 1
                        }`
                      "
                    />
                  </button>
                </div>
              </div>

              <div class="report-content">

                <div
                  v-if="
                    !fotoList.length
                  "
                  class="report-top"
                >
                  <span
                    class="report-category"
                  >
                    {{ kategoriIcon }}
                    {{ kategoriNama }}
                  </span>

                  <span
                    class="status"
                    :class="[
                      statusData.class,
                      {
                        'status-pulse':
                          statusJustUpdated
                      }
                    ]"
                  >
                    {{ statusData.icon }}
                    {{ statusData.label }}
                  </span>
                </div>

                <h1 class="report-title">
                  {{ laporan.judul }}
                </h1>

                <div class="report-meta">
                  <span class="meta-chip">
                    👤
                    {{
                      laporan.user?.name ||
                      'Warga'
                    }}
                  </span>

                  <span class="meta-chip">
                    📅
                    {{
                      formatDate(
                        laporan.created_at
                      )
                    }}
                  </span>

                  <span class="meta-chip">
                    📍
                    {{
                      laporan.lokasi ||
                      'Lokasi tidak tersedia'
                    }}
                  </span>
                </div>

                <!-- ALASAN DITOLAK -->
                <div
                  v-if="
                    laporan.status ===
                      'ditolak' &&
                    alasanDitolak
                  "
                  class="alasan-ditolak-box"
                >
                  <div
                    class="alasan-ditolak-header"
                  >
                    <span
                      class="alasan-ditolak-icon"
                    >
                      🔴
                    </span>

                    <strong>
                      Alasan Laporan Ditolak
                    </strong>
                  </div>

                  <p>
                    {{ alasanDitolak }}
                  </p>
                </div>

                <!-- DESKRIPSI -->
                <div class="description">
                  <h3>
                    Deskripsi Laporan
                  </h3>

                  <p>
                    {{ laporan.deskripsi }}
                  </p>
                </div>

                <!-- PROGRESS -->
                <div
                  v-if="
                    namaPetugas ||
                    fotoSesudahList.length
                  "
                  class="progress-section"
                >
                  <h3>
                    🔧 Progress Penanganan
                  </h3>

                  <div
                    v-if="namaPetugas"
                    class="petugas-info"
                  >
                    Laporan ini sedang
                    ditangani oleh
                    <strong>
                      {{ namaPetugas }}
                    </strong>.
                  </div>

                  <div
                    v-if="
                      fotoSesudahList.length
                    "
                    class="before-after"
                  >
                    <div
                      class="before-after-col"
                    >
                      <span
                        class="before-after-label"
                      >
                        📷 Sebelum
                      </span>

                      <img
                        v-if="
                          fotoList.length
                        "
                        :src="
                          fotoList[0]
                        "
                        alt="Foto sebelum"
                        @click="
                          openPhoto(
                            fotoList[0]
                          )
                        "
                      />

                      <div
                        v-else
                        class="no-photo"
                      >
                        Tidak ada foto
                      </div>
                    </div>

                    <div
                      class="before-after-col"
                    >
                      <span
                        class="before-after-label"
                      >
                        ✅ Sesudah
                      </span>

                      <img
                        :src="
                          fotoSesudahList[0]
                        "
                        alt="Foto sesudah"
                        @click="
                          openPhoto(
                            fotoSesudahList[0]
                          )
                        "
                      />
                    </div>
                  </div>

                  <div
                    v-if="
                      fotoSesudahList.length >
                      1
                    "
                    class="foto-sesudah-grid"
                  >
                    <img
                      v-for="(
                        foto,
                        idx
                      ) in fotoSesudahList.slice(
                        1
                      )"
                      :key="idx"
                      :src="foto"
                      alt="Foto sesudah tambahan"
                      @click="
                        openPhoto(foto)
                      "
                    />
                  </div>
                </div>

                <!-- PETA -->
                <div
                  v-if="hasCoordinates"
                  class="location-map-section"
                >
                  <h3>
                    📍 Lokasi di Peta
                  </h3>

                  <div
                    ref="mapContainer"
                    class="location-map"
                  ></div>
                </div>

                <div
                  v-else
                  class="location-map-section"
                >
                  <h3>
                    📍 Lokasi di Peta
                  </h3>

                  <div
                    class="map-placeholder"
                  >
                    Koordinat lokasi tidak
                    tersedia untuk laporan ini.
                  </div>
                </div>
              </div>
            </div>

            <!-- KOMENTAR -->
            <div
              class="card comments-card"
            >
              <div
                class="comments-header"
              >
                <div>
                  <h2>
                    Komentar Warga
                  </h2>

                  <p class="text-muted">
                    Diskusikan dan berikan
                    informasi tambahan.
                  </p>
                </div>

                <span
                  class="comment-count"
                >
                  {{ totalKomentar }}
                </span>
              </div>

              <!-- FORM KOMENTAR -->
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
                      submittingKomentar ||
                      !komentar.trim()
                    "
                    @click="
                      kirimKomentar
                    "
                  >
                    {{
                      submittingKomentar
                        ? 'Mengirim...'
                        : 'Kirim Komentar'
                    }}
                  </button>
                </div>
              </div>

              <!-- LOGIN -->
              <div
                v-else
                class="login-comment"
              >
                <div
                  class="login-icon"
                >
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

              <!-- ERROR KOMENTAR -->
              <div
                v-if="
                  tanggapanError
                "
                class="comment-error"
              >
                {{ tanggapanError }}
              </div>

              <!-- LOADING -->
              <div
                v-if="
                  loadingTanggapan
                "
                class="empty-comments"
              >
                Memuat komentar...
              </div>

              <!-- EMPTY -->
              <div
                v-else-if="
                  !tanggapans.length
                "
                class="empty-comments"
              >
                <div
                  class="empty-comments-icon"
                >
                  💬
                </div>

                <p>
                  Belum ada komentar.
                </p>

                <small
                  class="text-muted"
                >
                  Jadilah warga pertama yang
                  memberikan komentar.
                </small>
              </div>

              <!-- LIST -->
              <div
                v-else
                class="comment-list"
              >
                <div
                  v-for="tg in tanggapans"
                  :key="tg.id"
                  class="comment-item"
                >
                  <div class="avatar">
                    {{
                      getInitial(
                        tg.user?.name
                      )
                    }}
                  </div>

                  <div
                    class="comment-body"
                  >
                    <div
                      class="comment-head"
                    >
                      <strong>
                        {{
                          tg.user?.name ||
                          'Warga'
                        }}
                      </strong>

                      <small
                        class="text-muted"
                      >
                        {{
                          formatDate(
                            tg.created_at
                          )
                        }}
                      </small>
                    </div>

                    <p
                      class="comment-text"
                    >
                      {{
                        tg.pesan ||
                        tg.isi
                      }}
                    </p>

                    <!-- ACTION KOMENTAR -->
                    <div
                      class="comment-actions"
                    >
                      <button
                        type="button"
                        class="comment-action"
                        :class="{
                          active:
                            tg.user_reaction ===
                            'like'
                        }"
                        :disabled="
                          reacting ===
                          tg.id
                        "
                        @click="
                          reactKomentar(
                            tg,
                            'like'
                          )
                        "
                      >
                        👍
                        {{
                          tg.likes_count ||
                          0
                        }}
                      </button>

                      <button
                        type="button"
                        class="comment-action"
                        :class="{
                          active:
                            tg.user_reaction ===
                            'dislike'
                        }"
                        :disabled="
                          reacting ===
                          tg.id
                        "
                        @click="
                          reactKomentar(
                            tg,
                            'dislike'
                          )
                        "
                      >
                        👎
                        {{
                          tg.dislikes_count ||
                          0
                        }}
                      </button>

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

                    <!-- REPLIES -->
                    <div
                      v-if="
                        tg.replies &&
                        tg.replies.length
                      "
                      class="replies"
                    >
                      <div
                        v-for="
                          reply in
                            tg.replies
                        "
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

                        <div
                          class="comment-body"
                        >
                          <div
                            class="comment-head"
                          >
                            <strong>
                              {{
                                reply
                                  .user
                                  ?.name ||
                                'Warga'
                              }}
                            </strong>

                            <small
                              class="text-muted"
                            >
                              {{
                                formatDate(
                                  reply.created_at
                                )
                              }}
                            </small>
                          </div>

                          <p
                            class="comment-text"
                          >
                            {{
                              reply.pesan ||
                              reply.isi
                            }}
                          </p>

                          <!-- ACTION REPLY -->
                          <div
                            class="comment-actions"
                          >
                            <button
                              type="button"
                              class="comment-action"
                              :class="{
                                active:
                                  reply.user_reaction ===
                                  'like'
                              }"
                              :disabled="
                                reacting ===
                                reply.id
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
                                reacting ===
                                reply.id
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

                    <!-- FORM BALAS -->
                    <div
                      v-if="
                        replyTo &&
                        replyTo.id ===
                          tg.id
                      "
                      class="reply-form"
                    >
                      <div
                        class="reply-title"
                      >
                        <span>
                          Membalas
                          <strong>
                            {{
                              tg.user?.name ||
                              'Warga'
                            }}
                          </strong>
                        </span>

                        <button
                          type="button"
                          class="close-reply"
                          @click="
                            batalBalas
                          "
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

                      <div
                        class="form-footer"
                      >
                        <small
                          class="text-muted"
                        >
                          {{
                            replyText.length
                          }}/1000
                        </small>

                        <button
                          type="button"
                          class="btn btn-primary"
                          :disabled="
                            submittingKomentar ||
                            !replyText.trim()
                          "
                          @click="
                            kirimBalasan
                          "
                        >
                          {{
                            submittingKomentar
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

          <!-- SIDEBAR -->
          <div class="side-column">

            <!-- INFORMASI -->
            <div
              class="card sidebar-card"
            >
              <h3>
                Informasi Laporan
              </h3>

              <div class="info-list">

                <div class="info-row">
                  <span
                    class="info-icon"
                  >
                    📌
                  </span>

                  <div
                    class="info-text"
                  >
                    <small>
                      Status
                    </small>

                    <span
                      class="status"
                      :class="[
                        statusData.class,
                        {
                          'status-pulse':
                            statusJustUpdated
                        }
                      ]"
                    >
                      {{ statusData.icon }}
                      {{ statusData.label }}
                    </span>
                  </div>
                </div>

                <div class="info-row">
                  <span
                    class="info-icon"
                  >
                    {{ kategoriIcon }}
                  </span>

                  <div
                    class="info-text"
                  >
                    <small>
                      Kategori
                    </small>

                    <strong>
                      {{ kategoriNama }}
                    </strong>
                  </div>
                </div>

                <div class="info-row">
                  <span
                    class="info-icon"
                  >
                    👤
                  </span>

                  <div
                    class="info-text"
                  >
                    <small>
                      Pelapor
                    </small>

                    <strong>
                      {{
                        laporan.user
                          ?.name ||
                        'Warga'
                      }}
                    </strong>
                  </div>
                </div>

                <div class="info-row">
                  <span
                    class="info-icon"
                  >
                    📍
                  </span>

                  <div
                    class="info-text"
                  >
                    <small>
                      Lokasi
                    </small>

                    <strong>
                      {{
                        laporan.lokasi ||
                        'Lokasi tidak tersedia'
                      }}
                    </strong>
                  </div>
                </div>

                <div class="info-row">
                  <span
                    class="info-icon"
                  >
                    📅
                  </span>

                  <div
                    class="info-text"
                  >
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
                </div>

                <div
                  v-if="namaPetugas"
                  class="info-row"
                >
                  <span
                    class="info-icon"
                  >
                    🔧
                  </span>

                  <div
                    class="info-text"
                  >
                    <small>
                      Ditangani oleh
                    </small>

                    <strong>
                      {{ namaPetugas }}
                    </strong>
                  </div>
                </div>

                <div
                  v-if="
                    laporan.status ===
                      'ditolak' &&
                    alasanDitolak
                  "
                  class="info-row"
                >
                  <span
                    class="info-icon"
                  >
                    🔴
                  </span>

                  <div
                    class="info-text"
                  >
                    <small>
                      Alasan Ditolak
                    </small>

                    <strong
                      class="sidebar-rejection"
                    >
                      {{ alasanDitolak }}
                    </strong>
                  </div>
                </div>

                <div class="info-row">
                  <span
                    class="info-icon"
                  >
                    💬
                  </span>

                  <div
                    class="info-text"
                  >
                    <small>
                      Total Komentar
                    </small>

                    <strong>
                      {{ totalKomentar }}
                    </strong>
                  </div>
                </div>

              </div>
            </div>

            <!-- RIWAYAT -->
            <div
              v-if="timelineLogs.length"
              class="card timeline-card"
            >
              <h3>
                🕒 Riwayat Laporan
              </h3>

              <div
                class="timeline-list"
              >
                <div
                  v-for="(
                    log,
                    idx
                  ) in timelineLogs"
                  :key="log.id"
                  class="timeline-item"
                >
                  <div
                    class="timeline-marker"
                  >
                    <span
                      class="timeline-icon"
                    >
                      {{
                        logIcon(
                          log.tipe
                        )
                      }}
                    </span>

                    <span
                      v-if="
                        idx <
                        timelineLogs.length -
                          1
                      "
                      class="timeline-line"
                    ></span>
                  </div>

                  <div
                    class="timeline-content"
                  >
                    <p
                      class="timeline-text"
                    >
                      {{
                        log.keterangan
                      }}
                    </p>

                    <small
                      class="timeline-date"
                    >
                      {{
                        formatDate(
                          log.created_at
                        )
                      }}
                    </small>
                  </div>
                </div>
              </div>
            </div>

            <!-- TIMELINE PENGERJAAN -->
            <div
              class="card timeline-card"
            >
              <h3>
                🕒 Timeline Pengerjaan
              </h3>

              <div
                class="timeline-list"
              >

                <div
                  class="timeline-item"
                >
                  <div
                    class="timeline-marker"
                  >
                    <span
                      class="timeline-icon"
                    >
                      📝
                    </span>

                    <span
                      v-if="
                        laporan.assigned_at ||
                        laporan.started_at ||
                        laporan.deadline_at ||
                        laporan.completed_at
                      "
                      class="timeline-line"
                    ></span>
                  </div>

                  <div
                    class="timeline-content"
                  >
                    <p
                      class="timeline-text"
                    >
                      Laporan dibuat
                    </p>

                    <small
                      class="timeline-date"
                    >
                      {{
                        formatDate(
                          laporan.created_at
                        )
                      }}
                    </small>
                  </div>
                </div>

                <div
                  v-if="
                    laporan.assigned_at
                  "
                  class="timeline-item"
                >
                  <div
                    class="timeline-marker"
                  >
                    <span
                      class="timeline-icon"
                    >
                      🔧
                    </span>

                    <span
                      v-if="
                        laporan.started_at ||
                        laporan.deadline_at ||
                        laporan.completed_at
                      "
                      class="timeline-line"
                    ></span>
                  </div>

                  <div
                    class="timeline-content"
                  >
                    <p
                      class="timeline-text"
                    >
                      Petugas ditugaskan
                    </p>

                    <small
                      class="timeline-date"
                    >
                      {{
                        formatDate(
                          laporan.assigned_at
                        )
                      }}
                    </small>

                    <small
                      v-if="
                        laporan.petugas
                      "
                      class="timeline-detail"
                    >
                      Petugas:
                      {{
                        laporan.petugas.name
                      }}
                    </small>
                  </div>
                </div>

                <div
                  v-if="
                    laporan.started_at
                  "
                  class="timeline-item"
                >
                  <div
                    class="timeline-marker"
                  >
                    <span
                      class="timeline-icon"
                    >
                      ⚙️
                    </span>

                    <span
                      v-if="
                        laporan.deadline_at ||
                        laporan.completed_at
                      "
                      class="timeline-line"
                    ></span>
                  </div>

                  <div
                    class="timeline-content"
                  >
                    <p
                      class="timeline-text"
                    >
                      Pengerjaan dimulai
                    </p>

                    <small
                      class="timeline-date"
                    >
                      {{
                        formatDate(
                          laporan.started_at
                        )
                      }}
                    </small>
                  </div>
                </div>

                <div
                  v-if="
                    laporan.deadline_at
                  "
                  class="timeline-item"
                >
                  <div
                    class="timeline-marker"
                  >
                    <span
                      class="timeline-icon"
                    >
                      ⏰
                    </span>

                    <span
                      v-if="
                        laporan.completed_at
                      "
                      class="timeline-line"
                    ></span>
                  </div>

                  <div
                    class="timeline-content"
                  >
                    <p
                      class="timeline-text"
                    >
                      Batas pengerjaan
                    </p>

                    <small
                      class="timeline-date"
                    >
                      {{
                        formatDate(
                          laporan.deadline_at
                        )
                      }}
                    </small>

                    <div
                      v-if="
                        deadlineInfo
                      "
                      class="deadline-status"
                      :class="
                        `deadline-${deadlineInfo.type}`
                      "
                    >
                      <span
                        v-if="
                          deadlineInfo.type ===
                          'aktif'
                        "
                      >
                        ⏳
                        {{
                          deadlineInfo.text
                        }}
                      </span>

                      <span
                        v-else-if="
                          deadlineInfo.type ===
                          'terlambat'
                        "
                      >
                        ⚠️
                        {{
                          deadlineInfo.text
                        }}
                      </span>

                      <span v-else>
                        ✅
                        {{
                          deadlineInfo.text
                        }}
                      </span>
                    </div>
                  </div>
                </div>

                <div
                  v-if="
                    laporan.completed_at
                  "
                  class="timeline-item"
                >
                  <div
                    class="timeline-marker"
                  >
                    <span
                      class="timeline-icon"
                    >
                      ✅
                    </span>
                  </div>

                  <div
                    class="timeline-content"
                  >
                    <p
                      class="timeline-text"
                    >
                      Laporan selesai
                    </p>

                    <small
                      class="timeline-date"
                    >
                      {{
                        formatDate(
                          laporan.completed_at
                        )
                      }}
                    </small>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- MODAL FOTO -->
    <div
      v-if="
        showPhotoModal &&
        currentPhoto
      "
      class="photo-modal"
      @click.self="closePhoto"
    >
      <button
        type="button"
        class="photo-modal-close"
        @click="closePhoto"
      >
        ✕
      </button>

      <img
        :src="
          currentPhoto.url ||
          currentPhoto
        "
        alt="Foto laporan"
      />
    </div>

    <Footer />
  </div>
</template>

<style scoped>
.report-detail-page {
  min-height: 100vh;
  background: #f8fafc;
  color: #0f172a;
}

.section {
  padding: 32px 0 60px;
}

.container {
  width: min(
    1180px,
    calc(100% - 32px)
  );
  margin: 0 auto;
}

.dashboard-grid {
  display: grid;
  grid-template-columns:
    minmax(0, 1fr) 340px;
  gap: 22px;
  align-items: start;
}

.main-column,
.side-column {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow:
    0 4px 15px
      rgba(15, 23, 42, .04);
}

.btn {
  border: 0;
  border-radius: 10px;
  padding: 10px 15px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: .2s ease;
}

.btn-primary {
  background: #0d9488;
  color: #fff;
}

.btn-primary:hover {
  background: #0f766e;
}

.btn-primary:disabled {
  opacity: .55;
  cursor: not-allowed;
}

.btn-secondary {
  background: #fff;
  border:
    1px solid #e2e8f0;
  color: #334155;
}

.btn-secondary:hover {
  background: #f1f5f9;
}

.back-button {
  margin-bottom: 20px;
}

.loading-card,
.error-card {
  padding: 45px 25px;
  text-align: center;
}

.loading-icon,
.error-icon {
  font-size: 42px;
  margin-bottom: 12px;
}

.loading-card h3,
.error-card h2 {
  margin:
    0 0 8px;
}

.text-muted {
  color: #64748b;
}

/* REPORT */

.report-detail-card {
  overflow: hidden;
}

.report-photo {
  position: relative;
  height: 360px;
  background: #e2e8f0;
}

.report-photo > img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  cursor: zoom-in;
}

.floating {
  position: absolute;
  z-index: 2;
}

.report-photo .report-category {
  top: 16px;
  left: 16px;
}

.report-photo .status {
  top: 16px;
  right: 16px;
}

.report-category {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 11px;
  border-radius: 999px;
  background: #f0fdfa;
  color: #0f766e;
  font-size: 12px;
  font-weight: 700;
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  width: fit-content;
  padding: 7px 11px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.status-baru {
  background: #fff7ed;
  color: #c2410c;
}

.status-diproses {
  background: #eff6ff;
  color: #1d4ed8;
}

.status-selesai {
  background: #f0fdf4;
  color: #15803d;
}

.status-ditolak {
  background: #fef2f2;
  color: #b91c1c;
}

.status-pulse {
  animation:
    statusPulse
    .6s ease;
}

@keyframes statusPulse {
  0% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.08);
  }

  100% {
    transform: scale(1);
  }
}

.foto-thumbs {
  position: absolute;
  z-index: 3;
  left: 16px;
  right: 16px;
  bottom: 16px;
  display: flex;
  gap: 8px;
  overflow-x: auto;
}

.foto-thumb {
  flex:
    0 0 58px;
  width: 58px;
  height: 58px;
  padding: 0;
  overflow: hidden;
  border:
    2px solid
      rgba(255,255,255,.7);
  border-radius: 9px;
  background: #fff;
  cursor: pointer;
}

.foto-thumb.active {
  border-color: #0d9488;
}

.foto-thumb img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.report-content {
  padding: 24px;
}

.report-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.report-title {
  margin: 0 0 14px;
  color: #0f172a;
  font-size: 28px;
  line-height: 1.25;
  font-weight: 800;
}

.report-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 22px;
}

.meta-chip {
  padding: 7px 10px;
  border-radius: 8px;
  background: #f8fafc;
  color: #64748b;
  font-size: 11px;
}

.description {
  padding-top: 20px;
  border-top:
    1px solid #f1f5f9;
}

.description h3,
.progress-section h3,
.location-map-section h3 {
  margin: 0 0 10px;
  color: #0f172a;
  font-size: 16px;
  font-weight: 800;
}

.description p {
  margin: 0;
  color: #475569;
  font-size: 14px;
  line-height: 1.75;
  white-space: pre-line;
}

/* DITOLAK */

.alasan-ditolak-box {
  margin-bottom: 20px;
  padding: 15px;
  border:
    1px solid #fecaca;
  border-radius: 12px;
  background: #fef2f2;
}

.alasan-ditolak-header {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #b91c1c;
  font-size: 13px;
}

.alasan-ditolak-box p {
  margin: 8px 0 0;
  color: #7f1d1d;
  font-size: 13px;
  line-height: 1.6;
}

/* PROGRESS */

.progress-section {
  margin-top: 24px;
  padding: 18px;
  border-radius: 12px;
  background: #f8fafc;
}

.petugas-info {
  margin-bottom: 15px;
  color: #475569;
  font-size: 13px;
}

.before-after {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.before-after-col {
  overflow: hidden;
  border:
    1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
}

.before-after-label {
  display: block;
  padding: 9px 11px;
  color: #475569;
  font-size: 11px;
  font-weight: 700;
}

.before-after-col img {
  width: 100%;
  height: 190px;
  display: block;
  object-fit: cover;
  cursor: zoom-in;
}

.no-photo {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 190px;
  color: #94a3b8;
  font-size: 12px;
}

.foto-sesudah-grid {
  display: grid;
  grid-template-columns:
    repeat(3, 1fr);
  gap: 8px;
  margin-top: 10px;
}

.foto-sesudah-grid img {
  width: 100%;
  height: 100px;
  display: block;
  border-radius: 8px;
  object-fit: cover;
  cursor: zoom-in;
}

/* MAP */

.location-map-section {
  margin-top: 24px;
}

.location-map {
  width: 100%;
  height: 320px;
  overflow: hidden;
  border-radius: 12px;
  background: #e2e8f0;
}

.map-placeholder {
  padding: 30px;
  border:
    1px dashed #cbd5e1;
  border-radius: 12px;
  color: #64748b;
  background: #f8fafc;
  text-align: center;
  font-size: 13px;
}

/* KOMENTAR */

.comments-card {
  padding: 22px;
}

.comments-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 20px;
}

.comments-header h2 {
  margin:
    0 0 5px;
  color: #0f172a;
  font-size: 18px;
}

.comments-header p {
  margin: 0;
  font-size: 12px;
}

.comment-count {
  min-width: 34px;
  padding: 7px 10px;
  border-radius: 999px;
  background: #f0fdfa;
  color: #0f766e;
  text-align: center;
  font-size: 12px;
  font-weight: 800;
}

.comment-form,
.reply-form {
  margin-bottom: 22px;
}

.form-control {
  width: 100%;
  padding: 11px 13px;
  border:
    1px solid #cbd5e1;
  border-radius: 10px;
  outline: none;
  background: #fff;
  color: #0f172a;
  font: inherit;
  font-size: 13px;
  resize: vertical;
  transition: .2s ease;
}

.form-control:focus {
  border-color: #0d9488;
  box-shadow:
    0 0 0 3px
      rgba(13,148,136,.1);
}

.form-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-top: 8px;
}

.login-comment {
  padding: 25px;
  margin-bottom: 20px;
  border-radius: 12px;
  background: #f8fafc;
  text-align: center;
}

.login-icon {
  margin-bottom: 8px;
  font-size: 30px;
}

.login-comment p {
  margin:
    0 0 12px;
  color: #64748b;
  font-size: 13px;
}

.comment-error {
  margin-bottom: 15px;
  padding: 10px 12px;
  border-radius: 8px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 12px;
}

.empty-comments {
  padding: 30px 15px;
  color: #94a3b8;
  text-align: center;
  font-size: 13px;
}

.empty-comments-icon {
  margin-bottom: 8px;
  font-size: 30px;
}

.empty-comments p {
  margin: 0 0 4px;
}

.comment-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.comment-item {
  display: flex;
  gap: 11px;
  padding-bottom: 16px;
  border-bottom:
    1px solid #f1f5f9;
}

.comment-item:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.avatar {
  flex:
    0 0 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #ccfbf1;
  color: #0f766e;
  font-size: 13px;
  font-weight: 800;
}

.reply-avatar {
  width: 30px;
  height: 30px;
  flex-basis: 30px;
  font-size: 11px;
}

.comment-body {
  min-width: 0;
  flex: 1;
}

.comment-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 5px;
}

.comment-head strong {
  color: #334155;
  font-size: 13px;
}

.comment-head small {
  font-size: 10px;
}

.comment-text {
  margin: 0;
  color: #475569;
  font-size: 13px;
  line-height: 1.6;
  white-space: pre-line;
}

/* LIKE / DISLIKE */

.comment-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 9px;
}

.comment-action {
  padding: 5px 8px;
  border:
    1px solid #e2e8f0;
  border-radius: 7px;
  background: #fff;
  color: #64748b;
  font-size: 11px;
  cursor: pointer;
  transition: .15s ease;
}

.comment-action:hover {
  background: #f0fdfa;
  border-color: #99f6e4;
  color: #0f766e;
}

.comment-action.active {
  background: #f0fdfa;
  border-color: #5eead4;
  color: #0f766e;
  font-weight: 700;
}

.comment-action:disabled {
  opacity: .55;
  cursor: not-allowed;
}

/* REPLY */

.replies {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 14px;
  padding-left: 12px;
  border-left:
    2px solid #e2e8f0;
}

.reply-item {
  display: flex;
  gap: 9px;
}

.reply-form {
  margin-top: 13px;
  padding: 12px;
  border-radius: 10px;
  background: #f8fafc;
}

.reply-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 8px;
  color: #64748b;
  font-size: 12px;
}

.close-reply {
  border: 0;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
}

.close-reply:hover {
  color: #334155;
}

/* SIDEBAR */

.sidebar-card {
  padding: 20px;
}

.sidebar-card h3 {
  margin:
    0 0 18px;
  color: #0f172a;
  font-size: 17px;
}

.info-list {
  display: flex;
  flex-direction: column;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  padding: 13px 0;
  border-bottom:
    1px solid #f1f5f9;
}

.info-row:first-child {
  padding-top: 0;
}

.info-row:last-child {
  padding-bottom: 0;
  border-bottom: 0;
}

.info-icon {
  flex:
    0 0 28px;
  font-size: 17px;
  text-align: center;
}

.info-text {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-text small {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.info-text strong {
  color: #334155;
  font-size: 12px;
  line-height: 1.5;
  word-break: break-word;
}

.sidebar-rejection {
  color: #b91c1c !important;
}

/* TIMELINE */

.timeline-card {
  padding: 20px;
}

.timeline-card h3 {
  margin:
    0 0 18px;
  color: #0f172a;
  font-size: 17px;
}

.timeline-list {
  position: relative;
}

.timeline-item {
  display: grid;
  grid-template-columns:
    34px minmax(0, 1fr);
  gap: 11px;
  min-height: 68px;
}

.timeline-marker {
  position: relative;
  display: flex;
  justify-content: center;
  width: 34px;
}

.timeline-icon {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border:
    1px solid #dbeafe;
  border-radius: 50%;
  background: #eff6ff;
  font-size: 14px;
}

.timeline-line {
  position: absolute;
  top: 30px;
  bottom: -3px;
  width: 2px;
  background: #e2e8f0;
}

.timeline-content {
  padding-bottom: 17px;
}

.timeline-text {
  margin:
    2px 0 4px;
  color: #334155;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.5;
}

.timeline-date {
  display: block;
  color: #94a3b8;
  font-size: 10px;
  line-height: 1.5;
}

.timeline-detail {
  display: block;
  margin-top: 5px;
  color: #64748b;
  font-size: 11px;
}

/* DEADLINE */

.deadline-status {
  display: inline-block;
  margin-top: 8px;
  padding: 8px 10px;
  border-radius: 9px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.5;
}

.deadline-aktif {
  background: #ecfeff;
  border:
    1px solid #a5f3fc;
  color: #0f766e;
}

.deadline-terlambat {
  background: #fef2f2;
  border:
    1px solid #fecaca;
  color: #b91c1c;
}

.deadline-selesai {
  background: #f0fdf4;
  border:
    1px solid #bbf7d0;
  color: #15803d;
}

/* FOTO MODAL */

.photo-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background:
    rgba(15,23,42,.88);
}

.photo-modal img {
  max-width:
    min(100%, 1000px);
  max-height: 90vh;
  border-radius: 12px;
  object-fit: contain;
}

.photo-modal-close {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 40px;
  height: 40px;
  border: 0;
  border-radius: 50%;
  background:
    rgba(255,255,255,.15);
  color: #fff;
  font-size: 20px;
  cursor: pointer;
}

@media (max-width: 900px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .report-title {
    font-size: 24px;
  }
}

@media (max-width: 600px) {
  .section {
    padding:
      20px 0 40px;
  }

  .container {
    width:
      calc(100% - 20px);
  }

  .report-photo {
    height: 270px;
  }

  .report-content,
  .comments-card,
  .sidebar-card,
  .timeline-card {
    padding: 17px;
  }

  .report-title {
    font-size: 21px;
  }

  .report-top {
    align-items: flex-start;
    flex-direction: column;
  }

  .report-meta {
    flex-direction: column;
  }

  .before-after {
    grid-template-columns: 1fr;
  }

  .foto-sesudah-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .location-map {
    height: 250px;
  }

  .comment-head {
    align-items: flex-start;
    flex-direction: column;
    gap: 3px;
  }

  .form-footer {
    align-items: stretch;
    flex-direction: column;
  }

  .form-footer .btn {
    width: 100%;
  }

  .timeline-item {
    grid-template-columns:
      30px minmax(0, 1fr);
    gap: 9px;
  }

  .timeline-marker {
    width: 30px;
  }

  .timeline-icon {
    width: 28px;
    height: 28px;
    font-size: 13px;
  }

  .timeline-line {
    top: 28px;
  }
}
</style>