<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Navbar from '../../components/Navbar.vue'
import Footer from '../../components/Footer.vue'
import api from '../../services/api.js'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIcon2x, iconUrl: markerIcon, shadowUrl: markerShadow })

const route = useRoute()
const router = useRouter()

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
const mapContainer = ref(null)
let map = null

const user = ref(null)
try {
  user.value = JSON.parse(localStorage.getItem('user') || 'null')
} catch {
  user.value = null
}

const statusMap = {
  baru: { label: 'Menunggu', class: 'status-waiting', icon: '🟡' },
  diproses: { label: 'Diproses', class: 'status-processing', icon: '🔵' },
  selesai: { label: 'Selesai', class: 'status-success', icon: '🟢' },
  ditolak: { label: 'Ditolak', class: 'status-rejected', icon: '🔴' },
}

const kategoriNama = computed(() => laporan.value?.kategori_relasi?.nama || laporan.value?.kategori || 'Lainnya')

const kategoriIcon = computed(() => {
  if (laporan.value?.kategori_relasi?.icon) return laporan.value.kategori_relasi.icon
  const nama = kategoriNama.value.toLowerCase()
  if (nama.includes('jalan')) return '🚧'
  if (nama.includes('sampah')) return '🗑️'
  if (nama.includes('lampu')) return '💡'
  if (nama.includes('selokan')) return '🌊'
  if (nama.includes('fasilitas')) return '🏞️'
  return '📋'
})

const statusData = computed(() => {
  const status = laporan.value?.status
  return statusMap[status] || { label: status || 'Tidak diketahui', class: 'status-waiting', icon: '⚪' }
})

const isLoggedIn = computed(() => Boolean(localStorage.getItem('token')))
const totalKomentar = computed(() => tanggapans.value.length)

const hasCoordinates = computed(() => {
  const lat = laporan.value?.latitude
  const lng = laporan.value?.longitude
  // Pakai pengecekan null/undefined/empty string, BUKAN falsy check biasa,
  // supaya koordinat 0 (mis. di garis khatulistiwa) tetap dianggap valid.
  return lat !== null && lat !== undefined && lat !== '' &&
         lng !== null && lng !== undefined && lng !== '' &&
         !Number.isNaN(parseFloat(lat)) && !Number.isNaN(parseFloat(lng))
})

function getTokenExists() {
  return Boolean(localStorage.getItem('token'))
}

function formatDate(date) {
  if (!date) return '-'
  try {
    return new Date(date).toLocaleString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
  } catch {
    return '-'
  }
}

function getInitial(name) {
  return name ? name.trim().charAt(0).toUpperCase() : 'W'
}

function getFotoUrl() {
  const foto = laporan.value?.foto_url
  if (!foto) return null
  if (foto.startsWith('http://') || foto.startsWith('https://')) return foto
  if (foto.startsWith('/storage/')) return `http://127.0.0.1:8000${foto}`
  if (foto.startsWith('storage/')) return `http://127.0.0.1:8000/${foto}`
  return `http://127.0.0.1:8000/storage/${foto}`
}

function goLogin() {
  router.push({ path: '/login', query: { redirect: route.fullPath } })
}

function kembali() {
  router.push('/laporan')
}

function initMap() {
  if (!hasCoordinates.value || !mapContainer.value) return

  // Kalau sebelumnya sudah ada instance map (misal navigasi ulang ke laporan lain),
  // hancurkan dulu supaya tidak terjadi "Map container is already initialized".
  if (map) {
    map.remove()
    map = null
  }

  const lat = parseFloat(laporan.value.latitude)
  const lng = parseFloat(laporan.value.longitude)

  map = L.map(mapContainer.value, { zoomControl: true, dragging: true, scrollWheelZoom: false }).setView([lat, lng], 15)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  L.marker([lat, lng]).addTo(map)

  // Leaflet kadang salah hitung ukuran container kalau di-render tepat
  // saat transisi/layout belum benar-benar selesai. invalidateSize()
  // memaksa Leaflet menghitung ulang dimensi peta.
  setTimeout(() => {
    map?.invalidateSize()
  }, 150)
}

