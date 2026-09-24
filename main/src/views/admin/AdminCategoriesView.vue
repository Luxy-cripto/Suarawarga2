<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import AdminLayout from '../../components/AdminLayout.vue'

const kategoris = ref([])
const loading = ref(true)
const editingId = ref(null)

const form = ref({
  nama: '',
  icon: '📋',
  warna: 'blue',
  deskripsi: '',
})

const warnaOptions = [
  { key: 'blue', label: 'Biru' },
  { key: 'green', label: 'Hijau' },
  { key: 'yellow', label: 'Kuning' },
  { key: 'orange', label: 'Oranye' },
  { key: 'purple', label: 'Ungu' },
]

function resetForm() {
  form.value = { nama: '', icon: '📋', warna: 'blue', deskripsi: '' }
  editingId.value = null
}

function editKategori(kategori) {
  editingId.value = kategori.id
  form.value = {
    nama: kategori.nama,
    icon: kategori.icon,
    warna: kategori.warna,
    deskripsi: kategori.deskripsi || '',
  }
}

async function fetchKategoris() {
  loading.value = true
  try {
    const res = await api.get('/kategoris')
    kategoris.value = res.data
  } catch (err) {
    kategoris.value = []
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  try {
    if (editingId.value) {
      await api.put(`/kategoris/${editingId.value}`, form.value)
    } else {
      await api.post('/kategoris', form.value)
    }
    resetForm()
    await fetchKategoris()
  } catch (err) {
    console.error('Gagal menyimpan kategori', err)
  }
}

async function deleteKategori(kategori) {
  if (!confirm(`Yakin mau hapus kategori "${kategori.nama}"?`)) return
  try {
    await api.delete(`/kategoris/${kategori.id}`)
    await fetchKategoris()
  } catch (err) {
    console.error('Gagal hapus kategori', err)
  }
}

onMounted(fetchKategoris)
</script>

<template>
  <AdminLayout page-title="Kelola Kategori" page-description="Atur kategori jenis laporan yang tersedia">

    <!-- Form tambah/edit -->
    <div class="card admin-category-form">
      <h3>{{ editingId ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</h3>

      <form @submit.prevent="handleSubmit">
        <div class="admin-form-row">
          <div class="form-group admin-icon-group">
            <label class="form-label">Icon</label>
            <input v-model="form.icon" type="text" class="form-control" maxlength="4" />
          </div>
          <div class="form-group">
            <label class="form-label">Nama Kategori</label>
            <input v-model="form.nama" type="text" class="form-control" placeholder="Contoh: Jalan Rusak" required />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Deskripsi</label>
          <input v-model="form.deskripsi" type="text" class="form-control" placeholder="Deskripsi singkat kategori" />
        </div>

        <div class="form-group">
          <label class="form-label">Warna</label>
          <div class="color-picker">
            <button
              v-for="w in warnaOptions"
              :key="w.key"
              type="button"
              class="color-swatch"
              :class="['swatch-' + w.key, { active: form.warna === w.key }]"
              @click="form.warna = w.key"
              :title="w.label"
            ></button>
          </div>
        </div>

        <div class="admin-form-actions">
          <button v-if="editingId" type="button" class="btn btn-secondary" @click="resetForm">Batal</button>
          <button type="submit" class="btn btn-primary">
            {{ editingId ? 'Simpan Perubahan' : 'Tambah Kategori' }}
          </button>
        </div>
      </form>
    </div>

    <p v-if="loading" class="text-muted" style="padding: 20px;">Memuat data...</p>

    <div v-else class="admin-category-grid">
      <div v-for="kategori in kategoris" :key="kategori.id" class="card admin-category-card">
        <div class="admin-category-icon" :class="'category-' + kategori.warna">
          {{ kategori.icon }}
        </div>
        <h3>{{ kategori.nama }}</h3>
        <p class="admin-category-desc text-muted">{{ kategori.deskripsi || 'Tidak ada deskripsi' }}</p>

        <div class="admin-category-footer">
          <span class="admin-category-count">{{ kategori.laporans_count ?? 0 }} laporan</span>
          <div class="admin-row-actions">
            <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" @click="editKategori(kategori)">
              ✏️ Edit
            </button>
            <button class="admin-delete-btn" @click="deleteKategori(kategori)">🗑️</button>
          </div>
        </div>
      </div>

      <div v-if="kategoris.length === 0" class="empty-state card" style="grid-column: 1 / -1;">
        <div class="empty-icon">🏷️</div>
        <h3>Belum ada kategori</h3>
        <p class="text-muted">Tambahkan kategori pertama menggunakan form di atas.</p>
      </div>
    </div>

  </AdminLayout>
</template>