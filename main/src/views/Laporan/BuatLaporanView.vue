<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import Navbar from '../../components/Navbar.vue'
import Footer from '../../components/Footer.vue'
import api from '../../services/api.js'
import LocationPicker from '@/components/LocationPicker.vue'

const router = useRouter()

const MAX_FOTO = 5
const MAX_UKURAN = 2 * 1024 * 1024 // 2 MB
const TIPE_DIIZINKAN = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']

const loading = ref(false)
const loadingKategori = ref(true)
const gettingLocation = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const categories = ref([])
const locationPickerRef = ref(null)

// Setiap item: { file, url }. Dipisah dari `form` karena file tidak
// cocok disimpan langsung di objek yang di-`v-model`-kan ke banyak input.
const fotoItems = ref([])

const form = ref({
  judul: '',
  deskripsi: '',
  kategori_id: '',
  lokasi: '',
  latitude: '',
  longitude: '',
})

function checkLogin() {
  const token = sessionStorage.getItem('token')
  if (!token) {
    router.push({ name: 'login', query: { redirect: '/laporan/buat' } })
    return false
  }
  return true
}

function getCategoryEmoji(nama) {
  if (!nama) return '📌'
  const text = nama.toLowerCase()

  if (text.includes('jalan') || text.includes('infrastruktur')) return '🚧'
  if (text.includes('sampah') || text.includes('kebersihan')) return '🗑️'
  if (text.includes('lampu') || text.includes('penerangan')) return '💡'
  if (text.includes('selokan') || text.includes('drainase') || text.includes('air')) return '🌊'
  if (text.includes('fasilitas') || text.includes('taman') || text.includes('umum')) return '🏞️'
  if (text.includes('keamanan')) return '🛡️'
  if (text.includes('lingkungan')) return '🌱'

  return '📌'
}

function getCategoryLabel(category) {
  return `${getCategoryEmoji(category.nama)} ${category.nama}`
}

async function fetchKategori() {
  loadingKategori.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/kategoris')
    const data = response.data?.data || response.data || []
    categories.value = Array.isArray(data) ? data : []
  } catch (error) {
    if (error.response?.status === 401) {
      sessionStorage.removeItem('token')
      sessionStorage.removeItem('user')
      router.push({ name: 'login', query: { redirect: '/laporan/buat' } })
      return
    }
    errorMessage.value = error.response?.data?.message || 'Gagal mengambil data kategori.'
  } finally {
    loadingKategori.value = false
  }
}

function handleFotoChange(event) {
  const files = Array.from(event.target.files || [])
  event.target.value = '' // biar file yang sama bisa dipilih lagi nanti

  if (!files.length) return

  errorMessage.value = ''

  const sisaSlot = MAX_FOTO - fotoItems.value.length
  if (sisaSlot <= 0) {
    errorMessage.value = `Maksimal ${MAX_FOTO} foto per laporan.`
    return
  }

  const valid = []
  for (const file of files) {
    if (!TIPE_DIIZINKAN.includes(file.type)) {
      errorMessage.value = `Foto "${file.name}" harus berformat JPG, JPEG, PNG, atau WEBP.`
      continue
    }
    if (file.size > MAX_UKURAN) {
      errorMessage.value = `Foto "${file.name}" ukurannya lebih dari 2 MB.`
      continue
    }
    valid.push(file)
  }

  if (valid.length > sisaSlot) {
    errorMessage.value = `Cuma bisa tambah ${sisaSlot} foto lagi (maksimal ${MAX_FOTO} foto).`
  }

  const dipakai = valid.slice(0, sisaSlot)
  for (const file of dipakai) {
    fotoItems.value.push({ file, url: URL.createObjectURL(file) })
  }
}

function hapusFoto(index) {
  const item = fotoItems.value[index]
  if (item) URL.revokeObjectURL(item.url)
  fotoItems.value.splice(index, 1)
}

