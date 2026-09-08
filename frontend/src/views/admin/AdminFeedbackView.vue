<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import AdminLayout from '../../components/AdminLayout.vue'

// ======================================================
// DATA
// ======================================================

const feedbacks = ref([])
const loading = ref(true)

const filterKategori = ref('')
const filterStatus = ref('')

const editingCatatan = ref(null)
const catatanInput = ref('')

const savingCatatan = ref(false)
const catatanError = ref('')

const updatingStatus = ref(null)
const deletingFeedback = ref(null)

// ======================================================
// STATUS OPTIONS
// ======================================================

const statusOptions = [
  {
    key: 'baru',
    label: 'Baru',
  },
  {
    key: 'ditinjau',
    label: 'Ditinjau',
  },
  {
    key: 'dikerjakan',
    label: 'Dikerjakan',
  },
  {
    key: 'selesai',
    label: 'Selesai',
  },
]

// ======================================================
// KATEGORI
// ======================================================

const kategoriLabel = {
  bug: '🐛 Bug',
  saran: '💡 Saran',
  pujian: '😊 Pujian',
  lainnya: '📋 Lainnya',
}

// ======================================================
// FILTER FEEDBACK
// ======================================================

const filteredFeedbacks = computed(() => {
  return feedbacks.value.filter((f) => {

    const matchKategori =
      filterKategori.value
        ? f.kategori === filterKategori.value
        : true

    const matchStatus =
      filterStatus.value
        ? f.status === filterStatus.value
        : true

    return matchKategori && matchStatus
  })
})

// ======================================================
// STATISTIK
// ======================================================

const stats = computed(() => {

  const total = feedbacks.value.length

  const bug = feedbacks.value.filter(
    f => f.kategori === 'bug'
  ).length

  const baru = feedbacks.value.filter(
    f => f.status === 'baru'
  ).length

  const avgRating = total
    ? (
        feedbacks.value.reduce(
          (sum, f) => sum + Number(f.rating || 0),
          0
        ) / total
      ).toFixed(1)
    : '0.0'

  return {
    total,
    bug,
    baru,
    avgRating,
  }
})

// ======================================================
// STATUS CLASS
// ======================================================

function statusClass(status) {

  const map = {
    baru: 'status-waiting',
    ditinjau: 'status-processing',
    dikerjakan: 'status-processing',
    selesai: 'status-success',
  }

  return map[status] || 'status-waiting'
}

// ======================================================
// FORMAT TANGGAL
// ======================================================

function formatDate(date) {

  if (!date) {
    return '-'
  }

  return new Date(date).toLocaleDateString(
    'id-ID',
    {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    }
  )
}

// ======================================================
// FETCH FEEDBACK
// ======================================================

async function fetchFeedbacks() {

  loading.value = true

  try {

    const res = await api.get('/feedbacks')

    feedbacks.value = Array.isArray(res.data)
      ? res.data
      : []

  } catch (err) {

    console.error(
      'Gagal mengambil feedback:',
      err
    )

    feedbacks.value = []

  } finally {

    loading.value = false

  }
}

// ======================================================
// UPDATE STATUS
// ======================================================

async function updateStatus(
  feedback,
  newStatus
) {

  if (
    !feedback ||
    !feedback.id ||
    !newStatus
  ) {
    return
  }

  updatingStatus.value = feedback.id

  try {

    const res = await api.put(
      `/feedbacks/${feedback.id}`,
      {
        status: newStatus,
      }
    )

    feedback.status =
      res.data.status || newStatus

  } catch (err) {

    console.error(
      'Gagal update status:',
      err
    )

    alert(
      err.response?.data?.message ||
      'Gagal mengubah status feedback.'
    )

  } finally {

    updatingStatus.value = null

  }
}

// ======================================================
// MULAI EDIT CATATAN
// ======================================================

function startEditCatatan(feedback) {

  editingCatatan.value = feedback.id

  catatanInput.value =
    feedback.catatan_admin || ''

  catatanError.value = ''
}

// ======================================================
// BATAL EDIT CATATAN
// ======================================================

function cancelEditCatatan() {

  editingCatatan.value = null

  catatanInput.value = ''

  catatanError.value = ''

}

