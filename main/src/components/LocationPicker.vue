<template>
  <div>
    <div ref="mapContainer" style="height: 300px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border);"></div>
    <p class="text-muted" style="font-size: 12px; margin-top: 6px;">
      📍 Klik di peta untuk menandai lokasi kejadian
    </p>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ latitude: null, longitude: null, lokasi: '' }),
  },
})

const emit = defineEmits(['update:modelValue'])

const mapContainer = ref(null)
let map = null
let marker = null

async function reverseGeocode(lat, lng) {
  try {
    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
    const data = await res.json()
    return data.display_name || ''
  } catch (err) {
    return ''
  }
}

function placeMarker(lat, lng) {
  if (marker) {
    marker.setLatLng([lat, lng])
  } else {
    marker = L.marker([lat, lng]).addTo(map)
  }
}

async function handleMapClick(e) {
  const { lat, lng } = e.latlng
  placeMarker(lat, lng)

  const alamat = await reverseGeocode(lat, lng)

  emit('update:modelValue', {
    latitude: lat,
    longitude: lng,
    lokasi: alamat || `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
  })
}

function flyToLocation(lat, lng, zoom = 16) {
  if (!map) return
  map.setView([lat, lng], zoom)
  placeMarker(lat, lng)
}

defineExpose({ flyToLocation })

onMounted(() => {
  const defaultLat = props.modelValue.latitude || -6.9175
  const defaultLng = props.modelValue.longitude || 107.6191

  map = L.map(mapContainer.value).setView([defaultLat, defaultLng], 13)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  if (props.modelValue.latitude && props.modelValue.longitude) {
    placeMarker(props.modelValue.latitude, props.modelValue.longitude)
  }

  map.on('click', handleMapClick)

  if (!props.modelValue.latitude && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((pos) => {
      const { latitude, longitude } = pos.coords
      map.setView([latitude, longitude], 15)
    })
  }
})

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
  }
})
</script>