function handleLocationPick(val) {
  form.value.lokasi = val.lokasi
  form.value.latitude = val.latitude
  form.value.longitude = val.longitude
}

function getLocation() {
  errorMessage.value = ''

  if (!navigator.geolocation) {
    errorMessage.value = 'Browser kamu tidak mendukung fitur lokasi.'
    return
  }

  gettingLocation.value = true

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude
      const lng = position.coords.longitude

      form.value.latitude = lat
      form.value.longitude = lng

      if (!form.value.lokasi) {
        form.value.lokasi = `Lokasi GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}`
      }

      locationPickerRef.value?.flyToLocation(lat, lng)

      gettingLocation.value = false
    },
    (error) => {
      gettingLocation.value = false
      if (error.code === 1) errorMessage.value = 'Izin lokasi ditolak. Silakan izinkan akses lokasi di browser.'
      else if (error.code === 2) errorMessage.value = 'Lokasi tidak dapat ditemukan.'
      else if (error.code === 3) errorMessage.value = 'Pengambilan lokasi terlalu lama. Silakan coba lagi.'
      else errorMessage.value = 'Gagal mendapatkan lokasi.'
    },
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
  )
}

function bersihkanFotoItems() {
  for (const item of fotoItems.value) URL.revokeObjectURL(item.url)
  fotoItems.value = []
}

function resetForm() {
  form.value = {
    judul: '', deskripsi: '', kategori_id: '',
    lokasi: '', latitude: '', longitude: '',
  }
  bersihkanFotoItems()
  errorMessage.value = ''
  successMessage.value = ''

  const fileInput = document.getElementById('foto')
  if (fileInput) fileInput.value = ''
}

function validateForm() {
  if (!form.value.judul.trim()) {
    errorMessage.value = 'Judul laporan wajib diisi.'
    return false
  }
  if (!form.value.deskripsi.trim()) {
    errorMessage.value = 'Deskripsi laporan wajib diisi.'
    return false
  }
  if (!form.value.kategori_id) {
    errorMessage.value = 'Silakan pilih kategori laporan.'
    return false
  }
  return true
}

