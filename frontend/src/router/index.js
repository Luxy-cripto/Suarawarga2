import { createRouter, createWebHistory } from 'vue-router'

// =====================================================
// PUBLIC
// =====================================================

import HomeView from '../views/HomeView.vue'
import AboutView from '../views/AboutView.vue'
import PrivacyView from '../views/PrivacyView.vue'
import TermsView from '../views/TermsView.vue'

// =====================================================
// AUTH
// =====================================================

import LoginView from '../views/Auth/LoginView.vue'
import RegisterView from '../views/Auth/RegisterView.vue'
import ForgotPasswordView from '../views/Auth/ForgotPasswordView.vue'
import ResetPasswordView from '../views/Auth/ResetPasswordView.vue'
// =====================================================
// USER
// =====================================================

import ProfilView from '../views/ProfilView.vue'

// =====================================================
// LAPORAN
// =====================================================

import LaporanView from '../views/Laporan/LaporanView.vue'
import BuatLaporanView from '../views/Laporan/BuatLaporanView.vue'
import ReportDetail from '../views/Laporan/ReportDetail.vue'

// =====================================================
// ADMIN
// =====================================================

import AdminDashboard from '../views/admin/AdminDashboardView.vue'
import AdminLaporan from '../views/admin/AdminReportsView.vue'
import AdminKategori from '../views/admin/AdminCategoriesView.vue'
import AdminUsers from '../views/admin/AdminUsersView.vue'
import AdminSettings from '../views/admin/AdminSettingsView.vue'
import AdminProfile from '../views/admin/AdminProfileView.vue'
import AdminDetailLaporan from '../views/admin/DetailLaporan.vue'


// =====================================================
// ROUTES
// =====================================================

const routes = [

  // ===================================================
  // PUBLIC
  // ===================================================

  {
    path: '/',
    name: 'home',
    component: HomeView
  },

  {
    path: '/about',
    name: 'about',
    component: AboutView
  },

  {
    path: '/privacy',
    name: 'privacy',
    component: PrivacyView
  },

  {
    path: '/terms',
    name: 'terms',
    component: TermsView
  },


  // ===================================================
  // AUTH
  // ===================================================

  {
    path: '/login',
    name: 'login',
    component: LoginView
  },

  {
    path: '/register',
    name: 'register',
    component: RegisterView
  },

  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: ForgotPasswordView,
  },

  {
    path: '/reset-password',
    name: 'reset-password',
    component: ResetPasswordView,
  },


  // ===================================================
  // PROFIL USER
  // ===================================================

  {
    path: '/profil',
    name: 'profil',
    component: ProfilView,
    meta: {
      requiresAuth: true
    }
  },


  // =====================================================
  // LAPORAN PUBLIC
  // =====================================================

  {
    path: '/laporan',
    name: 'laporan',
    component: LaporanView
  },

  // =====================================================
  // BUAT LAPORAN
  // HARUS SEBELUM /laporan/:id
  // =====================================================

  {
    path: '/laporan/buat',
    name: 'buat-laporan',
    component: BuatLaporanView,
    meta: {
      requiresAuth: true
    }
  },

  // =====================================================
  // DETAIL LAPORAN
  // =====================================================

  {
    path: '/laporan/:id',
    name: 'report-detail',
    component: ReportDetail,
    meta: {
      requiresAuth: true
    }
  },


  // ===================================================
  // ADMIN
  // ===================================================

  {
    path: '/admin',
    name: 'admin-dashboard',
    component: AdminDashboard,
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    }
  },

  {
    path: '/admin/laporan',
    name: 'admin-laporan',
    component: AdminLaporan,
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    }
  },

  {
    path: '/admin/laporan/:id',
    name: 'admin-detail-laporan',
    component: AdminDetailLaporan,
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    }
  },

  {
    path: '/admin/kategori',
    name: 'admin-kategori',
    component: AdminKategori,
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    }
  },

  {
    path: '/admin/users',
    name: 'admin-users',
    component: AdminUsers,
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    }
  },

  {
    path: '/admin/settings',
    name: 'admin-settings',
    component: AdminSettings,
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    }
  },

  {
    path: '/admin/profile',
    name: 'admin-profile',
    component: AdminProfile,
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    }
  },


  // ===================================================
  // REDIRECT ROUTE LAMA
  // ===================================================

  {
    path: '/admin/reports',
    redirect: '/admin/laporan'
  },

  {
    path: '/admin/reports/:id',
    redirect: to => `/admin/laporan/${to.params.id}`
  },

  {
    path: '/admin/categories',
    redirect: '/admin/kategori'
  }

]


// =====================================================
// ROUTER
// =====================================================

const router = createRouter({
  history: createWebHistory(),
  routes,

  scrollBehavior() {
    return {
      top: 0
    }
  }
})


// =====================================================
// AUTH GUARD
// =====================================================

router.beforeEach((to) => {

  const token = localStorage.getItem('token')

  const isAuthenticated = Boolean(token)

  let user = null

  try {
    user = JSON.parse(
      localStorage.getItem('user') || 'null'
    )
  } catch {
    user = null
  }


  // ---------------------------------------------------
  // HARUS LOGIN
  // ---------------------------------------------------

  if (to.meta.requiresAuth && !isAuthenticated) {

    return {
      name: 'login',

      query: {
        redirect: to.fullPath
      }
    }

  }


  // ---------------------------------------------------
  // HARUS ADMIN
  // ---------------------------------------------------

  if (to.meta.requiresAdmin && user?.role !== 'admin') {

    return {
      name: 'home'
    }

  }


  return true

})


export default router