// ======================================================
// SIMPAN CATATAN
// ======================================================

async function saveCatatan(feedback) {

  if (!feedback || !feedback.id) {
    return
  }

  const catatan =
    catatanInput.value.trim()

  // Validasi
  if (!catatan) {

    catatanError.value =
      'Catatan tidak boleh kosong.'

    return
  }

  savingCatatan.value = true

  catatanError.value = ''

  try {

    const res = await api.put(
      `/feedbacks/${feedback.id}`,
      {
        catatan_admin: catatan,
      }
    )

    // Update data langsung di halaman
    feedback.catatan_admin =
      res.data.catatan_admin

    // Tutup form
    editingCatatan.value = null

    catatanInput.value = ''

  } catch (err) {

    console.error(
      'Gagal simpan catatan:',
      err
    )

    catatanError.value =
      err.response?.data?.message ||
      'Gagal menyimpan catatan. Silakan coba lagi.'

  } finally {

    savingCatatan.value = false

  }
}

// ======================================================
// HAPUS FEEDBACK
// ======================================================

async function deleteFeedback(feedback) {

  if (!feedback || !feedback.id) {
    return
  }

  const confirmed = confirm(
    'Yakin mau menghapus feedback ini?\n\nData yang sudah dihapus tidak dapat dikembalikan.'
  )

  if (!confirmed) {
    return
  }

  deletingFeedback.value = feedback.id

  try {

    await api.delete(
      `/feedbacks/${feedback.id}`
    )

    feedbacks.value =
      feedbacks.value.filter(
        f => f.id !== feedback.id
      )

  } catch (err) {

    console.error(
      'Gagal hapus feedback:',
      err
    )

    alert(
      err.response?.data?.message ||
      'Gagal menghapus feedback.'
    )

  } finally {

    deletingFeedback.value = null

  }
}

// ======================================================
// RESET FILTER
// ======================================================

function resetFilter() {

  filterKategori.value = ''

  filterStatus.value = ''

}

// ======================================================
// ON MOUNTED
// ======================================================

onMounted(() => {

  fetchFeedbacks()

})

</script>