async function fetchLaporan() {
  loading.value = true
  error.value = ''
  laporan.value = null

  const id = route.params.id

  if (!id || !/^\d+$/.test(String(id))) {
    error.value = 'ID laporan tidak valid.'
    loading.value = false
    return
  }

  try {
    const response = await api.get(`/laporans/${id}`)
    const data = response.data?.data ?? response.data

    if (!data || !data.id) {
      error.value = 'Laporan tidak ditemukan.'
      loading.value = false
      return
    }

    laporan.value = data
    await fetchTanggapans()

    // PENTING: matikan loading DULU supaya section yang berisi
    // <div ref="mapContainer"> benar-benar ter-render ke DOM,
    // baru setelah itu nextTick() + initMap() dipanggil.
    loading.value = false

    await nextTick()
    initMap()
  } catch (err) {
    if (err.response?.status === 404) error.value = 'Laporan dengan ID tersebut tidak ditemukan.'
    else if (err.response?.status === 401) error.value = 'Kamu tidak memiliki akses ke laporan ini.'
    else error.value = err.response?.data?.message || 'Gagal mengambil detail laporan.'
    loading.value = false
  }
}

async function fetchTanggapans() {
  if (!laporan.value?.id) return

  loadingTanggapan.value = true

  try {
    const response = await api.get(`/laporans/${laporan.value.id}/tanggapans`)
    const data = response.data?.data ?? response.data

    tanggapans.value = (Array.isArray(data) ? data : []).map((item) => ({
      ...item,
      replies: Array.isArray(item.replies) ? item.replies : [],
      likes_count: item.likes_count ?? 0,
      dislikes_count: item.dislikes_count ?? 0,
      user_reaction: item.user_reaction ?? null,
    }))
  } catch (err) {
    tanggapans.value = Array.isArray(laporan.value?.tanggapans) ? laporan.value.tanggapans : []
  } finally {
    loadingTanggapan.value = false
  }
}

async function kirimKomentar() {
  const pesan = komentar.value.trim()
  if (!pesan) return

  if (!getTokenExists()) {
    alert('Silakan login terlebih dahulu untuk memberikan komentar.')
    goLogin()
    return
  }

  sending.value = true

  try {
    const response = await api.post(`/laporans/${laporan.value.id}/tanggapans`, { pesan })
    const data = response.data?.data ?? response.data
    if (!data) throw new Error('Data komentar tidak ditemukan.')

    tanggapans.value.unshift({
      ...data,
      replies: Array.isArray(data.replies) ? data.replies : [],
      likes_count: data.likes_count ?? 0,
      dislikes_count: data.dislikes_count ?? 0,
      user_reaction: data.user_reaction ?? null,
    })

    komentar.value = ''
  } catch (err) {
    if (err.response?.status === 401) {
      alert('Sesi login kamu sudah berakhir. Silakan login kembali.')
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      user.value = null
      goLogin()
      return
    }
    alert(err.response?.data?.message || 'Gagal mengirim komentar.')
  } finally {
    sending.value = false
  }
}

function mulaiBalas(tanggapan) {
  if (!getTokenExists()) {
    alert('Silakan login terlebih dahulu untuk membalas komentar.')
    goLogin()
    return
  }

  replyTo.value = tanggapan
  replyText.value = ''

  nextTick(() => document.getElementById('reply-input')?.focus())
}

function batalBalas() {
  replyTo.value = null
  replyText.value = ''
}

