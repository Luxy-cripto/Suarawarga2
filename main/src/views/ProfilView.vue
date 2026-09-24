<template>
  <div>
    <Navbar />

    <div class="page-header">
      <div class="container">
        <router-link to="/" class="auth-link-small" style="display: inline-block; margin-bottom: 8px;">← Kembali ke Beranda</router-link>
        <h1 class="page-title">Profil Saya</h1>
        <p class="page-description">Lihat dan kelola informasi akun Anda</p>
      </div>
    </div>

    <div class="container" style="padding: 32px 24px;">
      <ProfileContent />
    </div>

    <Footer />
  </div>
</template>

<script setup>
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import ProfileContent from '../components/ProfileContent.vue'
</script>