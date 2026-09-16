<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '../../components/AdminLayout.vue'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()

const laporan = ref(null)
const loading = ref(true)
const error = ref('')
const updating = ref(false)
const deleting = ref(false)

const categoryMap = {
  jalan_rusak: { emoji: '🚧', label: 'Jalan' },
  sampah: { emoji: '🗑️', label: 'Sampah' },
  lampu_mati: { emoji: '💡', label: 'Lampu' },
  selokan: { emoji: '🌊', label: 'Selokan' },
  fasilitas: { emoji: '🏞️', label: 'Fasilitas' },
  lainnya: { emoji: '📋', label: 'Lainnya' }
}

const statusMap = {
  baru: 'Menunggu',
  diproses: 'Diproses',
  selesai: 'Selesai',
  ditolak: 'Ditolak'
}

async function fetchDetail() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get(`/laporans/${route.params.id}`)
    // Backend membungkus resource tunggal dalam { data: {...} }.
    // Sebelumnya kode ini langsung pakai response.data (objek wrapper-nya),
    // jadi laporan.value.judul / .status / .foto semuanya undefined.
    const data = response.data?.data ?? response.data

    if (!data || !data.id) {
      error.value = 'Laporan tidak ditemukan.'
      laporan.value = null
      return
    }

    laporan.value = data
  } catch (err) {
    console.error('ERROR DETAIL:', err)
    error.value =
      err.response?.data?.message ||
      'Gagal mengambil detail laporan.'
  } finally {
    loading.value = false
  }
}

async function updateStatus(event) {
  const newStatus = event.target.value

  if (!laporan.value || newStatus === laporan.value.status) {
    return
  }

  const oldStatus = laporan.value.status
  updating.value = true

  try {
    const response = await api.put(`/laporans/${laporan.value.id}`, {
      status: newStatus
    })

    laporan.value = response.data?.data ?? response.data
  } catch (err) {
    console.error('ERROR UPDATE STATUS:', err)
    laporan.value.status = oldStatus

    alert(
      err.response?.data?.message ||
      'Gagal mengubah status laporan.'
    )
  } finally {
    updating.value = false
  }
}

async function deleteLaporan() {
  if (!laporan.value) return

  const yakin = confirm(
    `Hapus laporan "${laporan.value.judul}"?`
  )

  if (!yakin) return

  deleting.value = true

  try {
    await api.delete(`/laporans/${laporan.value.id}`)
    router.push('/admin/reports')
  } catch (err) {
    console.error('ERROR DELETE:', err)

    alert(
      err.response?.data?.message ||
      'Gagal menghapus laporan.'
    )
  } finally {
    deleting.value = false
  }
}

function kembali() {
  router.push('/admin/reports')
}

// Kategori bisa datang sebagai string slug ("kategori") ATAU sebagai
// relasi objek ("kategori_relasi": { nama, icon }), tergantung endpoint.
// Fungsi di bawah menangani keduanya supaya tidak blank kalau salah satu tidak ada.
function getKategoriSlugOrNama(laporanData) {
  if (!laporanData) return ''
  return laporanData.kategori_relasi?.nama || laporanData.kategori || ''
}

function categoryEmoji(laporanData) {
  if (!laporanData) return '📋'
  if (laporanData.kategori_relasi?.icon) return laporanData.kategori_relasi.icon
  const slug = laporanData.kategori
  return categoryMap[slug]?.emoji || '📋'
}

function categoryLabel(laporanData) {
  if (!laporanData) return 'Lainnya'
  const nama = getKategoriSlugOrNama(laporanData)
  return categoryMap[laporanData.kategori]?.label || nama || 'Lainnya'
}

function statusClass(status) {
  const map = {
    baru: 'status-waiting',
    diproses: 'status-processing',
    selesai: 'status-success',
    ditolak: 'status-rejected'
  }

  return map[status] || 'status-waiting'
}

function statusLabel(status) {
  return statusMap[status] || status || '-'
}