async function submitLaporan() {
  errorMessage.value = ''
  successMessage.value = ''

  if (!checkLogin()) return
  if (!validateForm()) return

  loading.value = true

  try {
    const formData = new FormData()
    formData.append('judul', form.value.judul.trim())
    formData.append('deskripsi', form.value.deskripsi.trim())
    formData.append('kategori_id', form.value.kategori_id)

    if (form.value.lokasi) formData.append('lokasi', form.value.lokasi.trim())
    if (form.value.latitude !== '' && form.value.latitude !== null) formData.append('latitude', form.value.latitude)
    if (form.value.longitude !== '' && form.value.longitude !== null) formData.append('longitude', form.value.longitude)

    for (const item of fotoItems.value) {
      formData.append('fotos[]', item.file)
    }

    const response = await api.post('/laporans', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    successMessage.value = response.data?.message || 'Laporan berhasil dikirim.'

    const laporan = response.data?.data || response.data?.laporan || response.data
    const laporanId = laporan?.id

    setTimeout(() => {
      router.push(laporanId ? `/laporan/${laporanId}` : '/laporan')
    }, 900)
  } catch (error) {
    if (error.response?.status === 401) {
      sessionStorage.removeItem('token')
      sessionStorage.removeItem('user')
      router.push({ name: 'login', query: { redirect: '/laporan/buat' } })
      return
    }

    if (error.response?.status === 422) {
      const errors = error.response?.data?.errors
      if (errors) {
        const firstError = Object.values(errors)[0]
        errorMessage.value = Array.isArray(firstError) ? firstError[0] : String(firstError)
      } else {
        errorMessage.value = error.response?.data?.message || 'Data laporan belum lengkap.'
      }
      return
    }

    errorMessage.value = error.response?.data?.message || 'Gagal mengirim laporan. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (!checkLogin()) return
  fetchKategori()
})

onBeforeUnmount(() => {
  bersihkanFotoItems()
})
</script>

<template>
  <div class="buat-laporan-page">
    <Navbar />

    <section class="hero">
      <div class="hero-content">
        <div class="hero-badge">📢 <span>Layanan Laporan Masyarakat</span></div>
        <h1>Buat <span>Laporan Warga</span></h1>
        <p>Sampaikan masalah yang kamu temukan di lingkungan sekitar agar dapat segera ditindaklanjuti.</p>
      </div>
    </section>

    <section class="form-section">
      <div class="form-container">
        <div class="form-card">

          <div class="form-header">
            <div class="form-icon">📝</div>
            <div>
              <h2>Sampaikan Laporan</h2>
              <p>Lengkapi informasi berikut dengan jelas agar laporan mudah ditindaklanjuti.</p>
            </div>
          </div>

          <div v-if="errorMessage" class="alert alert-error">
            <span class="alert-icon">⚠️</span>
            <span>{{ errorMessage }}</span>
          </div>

          <div v-if="successMessage" class="alert alert-success">
            <span class="alert-icon">✅</span>
            <span>{{ successMessage }}</span>
          </div>

          <div class="form-group">
            <label for="judul">Judul Laporan <span>*</span></label>
            <input id="judul" v-model="form.judul" type="text" placeholder="Contoh: Jalan berlubang di Jalan Melati" maxlength="255" />
            <small class="help-text">Buat judul yang singkat dan mudah dipahami.</small>
          </div>

          <div class="form-group">
            <label for="kategori">Kategori Masalah <span>*</span></label>
            <div class="category-select-wrapper">
              <select id="kategori" v-model="form.kategori_id" :disabled="loadingKategori">
                <option value="" disabled>
                  {{ loadingKategori ? '⏳ Memuat kategori...' : '📂 Pilih kategori masalah' }}
                </option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ getCategoryLabel(category) }}
                </option>
              </select>
            </div>
            <small class="help-text">💡 Pilih kategori yang paling sesuai dengan masalah yang ingin kamu laporkan.</small>
          </div>

          <div class="form-group">
            <label for="deskripsi">Deskripsi Laporan <span>*</span></label>
            <textarea id="deskripsi" v-model="form.deskripsi" placeholder="Jelaskan masalah secara detail. Contoh: Jalan berlubang cukup besar dan membahayakan pengendara terutama pada malam hari."></textarea>
            <small class="help-text">✍️ Jelaskan lokasi, kondisi masalah, dan informasi lain yang menurut kamu penting.</small>
          </div>

          <div class="form-group">
            <label>Tandai Lokasi di Peta</label>
            <LocationPicker
              ref="locationPickerRef"
              :model-value="{ latitude: form.latitude, longitude: form.longitude, lokasi: form.lokasi }"
              @update:model-value="handleLocationPick"
            />
          </div>

          <div class="form-group">
            <label for="lokasi">Alamat Lokasi Kejadian</label>
            <div class="location-input">
              <input id="lokasi" v-model="form.lokasi" type="text" placeholder="Contoh: Jalan Melati RT 03 RW 05" />
              <button type="button" class="location-button" :disabled="gettingLocation" @click="getLocation">
                <span v-if="gettingLocation">⏳ Mencari...</span>
                <span v-else>📍 Gunakan Lokasi Saya</span>
              </button>
            </div>
            <small class="help-text">📍 Klik di peta, isi otomatis lewat GPS, atau tulis alamat manual.</small>
          </div>

          <div v-if="form.latitude !== '' && form.longitude !== ''" class="coordinate-box">
            <div>📍 Koordinat lokasi tersimpan</div>
            <small>
              Latitude: {{ Number(form.latitude).toFixed(6) }} &nbsp;•&nbsp;
              Longitude: {{ Number(form.longitude).toFixed(6) }}
            </small>
          </div>

          <div class="form-group">
            <label for="foto">Foto Bukti</label>
            <div class="upload-box" :class="{ disabled: fotoItems.length >= MAX_FOTO }">
              <div class="upload-icon">📷</div>
              <div class="upload-content">
                <strong>Tambahkan foto bukti ({{ fotoItems.length }}/{{ MAX_FOTO }})</strong>
                <small>Bisa pilih lebih dari satu foto sekaligus.</small>
                <input
                  id="foto"
                  type="file"
                  accept="image/jpeg,image/png,image/jpg,image/webp"
                  multiple
                  :disabled="fotoItems.length >= MAX_FOTO"
                  @change="handleFotoChange"
                />
              </div>
            </div>
            <small class="help-text">📸 Format JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB per foto, maksimal {{ MAX_FOTO }} foto.</small>
          </div>

          <div v-if="fotoItems.length" class="preview-wrapper">
            <div class="preview-header">
              <strong>🖼️ Preview Foto ({{ fotoItems.length }})</strong>
            </div>
            <div class="preview-grid">
              <div v-for="(item, index) in fotoItems" :key="item.url" class="preview-item">
                <img :src="item.url" :alt="`Preview foto ${index + 1}`" class="foto-preview" />
                <button type="button" class="remove-photo" @click="hapusFoto(index)">✕</button>
              </div>
            </div>
          </div>

          <div class="info-box">
            <div class="info-icon">💡</div>
            <div>
              <strong>Tips membuat laporan</strong>
              <p>Gunakan judul yang jelas, pilih kategori yang tepat, jelaskan masalah secara detail, dan tambahkan foto bukti jika tersedia.</p>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="reset-button" :disabled="loading" @click="resetForm">↻ Reset</button>
            <button type="button" class="submit-button" :disabled="loading || loadingKategori" @click="submitLaporan">
              <span v-if="loading">⏳ Mengirim...</span>
              <span v-else>📤 Kirim Laporan</span>
            </button>
          </div>

          <div class="required-note"><span>*</span> Wajib diisi</div>

        </div>
      </div>
    </section>

    <Footer />
  </div>