async function kirimBalasan() {
  const pesan = replyText.value.trim()
  if (!pesan || !replyTo.value) return
  if (!getTokenExists()) {
    goLogin()
    return
  }

  sending.value = true

  try {
    const response = await api.post(`/laporans/${laporan.value.id}/tanggapans`, {
      pesan, parent_id: replyTo.value.id,
    })

    const data = response.data?.data ?? response.data
    if (!data) throw new Error('Data balasan tidak ditemukan.')

    if (!Array.isArray(replyTo.value.replies)) replyTo.value.replies = []

    replyTo.value.replies.unshift({
      ...data,
      replies: [],
      likes_count: data.likes_count ?? 0,
      dislikes_count: data.dislikes_count ?? 0,
      user_reaction: data.user_reaction ?? null,
    })

    replyText.value = ''
    replyTo.value = null
  } catch (err) {
    if (err.response?.status === 401) {
      alert('Sesi login kamu sudah berakhir. Silakan login kembali.')
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      user.value = null
      goLogin()
      return
    }
    alert(err.response?.data?.message || 'Gagal mengirim balasan.')
  } finally {
    sending.value = false
  }
}

async function reactKomentar(tanggapan, type) {
  if (!getTokenExists()) {
    alert('Silakan login terlebih dahulu untuk memberikan reaction.')
    goLogin()
    return
  }
  if (reacting.value === tanggapan.id) return

  reacting.value = tanggapan.id

  try {
    const response = await api.post(`/tanggapans/${tanggapan.id}/reaction`, { type })
    const data = response.data?.data ?? response.data

    tanggapan.likes_count = data?.likes_count ?? 0
    tanggapan.dislikes_count = data?.dislikes_count ?? 0
    tanggapan.user_reaction = data?.user_reaction ?? null
  } catch (err) {
    if (err.response?.status === 401) {
      alert('Sesi login kamu sudah berakhir. Silakan login kembali.')
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      user.value = null
      goLogin()
      return
    }
    alert(err.response?.data?.message || 'Gagal memberikan reaction.')
  } finally {
    reacting.value = null
  }
}

onMounted(fetchLaporan)
</script>