function formatDate(date) {
  if (!date) return '-'

  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

function formatDateTime(date) {
  if (!date) return '-'

  return new Date(date).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Nama field foto dari backend kadang "foto", kadang "foto_url" —
// fungsi ini menerima laporan-nya langsung supaya bisa cek keduanya.
function resolveFotoValue(laporanData) {
  return laporanData?.foto_url || laporanData?.foto || ''
}

function getFotoUrl(fotoValue) {
  if (!fotoValue) return ''

  if (
    fotoValue.startsWith('http://') ||
    fotoValue.startsWith('https://')
  ) {
    return fotoValue
  }

  if (fotoValue.startsWith('/storage/')) {
    return `http://127.0.0.1:8000${fotoValue}`
  }

  if (fotoValue.startsWith('storage/')) {
    return `http://127.0.0.1:8000/${fotoValue}`
  }

  return `http://127.0.0.1:8000/storage/${fotoValue}`
}

// Cek koordinat dengan aman (0 dianggap valid, bukan falsy check biasa)
function hasCoordinates(laporanData) {
  const lat = laporanData?.latitude
  const lng = laporanData?.longitude
  return lat !== null && lat !== undefined && lat !== '' &&
         lng !== null && lng !== undefined && lng !== '' &&
         !Number.isNaN(parseFloat(lat)) && !Number.isNaN(parseFloat(lng))
}

// Embed Google Maps tanpa API key (pakai endpoint output=embed).
// Kalau proyekmu sudah punya Google Maps API key, ganti dengan:
// `https://www.google.com/maps/embed/v1/place?key=API_KEY&q=${lat},${lng}`
function mapEmbedUrl(lat, lng) {
  return `https://www.google.com/maps?q=${lat},${lng}&z=16&output=embed`
}

function mapLinkUrl(lat, lng) {
  return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`
}

onMounted(fetchDetail)
</script>

<template>
  <AdminLayout
    page-title="Detail Laporan"
    page-description="Informasi lengkap laporan warga"
  >
    <div class="detail-topbar">
      <button class="detail-back-button" @click="kembali">
        ← Kembali ke Laporan
      </button>
    </div>

    <div v-if="loading" class="card detail-loading">
      <div class="detail-spinner"></div>
      <p>Memuat detail laporan...</p>
    </div>

    <div v-else-if="error" class="card detail-error">
      <div class="detail-error-icon">⚠️</div>
      <h3>Gagal Memuat Laporan</h3>
      <p>{{ error }}</p>

      <button class="btn btn-secondary" @click="fetchDetail">
        🔄 Coba Lagi
      </button>
    </div>

    <div v-else-if="laporan" class="report-detail-page">
      <div class="card report-detail-card">
        <div class="report-detail-image">
          <img
            v-if="resolveFotoValue(laporan)"
            :src="getFotoUrl(resolveFotoValue(laporan))"
            :alt="laporan.judul"
            @error="$event.target.style.display = 'none'"
          />

          <div v-else class="report-no-image">
            <div>{{ categoryEmoji(laporan) }}</div>
            <span>Tidak ada foto laporan</span>
          </div>
        </div>

        <div class="report-detail-content">
          <div class="detail-category">
            {{ categoryEmoji(laporan) }}
            {{ categoryLabel(laporan) }}
          </div>

          <h2 class="detail-title">
            {{ laporan.judul }}
          </h2>

          <div class="detail-status-wrapper">
            <span
              class="detail-status"
              :class="statusClass(laporan.status)"
            >
              {{ statusLabel(laporan.status) }}
            </span>
          </div>

          <div class="detail-section">
            <h3>Deskripsi Laporan</h3>
            <p>{{ laporan.deskripsi }}</p>
          </div>

          <div class="detail-section">
            <h3>Lokasi</h3>

            <div class="location-box">
              <span>📍</span>
              <span>
                {{ laporan.lokasi || 'Lokasi tidak tersedia' }}
              </span>
            </div>

            <div v-if="hasCoordinates(laporan)" class="location-map-wrapper">
              <iframe
                class="location-map-frame"
                :src="mapEmbedUrl(laporan.latitude, laporan.longitude)"
                width="100%"
                height="260"
                style="border: 0"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Lokasi laporan di Google Maps"
              ></iframe>

              <a
                :href="mapLinkUrl(laporan.latitude, laporan.longitude)"
                target="_blank"
                rel="noopener noreferrer"
                class="location-map-link"
              >
                🔗 Buka di Google Maps
              </a>
            </div>

            <div v-else class="location-map-placeholder">
              Koordinat GPS tidak tersedia untuk laporan ini.
            </div>
          </div>
        </div>
      </div>

      <div class="card report-info-card">
        <div class="detail-info-header">
          <div>
            <h2>Informasi Laporan</h2>
            <p>Data warga dan waktu laporan</p>
          </div>
        </div>

        <div class="detail-info-grid">
          <div class="detail-info-item">
            <span class="detail-info-icon">👤</span>

            <div>
              <span class="detail-info-label">Pelapor</span>
              <strong>
                {{ laporan.user?.name || 'Warga' }}
              </strong>
            </div>
          </div>

          <div
            v-if="laporan.user?.email"
            class="detail-info-item"
          >
            <span class="detail-info-icon">✉️</span>

            <div>
              <span class="detail-info-label">Email</span>
              <strong>{{ laporan.user.email }}</strong>
            </div>
          </div>

          <div class="detail-info-item">
            <span class="detail-info-icon">
              {{ categoryEmoji(laporan) }}
            </span>

            <div>
              <span class="detail-info-label">Kategori</span>
              <strong>
                {{ categoryLabel(laporan) }}
              </strong>
            </div>
          </div>

          <div class="detail-info-item">
            <span class="detail-info-icon">📅</span>

            <div>
              <span class="detail-info-label">Dibuat</span>
              <strong>
                {{ formatDateTime(laporan.created_at) }}
              </strong>
            </div>
          </div>

          <div class="detail-info-item">
            <span class="detail-info-icon">🔄</span>

            <div>
              <span class="detail-info-label">
                Terakhir diperbarui
              </span>

              <strong>
                {{ formatDateTime(laporan.updated_at) }}
              </strong>
            </div>
          </div>
        </div>
      </div>

      <div class="card report-action-card">
        <div>
          <h3>Kelola Status</h3>
          <p>
            Perbarui status laporan sesuai perkembangan.
          </p>
        </div>

        <div class="detail-actions">
          <select
            class="admin-status-select"
            :class="statusClass(laporan.status)"
            :value="laporan.status"
            :disabled="updating"
            @change="updateStatus"
          >
            <option value="baru">Menunggu</option>
            <option value="diproses">Diproses</option>
            <option value="selesai">Selesai</option>
            <option value="ditolak">Ditolak</option>
          </select>

          <button
            class="btn btn-danger"
            :disabled="deleting"
            @click="deleteLaporan"
          >
            {{ deleting ? 'Menghapus...' : '🗑️ Hapus Laporan' }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.location-map-wrapper {
  margin-top: 12px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border, #e2e8f0);
}
.location-map-frame { display: block; }
.location-map-link {
  display: block;
  padding: 10px 14px;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--primary, #0d9d98);
  background: var(--surface-soft, #f1f5f9);
  text-decoration: none;
  border-top: 1px solid var(--border, #e2e8f0);
}
.location-map-link:hover { text-decoration: underline; }
.location-map-placeholder {
  margin-top: 12px;
  padding: 16px;
  border-radius: 12px;
  border: 1px dashed var(--border, #e2e8f0);
  background: var(--surface-soft, #f1f5f9);
  color: var(--text-secondary, #64748b);
  font-size: 13.5px;
  text-align: center;
}
</style>  