<template>

  <AdminLayout
    page-title="Masukan & Bug Report"
    page-description="Kelola rating, saran, dan laporan bug dari warga"
  >

    <!-- ==================================================
         STATISTIK
    ================================================== -->

    <div class="admin-stats-grid">

      <!-- TOTAL -->
      <div class="stat-card">

        <div class="stat-icon">
          💬
        </div>

        <div>

          <div class="stat-number">
            {{ stats.total }}
          </div>

          <div class="stat-label">
            Total Masukan
          </div>

        </div>

      </div>

      <!-- BUG -->
      <div class="stat-card">

        <div class="stat-icon waiting">
          🐛
        </div>

        <div>

          <div class="stat-number">
            {{ stats.bug }}
          </div>

          <div class="stat-label">
            Laporan Bug
          </div>

        </div>

      </div>

      <!-- BARU -->
      <div class="stat-card">

        <div class="stat-icon processing">
          🆕
        </div>

        <div>

          <div class="stat-number">
            {{ stats.baru }}
          </div>

          <div class="stat-label">
            Belum Ditinjau
          </div>

        </div>

      </div>

      <!-- RATING -->
      <div class="stat-card">

        <div class="stat-icon success">
          ⭐
        </div>

        <div>

          <div class="stat-number">
            {{ stats.avgRating }}
          </div>

          <div class="stat-label">
            Rating Rata-rata
          </div>

        </div>

      </div>

    </div>

    <!-- ==================================================
         FILTER
    ================================================== -->

    <div class="card admin-filter-bar">

      <div class="filter-group">

        <label class="filter-label">
          Kategori
        </label>

        <select
          v-model="filterKategori"
          class="filter-select"
        >

          <option value="">
            Semua Kategori
          </option>

          <option value="bug">
            🐛 Bug
          </option>

          <option value="saran">
            💡 Saran
          </option>

          <option value="pujian">
            😊 Pujian
          </option>

          <option value="lainnya">
            📋 Lainnya
          </option>

        </select>

      </div>

      <div class="filter-group">

        <label class="filter-label">
          Status
        </label>

        <select
          v-model="filterStatus"
          class="filter-select"
        >

          <option value="">
            Semua Status
          </option>

          <option
            v-for="opt in statusOptions"
            :key="opt.key"
            :value="opt.key"
          >
            {{ opt.label }}
          </option>

        </select>

      </div>

      <button
        v-if="filterKategori || filterStatus"
        class="btn btn-secondary reset-filter-btn"
        @click="resetFilter"
      >
        ↻ Reset Filter
      </button>

    </div>

    <!-- ==================================================
         LOADING
    ================================================== -->

    <div
      v-if="loading"
      class="loading-container"
    >

      <div class="loading-spinner"></div>

      <p>
        Memuat data masukan...
      </p>

    </div>

    <!-- ==================================================
         DATA FEEDBACK
    ================================================== -->

    <div
      v-else
      class="feedback-list"
    >

      <!-- FEEDBACK CARD -->
      <div
        v-for="fb in filteredFeedbacks"
        :key="fb.id"
        class="card feedback-card"
      >

        <!-- ==================================================
             HEADER FEEDBACK
        ================================================== -->

        <div class="feedback-header">

          <div class="feedback-main">

            <!-- RATING + KATEGORI -->

            <div class="feedback-meta">

              <span
                class="rating-stars"
                :title="`${fb.rating} dari 5 bintang`"
              >
                {{ '⭐'.repeat(Number(fb.rating || 0)) }}
              </span>

              <span
                class="role-badge role-warga"
              >
                {{
                  kategoriLabel[fb.kategori]
                  || '📋 Lainnya'
                }}
              </span>

            </div>

            <!-- PESAN -->

            <p class="feedback-message">
              {{ fb.pesan }}
            </p>

            <!-- USER + TANGGAL -->

            <div class="feedback-author">

              <span>
                👤
                {{ fb.user?.name || 'Warga' }}
              </span>

              <span>
                •
              </span>

              <span>
                📅
                {{ formatDate(fb.created_at) }}
              </span>

            </div>

          </div>

          <!-- ==================================================
               STATUS + DELETE
          ================================================== -->

          <div class="feedback-actions">

            <select
              class="admin-status-select"
              :class="statusClass(fb.status)"
              :value="fb.status"
              :disabled="updatingStatus === fb.id"
              @change="
                updateStatus(
                  fb,
                  $event.target.value
                )
              "
            >

              <option
                v-for="opt in statusOptions"
                :key="opt.key"
                :value="opt.key"
              >
                {{ opt.label }}
              </option>

            </select>

            <button
              class="admin-delete-btn"
              :disabled="deletingFeedback === fb.id"
              @click="deleteFeedback(fb)"
              title="Hapus feedback"
            >

              <span
                v-if="deletingFeedback === fb.id"
              >
                ⏳
              </span>

              <span v-else>
                🗑️
              </span>

            </button>

          </div>

        </div>

        <!-- ==================================================
             CATATAN ADMIN
        ================================================== -->

        <div class="admin-note-section">

          <!-- ============================================
               CATATAN SUDAH ADA
          ============================================= -->

          <div
            v-if="editingCatatan !== fb.id"
          >

            <div
              v-if="fb.catatan_admin"
              class="admin-note-content"
            >

              <div class="admin-note-header">

                <span class="note-icon">
                  📝
                </span>

                <strong>
                  Catatan Admin
                </strong>

              </div>

              <p class="admin-note-text">
                {{ fb.catatan_admin }}
              </p>

            </div>

            <button
              class="btn btn-secondary note-button"
              @click="startEditCatatan(fb)"
            >

              <span v-if="fb.catatan_admin">
                ✏️ Edit Catatan
              </span>

              <span v-else>
                📝 Tambah Catatan
              </span>

            </button>

          </div>

          <!-- ============================================
               FORM CATATAN
          ============================================= -->

          <div
            v-else
            class="admin-note-form"
          >

            <div class="note-form-header">

              <div>

                <label class="form-label">
                  📝 Catatan untuk feedback ini
                </label>

                <p class="note-description">
                  Berikan informasi tindak lanjut kepada warga.
                </p>

              </div>

            </div>

            <!-- TEXTAREA -->

            <textarea
              v-model="catatanInput"
              class="form-control note-textarea"
              rows="4"
              maxlength="1000"
              placeholder="Contoh: Laporan sudah diteruskan ke programmer dan akan diperbaiki minggu ini..."
              :disabled="savingCatatan"
            ></textarea>

            <!-- ERROR -->

            <p
              v-if="catatanError"
              class="catatan-error"
            >
              ⚠️ {{ catatanError }}
            </p>

            <!-- FOOTER -->

            <div class="note-form-footer">

              <small class="character-count">
                {{ catatanInput.length }}/1000 karakter
              </small>

              <div class="note-actions">

                <button
                  type="button"
                  class="btn btn-secondary"
                  :disabled="savingCatatan"
                  @click="cancelEditCatatan"
                >
                  Batal
                </button>

                <button
                  type="button"
                  class="btn btn-primary save-note-btn"
                  :disabled="
                    savingCatatan ||
                    !catatanInput.trim()
                  "
                  @click="saveCatatan(fb)"
                >

                  <span v-if="savingCatatan">
                    ⏳ Menyimpan...
                  </span>

                  <span v-else>
                    💾 Simpan Catatan
                  </span>

                </button>

              </div>

            </div>

          </div>

        </div>

      </div>

      <!-- ==================================================
           EMPTY STATE
      ================================================== -->

      <div
        v-if="filteredFeedbacks.length === 0"
        class="empty-state card"
      >

        <div class="empty-icon">
          📭
        </div>

        <h3>
          Belum ada masukan
        </h3>

        <p>
          Belum ada feedback yang sesuai dengan filter yang dipilih.
        </p>

        <button
          v-if="filterKategori || filterStatus"
          class="btn btn-secondary"
          @click="resetFilter"
        >
          ↻ Tampilkan Semua
        </button>

      </div>

    </div>

  </AdminLayout>