</template>

<style scoped>
.buat-laporan-page {
  min-height: 100vh;
  background: #f6f9fc;
  color: #172b4d;
  overflow-x: hidden;
}

.hero {
  position: relative;
  min-height: 365px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 75px 20px 125px;
  overflow: hidden;
  text-align: center;
  background: linear-gradient(135deg, #123c56 0%, #105568 48%, #1196a2 100%);
}

.hero::before {
  content: "";
  position: absolute;
  width: 560px;
  height: 560px;
  top: -330px;
  right: -120px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.055);
  pointer-events: none;
}

.hero::after {
  content: "";
  position: absolute;
  width: 420px;
  height: 420px;
  bottom: -310px;
  left: -140px;
  border-radius: 50%;
  background: rgba(102, 238, 220, 0.05);
  pointer-events: none;
}

.hero-content {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 720px;
  margin: auto;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 10px 18px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  color: #73e7dc;
  font-size: 12px;
  font-weight: 750;
  backdrop-filter: blur(8px);
}

.hero h1 {
  margin: 21px 0 15px;
  color: #ffffff;
  font-size: clamp(38px, 5vw, 56px);
  line-height: 1.08;
  font-weight: 850;
  letter-spacing: -1.8px;
}

.hero h1 span { color: #70e8dc; }

.hero p {
  max-width: 610px;
  margin: auto;
  color: rgba(255, 255, 255, 0.78);
  font-size: 15px;
  line-height: 1.75;
}

.form-section {
  position: relative;
  z-index: 5;
  margin-top: -82px;
  padding-bottom: 90px;
}

.form-container {
  width: min(880px, calc(100% - 40px));
  margin: auto;
}

.form-card {
  padding: 36px 40px;
  border: 1px solid #e2ebf0;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 22px 55px rgba(25, 55, 75, 0.10), 0 5px 15px rgba(25, 55, 75, 0.04);
}

.form-header {
  display: flex;
  align-items: center;
  gap: 17px;
  margin-bottom: 30px;
  padding-bottom: 24px;
  border-bottom: 1px solid #edf1f4;
}

.form-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 58px;
  height: 58px;
  flex-shrink: 0;
  border-radius: 16px;
  background: linear-gradient(135deg, #e9faf8, #e8f7fa);
  font-size: 27px;
}

.form-header h2 {
  margin: 0 0 6px;
  color: #172b4d;
  font-size: 25px;
  font-weight: 850;
  letter-spacing: -0.5px;
}

.form-header p {
  margin: 0;
  color: #8796a9;
  font-size: 13px;
  line-height: 1.6;
}

.alert {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 14px 16px;
  margin-bottom: 22px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 650;
  line-height: 1.5;
}

.alert-icon { font-size: 18px; }
.alert-error { border: 1px solid #ffd5d5; background: #fff4f4; color: #c44747; }
.alert-success { border: 1px solid #cceede; background: #effbf5; color: #27815e; }

.form-group { margin-bottom: 22px; }

.form-group label {
  display: block;
  margin-bottom: 9px;
  color: #344862;
  font-size: 13px;
  font-weight: 800;
}

.form-group label span { margin-left: 2px; color: #e54848; }

.form-group input[type="text"],
.form-group select,
.form-group textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #dce6ec;
  border-radius: 12px;
  outline: none;
  background: #f9fbfc;
  color: #172b4d;
  font-family: inherit;
  font-size: 13px;
  transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.form-group input[type="text"],
.form-group select {
  height: 48px;
  padding: 0 14px;
}

.form-group textarea {
  min-height: 145px;
  padding: 14px;
  line-height: 1.65;
  resize: vertical;
}

.form-group input[type="text"]:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #0d9d98;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(13, 157, 152, 0.08);
}

.form-group select { cursor: pointer; }
.form-group select:disabled { opacity: 0.65; cursor: not-allowed; }

.location-input { display: flex; align-items: center; gap: 10px; }
.location-input input { flex: 1; min-width: 0; }

.location-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 48px;
  flex-shrink: 0;
  padding: 0 17px;
  border: 0;
  border-radius: 11px;
  background: linear-gradient(135deg, #0d9d98, #1196a2);
  color: #ffffff;
  font-family: inherit;
  font-size: 12px;
  font-weight: 750;
  cursor: pointer;
  box-shadow: 0 7px 17px rgba(13, 157, 152, 0.18);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.location-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(13, 157, 152, 0.25);
}

.coordinate-box {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: -7px;
  margin-bottom: 22px;
  padding: 13px 15px;
  border: 1px solid #cdeceb;
  border-radius: 11px;
  background: linear-gradient(135deg, #f0fbfa, #edfafa);
  color: #0d8f8a;
  font-size: 12px;
  font-weight: 750;
}

.coordinate-box small { color: #638287; font-size: 11px; font-weight: 500; }

.help-text {
  display: block;
  margin-top: 8px;
  color: #8998a9;
  font-size: 11px;
  line-height: 1.5;
}

.upload-box {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 17px;
  border: 1px dashed #cfdde4;
  border-radius: 14px;
  background: #f9fbfc;
  transition: border-color 0.2s ease, background 0.2s ease;
}

.upload-box:hover { border-color: #0d9d98; background: #f4fbfa; }
.upload-box.disabled { opacity: 0.6; }
.upload-box.disabled:hover { border-color: #cfdde4; background: #f9fbfc; }

.upload-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  flex-shrink: 0;
  border-radius: 13px;
  background: #eaf8f7;
  font-size: 24px;
}

.upload-content { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.upload-content strong { color: #344862; font-size: 13px; }
.upload-content small { color: #8998a9; font-size: 11px; }
.upload-content input { margin-top: 4px; width: 100%; color: #52657d; font-family: inherit; font-size: 11px; }

.preview-wrapper {
  margin: -2px 0 24px;
  padding: 16px;
  border: 1px solid #e1eaee;
  border-radius: 15px;
  background: #fbfcfd;
}

.preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.preview-header strong { color: #52657d; font-size: 12px; }

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 12px;
}

.preview-item { position: relative; }

.remove-photo {
  position: absolute;
  top: 7px;
  right: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: rgba(20, 30, 45, 0.55);
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  backdrop-filter: blur(2px);
}
.remove-photo:hover { background: #c44747; }

.foto-preview {
  display: block;
  width: 100%;
  height: 130px;
  object-fit: cover;
  border: 1px solid #dce7ec;
  border-radius: 13px;
  background: #f5f8fa;
  box-shadow: 0 8px 20px rgba(20, 45, 65, 0.08);
}

.info-box {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-top: 8px;
  padding: 15px 16px;
  border: 1px solid #dcefee;
  border-radius: 13px;
  background: linear-gradient(135deg, #f1fbfa, #f4fbfc);
}

.info-icon { font-size: 20px; }
.info-box strong { display: block; margin-bottom: 4px; color: #315665; font-size: 12px; }
.info-box p { margin: 0; color: #718891; font-size: 11px; line-height: 1.6; }

.form-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 30px;
  padding-top: 22px;
  border-top: 1px solid #edf1f4;
}

.reset-button,
.submit-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 45px;
  padding: 0 19px;
  border-radius: 10px;
  font-family: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s ease;
}

.reset-button { border: 1px solid #dce5eb; background: #ffffff; color: #65758b; }
.reset-button:hover { border-color: #c7d5dc; background: #f8fafb; color: #43566e; }

.submit-button {
  border: 0;
  background: linear-gradient(135deg, #0d9d98, #1196a2);
  color: #ffffff;
  box-shadow: 0 8px 20px rgba(13, 157, 152, 0.22);
}

.submit-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 11px 25px rgba(13, 157, 152, 0.28);
}

.reset-button:disabled,
.submit-button:disabled,
.location-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.required-note { margin-top: 15px; color: #9aa8b6; font-size: 10px; text-align: right; }
.required-note span { color: #e54848; font-weight: 800; }

@media (max-width: 800px) {
  .hero { min-height: 340px; padding: 60px 20px 115px; }
  .form-section { margin-top: -70px; }
  .form-container { width: min(720px, calc(100% - 30px)); }
  .form-card { padding: 30px; }
}

@media (max-width: 600px) {
  .hero { min-height: 350px; padding: 50px 15px 110px; }
  .hero h1 { font-size: 36px; letter-spacing: -1px; }
  .hero p { font-size: 13px; }
  .form-section { margin-top: -60px; padding-bottom: 60px; }
  .form-container { width: calc(100% - 24px); }
  .form-card { padding: 21px 17px; border-radius: 17px; }
  .form-header { align-items: flex-start; margin-bottom: 24px; }
  .form-icon { width: 48px; height: 48px; border-radius: 12px; font-size: 22px; }
  .form-header h2 { font-size: 21px; }
  .form-header p { font-size: 12px; }
  .form-group { margin-bottom: 19px; }
  .location-input { display: block; }
  .location-input input { width: 100%; }
  .location-button { width: 100%; margin-top: 9px; }
  .upload-box { align-items: flex-start; padding: 14px; }
  .upload-icon { width: 45px; height: 45px; font-size: 21px; }
  .preview-grid { grid-template-columns: repeat(auto-fill, minmax(105px, 1fr)); }
  .foto-preview { height: 105px; }
  .form-actions { flex-direction: column; gap: 9px; margin-top: 24px; padding-top: 19px; }
  .reset-button, .submit-button { width: 100%; }
  .required-note { text-align: center; }
}
</style>