<template>
  <div class="report-detail-page">
    <Navbar />

    <section v-if="loading" class="section">
      <div class="container">
        <div class="card loading-card">
          <div class="loading-icon">⏳</div>
          <h3>Memuat detail laporan...</h3>
          <p class="text-muted">Mohon tunggu sebentar.</p>
        </div>
      </div>
    </section>

    <section v-else-if="error" class="section">
      <div class="container">
        <div class="card error-card">
          <div class="error-icon">⚠️</div>
          <h2>Laporan Tidak Ditemukan</h2>
          <p class="text-muted">{{ error }}</p>
          <button type="button" class="btn btn-primary" @click="kembali">← Kembali ke Laporan</button>
        </div>
      </div>
    </section>

    <section v-else-if="laporan" class="section">
      <div class="container">
        <button type="button" class="btn btn-secondary back-button" @click="kembali">← Kembali ke Laporan</button>

        <div class="dashboard-grid">
          <div class="main-column">

            <!-- DETAIL LAPORAN -->
            <div class="card report-detail-card">
              <div v-if="getFotoUrl()" class="report-photo">
                <img :src="getFotoUrl()" alt="Foto laporan" @error="$event.target.style.display = 'none'" />
                <span class="report-category floating">{{ kategoriIcon }} {{ kategoriNama }}</span>
                <span class="status floating" :class="statusData.class">{{ statusData.icon }} {{ statusData.label }}</span>
              </div>

              <div class="report-content">
                <div v-if="!getFotoUrl()" class="report-top">
                  <span class="report-category">{{ kategoriIcon }} {{ kategoriNama }}</span>
                  <span class="status" :class="statusData.class">{{ statusData.icon }} {{ statusData.label }}</span>
                </div>

                <h1 class="report-title">{{ laporan.judul }}</h1>

                <div class="report-meta">
                  <span class="meta-chip">👤 {{ laporan.user?.name || 'Warga' }}</span>
                  <span class="meta-chip">📅 {{ formatDate(laporan.created_at) }}</span>
                  <span class="meta-chip">📍 {{ laporan.lokasi || 'Lokasi tidak tersedia' }}</span>
                </div>

                <div class="description">
                  <h3>Deskripsi Laporan</h3>
                  <p>{{ laporan.deskripsi }}</p>
                </div>

                <!-- PETA LOKASI -->
                <div v-if="hasCoordinates" class="location-map-section">
                  <h3>📍 Lokasi di Peta</h3>
                  <div ref="mapContainer" class="location-map"></div>
                </div>
                <div v-else class="location-map-section">
                  <h3>📍 Lokasi di Peta</h3>
                  <div class="map-placeholder">Koordinat lokasi tidak tersedia untuk laporan ini.</div>
                </div>
              </div>
            </div>

            <!-- KOMENTAR -->
            <div class="card comments-card">
              <div class="comments-header">
                <div>
                  <h2>Komentar Warga</h2>
                  <p class="text-muted">Diskusikan dan berikan informasi tambahan.</p>
                </div>
                <span class="comment-count">{{ totalKomentar }}</span>
              </div>

              <div v-if="isLoggedIn" class="comment-form">
                <textarea v-model="komentar" class="form-control" rows="3" maxlength="1000" placeholder="Tulis komentar atau informasi tambahan..."></textarea>
                <div class="form-footer">
                  <small class="text-muted">{{ komentar.length }}/1000</small>
                  <button type="button" class="btn btn-primary" :disabled="sending || !komentar.trim()" @click="kirimKomentar">
                    {{ sending ? 'Mengirim...' : 'Kirim Komentar' }}
                  </button>
                </div>
              </div>

              <div v-else class="login-comment">
                <div class="login-icon">🔐</div>
                <p>Login untuk ikut berdiskusi dengan warga lainnya.</p>
                <button type="button" class="btn btn-primary" @click="goLogin">Login untuk Berkomentar</button>
              </div>

              <div v-if="loadingTanggapan" class="empty-comments">Memuat komentar...</div>

              <div v-else-if="!tanggapans.length" class="empty-comments">
                <div class="empty-comments-icon">💬</div>
                <p>Belum ada komentar.</p>
                <small class="text-muted">Jadilah warga pertama yang memberikan komentar.</small>
              </div>

              <div v-else class="comment-list">
                <div v-for="tg in tanggapans" :key="tg.id" class="comment-item">
                  <div class="avatar">{{ getInitial(tg.user?.name) }}</div>

                  <div class="comment-body">
                    <div class="comment-head">
                      <strong>{{ tg.user?.name || 'Warga' }}</strong>
                      <small class="text-muted">{{ formatDate(tg.created_at) }}</small>
                    </div>

                    <p class="comment-text">{{ tg.pesan }}</p>

                    <div class="comment-actions">
                      <button type="button" class="comment-action" :class="{ active: tg.user_reaction === 'like' }" :disabled="reacting === tg.id" @click="reactKomentar(tg, 'like')">
                        👍 {{ tg.likes_count || 0 }}
                      </button>
                      <button type="button" class="comment-action" :class="{ active: tg.user_reaction === 'dislike' }" :disabled="reacting === tg.id" @click="reactKomentar(tg, 'dislike')">
                        👎 {{ tg.dislikes_count || 0 }}
                      </button>
                      <button type="button" class="comment-action" @click="mulaiBalas(tg)">💬 Balas</button>
                    </div>

                    <div v-if="tg.replies && tg.replies.length" class="replies">
                      <div v-for="reply in tg.replies" :key="reply.id" class="reply-item">
                        <div class="avatar reply-avatar">{{ getInitial(reply.user?.name) }}</div>

                        <div class="comment-body">
                          <div class="comment-head">
                            <strong>{{ reply.user?.name || 'Warga' }}</strong>
                            <small class="text-muted">{{ formatDate(reply.created_at) }}</small>
                          </div>

                          <p class="comment-text">{{ reply.pesan }}</p>

                          <div class="comment-actions">
                            <button type="button" class="comment-action" :class="{ active: reply.user_reaction === 'like' }" :disabled="reacting === reply.id" @click="reactKomentar(reply, 'like')">
                              👍 {{ reply.likes_count || 0 }}
                            </button>
                            <button type="button" class="comment-action" :class="{ active: reply.user_reaction === 'dislike' }" :disabled="reacting === reply.id" @click="reactKomentar(reply, 'dislike')">
                              👎 {{ reply.dislikes_count || 0 }}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div v-if="replyTo && replyTo.id === tg.id" class="reply-form">
                      <div class="reply-title">
                        Membalas <strong>{{ tg.user?.name || 'Warga' }}</strong>
                        <button type="button" class="close-reply" @click="batalBalas">✕</button>
                      </div>

                      <textarea id="reply-input" v-model="replyText" class="form-control" rows="2" maxlength="1000" placeholder="Tulis balasan..."></textarea>

                      <div class="form-footer">
                        <small class="text-muted">{{ replyText.length }}/1000</small>
                        <button type="button" class="btn btn-primary" :disabled="sending || !replyText.trim()" @click="kirimBalasan">
                          {{ sending ? 'Mengirim...' : 'Kirim Balasan' }}
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
            <div class="card sidebar-card">
              <h3>Informasi Laporan</h3>

              <div class="info-list">
                <div class="info-row">
                  <span class="info-icon">📌</span>
                  <div class="info-text">
                    <small>Status</small>
                    <span class="status" :class="statusData.class">{{ statusData.icon }} {{ statusData.label }}</span>
                  </div>
                </div>
                <div class="info-row">
                  <span class="info-icon">{{ kategoriIcon }}</span>
                  <div class="info-text">
                    <small>Kategori</small>
                    <strong>{{ kategoriNama }}</strong>
                  </div>
                </div>
                <div class="info-row">
                  <span class="info-icon">👤</span>
                  <div class="info-text">
                    <small>Pelapor</small>
                    <strong>{{ laporan.user?.name || 'Warga' }}</strong>
                  </div>
                </div>
                <div class="info-row">
                  <span class="info-icon">📍</span>
                  <div class="info-text">
                    <small>Lokasi</small>
                    <strong>{{ laporan.lokasi || 'Lokasi tidak tersedia' }}</strong>
                  </div>
                </div>
                <div class="info-row">
                  <span class="info-icon">📅</span>
                  <div class="info-text">
                    <small>Dibuat</small>
                    <strong>{{ formatDate(laporan.created_at) }}</strong>
                  </div>
                </div>
                <div class="info-row">
                  <span class="info-icon">💬</span>
                  <div class="info-text">
                    <small>Total Komentar</small>
                    <strong>{{ totalKomentar }}</strong>
                  </div>
                </div>
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
.report-detail-page { min-height: 100vh; background: var(--background, #f7fafc); }

.section { padding: 32px 0 56px; }
.container { max-width: 1180px; margin: 0 auto; padding: 0 20px; }

.card {
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e2e8f0);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

/* --- LOADING / ERROR --- */
.loading-card, .error-card { padding: 64px 25px; text-align: center; border-radius: 18px; }
.loading-icon, .error-icon { font-size: 44px; margin-bottom: 14px; opacity: 0.85; }
.loading-card h3, .error-card h2 { margin: 0 0 6px; color: var(--text, #172b4d); }
.error-card p { max-width: 480px; margin: 0 auto 22px; color: var(--text-secondary, #64748b); }

.back-button { margin-bottom: 20px; }

/* --- LAYOUT --- */
.dashboard-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 300px;
  align-items: start;
  gap: 24px;
}
.main-column { display: flex; flex-direction: column; gap: 20px; min-width: 0; }

/* --- DETAIL LAPORAN --- */
.report-detail-card { overflow: hidden; padding: 0; border-radius: 16px; animation: fade-in 0.35s ease both; }

.report-photo { position: relative; width: 100%; height: 320px; overflow: hidden; background: var(--surface-soft, #f1f5f9); }
.report-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.report-photo::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.45) 0%, rgba(0, 0, 0, 0) 45%);
  pointer-events: none;
}

.report-category, .status {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 13px; border-radius: 999px;
  font-weight: 600; font-size: 13px; line-height: 1;
}
.report-category {
  background: var(--surface-soft, #f1f5f9); color: var(--text, #172b4d);
}

.floating { position: absolute; z-index: 1; }
.report-category.floating { left: 16px; bottom: 16px; background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(4px); }
.status.floating { right: 16px; top: 16px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.12); }

.report-content { padding: 28px 30px 30px; }

.report-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }

.report-title { font-size: 30px; font-weight: 700; line-height: 1.28; margin: 0 0 16px; color: var(--text, #172b4d); word-break: break-word; letter-spacing: -0.01em; }

.report-meta { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 26px; }
.meta-chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 12px; border-radius: 10px;
  background: var(--surface-soft, #f1f5f9);
  color: var(--text-secondary, #64748b); font-size: 13.5px;
}

.description { border-top: 1px solid var(--border, #e2e8f0); padding-top: 22px; }
.description h3 { margin: 0 0 10px; font-size: 15px; font-weight: 700; color: var(--text, #172b4d); }
.description p { line-height: 1.75; white-space: pre-line; color: var(--text-secondary, #64748b); margin: 0; }

/* PETA LOKASI */
.location-map-section { margin-top: 22px; padding-top: 22px; border-top: 1px solid var(--border, #e2e8f0); }
.location-map-section h3 { margin: 0 0 12px; font-size: 15px; font-weight: 700; color: var(--text, #172b4d); }
.location-map { height: 260px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border, #e2e8f0); }
.map-placeholder {
  height: 120px; border-radius: 12px;
  border: 1px dashed var(--border, #e2e8f0);
  background: var(--surface-soft, #f1f5f9);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-secondary, #64748b); font-size: 13.5px; text-align: center; padding: 12px;
}

/* --- KOMENTAR --- */
.comments-card { padding: 28px 30px; border-radius: 16px; }
.comments-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; margin-bottom: 22px; }
.comments-header h2 { margin: 0 0 4px; font-size: 19px; font-weight: 700; color: var(--text, #172b4d); }
.comments-header p { margin: 0; color: var(--text-secondary, #64748b); font-size: 13.5px; }

.comment-count {
  min-width: 34px; height: 34px; padding: 0 11px; border-radius: 10px;
  background: var(--surface-soft, #f1f5f9); color: var(--primary, #0d9d98);
  display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;
}

.comment-form { margin-bottom: 24px; }
.form-control {
  resize: vertical; border-radius: 12px; border: 1px solid var(--border, #e2e8f0);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.form-control:focus { border-color: var(--primary, #0d9d98); box-shadow: 0 0 0 3px rgba(13, 157, 152, 0.12); outline: none; }
.form-footer { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 10px; }

.login-comment { padding: 26px; border-radius: 14px; background: var(--surface-soft, #f1f5f9); margin-bottom: 24px; text-align: center; }
.login-icon { font-size: 34px; margin-bottom: 8px; opacity: 0.85; }
.login-comment p { margin: 0 0 14px; color: var(--text-secondary, #64748b); }

.empty-comments { text-align: center; padding: 44px 10px; color: var(--text-secondary, #64748b); }
.empty-comments-icon { font-size: 34px; margin-bottom: 10px; opacity: 0.7; }
.empty-comments p { margin: 0 0 4px; color: var(--text, #172b4d); font-weight: 600; }

.comment-list { border-top: 1px solid var(--border, #e2e8f0); }
.comment-item { display: flex; gap: 13px; padding: 20px 0; border-bottom: 1px solid var(--border, #e2e8f0); }
.comment-item:last-child { border-bottom: none; }

.avatar {
  width: 42px; height: 42px; border-radius: 50%;
  background: var(--primary, #0d9d98);
  color: white;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 15px; flex-shrink: 0;
}
.reply-avatar { width: 34px; height: 34px; font-size: 13px; }

.comment-body { flex: 1; min-width: 0; }
.comment-head { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; flex-wrap: wrap; }
.comment-head strong { color: var(--text, #172b4d); font-size: 14.5px; }
.comment-text { margin: 6px 0 8px; color: var(--text-secondary, #64748b); white-space: pre-line; line-height: 1.65; font-size: 14.5px; }

.comment-actions { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }

.comment-action {
  border: none; background: transparent; padding: 6px 10px; border-radius: 8px;
  cursor: pointer; color: var(--text-secondary, #64748b); font-size: 13px; font-weight: 500;
  transition: background-color 0.15s ease, color 0.15s ease;
}
.comment-action:hover:not(:disabled) { background: var(--surface-soft, #f1f5f9); }
.comment-action.active { background: rgba(13, 157, 152, 0.1); color: var(--primary, #0d9d98); font-weight: 700; }
.comment-action:disabled { opacity: 0.55; cursor: not-allowed; }

.replies { margin-top: 14px; margin-left: 4px; padding-left: 17px; border-left: 2px solid var(--border, #e2e8f0); display: flex; flex-direction: column; gap: 4px; }
.reply-item { display: flex; gap: 10px; padding: 11px 0; }

.reply-form {
  margin-top: 14px; padding: 15px; border-radius: 12px;
  background: var(--surface-soft, #f1f5f9); border: 1px solid var(--border, #e2e8f0);
}
.reply-title { display: flex; align-items: center; gap: 5px; margin-bottom: 10px; color: var(--text, #172b4d); font-size: 13.5px; }
.close-reply { margin-left: auto; border: none; background: transparent; cursor: pointer; color: var(--text-secondary, #64748b); font-size: 15px; line-height: 1; padding: 4px; }
.close-reply:hover { color: var(--text, #172b4d); }

/* --- SIDEBAR --- */
.side-column { min-width: 0; }
.sidebar-card { padding: 22px; position: sticky; top: 90px; border-radius: 16px; }
.sidebar-card h3 { margin: 0 0 18px; font-size: 15px; font-weight: 700; color: var(--text, #172b4d); }

.info-list { display: flex; flex-direction: column; }
.info-row { display: flex; gap: 12px; align-items: flex-start; padding: 13px 0; border-bottom: 1px solid var(--border, #e2e8f0); }
.info-row:first-child { padding-top: 0; }
.info-row:last-child { border-bottom: none; padding-bottom: 0; }
.info-icon { font-size: 16px; line-height: 1.4; width: 20px; text-align: center; flex-shrink: 0; }
.info-text { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.info-text small { color: var(--text-secondary, #64748b); font-size: 12.5px; }
.info-text strong { color: var(--text, #172b4d); font-size: 14px; line-height: 1.4; word-break: break-word; }

.status { width: fit-content; font-size: 12.5px; }
.status-waiting { background: #fff7d6; color: #9a7200; }
.status-processing { background: #e0efff; color: #1769aa; }
.status-success { background: #dcfce7; color: #16803c; }
.status-rejected { background: #fee2e2; color: #b42318; }

@keyframes fade-in {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .report-detail-card { animation: none; }
}

@media (max-width: 992px) {
  .dashboard-grid { grid-template-columns: 1fr; }
  .sidebar-card { position: static; }
}

@media (max-width: 768px) {
  .report-content { padding: 22px; }
  .comments-card { padding: 22px; }
  .report-title { font-size: 24px; }
  .report-top { align-items: flex-start; flex-direction: column; }
  .comment-head { align-items: flex-start; flex-direction: column; gap: 3px; }
  .form-footer { align-items: flex-start; flex-direction: column; }
  .form-footer .btn { width: 100%; }
  .login-comment { padding: 22px 15px; }
  .login-comment .btn { width: 100%; }
  .replies { margin-left: 0; padding-left: 12px; }
  .avatar { width: 38px; height: 38px; }
  .reply-avatar { width: 32px; height: 32px; }
  .location-map { height: 200px; }
  .report-photo { height: 220px; }
  .floating.status, .floating.report-category { position: static; display: inline-flex; margin: 10px 10px 0 0; background: var(--surface-soft, #f1f5f9); backdrop-filter: none; box-shadow: none; }
  .report-photo::after { display: none; }
}

@media (max-width: 480px) {
  .report-title { font-size: 21px; }
  .report-content, .comments-card, .sidebar-card { padding: 18px; }
  .comment-item { gap: 9px; }
  .comment-action { padding: 6px 7px; }
  .meta-chip { font-size: 13px; padding: 6px 10px; }
}
</style>