</template>

<style scoped>

/* ======================================================
   STATISTIK
====================================================== */

.admin-stats-grid {
  display: grid;

  grid-template-columns:
    repeat(4, minmax(0, 1fr));

  gap: 16px;

  margin-bottom: 20px;
}

.stat-card {
  display: flex;

  align-items: center;

  gap: 14px;

  padding: 20px;

  border-radius: 16px;

  background: var(--card-bg, #ffffff);

  border: 1px solid var(--border);

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.04);

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-3px);

  box-shadow:
    0 10px 25px rgba(0, 0, 0, 0.08);
}

.stat-icon {
  width: 48px;
  height: 48px;

  display: flex;

  align-items: center;
  justify-content: center;

  flex-shrink: 0;

  border-radius: 13px;

  background: rgba(99, 102, 241, 0.10);

  font-size: 22px;
}

.stat-icon.waiting {
  background: rgba(245, 158, 11, 0.12);
}

.stat-icon.processing {
  background: rgba(59, 130, 246, 0.12);
}

.stat-icon.success {
  background: rgba(34, 197, 94, 0.12);
}

.stat-number {
  font-size: 24px;

  font-weight: 800;

  line-height: 1.2;
}

.stat-label {
  margin-top: 3px;

  font-size: 12px;

  color: var(--text-muted, #64748b);
}

/* ======================================================
   FILTER
====================================================== */

.admin-filter-bar {
  display: flex;

  align-items: flex-end;

  gap: 14px;

  padding: 16px;

  margin-bottom: 18px;

  flex-wrap: wrap;
}

.filter-group {
  display: flex;

  flex-direction: column;

  gap: 5px;
}

.filter-label {
  font-size: 12px;

  font-weight: 600;

  color: var(--text-muted, #64748b);
}

.filter-select {
  min-width: 180px;

  padding: 9px 12px;

  border-radius: 9px;

  border: 1px solid var(--border);

  background: var(--card-bg, #ffffff);

  color: inherit;

  cursor: pointer;
}

.reset-filter-btn {
  white-space: nowrap;
}

/* ======================================================
   LOADING
====================================================== */

.loading-container {
  min-height: 250px;

  display: flex;

  flex-direction: column;

  align-items: center;
  justify-content: center;

  gap: 12px;

  color: var(--text-muted, #64748b);
}

.loading-spinner {
  width: 36px;
  height: 36px;

  border: 3px solid rgba(99, 102, 241, 0.15);

  border-top-color: currentColor;

  border-radius: 50%;

  animation: spin 0.8s linear infinite;
}

@keyframes spin {

  to {
    transform: rotate(360deg);
  }

}

/* ======================================================
   FEEDBACK LIST
====================================================== */

.feedback-list {
  display: flex;

  flex-direction: column;

  gap: 14px;
}

.feedback-card {
  padding: 20px;

  border-radius: 16px;

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.feedback-card:hover {
  box-shadow:
    0 10px 28px rgba(0, 0, 0, 0.07);
}

/* ======================================================
   FEEDBACK HEADER
====================================================== */

.feedback-header {
  display: flex;

  justify-content: space-between;

  align-items: flex-start;

  gap: 18px;

  flex-wrap: wrap;
}

.feedback-main {
  flex: 1;

  min-width: 250px;
}

.feedback-meta {
  display: flex;

  align-items: center;

  flex-wrap: wrap;

  gap: 9px;

  margin-bottom: 8px;
}

.rating-stars {
  font-size: 16px;

  letter-spacing: 1px;
}

.feedback-message {
  margin: 0 0 8px;

  line-height: 1.65;

  font-size: 14px;

  white-space: pre-wrap;

  word-break: break-word;
}

.feedback-author {
  display: flex;

  align-items: center;

  flex-wrap: wrap;

  gap: 6px;

  font-size: 12px;

  color: var(--text-muted, #64748b);
}

/* ======================================================
   FEEDBACK ACTIONS
====================================================== */

.feedback-actions {
  display: flex;

  align-items: center;

  gap: 8px;
}

.admin-status-select {
  min-width: 125px;

  padding: 8px 12px;

  border-radius: 9px;

  border: 1px solid var(--border);

  font-size: 13px;

  font-weight: 600;

  cursor: pointer;

  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.admin-status-select:hover:not(:disabled) {
  transform: translateY(-1px);
}

.admin-status-select:disabled {
  opacity: 0.6;

  cursor: not-allowed;
}

.status-waiting {
  background: rgba(245, 158, 11, 0.10);

  color: #b45309;
}

.status-processing {
  background: rgba(59, 130, 246, 0.10);

  color: #2563eb;
}

.status-success {
  background: rgba(34, 197, 94, 0.10);

  color: #16a34a;
}

.admin-delete-btn {
  width: 38px;
  height: 38px;

  display: flex;

  align-items: center;
  justify-content: center;

  border: 1px solid rgba(239, 68, 68, 0.20);

  border-radius: 9px;

  background: rgba(239, 68, 68, 0.07);

  cursor: pointer;

  transition:
    transform 0.2s ease,
    background 0.2s ease,
    opacity 0.2s ease;
}

.admin-delete-btn:hover:not(:disabled) {
  transform: translateY(-1px);

  background: rgba(239, 68, 68, 0.14);
}

.admin-delete-btn:disabled {
  opacity: 0.5;

  cursor: not-allowed;
}

/* ======================================================
   CATATAN ADMIN
====================================================== */

.admin-note-section {
  margin-top: 16px;

  padding-top: 16px;

  border-top: 1px solid var(--border);
}

/* ======================================================
   CATATAN SUDAH ADA
====================================================== */

.admin-note-content {
  margin-bottom: 12px;

  padding: 14px 16px;

  border-radius: 12px;

  background: var(
    --bg-secondary,
    #f8fafc
  );

  border: 1px solid var(--border);

  animation: noteFadeIn 0.3s ease;
}

.admin-note-header {
  display: flex;

  align-items: center;

  gap: 7px;

  margin-bottom: 7px;

  font-size: 13px;
}

.note-icon {
  font-size: 15px;
}

.admin-note-text {
  margin: 0;

  font-size: 14px;

  line-height: 1.6;

  white-space: pre-wrap;

  word-break: break-word;
}

.note-button {
  padding: 7px 13px;

  font-size: 12px;
}

/* ======================================================
   FORM CATATAN
====================================================== */

.admin-note-form {
  padding: 16px;

  border-radius: 14px;

  background: var(
    --bg-secondary,
    #f8fafc
  );

  border: 1px solid var(--border);

  animation: noteSlideIn 0.3s ease;
}

.note-form-header {
  margin-bottom: 10px;
}

.note-description {
  margin: 3px 0 0;

  font-size: 12px;

  color: var(--text-muted, #64748b);
}

.note-textarea {
  width: 100%;

  min-height: 95px;

  resize: vertical;

  margin-top: 4px;

  line-height: 1.6;
}

.note-textarea:focus {
  outline: none;

  border-color:
    var(--primary, #6366f1);

  box-shadow:
    0 0 0 3px
    rgba(99, 102, 241, 0.10);
}

.note-textarea:disabled {
  opacity: 0.7;

  cursor: not-allowed;
}

/* ======================================================
   ERROR CATATAN
====================================================== */

.catatan-error {
  margin: 10px 0 0;

  padding: 9px 12px;

  border-radius: 8px;

  font-size: 13px;

  color: #dc2626;

  background:
    rgba(239, 68, 68, 0.08);

  animation: errorShake 0.3s ease;
}

@keyframes errorShake {

  0%,
  100% {
    transform: translateX(0);
  }

  25% {
    transform: translateX(-4px);
  }

  75% {
    transform: translateX(4px);
  }

}

/* ======================================================
   FOOTER FORM CATATAN
====================================================== */

.note-form-footer {
  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 12px;

  margin-top: 10px;
}

.character-count {
  color: var(--text-muted, #64748b);

  font-size: 12px;
}

.note-actions {
  display: flex;

  align-items: center;

  gap: 8px;
}

.save-note-btn {
  min-width: 145px;
}

.note-actions .btn {
  transition:
    transform 0.2s ease,
    opacity 0.2s ease;
}

.note-actions .btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.note-actions .btn:disabled {
  opacity: 0.6;

  cursor: not-allowed;
}

/* ======================================================
   EMPTY STATE
====================================================== */

.empty-state {
  padding: 55px 25px;

  text-align: center;

  border-radius: 16px;
}

.empty-icon {
  font-size: 50px;

  margin-bottom: 12px;

  animation: emptyFloat 2.5s ease-in-out infinite;
}

.empty-state h3 {
  margin-bottom: 6px;
}

.empty-state p {
  margin-bottom: 18px;

  font-size: 14px;

  color: var(--text-muted, #64748b);
}

@keyframes emptyFloat {

  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-5px);
  }

}

/* ======================================================
   ANIMASI CATATAN
====================================================== */

@keyframes noteFadeIn {

  from {
    opacity: 0;

    transform:
      translateY(5px);
  }

  to {
    opacity: 1;

    transform:
      translateY(0);
  }

}

@keyframes noteSlideIn {

  from {
    opacity: 0;

    transform:
      translateY(-7px)
      scale(0.99);
  }

  to {
    opacity: 1;

    transform:
      translateY(0)
      scale(1);
  }

}

/* ======================================================
   RESPONSIVE TABLET
====================================================== */

@media (max-width: 900px) {

  .admin-stats-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }

}

/* ======================================================
   RESPONSIVE HP
====================================================== */

@media (max-width: 600px) {

  .admin-stats-grid {
    grid-template-columns:
      1fr;
  }

  .admin-filter-bar {
    align-items: stretch;

    flex-direction: column;
  }

  .filter-group {
    width: 100%;
  }

  .filter-select {
    width: 100%;

    min-width: 0;
  }

  .reset-filter-btn {
    width: 100%;
  }

  .feedback-card {
    padding: 16px;
  }

  .feedback-header {
    flex-direction: column;
  }

  .feedback-main {
    width: 100%;

    min-width: 0;
  }

  .feedback-actions {
    width: 100%;
  }

  .admin-status-select {
    flex: 1;

    width: 100%;
  }

  .admin-delete-btn {
    flex-shrink: 0;
  }

  .admin-note-form {
    padding: 13px;
  }

  .note-form-footer {
    align-items: stretch;

    flex-direction: column;
  }

  .note-actions {
    width: 100%;
  }

  .note-actions .btn {
    flex: 1;
  }

  .save-note-btn {
    min-width: 0;
  }

}

</style>